import { zodResolver } from "@hookform/resolvers/zod"
import { Loader2 } from "lucide-react"
import { useEffect, useState } from "react"
import { useForm } from "react-hook-form"
import { useNavigate, useParams } from "react-router-dom"
import { toast } from "sonner"

import { AddressCascader, EMPTY_ADDRESS, type AddressValue } from "@/components/data/AddressCascader"
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
import { useCreateSupplier, useSupplier, useUpdateSupplier } from "@/features/suppliers/api"
import { supplierSchema, type SupplierFormValues } from "@/features/suppliers/schemas"
import { parseSupabaseError } from "@/lib/supabase-errors"

const DEFAULT_VALUES: SupplierFormValues = {
  name: "",
  contact_person: "",
  email: "",
  phone: "",
  province_id: null,
  district_id: null,
  commune_id: null,
  village_id: null,
  address: "",
  tax_number: "",
  payment_terms: "",
  notes: "",
  is_active: true,
}

const LIST_PATH = "/master/suppliers"

/**
 * Full-page create/edit form for suppliers (dedicated route, not a modal),
 * mirroring legacy-php-vue/resources/js/pages/master/SupplierForm.vue:
 * Identity, Contact (with address cascader, province+district required),
 * and Commercial terms sections.
 */
export function SupplierFormPage() {
  const navigate = useNavigate()
  const { id } = useParams<{ id: string }>()
  const supplierId = id ? Number(id) : undefined
  const isEditing = supplierId != null

  const supplierQuery = useSupplier(supplierId)
  const createSupplier = useCreateSupplier()
  const updateSupplier = useUpdateSupplier()
  const isPending = createSupplier.isPending || updateSupplier.isPending

  const supplier = supplierQuery.data

  const [address, setAddress] = useState<AddressValue>(EMPTY_ADDRESS)

  const form = useForm<SupplierFormValues>({
    resolver: zodResolver(supplierSchema),
    defaultValues: DEFAULT_VALUES,
  })

  useEffect(() => {
    if (supplier) {
      form.reset({
        name: supplier.name,
        contact_person: supplier.contact_person ?? "",
        email: supplier.email ?? "",
        phone: supplier.phone ?? "",
        province_id: supplier.province_id,
        district_id: supplier.district_id,
        commune_id: supplier.commune_id,
        village_id: supplier.village_id,
        address: supplier.address ?? "",
        tax_number: supplier.tax_number ?? "",
        payment_terms: supplier.payment_terms ?? "",
        notes: supplier.notes ?? "",
        is_active: supplier.is_active,
      })
      setAddress({
        province_id: supplier.province_id,
        district_id: supplier.district_id,
        commune_id: supplier.commune_id,
        village_id: supplier.village_id,
        address: supplier.address ?? "",
      })
    }
  }, [supplier, form])

  function handleAddressChange(next: AddressValue) {
    setAddress(next)
    form.setValue("province_id", next.province_id)
    form.setValue("district_id", next.district_id)
    form.setValue("commune_id", next.commune_id)
    form.setValue("village_id", next.village_id)
    form.setValue("address", next.address)
  }

  async function onSubmit(values: SupplierFormValues) {
    const input = {
      name: values.name,
      contact_person: values.contact_person?.trim() || null,
      email: values.email?.trim() || null,
      phone: values.phone?.trim() || null,
      province_id: values.province_id,
      district_id: values.district_id,
      commune_id: values.commune_id,
      village_id: values.village_id,
      address: values.address?.trim() || null,
      tax_number: values.tax_number?.trim() || null,
      payment_terms: values.payment_terms?.trim() || null,
      notes: values.notes?.trim() || null,
      is_active: values.is_active,
    }
    try {
      if (isEditing && supplier) {
        await updateSupplier.mutateAsync({ id: supplier.id, input })
        toast.success("Supplier updated.")
      } else {
        await createSupplier.mutateAsync(input)
        toast.success("Supplier created.")
      }
      navigate(LIST_PATH)
    } catch (err) {
      const { message, fieldErrors } = parseSupabaseError(err as Error)
      for (const [field, msg] of Object.entries(fieldErrors)) {
        if (field === "name" || field === "email") {
          form.setError(field, { message: msg })
        }
      }
      if (Object.keys(fieldErrors).length === 0) {
        toast.error(message)
      }
    }
  }

  if (isEditing && supplierQuery.isLoading) {
    return <PageSpinner label="Loading supplier…" />
  }

  return (
    <div>
      <FormPageHeader
        backTo={LIST_PATH}
        backLabel="Back to suppliers"
        title={isEditing ? `Update supplier: ${supplier?.name || "—"}` : "Create supplier"}
        subtitle={
          isEditing
            ? "Update contact, address, or terms for this supplier."
            : "Add a new supplier. Contact and tax fields are optional but help with purchase orders."
        }
      />

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} noValidate className="space-y-6">
          <FormSection title="Identity">
            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Name</FormLabel>
                    <FormControl>
                      <Input {...field} placeholder="e.g. Angkor Distribution Co." />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="contact_person"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Contact person</FormLabel>
                    <FormControl>
                      <Input {...field} placeholder="e.g. Sok Dara" />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
          </FormSection>

          <FormSection title="Contact">
            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="email"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Email</FormLabel>
                    <FormControl>
                      <Input {...field} type="email" placeholder="supplier@example.com" />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="phone"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Phone</FormLabel>
                    <FormControl>
                      <Input {...field} placeholder="e.g. 012 345 678" />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <div className="border-t border-slate-100 pt-4">
              <AddressCascader
                value={address}
                onChange={handleAddressChange}
                required={{ province: true, district: true }}
                addressPlaceholder="Street, house number, landmark"
              />
            </div>
          </FormSection>

          <FormSection title="Commercial terms">
            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="tax_number"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Tax number</FormLabel>
                    <FormControl>
                      <Input {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="payment_terms"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Payment terms</FormLabel>
                    <FormControl>
                      <Input {...field} placeholder="e.g. Net 30" />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
            <FormField
              control={form.control}
              name="notes"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Notes</FormLabel>
                  <FormControl>
                    <Textarea {...field} rows={2} placeholder="Internal notes about this supplier" />
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
                    Active — available for purchase orders
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
              {isEditing ? "Save changes" : "Create supplier"}
            </Button>
          </div>
        </form>
      </Form>
    </div>
  )
}

export default SupplierFormPage
