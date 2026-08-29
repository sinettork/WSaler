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
import { Textarea } from "@/components/ui/textarea"
import { useBrand, useCreateBrand, useUpdateBrand } from "@/features/brands/api"
import { brandSchema, type BrandFormValues } from "@/features/brands/schemas"
import { slugify } from "@/lib/slugify"
import { parseSupabaseError } from "@/lib/supabase-errors"

const DEFAULT_VALUES: BrandFormValues = {
  name: "",
  slug: "",
  description: "",
  is_active: true,
}

const LIST_PATH = "/master/brands"

/** Full-page create/edit form for brands (dedicated route, not a modal). */
export function BrandFormPage() {
  const navigate = useNavigate()
  const { id } = useParams<{ id: string }>()
  const brandId = id ? Number(id) : undefined
  const isEditing = brandId != null

  const brandQuery = useBrand(brandId)
  const createBrand = useCreateBrand()
  const updateBrand = useUpdateBrand()
  const isPending = createBrand.isPending || updateBrand.isPending

  const brand = brandQuery.data

  const form = useForm<BrandFormValues>({
    resolver: zodResolver(brandSchema),
    defaultValues: DEFAULT_VALUES,
  })

  useEffect(() => {
    if (brand) {
      form.reset({
        name: brand.name,
        slug: brand.slug,
        description: brand.description ?? "",
        is_active: brand.is_active,
      })
    }
  }, [brand, form])

  function handleNameChange(name: string) {
    form.setValue("name", name)
    if (!isEditing && !form.getFieldState("slug").isDirty) {
      form.setValue("slug", slugify(name))
    }
  }

  async function onSubmit(values: BrandFormValues) {
    const input = {
      name: values.name,
      slug: values.slug,
      description: values.description || null,
      logo: brand?.logo ?? null,
      is_active: values.is_active,
    }
    try {
      if (isEditing && brand) {
        await updateBrand.mutateAsync({ id: brand.id, input })
        toast.success("Brand updated.")
      } else {
        await createBrand.mutateAsync(input)
        toast.success("Brand created.")
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

  if (isEditing && brandQuery.isLoading) {
    return <PageSpinner label="Loading brand…" />
  }

  return (
    <div>
      <FormPageHeader
        backTo={LIST_PATH}
        backLabel="Back to brands"
        title={isEditing ? `Update brand: ${brand?.name || "—"}` : "Create brand"}
        subtitle={
          isEditing
            ? "Change the name, slug, or active status for this brand."
            : "Add a new brand. Slug is auto-generated from the name."
        }
      />

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} noValidate className="space-y-6">
          <FormSection title="Brand details">
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Name</FormLabel>
                  <FormControl>
                    <Input
                      {...field}
                      placeholder="e.g. Acme Foods"
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
                    <Input {...field} placeholder="e.g. acme-foods" />
                  </FormControl>
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
                    <Textarea {...field} rows={3} placeholder="Optional description" />
                  </FormControl>
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
              {isEditing ? "Save changes" : "Create brand"}
            </Button>
          </div>
        </form>
      </Form>
    </div>
  )
}

export default BrandFormPage
