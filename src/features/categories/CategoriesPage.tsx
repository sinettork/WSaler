import { Pencil, Plus, Tag, Trash2 } from "lucide-react"
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
import { useCategories, useDeleteCategory } from "@/features/categories/api"
import type { CategoryWithParent } from "@/features/categories/types"
import { useAuth } from "@/hooks/useAuth"
import { parseSupabaseError } from "@/lib/supabase-errors"

export function CategoriesPage() {
  const navigate = useNavigate()
  const { hasPermission } = useAuth()
  const canWrite = hasPermission(["create products", "edit products"])
  const canDelete = hasPermission("delete products")

  const [search, setSearch] = useState("")
  const [deleteTarget, setDeleteTarget] = useState<CategoryWithParent | null>(null)

  const categories = useCategories(search)
  const deleteCategory = useDeleteCategory()

  async function performDelete() {
    if (!deleteTarget) return
    try {
      await deleteCategory.mutateAsync(deleteTarget.id)
      toast.success("Category removed.")
      setDeleteTarget(null)
    } catch (err) {
      toast.error(parseSupabaseError(err as Error).message)
    }
  }

  const rows = categories.data ?? []
  const isEmpty = !categories.isLoading && rows.length === 0

  return (
    <div>
      <PageHeader
        title="Categories"
        subtitle="Manage product categories."
        actions={
          canWrite ? (
            <Button onClick={() => navigate("/master/categories/new")}>
              <Plus className="size-4" />
              Add Category
            </Button>
          ) : undefined
        }
      />

      <div className="mb-4">
        <SearchInput value={search} onChange={setSearch} placeholder="Search categories…" />
      </div>

      {isEmpty ? (
        <EmptyState
          icon={Tag}
          title={search ? "No categories match your search." : "No categories yet"}
          description={
            search
              ? "Try a different search term."
              : "Create your first category to start organizing products."
          }
          action={
            !search && canWrite ? (
              <Button onClick={() => navigate("/master/categories/new")}>
                <Plus className="size-4" />
                Add Category
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
                <TableHead>Parent</TableHead>
                <TableHead>Status</TableHead>
                {(canWrite || canDelete) && <TableHead className="text-right">Actions</TableHead>}
              </TableRow>
            </TableHeader>
            <TableBody>
              {categories.isLoading ? (
                <TableSkeletonRows columns={canWrite || canDelete ? 5 : 4} />
              ) : (
                rows.map((category) => (
                  <TableRow key={category.id}>
                    <TableCell className="font-medium text-slate-900">{category.name}</TableCell>
                    <TableCell className="font-mono text-slate-500">{category.slug}</TableCell>
                    <TableCell className="text-slate-500">
                      {category.parent?.name ?? "—"}
                    </TableCell>
                    <TableCell>
                      <StatusBadge active={category.is_active} />
                    </TableCell>
                    {(canWrite || canDelete) && (
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-1">
                          {canWrite && (
                            <Button
                              size="icon-sm"
                              variant="ghost"
                              aria-label="Edit"
                              onClick={() => navigate(`/master/categories/${category.id}/edit`)}
                            >
                              <Pencil className="size-4" />
                            </Button>
                          )}
                          {canDelete && (
                            <Button
                              size="icon-sm"
                              variant="ghost"
                              aria-label="Delete"
                              onClick={() => setDeleteTarget(category)}
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
        title={`Remove category: ${deleteTarget?.name ?? ""}?`}
        description="Products in this category will need to be reassigned. This action cannot be undone."
        confirmLabel="Remove category"
        isPending={deleteCategory.isPending}
        onConfirm={performDelete}
      />
    </div>
  )
}

export default CategoriesPage
