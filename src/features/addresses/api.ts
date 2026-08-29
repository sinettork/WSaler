import { useQuery } from "@tanstack/react-query"

import { supabase } from "@/lib/supabase"

export interface AddressOption {
  id: number
  code: string
  name_en: string
  name_km: string
}

async function fetchProvinces(): Promise<AddressOption[]> {
  const { data, error } = await supabase
    .from("provinces")
    .select("id, code, name_en, name_km")
    .order("type", { ascending: true })
    .order("sort_order", { ascending: true })
  if (error) throw error
  return data as AddressOption[]
}

async function fetchDistricts(provinceId: number): Promise<AddressOption[]> {
  const { data, error } = await supabase
    .from("districts")
    .select("id, code, name_en, name_km")
    .eq("province_id", provinceId)
    .order("sort_order", { ascending: true })
  if (error) throw error
  return data as AddressOption[]
}

async function fetchCommunes(districtId: number): Promise<AddressOption[]> {
  const { data, error } = await supabase
    .from("communes")
    .select("id, code, name_en, name_km")
    .eq("district_id", districtId)
    .order("sort_order", { ascending: true })
  if (error) throw error
  return data as AddressOption[]
}

async function fetchVillages(communeId: number): Promise<AddressOption[]> {
  const { data, error } = await supabase
    .from("villages")
    .select("id, code, name_en, name_km")
    .eq("commune_id", communeId)
    .order("sort_order", { ascending: true })
  if (error) throw error
  return data as AddressOption[]
}

// Reference data changes essentially never — cache for the whole session.
const REFERENCE_STALE_TIME = Infinity

export function useProvinces() {
  return useQuery({
    queryKey: ["addresses", "provinces"],
    queryFn: fetchProvinces,
    staleTime: REFERENCE_STALE_TIME,
  })
}

export function useDistricts(provinceId: number | null) {
  return useQuery({
    queryKey: ["addresses", "districts", provinceId],
    queryFn: () => fetchDistricts(provinceId as number),
    enabled: provinceId != null,
    staleTime: REFERENCE_STALE_TIME,
  })
}

export function useCommunes(districtId: number | null) {
  return useQuery({
    queryKey: ["addresses", "communes", districtId],
    queryFn: () => fetchCommunes(districtId as number),
    enabled: districtId != null,
    staleTime: REFERENCE_STALE_TIME,
  })
}

export function useVillages(communeId: number | null) {
  return useQuery({
    queryKey: ["addresses", "villages", communeId],
    queryFn: () => fetchVillages(communeId as number),
    enabled: communeId != null,
    staleTime: REFERENCE_STALE_TIME,
  })
}
