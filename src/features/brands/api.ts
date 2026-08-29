import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { supabase } from "@/lib/supabase"
import type { Brand, BrandInput } from "@/features/brands/types"

const QUERY_KEY = ["brands"] as const

async function fetchBrands(search: string): Promise<Brand[]> {
  let query = supabase
    .from("brands")
    .select("*")
    .is("deleted_at", null)
    .order("name", { ascending: true })

  if (search.trim()) {
    query = query.ilike("name", `%${search.trim()}%`)
  }

  const { data, error } = await query
  if (error) throw error
  return data as Brand[]
}

export function useBrands(search: string) {
  return useQuery({
    queryKey: [...QUERY_KEY, search],
    queryFn: () => fetchBrands(search),
  })
}

async function fetchBrand(id: number): Promise<Brand> {
  const { data, error } = await supabase.from("brands").select("*").eq("id", id).single()
  if (error) throw error
  return data as Brand
}

/** Single-record fetch for the full-page edit form. */
export function useBrand(id: number | undefined) {
  return useQuery({
    queryKey: [...QUERY_KEY, "detail", id],
    queryFn: () => fetchBrand(id as number),
    enabled: id != null,
  })
}

export function useCreateBrand() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input: BrandInput) => {
      const { data, error } = await supabase.from("brands").insert(input).select().single()
      if (error) throw error
      return data
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: QUERY_KEY })
    },
  })
}

export function useUpdateBrand() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, input }: { id: number; input: BrandInput }) => {
      const { data, error } = await supabase
        .from("brands")
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

export function useDeleteBrand() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => {
      const { error } = await supabase
        .from("brands")
        .update({ deleted_at: new Date().toISOString() })
        .eq("id", id)
      if (error) throw error
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: QUERY_KEY })
    },
  })
}
