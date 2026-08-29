import { ChevronLeft } from "lucide-react"
import { Link } from "react-router-dom"

interface FormPageHeaderProps {
  backTo: string
  backLabel: string
  title: string
  subtitle?: string
}

/**
 * Full-page create/edit form header: a "Back to list" link above the title,
 * mirroring the legacy Vue pattern (e.g.
 * legacy-php-vue/resources/js/pages/master/CategoryForm.vue) of dedicated
 * `/new` and `/:id/edit` routes rather than modal dialogs.
 */
export function FormPageHeader({ backTo, backLabel, title, subtitle }: FormPageHeaderProps) {
  return (
    <div className="mb-6 min-w-0">
      <Link
        to={backTo}
        className="mb-2 inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700"
      >
        <ChevronLeft className="size-4" />
        {backLabel}
      </Link>
      <h1 className="text-2xl font-bold tracking-tight text-slate-900">{title}</h1>
      {subtitle && <p className="mt-1 text-sm text-slate-500">{subtitle}</p>}
    </div>
  )
}
