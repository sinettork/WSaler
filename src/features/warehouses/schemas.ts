import { z } from "zod"

export const warehouseSchema = z.object({
  name: z.string().min(1, "Name is required.").max(100),
  code: z.string().max(20).optional().or(z.literal("")),
  province_id: z.number().int().positive().nullable(),
  district_id: z.number().int().positive().nullable(),
  commune_id: z.number().int().positive().nullable(),
  village_id: z.number().int().positive().nullable(),
  address: z.string().max(500).optional().or(z.literal("")),
  phone: z.string().max(30).optional().or(z.literal("")),
  is_default: z.boolean(),
  is_active: z.boolean(),
})

export type WarehouseFormValues = z.infer<typeof warehouseSchema>
