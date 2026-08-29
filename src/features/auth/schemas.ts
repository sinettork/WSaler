import { z } from "zod"

export const loginSchema = z.object({
  email: z.string().min(1, "auth.emailRequired").email("auth.emailRequired"),
  password: z.string().min(1, "auth.passwordRequired"),
})

export type LoginFormValues = z.infer<typeof loginSchema>

export const registerSchema = z
  .object({
    name: z.string().min(1, "auth.nameRequired"),
    email: z.string().min(1, "auth.emailRequired").email("auth.emailRequired"),
    password: z.string().min(8, "auth.passwordMinLength"),
    passwordConfirmation: z.string().min(1, "auth.passwordRequired"),
  })
  .refine((data) => data.password === data.passwordConfirmation, {
    message: "auth.passwordsDoNotMatch",
    path: ["passwordConfirmation"],
  })

export type RegisterFormValues = z.infer<typeof registerSchema>
