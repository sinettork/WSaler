/**
 * NOTE: legacy Laravel validation allowed a 4th type, 'vip', but the actual
 * Postgres `customer_type` enum (supabase/migrations/0001_extensions_and_enums.sql)
 * only defines 'retail' | 'wholesale' | 'distributor'. We match the real DB
 * enum here rather than the legacy (apparently never-migrated) validation
 * rule — inserting 'vip' would fail with a 22P02 invalid_text_representation
 * error. If VIP customers are needed, a migration adding the enum value
 * should come first.
 */
export type CustomerType = "retail" | "wholesale" | "distributor"

export interface Customer {
  id: number
  code: string
  name: string
  contact_person: string | null
  email: string | null
  phone: string | null
  address: string | null
  province_id: number | null
  district_id: number | null
  commune_id: number | null
  village_id: number | null
  type: CustomerType
  credit_limit: number
  current_balance: number
  payment_terms: string | null
  notes: string | null
  is_active: boolean
  created_at: string
  updated_at: string
  version: number
}

export interface CustomerWithAddress extends Customer {
  province: { id: number; name_en: string; name_km: string } | null
  district: { id: number; name_en: string; name_km: string } | null
  commune: { id: number; name_en: string; name_km: string } | null
  village: { id: number; name_en: string; name_km: string } | null
}

export interface CustomerInput {
  name: string
  contact_person: string | null
  email: string | null
  phone: string | null
  address: string | null
  province_id: number | null
  district_id: number | null
  commune_id: number | null
  village_id: number | null
  type: CustomerType
  credit_limit: number
  payment_terms: string | null
  notes: string | null
  is_active: boolean
}
