import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { supabase } from "@/lib/supabase"
import type { Unit, UnitInput } from "@/features/units/types"

const QUERY_KEY = ["units"] as const

async function fetchUnits(search: string): Promise<Unit[]> {
  let query = supabase.from("units").select("*").order("name", { ascending: true })

  if (search.trim()) {
    query = query.or(`name.ilike.%${search.trim()}%,short_code.ilike.%${search.trim()}%`)
  }

  const { data, error } = await query
  if (error) throw error
  return data as Unit[]
}

export function useUnits(search: string) {
  return useQuery({
    queryKey: [...QUERY_KEY, search],
    queryFn: () => fetchUnits(search),
  })
}

export function useCreateUnit() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input: UnitInput) => {
      const { data, error } = await supabase.from("units").insert(input).select().single()
      if (error) throw error
      return data
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: QUERY_KEY })
    },
  })
}

export function useUpdateUnit() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, input }: { id: number; input: UnitInput }) => {
      const { data, error } = await supabase
        .from("units")
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

export function useDeleteUnit() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => {
      // No soft-delete column on units — hard delete is the only option
      // (blocked by FK RESTRICT from products.base_unit_id when in use).
      const { error } = await supabase.from("units").delete().eq("id", id)
      if (error) throw error
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: QUERY_KEY })
    },
  })
}
