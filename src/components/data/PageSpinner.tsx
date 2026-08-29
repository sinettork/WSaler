import { Loader2 } from "lucide-react"

/** Full-width loading state for a full-page form while an entity is being fetched for editing. */
export function PageSpinner({ label }: { label: string }) {
  return (
    <div className="flex flex-col items-center justify-center gap-3 py-24 text-slate-400">
      <Loader2 className="size-8 animate-spin" />
      <p className="text-sm">{label}</p>
    </div>
  )
}
