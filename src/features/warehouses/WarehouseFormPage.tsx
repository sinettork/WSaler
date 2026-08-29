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
import { useCreateWarehouse, useUpdateWarehouse, useWarehouse } from "@/features/warehouses/api"
import { warehouseSchema, type WarehouseFormValues } from "@/features/warehouses/schemas"
import { parseSupabaseError } from "@/lib/supabase-errors"

const DEFAULT_VALUES: WarehouseFormValues = {
  name: "",
  code: "",
  province_id: null,
  district_id: null,
  commune_id: null,
  village_id: null,
  address: "",
  phone: "",
  is_default: false,
  is_active: true,
}

const LIST_PATH = "/warehouses"

/**
 * Full-page create/edit form for warehouses (dedicated route, not a modal),
 * mirroring legacy-php-vue/resources/js/pages/master/WarehouseForm.vue.
 * Holds the Cambodia address selection as a nested `AddressValue` object
 * (via `AddressCascader`) and flattens it into the top-level
 * province/district/commune/village FK fields on submit. `code` is left
 * blank on create so the server-side-equivalent `nextWarehouseCode()`
 * helper in `api.ts` can auto-assign a `WH-XXX` code.
 */
export function WarehouseFormPage() {
  const navigate = useNavigate()
  const { id } = useParams<{ id: string }>()
  const warehouseId = id ? Number(id) : undefined
  const isEditing = warehouseId != null

  const warehouseQuery = useWarehouse(warehouseId)
  const createWarehouse = useCreateWarehouse()
  const updateWarehouse = useUpdateWarehouse()
  const isPending = createWarehouse.isPending || updateWarehouse.isPending

  const warehouse = warehouseQuery.data

  const [address, setAddress] = useState<AddressValue>(EMPTY_ADDRESS)

  const form = useForm<WarehouseFormValues>({
    resolver: zodResolver(warehouseSchema),
    defaultValues: DEFAULT_VALUES,
  })

  useEffect(() => {
    if (warehouse) {
      form.reset({
        name: warehouse.name,
        code: warehouse.code,
        province_id: warehouse.province_id,
        district_id: warehouse.district_id,
        commune_id: warehouse.commune_id,
        village_id: warehouse.village_id,
        address: warehouse.address ?? "",
        phone: warehouse.phone ?? "",
        is_default: warehouse.is_default,
        is_active: warehouse.is_active,
      })
      setAddress({
        province_id: warehouse.province_id,
        district_id: warehouse.district_id,
        commune_id: warehouse.commune_id,
        village_id: warehouse.village_id,
        address: warehouse.address ?? "",
      })
    }
  }, [warehouse, form])

  function handleAddressChange(next: AddressValue) {
    setAddress(next)
    form.setValue("province_id", next.province_id)
    form.setValue("district_id", next.district_id)
    form.setValue("commune_id", next.commune_id)
    form.setValue("village_id", next.village_id)
    form.setValue("address", next.address)
  }

  async function onSubmit(values: WarehouseFormValues) {
    const input = {
      name: values.name,
      code: values.code?.trim() || undefined,
      province_id: values.province_id,
      district_id: values.district_id,
      commune_id: values.commune_id,
      village_id: values.village_id,
      address: values.address?.trim() || null,
      phone: values.phone?.trim() || null,
      is_default: values.is_default,
      is_active: values.is_active,
    }
    try {
      if (isEditing && warehouse) {
        await updateWarehouse.mutateAsync({ id: warehouse.id, input })
        toast.success("Warehouse updated.")
      } else {
        await createWarehouse.mutateAsync(input)
        toast.success("Warehouse created.")
      }
      navigate(LIST_PATH)
    } catch (err) {
      const { message, fieldErrors } = parseSupabaseError(err as Error)
      for (const [field, msg] of Object.entries(fieldErrors)) {
        if (field === "name" || field === "code") {
          form.setError(field, { message: msg })
        }
      }
      if (Object.keys(fieldErrors).length === 0) {
        toast.error(message)
      }
    }
  }

  if (isEditing && warehouseQuery.isLoading) {
    return <PageSpinner label="Loading warehouse…" />
  }

  return (
    <div>
      <FormPageHeader
        backTo={LIST_PATH}
        backLabel="Back to warehouses"
        title={isEditing ? `Update warehouse: ${warehouse?.name || "—"}` : "Create warehouse"}
        subtitle={
          isEditing
            ? "Update this warehouse or branch's details and address."
            : "Add a new warehouse or branch location. A code will be auto-assigned if left blank."
        }
      />

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} noValidate className="space-y-6">
          <FormSection title="Warehouse details">
            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Name</FormLabel>
                    <FormControl>
                      <Input {...field} placeholder="e.g. Main Warehouse" />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="code"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Code</FormLabel>
                    <FormControl>
                      <Input {...field} placeholder="Auto-assigned (e.g. WH-001)" />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

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
          </FormSection>

          <FormSection title="Address">
            <AddressCascader
              value={address}
              onChange={handleAddressChange}
              required={{ province: false, district: false }}
              addressPlaceholder="Street, house number, landmark"
            />
          </FormSection>

          <FormSection title="Status">
            <div className="flex flex-col gap-3">
              <FormField
                control={form.control}
                name="is_default"
                render={({ field }) => (
                  <FormItem className="flex flex-row items-center gap-2 space-y-0">
                    <FormControl>
                      <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                    </FormControl>
                    <FormLabel className="font-normal">
                      Default warehouse — used as the default for new stock movements
                    </FormLabel>
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="is_active"
                render={({ field }) => (
                  <FormItem className="flex flex-row items-center gap-2 space-y-0">
                    <FormControl>
                      <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                    </FormControl>
                    <FormLabel className="font-normal">Active</FormLabel>
                  </FormItem>
                )}
              />
            </div>
          </FormSection>

          <div className="flex items-center justify-end gap-2">
            <Button type="button" variant="outline" onClick={() => navigate(LIST_PATH)} disabled={isPending}>
              Cancel
            </Button>
            <Button type="submit" disabled={isPending}>
              {isPending && <Loader2 className="size-4 animate-spin" />}
              {isEditing ? "Save changes" : "Create warehouse"}
            </Button>
          </div>
        </form>
      </Form>
    </div>
  )
}

export default WarehouseFormPage
