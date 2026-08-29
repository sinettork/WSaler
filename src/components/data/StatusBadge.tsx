import { Badge } from "@/components/ui/badge"

export function StatusBadge({ active }: { active: boolean }) {
  return (
    <Badge
      variant="outline"
      className={
        active
          ? "border-status-fresh/30 bg-status-fresh-bg text-status-fresh"
          : "border-slate-200 bg-slate-100 text-slate-500"
      }
    >
      {active ? "Active" : "Inactive"}
    </Badge>
  )
}
