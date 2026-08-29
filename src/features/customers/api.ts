import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { supabase } from "@/lib/supabase"
import type { CustomerInput, CustomerWithAddress } from "@/features/customers/types"
import { isOptimisticLockMiss } from "@/lib/supabase-errors"

const QUERY_KEY = ["customers"] as const

const SELECT_WITH_ADDRESS =
  "*, province:province_id(id,name_en,name_km), district:district_id(id,name_en,name_km), commune:commune_id(id,name_en,name_km), village:village_id(id,name_en,name_km)"

async function fetchCustomers(search: string): Promise<CustomerWithAddress[]> {
  let query = supabase
    .from("customers")
    .select(SELECT_WITH_ADDRESS)
    .is("deleted_at", null)
    .order("name", { ascending: true })

  if (search.trim()) {
    const term = search.trim()
    query = query.or(
      `name.ilike.%${term}%,code.ilike.%${term}%,email.ilike.%${term}%,phone.ilike.%${term}%`,
    )
  }

  const { data, error } = await query
  if (error) throw error
  return data as unknown as CustomerWithAddress[]
}

/** Mirrors legacy CustomerController::store(): 'CUST-' + zero-padded(max(id)+1, 5). */
async function nextCustomerCode(): Promise<string> {
  const { data, error } = await supabase
    .from("customers")
    .select("id")
    .order("id", { ascending: false })
    .limit(1)
    .maybeSingle()
  if (error) throw error
  const nextId = (data?.id ?? 0) + 1
  return `CUST-${String(nextId).padStart(5, "0")}`
}

export function useCustomers(search: string, enabled = true) {
  return useQuery({
    queryKey: [...QUERY_KEY, search],
    queryFn: () => fetchCustomers(search),
    enabled,
  })
}

export function useCreateCustomer() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input: CustomerInput) => {
      const code = await nextCustomerCode()
      const { data, error } = await supabase
        .from("customers")
        .insert({ ...input, code })
        .select()
        .single()
      if (error) throw error
      return data
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: QUERY_KEY })
    },
  })
}

/**
 * Optimistic locking via the `version` integer column (customers is the
 * first Master Data table to have one — see supabase/migrations/0004_master_data.sql).
 * The update is scoped to `.eq("version", expectedVersion)`; if another user
 * updated the row first, `expectedVersion` no longer matches, no row is
 * updated, `.single()` throws PGRST116, and we surface a
 * "someone else changed this" conflict instead of silently overwriting.
 */
export function useUpdateCustomer() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({
      id,
      version,
      input,
    }: {
      id: number
      version: number
      input: CustomerInput
    }) => {
      const { data, error } = await supabase
        .from("customers")
        .update({ ...input, version: version + 1 })
        .eq("id", id)
        .eq("version", version)
        .select()
        .single()
      if (error) {
        if (isOptimisticLockMiss(error)) {
          throw new Error(
            "This customer was updated by someone else. Reload and try again.",
          )
        }
        throw error
      }
      return data
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: QUERY_KEY })
    },
  })
}

/**
 * Mirrors legacy CustomerController::destroy()'s F13 rule: block deletion
 * when the customer has sales history (audit trail integrity), surfacing
 * the sales count so the UI can explain why.
 */
export function useDeleteCustomer() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => {
      const { count, error: countError } = await supabase
        .from("sales")
        .select("id", { count: "exact", head: true })
        .eq("customer_id", id)
      if (countError) throw countError
      if ((count ?? 0) > 0) {
        throw new Error(
          `Cannot delete this customer: ${count} sale${count === 1 ? "" : "s"} reference it. Deactivate it instead.`,
        )
      }
      const { error } = await supabase
        .from("customers")
        .update({ deleted_at: new Date().toISOString() })
        .eq("id", id)
      if (error) throw error
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: QUERY_KEY })
    },
  })
}
