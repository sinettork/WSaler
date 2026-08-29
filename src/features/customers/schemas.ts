import { z } from "zod"

// Matches the Postgres `customer_type` enum exactly — see types.ts note
// about the legacy 'vip' option not existing in the actual database enum.
export const CUSTOMER_TYPES = ["retail", "wholesale", "distributor"] as const

export const customerSchema = z.object({
  name: z.string().min(1, "Name is required.").max(150),
  contact_person: z.string().max(150).optional().or(z.literal("")),
  email: z.string().email("Enter a valid email.").max(150).optional().or(z.literal("")),
  phone: z.string().max(30).optional().or(z.literal("")),
  province_id: z.number().int().positive().nullable(),
  district_id: z.number().int().positive().nullable(),
  commune_id: z.number().int().positive().nullable(),
  village_id: z.number().int().positive().nullable(),
  address: z.string().max(500).optional().or(z.literal("")),
  type: z.enum(CUSTOMER_TYPES),
  credit_limit: z.coerce.number().min(0, "Credit limit cannot be negative."),
  payment_terms: z.string().max(100).optional().or(z.literal("")),
  notes: z.string().max(2000).optional().or(z.literal("")),
  is_active: z.boolean(),
})

// Use the input type (pre-coercion) so react-hook-form's `TFieldValues`
// generic matches what zodResolver expects for a schema that uses
// `z.coerce.number()` (whose input type is `unknown`, output is `number`)
// for credit_limit — mirrors features/units/schemas.ts.
export type CustomerFormValues = z.input<typeof customerSchema>
export type CustomerFormOutput = z.output<typeof customerSchema>
