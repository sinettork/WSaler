export interface Warehouse {
  id: number
  name: string
  code: string
  address: string | null
  province_id: number | null
  district_id: number | null
  commune_id: number | null
  village_id: number | null
  phone: string | null
  is_default: boolean
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface WarehouseInput {
  name: string
  code?: string
  address: string | null
  province_id: number | null
  district_id: number | null
  commune_id: number | null
  village_id: number | null
  phone: string | null
  is_default: boolean
  is_active: boolean
}
