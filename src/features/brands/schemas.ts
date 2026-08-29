import { z } from "zod"

export const brandSchema = z.object({
  name: z.string().min(1, "Name is required.").max(150),
  slug: z
    .string()
    .min(1, "Slug is required.")
    .max(160)
    .regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/, "Use lowercase letters, numbers, and hyphens only."),
  description: z.string().max(2000).optional().or(z.literal("")),
  is_active: z.boolean(),
})

export type BrandFormValues = z.infer<typeof brandSchema>
