import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import { supabase } from "@/lib/supabase"
import type { CategoryInput, CategoryWithParent } from "@/features/categories/types"

const QUERY_KEY = ["categories"] as const

async function fetchCategories(search: string): Promise<CategoryWithParent[]> {
  let query = supabase
    .from("categories")
    .select("*, parent:parent_id(id, name)")
    .is("deleted_at", null)
    .order("name", { ascending: true })

  if (search.trim()) {
    query = query.ilike("name", `%${search.trim()}%`)
  }

  const { data, error } = await query
  if (error) throw error
  return data as unknown as CategoryWithParent[]
}

export function useCategories(search: string) {
  return useQuery({
    queryKey: [...QUERY_KEY, search],
    queryFn: () => fetchCategories(search),
  })
}

/** Full list (no search filter), used to populate the "parent category" dropdown. */
export function useAllCategories() {
  return useQuery({
    queryKey: [...QUERY_KEY, "all"],
    queryFn: () => fetchCategories(""),
  })
}

async function fetchCategory(id: number): Promise<CategoryWithParent> {
  const { data, error } = await supabase
    .from("categories")
    .select("*, parent:parent_id(id, name)")
    .eq("id", id)
    .single()
  if (error) throw error
  return data as unknown as CategoryWithParent
}

/** Single-record fetch for the full-page edit form. */
export function useCategory(id: number | undefined) {
  return useQuery({
    queryKey: [...QUERY_KEY, "detail", id],
    queryFn: () => fetchCategory(id as number),
    enabled: id != null,
  })
}

export function useCreateCategory() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input: CategoryInput) => {
      const { data, error } = await supabase.from("categories").insert(input).select().single()
      if (error) throw error
      return data
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: QUERY_KEY })
    },
  })
}

export function useUpdateCategory() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, input }: { id: number; input: CategoryInput }) => {
      const { data, error } = await supabase
        .from("categories")
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

export function useDeleteCategory() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => {
      // Soft delete, consistent with `deleted_at` column + Postgres pattern.
      const { error } = await supabase
        .from("categories")
        .update({ deleted_at: new Date().toISOString() })
        .eq("id", id)
      if (error) throw error
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: QUERY_KEY })
    },
  })
}
