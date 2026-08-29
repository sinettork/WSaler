import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { supabase } from "@/lib/supabase"
import type { SupplierInput, SupplierWithAddress } from "@/features/suppliers/types"

const QUERY_KEY = ["suppliers"] as const

const SELECT_WITH_ADDRESS =
  "*, province:province_id(id,name_en,name_km), district:district_id(id,name_en,name_km), commune:commune_id(id,name_en,name_km), village:village_id(id,name_en,name_km)"

async function fetchSuppliers(search: string): Promise<SupplierWithAddress[]> {
  let query = supabase
    .from("suppliers")
    .select(SELECT_WITH_ADDRESS)
    .is("deleted_at", null)
    .order("name", { ascending: true })

  if (search.trim()) {
    const term = search.trim()
    query = query.or(`name.ilike.%${term}%,email.ilike.%${term}%,phone.ilike.%${term}%`)
  }

  const { data, error } = await query
  if (error) throw error
  return data as unknown as SupplierWithAddress[]
}

async function fetchSupplier(id: number): Promise<SupplierWithAddress> {
  const { data, error } = await supabase
    .from("suppliers")
    .select(SELECT_WITH_ADDRESS)
    .eq("id", id)
    .single()
  if (error) throw error
  return data as unknown as SupplierWithAddress
}

export function useSupplier(id: number | undefined) {
  return useQuery({
    queryKey: [...QUERY_KEY, "detail", id],
    queryFn: () => fetchSupplier(id as number),
    enabled: id != null,
  })
}

export function useSuppliers(search: string, enabled = true) {
  return useQuery({
    queryKey: [...QUERY_KEY, search],
    queryFn: () => fetchSuppliers(search),
    enabled,
  })
}

export function useCreateSupplier() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input: SupplierInput) => {
      const { data, error } = await supabase.from("suppliers").insert(input).select().single()
      if (error) throw error
      return data
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: QUERY_KEY })
    },
  })
}

export function useUpdateSupplier() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, input }: { id: number; input: SupplierInput }) => {
      const { data, error } = await supabase
        .from("suppliers")
        .update(input)
        .eq("id", id)
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

export function useDeleteSupplier() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => {
      const { error } = await supabase
        .from("suppliers")
        .update({ deleted_at: new Date().toISOString() })
        .eq("id", id)
      if (error) throw error
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: QUERY_KEY })
    },
  })
}
