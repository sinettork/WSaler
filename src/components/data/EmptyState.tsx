import type { LucideIcon } from "lucide-react"
import type { ReactNode } from "react"

interface EmptyStateProps {
  icon: LucideIcon
  title: string
  description?: string
  action?: ReactNode
}

/**
 * Progressive-complexity empty state: shown when a Master Data table has no
 * rows yet (small business just starting out) or when the signed-in role
 * can't read the table at all (RLS-gated). The `action` slot renders a
 * create button only when the caller has permission — no separate
 * "small business mode", just data-driven UI.
 */
export function EmptyState({ icon: Icon, title, description, action }: EmptyStateProps) {
  return (
    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
      <Icon className="mb-3 size-9 text-slate-300" aria-hidden="true" />
      <h2 className="text-sm font-semibold text-slate-900">{title}</h2>
      {description && <p className="mt-1 max-w-sm text-sm text-slate-500">{description}</p>}
      {action && <div className="mt-4">{action}</div>}
    </div>
  )
}
