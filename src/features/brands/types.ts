export interface Brand {
  id: number
  name: string
  slug: string
  description: string | null
  logo: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface BrandInput {
  name: string
  slug: string
  description: string | null
  logo: string | null
  is_active: boolean
}
