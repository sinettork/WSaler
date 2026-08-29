import { useTranslation } from "react-i18next"

import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Textarea } from "@/components/ui/textarea"
import { Label } from "@/components/ui/label"
import {
  useCommunes,
  useDistricts,
  useProvinces,
  useVillages,
} from "@/features/addresses/api"

export interface AddressValue {
  province_id: number | null
  district_id: number | null
  commune_id: number | null
  village_id: number | null
  address: string
}

export const EMPTY_ADDRESS: AddressValue = {
  province_id: null,
  district_id: null,
  commune_id: null,
  village_id: null,
  address: "",
}

interface AddressCascaderProps {
  value: AddressValue
  onChange: (value: AddressValue) => void
  errors?: Partial<Record<keyof AddressValue, string>>
  required?: { province?: boolean; district?: boolean; commune?: boolean; village?: boolean }
  showAddressField?: boolean
  addressPlaceholder?: string
}

/**
 * Cambodia administrative-division cascader: province -> district -> commune
 * -> village, each level gated on the previous selection, backed by
 * `provinces`/`districts`/`communes`/`villages` reference tables (read-only,
 * RLS "readable by authenticated"). Mirrors
 * legacy-php-vue/resources/js/components/AddressCascader.vue.
 */
export function AddressCascader({
  value,
  onChange,
  errors,
  required = { province: true, district: true },
  showAddressField = true,
  addressPlaceholder = "Street, house number, landmark",
}: AddressCascaderProps) {
  const { i18n } = useTranslation()
  const isKm = i18n.language?.startsWith("km")

  const provinces = useProvinces()
  const districts = useDistricts(value.province_id)
  const communes = useCommunes(value.district_id)
  const villages = useVillages(value.commune_id)

  function label(opt: { name_en: string; name_km: string }) {
    return isKm ? opt.name_km : opt.name_en
  }

  function handleProvinceChange(idStr: string) {
    const id = idStr ? Number(idStr) : null
    onChange({ ...value, province_id: id, district_id: null, commune_id: null, village_id: null })
  }

  function handleDistrictChange(idStr: string) {
    const id = idStr ? Number(idStr) : null
    onChange({ ...value, district_id: id, commune_id: null, village_id: null })
  }

  function handleCommuneChange(idStr: string) {
    const id = idStr ? Number(idStr) : null
    onChange({ ...value, commune_id: id, village_id: null })
  }

  function handleVillageChange(idStr: string) {
    const id = idStr ? Number(idStr) : null
    onChange({ ...value, village_id: id })
  }

  return (
    <div className="space-y-3">
      <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
        <div className="grid gap-2">
          <Label>
            Province{required.province && <span className="text-destructive"> *</span>}
          </Label>
          <Select
            value={value.province_id != null ? String(value.province_id) : ""}
            onValueChange={handleProvinceChange}
          >
            <SelectTrigger className="w-full" aria-invalid={Boolean(errors?.province_id)}>
              <SelectValue placeholder="Select province" />
            </SelectTrigger>
            <SelectContent>
              {(provinces.data ?? []).map((p) => (
                <SelectItem key={p.id} value={String(p.id)}>
                  {label(p)}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {errors?.province_id && <p className="text-sm text-destructive">{errors.province_id}</p>}
        </div>

        <div className="grid gap-2">
          <Label>
            District{required.district && <span className="text-destructive"> *</span>}
          </Label>
          <Select
            value={value.district_id != null ? String(value.district_id) : ""}
            onValueChange={handleDistrictChange}
            disabled={!value.province_id || districts.isLoading}
          >
            <SelectTrigger className="w-full" aria-invalid={Boolean(errors?.district_id)}>
              <SelectValue placeholder="Select district" />
            </SelectTrigger>
            <SelectContent>
              {(districts.data ?? []).map((d) => (
                <SelectItem key={d.id} value={String(d.id)}>
                  {label(d)}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {errors?.district_id && <p className="text-sm text-destructive">{errors.district_id}</p>}
        </div>

        <div className="grid gap-2">
          <Label>
            Commune{required.commune && <span className="text-destructive"> *</span>}
          </Label>
          <Select
            value={value.commune_id != null ? String(value.commune_id) : ""}
            onValueChange={handleCommuneChange}
            disabled={!value.district_id || communes.isLoading}
          >
            <SelectTrigger className="w-full" aria-invalid={Boolean(errors?.commune_id)}>
              <SelectValue placeholder="Select commune" />
            </SelectTrigger>
            <SelectContent>
              {(communes.data ?? []).map((c) => (
                <SelectItem key={c.id} value={String(c.id)}>
                  {label(c)}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {errors?.commune_id && <p className="text-sm text-destructive">{errors.commune_id}</p>}
        </div>

        <div className="grid gap-2">
          <Label>
            Village{required.village && <span className="text-destructive"> *</span>}
          </Label>
          <Select
            value={value.village_id != null ? String(value.village_id) : ""}
            onValueChange={handleVillageChange}
            disabled={!value.commune_id || villages.isLoading}
          >
            <SelectTrigger className="w-full" aria-invalid={Boolean(errors?.village_id)}>
              <SelectValue placeholder="Select village" />
            </SelectTrigger>
            <SelectContent>
              {(villages.data ?? []).map((v) => (
                <SelectItem key={v.id} value={String(v.id)}>
                  {label(v)}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {errors?.village_id && <p className="text-sm text-destructive">{errors.village_id}</p>}
        </div>
      </div>

      {showAddressField && (
        <div className="grid gap-2">
          <Label>Street / House no.</Label>
          <Textarea
            rows={2}
            value={value.address}
            onChange={(e) => onChange({ ...value, address: e.target.value })}
            placeholder={addressPlaceholder}
            aria-invalid={Boolean(errors?.address)}
          />
          {errors?.address && <p className="text-sm text-destructive">{errors.address}</p>}
        </div>
      )}
    </div>
  )
}
