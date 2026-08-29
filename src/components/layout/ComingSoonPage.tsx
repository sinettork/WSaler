import { Construction } from "lucide-react"
import { useTranslation } from "react-i18next"

/**
 * Placeholder for feature modules that haven't been ported to React yet
 * (POS, Sales, Master Data, Products/Batches, Inventory Ops, Admin/Users,
 * Settings, etc.). Renders at a real route so navigation/guards can be
 * exercised end-to-end before each module's real UI is built.
 */
export function ComingSoonPage({ titleKey }: { titleKey: string }) {
  const { t } = useTranslation()

  return (
    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white px-6 py-24 text-center">
      <Construction className="mb-4 size-10 text-slate-400" aria-hidden="true" />
      <h1 className="text-lg font-semibold text-slate-900">{t(titleKey)}</h1>
      <p className="mt-1 text-sm text-slate-500">{t("common.comingSoon")}</p>
      <p className="mt-1 max-w-sm text-sm text-slate-400">
        {t("common.comingSoonDescription")}
      </p>
    </div>
  )
}
