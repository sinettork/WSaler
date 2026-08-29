import { Pencil, Plus, Scale, Trash2 } from "lucide-react"
import { useState } from "react"
import { useNavigate } from "react-router-dom"
import { toast } from "sonner"

import { ConfirmDeleteDialog } from "@/components/data/ConfirmDeleteDialog"
import { EmptyState } from "@/components/data/EmptyState"
import { PageHeader } from "@/components/data/PageHeader"
import { SearchInput } from "@/components/data/SearchInput"
import { TableSkeletonRows } from "@/components/data/TableSkeleton"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { useAuth } from "@/hooks/useAuth"
import { useDeleteUnit, useUnits } from "@/features/units/api"
import type { Unit } from "@/features/units/types"
import { parseSupabaseError } from "@/lib/supabase-errors"

export function UnitsPage() {
  const navigate = useNavigate()
  const { hasPermission } = useAuth()
  const canWrite = hasPermission(["create products", "edit products"])
  const canDelete = hasPermission("delete products")

  const [search, setSearch] = useState("")
  const [deleteTarget, setDeleteTarget] = useState<Unit | null>(null)

  const units = useUnits(search)
  const deleteUnit = useDeleteUnit()

  async function performDelete() {
    if (!deleteTarget) return
    try {
      await deleteUnit.mutateAsync(deleteTarget.id)
      toast.success("Unit removed.")
      setDeleteTarget(null)
    } catch (err) {
      toast.error(parseSupabaseError(err as Error).message)
    }
  }

  const rows = units.data ?? []
  const isEmpty = !units.isLoading && rows.length === 0

  return (
    <div>
      <PageHeader
        title="Units"
        subtitle="Units of measure used for products and stock quantities."
        actions={
          canWrite ? (
            <Button onClick={() => navigate("/units/new")}>
              <Plus className="size-4" />
              Add Unit
            </Button>
          ) : undefined
        }
      />

      <div className="mb-4">
        <SearchInput value={search} onChange={setSearch} placeholder="Search units…" />
      </div>

      {isEmpty ? (
        <EmptyState
          icon={Scale}
          title={search ? "No units match your search." : "No units yet"}
          description={
            search
              ? "Try a different search term."
              : "Create your first unit of measure, e.g. Piece, Box, Kilogram."
          }
          action={
            !search && canWrite ? (
              <Button onClick={() => navigate("/units/new")}>
                <Plus className="size-4" />
                Add Unit
              </Button>
            ) : undefined
          }
        />
      ) : (
        <div className="rounded-lg border border-slate-200 bg-white">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Short code</TableHead>
                <TableHead>Base unit</TableHead>
                <TableHead>Conversion factor</TableHead>
                {(canWrite || canDelete) && <TableHead className="text-right">Actions</TableHead>}
              </TableRow>
            </TableHeader>
            <TableBody>
              {units.isLoading ? (
                <TableSkeletonRows columns={canWrite || canDelete ? 5 : 4} />
              ) : (
                rows.map((unit) => (
                  <TableRow key={unit.id}>
                    <TableCell className="font-medium text-slate-900">{unit.name}</TableCell>
                    <TableCell className="font-mono text-slate-500">{unit.short_code}</TableCell>
                    <TableCell>
                      {unit.base ? (
                        <Badge variant="outline" className="border-brand-200 bg-brand-50 text-brand-700">
                          Base
                        </Badge>
                      ) : (
                        "—"
                      )}
                    </TableCell>
                    <TableCell className="font-mono tabular-nums text-slate-500">
                      {unit.base ? "1.0000" : unit.conversion_factor_to_base}
                    </TableCell>
                    {(canWrite || canDelete) && (
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-1">
                          {canWrite && (
                            <Button
                              size="icon-sm"
                              variant="ghost"
                              aria-label="Edit"
                              onClick={() => navigate(`/units/${unit.id}/edit`)}
                            >
                              <Pencil className="size-4" />
                            </Button>
                          )}
                          {canDelete && (
                            <Button
                              size="icon-sm"
                              variant="ghost"
                              aria-label="Delete"
                              onClick={() => setDeleteTarget(unit)}
                            >
                              <Trash2 className="size-4 text-destructive" />
                            </Button>
                          )}
                        </div>
                      </TableCell>
                    )}
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </div>
      )}

      <ConfirmDeleteDialog
        open={deleteTarget != null}
        onOpenChange={(open) => !open && setDeleteTarget(null)}
        title={`Remove unit: ${deleteTarget?.name ?? ""}?`}
        description="If any products use this unit as their base unit, deletion will be blocked. This action cannot be undone."
        confirmLabel="Remove unit"
        isPending={deleteUnit.isPending}
        onConfirm={performDelete}
      />
    </div>
  )
}

export default UnitsPage
