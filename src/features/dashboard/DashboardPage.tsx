import { useTranslation } from "react-i18next"

import { useAuth } from "@/hooks/useAuth"

export function DashboardPage() {
  const { t } = useTranslation()
  const { profile } = useAuth()

  return (
    <div>
      <h1 className="text-2xl font-bold text-slate-900">{t("nav.dashboard")}</h1>
      <p className="mt-1 text-sm text-slate-500">
        {profile ? `Welcome back, ${profile.name}.` : ""}
      </p>
    </div>
  )
}

export default DashboardPage
