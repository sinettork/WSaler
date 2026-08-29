export interface Category {
  id: number
  name: string
  slug: string
  description: string | null
  parent_id: number | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface CategoryWithParent extends Category {
  parent: { id: number; name: string } | null
}

export interface CategoryInput {
  name: string
  slug: string
  description: string | null
  parent_id: number | null
  is_active: boolean
}
