import { zodResolver } from "@hookform/resolvers/zod"
import { Loader2 } from "lucide-react"
import { useEffect } from "react"
import { useForm } from "react-hook-form"
import { toast } from "sonner"

import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
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
import { useCreateBrand, useUpdateBrand } from "@/features/brands/api"
import { brandSchema, type BrandFormValues } from "@/features/brands/schemas"
import type { Brand } from "@/features/brands/types"
import { slugify } from "@/lib/slugify"
import { parseSupabaseError } from "@/lib/supabase-errors"

const DEFAULT_VALUES: BrandFormValues = {
  name: "",
  slug: "",
  description: "",
  is_active: true,
}

interface BrandFormDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  brand: Brand | null
}

export function BrandFormDialog({ open, onOpenChange, brand }: BrandFormDialogProps) {
  const isEditing = brand != null
  const createBrand = useCreateBrand()
  const updateBrand = useUpdateBrand()
  const isPending = createBrand.isPending || updateBrand.isPending

  const form = useForm<BrandFormValues>({
    resolver: zodResolver(brandSchema),
    defaultValues: DEFAULT_VALUES,
  })

  useEffect(() => {
    if (open) {
      form.reset(
        brand
          ? {
              name: brand.name,
              slug: brand.slug,
              description: brand.description ?? "",
              is_active: brand.is_active,
            }
          : DEFAULT_VALUES,
      )
    }
  }, [open, brand, form])

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
      if (isEditing) {
        await updateBrand.mutateAsync({ id: brand.id, input })
        toast.success("Brand updated.")
      } else {
        await createBrand.mutateAsync(input)
        toast.success("Brand created.")
      }
      onOpenChange(false)
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

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{isEditing ? "Edit brand" : "Create brand"}</DialogTitle>
          <DialogDescription>
            {isEditing
              ? "Change the name, slug, or active status for this brand."
              : "Add a new brand. Slug is auto-generated from the name."}
          </DialogDescription>
        </DialogHeader>

        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} noValidate className="space-y-4">
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

            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                onClick={() => onOpenChange(false)}
                disabled={isPending}
              >
                Cancel
              </Button>
              <Button type="submit" disabled={isPending}>
                {isPending && <Loader2 className="size-4 animate-spin" />}
                {isEditing ? "Save changes" : "Create brand"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  )
}
