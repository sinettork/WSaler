export interface Supplier {
  id: number
  name: string
  contact_person: string | null
  email: string | null
  phone: string | null
  address: string | null
  province_id: number | null
  district_id: number | null
  commune_id: number | null
  village_id: number | null
  tax_number: string | null
  payment_terms: string | null
  notes: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface SupplierWithAddress extends Supplier {
  province: { id: number; name_en: string; name_km: string } | null
  district: { id: number; name_en: string; name_km: string } | null
  commune: { id: number; name_en: string; name_km: string } | null
  village: { id: number; name_en: string; name_km: string } | null
}

export interface SupplierInput {
  name: string
  contact_person: string | null
  email: string | null
  phone: string | null
  address: string | null
  province_id: number | null
  district_id: number | null
  commune_id: number | null
  village_id: number | null
  tax_number: string | null
  payment_terms: string | null
  notes: string | null
  is_active: boolean
}
