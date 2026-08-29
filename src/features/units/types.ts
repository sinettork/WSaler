export interface Unit {
  id: number
  name: string
  short_code: string
  base: boolean
  conversion_factor_to_base: number
  created_at: string
  updated_at: string
}

export interface UnitInput {
  name: string
  short_code: string
  base: boolean
  conversion_factor_to_base: number
}
