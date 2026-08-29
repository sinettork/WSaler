import { z } from "zod"

export const supplierSchema = z.object({
  name: z.string().min(1, "Name is required.").max(150),
  contact_person: z.string().max(150).optional().or(z.literal("")),
  email: z.string().email("Enter a valid email.").max(150).optional().or(z.literal("")),
  phone: z.string().max(30).optional().or(z.literal("")),
  province_id: z.number().int().positive().nullable(),
  district_id: z.number().int().positive().nullable(),
  commune_id: z.number().int().positive().nullable(),
  village_id: z.number().int().positive().nullable(),
  address: z.string().max(500).optional().or(z.literal("")),
  tax_number: z.string().max(100).optional().or(z.literal("")),
  payment_terms: z.string().max(100).optional().or(z.literal("")),
  notes: z.string().max(2000).optional().or(z.literal("")),
  is_active: z.boolean(),
})

export type SupplierFormValues = z.infer<typeof supplierSchema>
