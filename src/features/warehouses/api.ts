import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { supabase } from "@/lib/supabase"
import type { Warehouse, WarehouseInput } from "@/features/warehouses/types"

const QUERY_KEY = ["warehouses"] as const

async function fetchWarehouses(search: string): Promise<Warehouse[]> {
  let query = supabase
    .from("warehouses")
    .select("*")
    .is("deleted_at", null)
    .order("name", { ascending: true })

  if (search.trim()) {
    query = query.or(`name.ilike.%${search.trim()}%,code.ilike.%${search.trim()}%`)
  }

  const { data, error } = await query
  if (error) throw error
  return data as Warehouse[]
}

async function fetchWarehouse(id: number): Promise<Warehouse> {
  const { data, error } = await supabase.from("warehouses").select("*").eq("id", id).single()
  if (error) throw error
  return data as Warehouse
}

export function useWarehouse(id: number | undefined) {
  return useQuery({
    queryKey: [...QUERY_KEY, "detail", id],
    queryFn: () => fetchWarehouse(id as number),
    enabled: id != null,
  })
}

async function nextWarehouseCode(): Promise<string> {
  const { count, error } = await supabase
    .from("warehouses")
    .select("id", { count: "exact", head: true })
  if (error) throw error
  const n = (count ?? 0) + 1
  return `WH-${String(n).padStart(3, "0")}`
}

export function useWarehouses(search: string) {
  return useQuery({
    queryKey: [...QUERY_KEY, search],
    queryFn: () => fetchWarehouses(search),
  })
}

export function useCreateWarehouse() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input: WarehouseInput) => {
      const code = input.code?.trim() || (await nextWarehouseCode())
      // Clearing other defaults first mirrors the legacy
      // WarehouseController::store() DB::transaction. Two round-trips is
      // acceptable here (low-frequency admin action, not a hot path).
      if (input.is_default) {
        await supabase.from("warehouses").update({ is_default: false }).eq("is_default", true)
      }
      const { data, error } = await supabase
        .from("warehouses")
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

export function useUpdateWarehouse() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, input }: { id: number; input: WarehouseInput }) => {
      if (input.is_default) {
        await supabase
          .from("warehouses")
          .update({ is_default: false })
          .eq("is_default", true)
          .neq("id", id)
      }
      const { data, error } = await supabase
        .from("warehouses")
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

export function useDeleteWarehouse() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => {
      const { error } = await supabase
        .from("warehouses")
        .update({ deleted_at: new Date().toISOString() })
        .eq("id", id)
      if (error) throw error
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: QUERY_KEY })
    },
  })
}
