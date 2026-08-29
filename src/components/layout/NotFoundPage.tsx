import { ArrowLeft } from "lucide-react"
import { useTranslation } from "react-i18next"
import { Link } from "react-router-dom"

export function NotFoundPage() {
  const { t } = useTranslation()

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 text-center">
      <div className="mb-4 text-6xl font-bold text-brand-600">404</div>
      <h1 className="text-lg font-semibold text-slate-900">{t("common.pageNotFound")}</h1>
      <p className="mt-1 max-w-sm text-sm text-slate-500">
        {t("common.pageNotFoundDescription")}
      </p>
      <Link
        to="/dashboard"
        className="mt-6 inline-flex items-center gap-2 rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-brand-700"
      >
        <ArrowLeft className="size-4" aria-hidden="true" />
        {t("common.backToDashboard")}
      </Link>
    </div>
  )
}
