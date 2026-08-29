import { Building2, Lock, Pencil, Plus, Trash2 } from "lucide-react"
import { useState } from "react"
import { toast } from "sonner"

import { ConfirmDeleteDialog } from "@/components/data/ConfirmDeleteDialog"
import { EmptyState } from "@/components/data/EmptyState"
import { PageHeader } from "@/components/data/PageHeader"
import { SearchInput } from "@/components/data/SearchInput"
import { StatusBadge } from "@/components/data/StatusBadge"
import { TableSkeletonRows } from "@/components/data/TableSkeleton"
import { Button } from "@/components/ui/button"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { useDeleteSupplier, useSuppliers } from "@/features/suppliers/api"
import { SupplierFormDialog } from "@/features/suppliers/SupplierFormDialog"
import type { SupplierWithAddress } from "@/features/suppliers/types"
import { useAuth } from "@/hooks/useAuth"
import { parseSupabaseError } from "@/lib/supabase-errors"

function formatAddress(row: SupplierWithAddress): string {
  const parts = [row.address, row.village?.name_en, row.commune?.name_en, row.district?.name_en, row.province?.name_en]
    .filter(Boolean)
  return parts.length ? parts.join(" • ") : "—"
}

/**
 * Unlike Categories/Brands/Units/Warehouses (readable by any authenticated
 * user), suppliers.select is itself gated by "view suppliers" per
 * supabase/migrations/0009_rls_policies.sql — so this page needs a read
 * gate distinct from the write/delete gates.
 */
export function SuppliersPage() {
  const { hasPermission } = useAuth()
  const canRead = hasPermission("view suppliers")
  const canWrite = hasPermission(["create suppliers", "edit suppliers"])
  const canDelete = hasPermission("delete suppliers")

  const [search, setSearch] = useState("")
  const [formOpen, setFormOpen] = useState(false)
  const [editing, setEditing] = useState<SupplierWithAddress | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<SupplierWithAddress | null>(null)

  const suppliers = useSuppliers(search, canRead)
  const deleteSupplier = useDeleteSupplier()

  function openCreate() {
    setEditing(null)
    setFormOpen(true)
  }

  function openEdit(supplier: SupplierWithAddress) {
    setEditing(supplier)
    setFormOpen(true)
  }

  async function performDelete() {
    if (!deleteTarget) return
    try {
      await deleteSupplier.mutateAsync(deleteTarget.id)
      toast.success("Supplier removed.")
      setDeleteTarget(null)
    } catch (err) {
      toast.error(parseSupabaseError(err as Error).message)
    }
  }

  if (!canRead) {
    return (
      <div>
        <PageHeader title="Suppliers" subtitle="Manage your supplier directory." />
        <EmptyState
          icon={Lock}
          title="You don't have access to suppliers"
          description="Ask an administrator to grant you the 'view suppliers' permission."
        />
      </div>
    )
  }

  const rows = suppliers.data ?? []
  const isEmpty = !suppliers.isLoading && rows.length === 0

  return (
    <div>
      <PageHeader
        title="Suppliers"
        subtitle="Manage your supplier directory."
        actions={
          canWrite ? (
            <Button onClick={openCreate}>
              <Plus className="size-4" />
              Add Supplier
            </Button>
          ) : undefined
        }
      />

      <div className="mb-4">
        <SearchInput value={search} onChange={setSearch} placeholder="Search suppliers…" />
      </div>

      {isEmpty ? (
        <EmptyState
          icon={Building2}
          title={search ? "No suppliers match your search." : "No suppliers yet"}
          description={
            search
              ? "Try a different search term."
              : "Add your first supplier to start creating purchase orders."
          }
          action={
            !search && canWrite ? (
              <Button onClick={openCreate}>
                <Plus className="size-4" />
                Add Supplier
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
                <TableHead>Contact</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead>Address</TableHead>
                <TableHead>Status</TableHead>
                {(canWrite || canDelete) && <TableHead className="text-right">Actions</TableHead>}
              </TableRow>
            </TableHeader>
            <TableBody>
              {suppliers.isLoading ? (
                <TableSkeletonRows columns={canWrite || canDelete ? 7 : 6} />
              ) : (
                rows.map((supplier) => (
                  <TableRow key={supplier.id}>
                    <TableCell className="font-medium text-slate-900">{supplier.name}</TableCell>
                    <TableCell className="text-slate-500">{supplier.contact_person ?? "—"}</TableCell>
                    <TableCell className="text-slate-500">{supplier.email ?? "—"}</TableCell>
                    <TableCell className="text-slate-500">{supplier.phone ?? "—"}</TableCell>
                    <TableCell className="max-w-xs truncate text-slate-500">
                      {formatAddress(supplier)}
                    </TableCell>
                    <TableCell>
                      <StatusBadge active={supplier.is_active} />
                    </TableCell>
                    {(canWrite || canDelete) && (
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-1">
                          {canWrite && (
                            <Button
                              size="icon-sm"
                              variant="ghost"
                              aria-label="Edit"
                              onClick={() => openEdit(supplier)}
                            >
                              <Pencil className="size-4" />
                            </Button>
                          )}
                          {canDelete && (
                            <Button
                              size="icon-sm"
                              variant="ghost"
                              aria-label="Delete"
                              onClick={() => setDeleteTarget(supplier)}
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

      <SupplierFormDialog open={formOpen} onOpenChange={setFormOpen} supplier={editing} />

      <ConfirmDeleteDialog
        open={deleteTarget != null}
        onOpenChange={(open) => !open && setDeleteTarget(null)}
        title={`Remove supplier: ${deleteTarget?.name ?? ""}?`}
        description="Active purchase orders may reference this supplier. This action cannot be undone."
        confirmLabel="Remove supplier"
        isPending={deleteSupplier.isPending}
        onConfirm={performDelete}
      />
    </div>
  )
}

export default SuppliersPage
