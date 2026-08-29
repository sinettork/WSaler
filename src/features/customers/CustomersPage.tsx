import { Lock, Pencil, Plus, Trash2, Users } from "lucide-react"
import { useState } from "react"
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
import { CustomerFormDialog } from "@/features/customers/CustomerFormDialog"
import { useCustomers, useDeleteCustomer } from "@/features/customers/api"
import type { CustomerWithAddress } from "@/features/customers/types"
import { useAuth } from "@/hooks/useAuth"
import { parseSupabaseError } from "@/lib/supabase-errors"

const TYPE_BADGE_CLASS: Record<string, string> = {
  retail: "border-sky-200 bg-sky-50 text-sky-700",
  wholesale: "border-brand-200 bg-brand-50 text-brand-700",
  distributor: "border-amber-200 bg-amber-50 text-amber-700",
}

function formatAddress(row: CustomerWithAddress): string {
  const parts = [row.address, row.village?.name_en, row.commune?.name_en, row.district?.name_en, row.province?.name_en]
    .filter(Boolean)
  return parts.length ? parts.join(" • ") : "—"
}

/**
 * Like Suppliers, customers.select is gated by "view customers" (not
 * "read all authenticated") — see supabase/migrations/0009_rls_policies.sql.
 */
export function CustomersPage() {
  const { hasPermission } = useAuth()
  const canRead = hasPermission("view customers")
  const canWrite = hasPermission(["create customers", "edit customers"])
  const canDelete = hasPermission("delete customers")

  const [search, setSearch] = useState("")
  const [formOpen, setFormOpen] = useState(false)
  const [editing, setEditing] = useState<CustomerWithAddress | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<CustomerWithAddress | null>(null)

  const customers = useCustomers(search, canRead)
  const deleteCustomer = useDeleteCustomer()

  function openCreate() {
    setEditing(null)
    setFormOpen(true)
  }

  function openEdit(customer: CustomerWithAddress) {
    setEditing(customer)
    setFormOpen(true)
  }

  async function performDelete() {
    if (!deleteTarget) return
    try {
      await deleteCustomer.mutateAsync(deleteTarget.id)
      toast.success("Customer removed.")
      setDeleteTarget(null)
    } catch (err) {
      toast.error((err as Error).message || parseSupabaseError(err as Error).message)
    }
  }

  if (!canRead) {
    return (
      <div>
        <PageHeader title="Customers" subtitle="Manage your customer directory." />
        <EmptyState
          icon={Lock}
          title="You don't have access to customers"
          description="Ask an administrator to grant you the 'view customers' permission."
        />
      </div>
    )
  }

  const rows = customers.data ?? []
  const isEmpty = !customers.isLoading && rows.length === 0

  return (
    <div>
      <PageHeader
        title="Customers"
        subtitle="Manage your customer directory."
        actions={
          canWrite ? (
            <Button onClick={openCreate}>
              <Plus className="size-4" />
              Add Customer
            </Button>
          ) : undefined
        }
      />

      <div className="mb-4">
        <SearchInput value={search} onChange={setSearch} placeholder="Search customers…" />
      </div>

      {isEmpty ? (
        <EmptyState
          icon={Users}
          title={search ? "No customers match your search." : "No customers yet"}
          description={
            search
              ? "Try a different search term."
              : "Add your first customer to start recording sales."
          }
          action={
            !search && canWrite ? (
              <Button onClick={openCreate}>
                <Plus className="size-4" />
                Add Customer
              </Button>
            ) : undefined
          }
        />
      ) : (
        <div className="rounded-lg border border-slate-200 bg-white">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Code</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead>Address</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Status</TableHead>
                {(canWrite || canDelete) && <TableHead className="text-right">Actions</TableHead>}
              </TableRow>
            </TableHeader>
            <TableBody>
              {customers.isLoading ? (
                <TableSkeletonRows columns={canWrite || canDelete ? 8 : 7} />
              ) : (
                rows.map((customer) => (
                  <TableRow key={customer.id}>
                    <TableCell className="font-mono text-slate-500">{customer.code}</TableCell>
                    <TableCell className="font-medium text-slate-900">{customer.name}</TableCell>
                    <TableCell className="text-slate-500">{customer.email ?? "—"}</TableCell>
                    <TableCell className="text-slate-500">{customer.phone ?? "—"}</TableCell>
                    <TableCell className="max-w-xs truncate text-slate-500">
                      {formatAddress(customer)}
                    </TableCell>
                    <TableCell>
                      <Badge
                        variant="outline"
                        className={TYPE_BADGE_CLASS[customer.type] ?? "border-slate-200 bg-slate-50 text-slate-700"}
                      >
                        {customer.type}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <StatusBadge active={customer.is_active} />
                    </TableCell>
                    {(canWrite || canDelete) && (
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-1">
                          {canWrite && (
                            <Button
                              size="icon-sm"
                              variant="ghost"
                              aria-label="Edit"
                              onClick={() => openEdit(customer)}
                            >
                              <Pencil className="size-4" />
                            </Button>
                          )}
                          {canDelete && (
                            <Button
                              size="icon-sm"
                              variant="ghost"
                              aria-label="Delete"
                              onClick={() => setDeleteTarget(customer)}
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

      <CustomerFormDialog open={formOpen} onOpenChange={setFormOpen} customer={editing} />

      <ConfirmDeleteDialog
        open={deleteTarget != null}
        onOpenChange={(open) => !open && setDeleteTarget(null)}
        title={`Remove customer: ${deleteTarget?.name ?? ""}?`}
        description="Customers with sales history cannot be removed — deactivate them instead. This action cannot be undone."
        confirmLabel="Remove customer"
        isPending={deleteCustomer.isPending}
        onConfirm={performDelete}
      />
    </div>
  )
}

export default CustomersPage
