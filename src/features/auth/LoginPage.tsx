import { zodResolver } from "@hookform/resolvers/zod"
import { Loader2, Lock, Mail } from "lucide-react"
import { useState } from "react"
import { useForm } from "react-hook-form"
import { useTranslation } from "react-i18next"
import { Link, useLocation, useNavigate } from "react-router-dom"

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
import { loginSchema, type LoginFormValues } from "@/features/auth/schemas"
import { useAuth } from "@/hooks/useAuth"

export function LoginPage() {
  const { t } = useTranslation()
  const { login, isSubmitting } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [topError, setTopError] = useState<string | null>(null)

  const form = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: "", password: "" },
  })

  async function onSubmit(values: LoginFormValues) {
    setTopError(null)
    try {
      await login(values.email, values.password)
      const from = (location.state as { from?: { pathname: string } } | null)?.from?.pathname
      navigate(from ?? "/dashboard", { replace: true })
    } catch {
      setTopError(t("auth.invalidCredentials"))
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 via-white to-brand-50 px-4 py-12">
      <div className="w-full max-w-md">
        <div className="mb-8 flex flex-col items-center">
          <div className="mb-3 flex size-12 items-center justify-center rounded-lg bg-brand-600 text-xl font-bold text-white shadow-lg shadow-brand-600/30">
            W
          </div>
          <div className="mb-1 text-[10px] font-semibold uppercase tracking-widest text-brand-700">
            Wholesale Operations
          </div>
          <h1 className="text-2xl font-bold text-slate-900">{t("auth.welcomeBack")}</h1>
          <p className="mt-1 text-sm text-slate-500">{t("auth.signInToContinue")}</p>
        </div>

        <Card className="shadow-xl shadow-slate-900/5 ring-1 ring-slate-200">
          <CardContent>
            <Form {...form}>
              <form onSubmit={form.handleSubmit(onSubmit)} noValidate className="space-y-5">
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
                            autoComplete="current-password"
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

                {topError && (
                  <div
                    role="alert"
                    className="flex items-start gap-2 rounded-md border border-status-critical/30 bg-status-critical-bg px-4 py-3 font-mono text-sm text-status-critical"
                  >
                    <span>{topError}</span>
                  </div>
                )}

                <Button type="submit" disabled={isSubmitting} className="w-full">
                  {isSubmitting && <Loader2 className="size-4 animate-spin" />}
                  {isSubmitting ? t("auth.signingIn") : t("auth.signIn")}
                </Button>
              </form>
            </Form>
          </CardContent>
        </Card>

        <p className="mt-6 text-center text-sm text-slate-600">
          {t("auth.noAccount")}{" "}
          <Link to="/register" className="font-semibold text-brand-600 hover:text-brand-700">
            {t("auth.createOne")}
          </Link>
        </p>
      </div>
    </div>
  )
}

export default LoginPage
