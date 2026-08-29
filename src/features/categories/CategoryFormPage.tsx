import { zodResolver } from "@hookform/resolvers/zod"
import { Loader2 } from "lucide-react"
import { useEffect } from "react"
import { useForm } from "react-hook-form"
import { useNavigate, useParams } from "react-router-dom"
import { toast } from "sonner"

import { FormPageHeader } from "@/components/data/FormPageHeader"
import { FormSection } from "@/components/data/FormSection"
import { PageSpinner } from "@/components/data/PageSpinner"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Textarea } from "@/components/ui/textarea"
import {
  useAllCategories,
  useCategory,
  useCreateCategory,
  useUpdateCategory,
} from "@/features/categories/api"
import { categorySchema, type CategoryFormValues } from "@/features/categories/schemas"
import { slugify } from "@/lib/slugify"
import { parseSupabaseError } from "@/lib/supabase-errors"

const DEFAULT_VALUES: CategoryFormValues = {
  name: "",
  slug: "",
  description: "",
  parent_id: null,
  is_active: true,
}

const LIST_PATH = "/master/categories"

/**
 * Full-page create/edit form for categories, mirroring
 * legacy-php-vue/resources/js/pages/master/CategoryForm.vue — a dedicated
 * route (`/master/categories/new` or `/master/categories/:id/edit`) rather
 * than a modal dialog.
 */
export function CategoryFormPage() {
  const navigate = useNavigate()
  const { id } = useParams<{ id: string }>()
  const categoryId = id ? Number(id) : undefined
  const isEditing = categoryId != null

  const categoryQuery = useCategory(categoryId)
  const allCategories = useAllCategories()
  const createCategory = useCreateCategory()
  const updateCategory = useUpdateCategory()
  const isPending = createCategory.isPending || updateCategory.isPending

  const category = categoryQuery.data

  const form = useForm<CategoryFormValues>({
    resolver: zodResolver(categorySchema),
    defaultValues: DEFAULT_VALUES,
  })

  useEffect(() => {
    if (category) {
      form.reset({
        name: category.name,
        slug: category.slug,
        description: category.description ?? "",
        parent_id: category.parent_id,
        is_active: category.is_active,
      })
    }
  }, [category, form])

  function handleNameChange(name: string) {
    form.setValue("name", name)
    if (!isEditing && !form.getFieldState("slug").isDirty) {
      form.setValue("slug", slugify(name))
    }
  }

  async function onSubmit(values: CategoryFormValues) {
    const input = {
      name: values.name,
      slug: values.slug,
      description: values.description || null,
      parent_id: values.parent_id,
      is_active: values.is_active,
    }
    try {
      if (isEditing && category) {
        await updateCategory.mutateAsync({ id: category.id, input })
        toast.success("Category updated.")
      } else {
        await createCategory.mutateAsync(input)
        toast.success("Category created.")
      }
      navigate(LIST_PATH)
    } catch (err) {
      const { message, fieldErrors } = parseSupabaseError(err as Error)
      for (const [field, msg] of Object.entries(fieldErrors)) {
        if (field === "name" || field === "slug") {
          form.setError(field, { message: msg })
        }
      }
      if (Object.keys(fieldErrors).length === 0) {
        toast.error(message)
      }
    }
  }

  if (isEditing && categoryQuery.isLoading) {
    return <PageSpinner label="Loading category…" />
  }

  const availableParents = (allCategories.data ?? []).filter((c) => c.id !== categoryId)

  return (
    <div>
      <FormPageHeader
        backTo={LIST_PATH}
        backLabel="Back to categories"
        title={isEditing ? `Update category: ${category?.name || "—"}` : "Create product category"}
        subtitle={
          isEditing
            ? "Change the name, slug, parent, or active status for this category."
            : "Add a new category to organize your products. Slug is auto-generated from the name."
        }
      />

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} noValidate className="space-y-6">
          <FormSection title="Category details">
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Name</FormLabel>
                  <FormControl>
                    <Input
                      {...field}
                      placeholder="e.g. Beverages"
                      onChange={(e) => handleNameChange(e.target.value)}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="slug"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Slug</FormLabel>
                  <FormControl>
                    <Input {...field} placeholder="e.g. beverages" />
                  </FormControl>
                  <p className="text-xs text-slate-400">
                    URL-friendly identifier (auto-generated from the name)
                  </p>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="description"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Description</FormLabel>
                  <FormControl>
                    <Textarea
                      {...field}
                      rows={3}
                      placeholder="Optional description visible to your team"
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="parent_id"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Parent category</FormLabel>
                  <FormControl>
                    <Select
                      value={field.value != null ? String(field.value) : "none"}
                      onValueChange={(v) => field.onChange(v === "none" ? null : Number(v))}
                    >
                      <SelectTrigger className="w-full">
                        <SelectValue placeholder="— None —" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="none">— None —</SelectItem>
                        {availableParents.map((c) => (
                          <SelectItem key={c.id} value={String(c.id)}>
                            {c.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </FormControl>
                  <p className="text-xs text-slate-400">
                    Make this a sub-category of another category.
                  </p>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="is_active"
              render={({ field }) => (
                <FormItem className="flex flex-row items-center gap-2 space-y-0 border-t border-slate-100 pt-4">
                  <FormControl>
                    <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                  </FormControl>
                  <FormLabel className="font-normal">
                    Active — visible in product creation
                  </FormLabel>
                </FormItem>
              )}
            />
          </FormSection>

          <div className="flex items-center justify-end gap-2">
            <Button type="button" variant="outline" onClick={() => navigate(LIST_PATH)} disabled={isPending}>
              Cancel
            </Button>
            <Button type="submit" disabled={isPending}>
              {isPending && <Loader2 className="size-4 animate-spin" />}
              {isEditing ? "Save changes" : "Create category"}
            </Button>
          </div>
        </form>
      </Form>
    </div>
  )
}

export default CategoryFormPage
