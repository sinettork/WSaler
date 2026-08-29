import { Pencil, Plus, Star, Trash2, Warehouse as WarehouseIcon } from "lucide-react"
import { useState } from "react"
import { useNavigate } from "react-router-dom"
import { toast } from "sonner"

import { ConfirmDeleteDialog } from "@/components/data/ConfirmDeleteDialog"
import { EmptyState } from "@/components/data/EmptyState"
import { PageHeader } from "@/components/data/PageHeader"
import { SearchInput } from "@/components/data/SearchInput"
import { StatusBadge } from "@/components/data/StatusBadge"
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
import { useDeleteWarehouse, useWarehouses } from "@/features/warehouses/api"
import type { Warehouse } from "@/features/warehouses/types"
import { useAuth } from "@/hooks/useAuth"
import { parseSupabaseError } from "@/lib/supabase-errors"

/**
 * Unlike Categories/Brands/Units (gated by the generic "products" write
 * permissions), Warehouses has its own dedicated permission set in
 * supabase/migrations/0009_rls_policies.sql — must use "warehouses" here,
 * not "products".
 */
export function WarehousesPage() {
  const navigate = useNavigate()
  const { hasPermission } = useAuth()
  const canWrite = hasPermission(["create warehouses", "edit warehouses"])
  const canDelete = hasPermission("delete warehouses")

  const [search, setSearch] = useState("")
  const [deleteTarget, setDeleteTarget] = useState<Warehouse | null>(null)

  const warehouses = useWarehouses(search)
  const deleteWarehouse = useDeleteWarehouse()

  async function performDelete() {
    if (!deleteTarget) return
    try {
      await deleteWarehouse.mutateAsync(deleteTarget.id)
      toast.success("Warehouse removed.")
      setDeleteTarget(null)
    } catch (err) {
      toast.error(parseSupabaseError(err as Error).message)
    }
  }

  const rows = warehouses.data ?? []
  const isEmpty = !warehouses.isLoading && rows.length === 0

  return (
    <div>
      <PageHeader
        title="Warehouses"
        subtitle="Manage warehouse and branch locations used for stock and sales."
        actions={
          canWrite ? (
            <Button onClick={() => navigate("/warehouses/new")}>
              <Plus className="size-4" />
              Add Warehouse
            </Button>
          ) : undefined
        }
      />

      <div className="mb-4">
        <SearchInput value={search} onChange={setSearch} placeholder="Search warehouses…" />
      </div>

      {isEmpty ? (
        <EmptyState
          icon={WarehouseIcon}
          title={search ? "No warehouses match your search." : "No warehouses yet"}
          description={
            search
              ? "Try a different search term."
              : "Create your first warehouse or branch location to start tracking stock."
          }
          action={
            !search && canWrite ? (
              <Button onClick={() => navigate("/warehouses/new")}>
                <Plus className="size-4" />
                Add Warehouse
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
                <TableHead>Code</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead>Default</TableHead>
                <TableHead>Status</TableHead>
                {(canWrite || canDelete) && <TableHead className="text-right">Actions</TableHead>}
              </TableRow>
            </TableHeader>
            <TableBody>
              {warehouses.isLoading ? (
                <TableSkeletonRows columns={canWrite || canDelete ? 6 : 5} />
              ) : (
                rows.map((warehouse) => (
                  <TableRow key={warehouse.id}>
                    <TableCell className="font-medium text-slate-900">{warehouse.name}</TableCell>
                    <TableCell className="font-mono text-slate-500">{warehouse.code}</TableCell>
                    <TableCell className="text-slate-500">{warehouse.phone ?? "—"}</TableCell>
                    <TableCell>
                      {warehouse.is_default ? (
                        <Badge variant="outline" className="border-brand-200 bg-brand-50 text-brand-700">
                          <Star className="size-3 fill-current" />
                          Default
                        </Badge>
                      ) : (
                        "—"
                      )}
                    </TableCell>
                    <TableCell>
                      <StatusBadge active={warehouse.is_active} />
                    </TableCell>
                    {(canWrite || canDelete) && (
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-1">
                          {canWrite && (
                            <Button
                              size="icon-sm"
                              variant="ghost"
                              aria-label="Edit"
                              onClick={() => navigate(`/warehouses/${warehouse.id}/edit`)}
                            >
                              <Pencil className="size-4" />
                            </Button>
                          )}
                          {canDelete && (
                            <Button
                              size="icon-sm"
                              variant="ghost"
                              aria-label="Delete"
                              onClick={() => setDeleteTarget(warehouse)}
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
        title={`Remove warehouse: ${deleteTarget?.name ?? ""}?`}
        description="Warehouses with existing stock or transaction history should be deactivated instead of removed. This action cannot be undone."
        confirmLabel="Remove warehouse"
        isPending={deleteWarehouse.isPending}
        onConfirm={performDelete}
      />
    </div>
  )
}

export default WarehousesPage
