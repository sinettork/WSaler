import { Boxes, Pencil, Plus, Trash2 } from "lucide-react"
import { useState } from "react"
import { useNavigate } from "react-router-dom"
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
import { useBrands, useDeleteBrand } from "@/features/brands/api"
import type { Brand } from "@/features/brands/types"
import { useAuth } from "@/hooks/useAuth"
import { parseSupabaseError } from "@/lib/supabase-errors"

export function BrandsPage() {
  const navigate = useNavigate()
  const { hasPermission } = useAuth()
  const canWrite = hasPermission(["create products", "edit products"])
  const canDelete = hasPermission("delete products")

  const [search, setSearch] = useState("")
  const [deleteTarget, setDeleteTarget] = useState<Brand | null>(null)

  const brands = useBrands(search)
  const deleteBrand = useDeleteBrand()

  async function performDelete() {
    if (!deleteTarget) return
    try {
      await deleteBrand.mutateAsync(deleteTarget.id)
      toast.success("Brand removed.")
      setDeleteTarget(null)
    } catch (err) {
      toast.error(parseSupabaseError(err as Error).message)
    }
  }

  const rows = brands.data ?? []
  const isEmpty = !brands.isLoading && rows.length === 0

  return (
    <div>
      <PageHeader
        title="Brands"
        subtitle="Manage product brands."
        actions={
          canWrite ? (
            <Button onClick={() => navigate("/master/brands/new")}>
              <Plus className="size-4" />
              Add Brand
            </Button>
          ) : undefined
        }
      />

      <div className="mb-4">
        <SearchInput value={search} onChange={setSearch} placeholder="Search brands…" />
      </div>

      {isEmpty ? (
        <EmptyState
          icon={Boxes}
          title={search ? "No brands match your search." : "No brands yet"}
          description={
            search ? "Try a different search term." : "Create your first brand to get started."
          }
          action={
            !search && canWrite ? (
              <Button onClick={() => navigate("/master/brands/new")}>
                <Plus className="size-4" />
                Add Brand
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
                <TableHead>Slug</TableHead>
                <TableHead>Status</TableHead>
                {(canWrite || canDelete) && <TableHead className="text-right">Actions</TableHead>}
              </TableRow>
            </TableHeader>
            <TableBody>
              {brands.isLoading ? (
                <TableSkeletonRows columns={canWrite || canDelete ? 4 : 3} />
              ) : (
                rows.map((brand) => (
                  <TableRow key={brand.id}>
                    <TableCell className="font-medium text-slate-900">{brand.name}</TableCell>
                    <TableCell className="font-mono text-slate-500">{brand.slug}</TableCell>
                    <TableCell>
                      <StatusBadge active={brand.is_active} />
                    </TableCell>
                    {(canWrite || canDelete) && (
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-1">
                          {canWrite && (
                            <Button
                              size="icon-sm"
                              variant="ghost"
                              aria-label="Edit"
                              onClick={() => navigate(`/master/brands/${brand.id}/edit`)}
                            >
                              <Pencil className="size-4" />
                            </Button>
                          )}
                          {canDelete && (
                            <Button
                              size="icon-sm"
                              variant="ghost"
                              aria-label="Delete"
                              onClick={() => setDeleteTarget(brand)}
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
        title={`Remove brand: ${deleteTarget?.name ?? ""}?`}
        description="Products under this brand will need to be reassigned. This action cannot be undone."
        confirmLabel="Remove brand"
        isPending={deleteBrand.isPending}
        onConfirm={performDelete}
      />
    </div>
  )
}

export default BrandsPage
