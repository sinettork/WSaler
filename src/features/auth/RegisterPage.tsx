import { zodResolver } from "@hookform/resolvers/zod"
import { Loader2, Lock, Mail, User } from "lucide-react"
import { useState } from "react"
import { useForm } from "react-hook-form"
import { useTranslation } from "react-i18next"
import { Link, useNavigate } from "react-router-dom"

import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { registerSchema, type RegisterFormValues } from "@/features/auth/schemas"
import { useAuth } from "@/hooks/useAuth"

export function RegisterPage() {
  const { t } = useTranslation()
  const { register, isSubmitting } = useAuth()
  const navigate = useNavigate()
  const [topError, setTopError] = useState<string | null>(null)
  const [confirmationNotice, setConfirmationNotice] = useState<string | null>(null)

  const form = useForm<RegisterFormValues>({
    resolver: zodResolver(registerSchema),
    defaultValues: { name: "", email: "", password: "", passwordConfirmation: "" },
  })

  async function onSubmit(values: RegisterFormValues) {
    setTopError(null)
    setConfirmationNotice(null)
    try {
      const { needsEmailConfirmation } = await register(values.name, values.email, values.password)
      if (needsEmailConfirmation) {
        setConfirmationNotice(t("auth.checkEmailToConfirm"))
        return
      }
      navigate("/dashboard", { replace: true })
    } catch {
      setTopError(t("auth.registrationFailed"))
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 via-white to-brand-50 px-4 py-12">
      <div className="w-full max-w-md">
        <div className="mb-8 flex flex-col items-center">
          <div className="mb-3 flex size-12 items-center justify-center rounded-xl bg-brand-600 text-xl font-bold text-white shadow-lg shadow-brand-600/20">
            W
          </div>
          <h1 className="text-2xl font-bold text-slate-900">{t("auth.createYourAccount")}</h1>
          <p className="mt-1 text-sm text-slate-500">{t("auth.startManaging")}</p>
        </div>

        <Card className="shadow-xl shadow-slate-900/5 ring-1 ring-slate-200">
          <CardContent>
            <Form {...form}>
              <form onSubmit={form.handleSubmit(onSubmit)} noValidate className="space-y-5">
                <FormField
                  control={form.control}
                  name="name"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>{t("auth.fullName")}</FormLabel>
                      <FormControl>
                        <div className="relative">
                          <User className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                          <Input autoComplete="name" placeholder="Jane Doe" className="pl-9" {...field} />
                        </div>
                      </FormControl>
                      <FormMessage>
                        {form.formState.errors.name?.message
                          ? t(form.formState.errors.name.message)
                          : undefined}
                      </FormMessage>
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="email"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>{t("auth.email")}</FormLabel>
                      <FormControl>
                        <div className="relative">
                          <Mail className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                          <Input
                            type="email"
                            autoComplete="email"
                            placeholder="you@company.com"
                            className="pl-9"
                            {...field}
                          />
                        </div>
                      </FormControl>
                      <FormMessage>
                        {form.formState.errors.email?.message
                          ? t(form.formState.errors.email.message)
                          : undefined}
                      </FormMessage>
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="password"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>{t("auth.password")}</FormLabel>
                      <FormControl>
                        <div className="relative">
                          <Lock className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                          <Input
                            type="password"
                            autoComplete="new-password"
                            placeholder="••••••••"
                            className="pl-9"
                            {...field}
                          />
                        </div>
                      </FormControl>
                      <FormMessage>
                        {form.formState.errors.password?.message
                          ? t(form.formState.errors.password.message)
                          : undefined}
                      </FormMessage>
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="passwordConfirmation"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>{t("auth.confirmPassword")}</FormLabel>
                      <FormControl>
                        <div className="relative">
                          <Lock className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                          <Input
                            type="password"
                            autoComplete="new-password"
                            placeholder="••••••••"
                            className="pl-9"
                            {...field}
                          />
                        </div>
                      </FormControl>
                      <FormMessage>
                        {form.formState.errors.passwordConfirmation?.message
                          ? t(form.formState.errors.passwordConfirmation.message)
                          : undefined}
                      </FormMessage>
                    </FormItem>
                  )}
                />

                {topError && (
                  <div
                    role="alert"
                    className="flex items-start gap-2 rounded-md border border-status-critical/30 bg-status-critical-bg px-4 py-3 text-sm text-status-critical"
                  >
                    <span>{topError}</span>
                  </div>
                )}

                {confirmationNotice && (
                  <div
                    role="status"
                    className="flex items-start gap-2 rounded-md border border-status-info/30 bg-status-info-bg px-4 py-3 text-sm text-status-info"
                  >
                    <span>{confirmationNotice}</span>
                  </div>
                )}

                <Button type="submit" disabled={isSubmitting} className="w-full">
                  {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                  {isSubmitting ? t("auth.creatingAccount") : t("auth.createAccount")}
                </Button>
              </form>
            </Form>
          </CardContent>
        </Card>

        <p className="mt-6 text-center text-sm text-slate-600">
          {t("auth.haveAccount")}{" "}
          <Link to="/login" className="font-semibold text-brand-600 hover:text-brand-700">
            {t("auth.signInLink")}
          </Link>
        </p>
      </div>
    </div>
  )
}

export default RegisterPage
