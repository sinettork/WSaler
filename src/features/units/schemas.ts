import { z } from "zod"

export const unitSchema = z.object({
  name: z.string().min(1, "Name is required.").max(100),
  short_code: z.string().min(1, "Short code is required.").max(20),
  base: z.boolean(),
  conversion_factor_to_base: z.coerce.number().positive("Must be a positive number."),
})

// Use the input type (pre-coercion) so react-hook-form's `TFieldValues`
// generic matches what zodResolver expects for a schema that uses
// `z.coerce.number()` (whose input type is `unknown`, output is `number`).
export type UnitFormValues = z.input<typeof unitSchema>
export type UnitFormOutput = z.output<typeof unitSchema>
