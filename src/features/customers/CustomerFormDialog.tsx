import { zodResolver } from "@hookform/resolvers/zod"
import { Loader2 } from "lucide-react"
import { useEffect, useState } from "react"
import { useForm } from "react-hook-form"
import { toast } from "sonner"

import { AddressCascader, EMPTY_ADDRESS, type AddressValue } from "@/components/data/AddressCascader"
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Textarea } from "@/components/ui/textarea"
import { useCreateCustomer, useUpdateCustomer } from "@/features/customers/api"
import { CUSTOMER_TYPES, customerSchema, type CustomerFormValues } from "@/features/customers/schemas"
import type { Customer } from "@/features/customers/types"
import { parseSupabaseError } from "@/lib/supabase-errors"

const TYPE_LABELS: Record<(typeof CUSTOMER_TYPES)[number], string> = {
  retail: "Retail",
  wholesale: "Wholesale",
  distributor: "Distributor",
}

const DEFAULT_VALUES: CustomerFormValues = {
  name: "",
  contact_person: "",
  email: "",
  phone: "",
  province_id: null,
  district_id: null,
  commune_id: null,
  village_id: null,
  address: "",
  type: "retail",
  credit_limit: 0,
  payment_terms: "",
  notes: "",
  is_active: true,
}

interface CustomerFormDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  customer: Customer | null
}

/**
 * Create/edit dialog for customers. Mirrors
 * legacy-php-vue/resources/js/pages/master/CustomerForm.vue: Identity,
 * Contact (address cascader — province/district/commune required), and
 * Credit & terms sections. Code is auto-assigned server-side (see
 * `nextCustomerCode()` in api.ts) and never shown/edited here on create;
 * shown read-only when editing. Update uses optimistic locking via the
 * customer's `version` — see `useUpdateCustomer()`.
 */
export function CustomerFormDialog({ open, onOpenChange, customer }: CustomerFormDialogProps) {
  const isEditing = customer != null
  const createCustomer = useCreateCustomer()
  const updateCustomer = useUpdateCustomer()
  const isPending = createCustomer.isPending || updateCustomer.isPending

  const [address, setAddress] = useState<AddressValue>(EMPTY_ADDRESS)

  const form = useForm<CustomerFormValues>({
    resolver: zodResolver(customerSchema),
    defaultValues: DEFAULT_VALUES,
  })

  useEffect(() => {
    if (open) {
      if (customer) {
        form.reset({
          name: customer.name,
          contact_person: customer.contact_person ?? "",
          email: customer.email ?? "",
          phone: customer.phone ?? "",
          province_id: customer.province_id,
          district_id: customer.district_id,
          commune_id: customer.commune_id,
          village_id: customer.village_id,
          address: customer.address ?? "",
          type: customer.type,
          credit_limit: customer.credit_limit,
          payment_terms: customer.payment_terms ?? "",
          notes: customer.notes ?? "",
          is_active: customer.is_active,
        })
        setAddress({
          province_id: customer.province_id,
          district_id: customer.district_id,
          commune_id: customer.commune_id,
          village_id: customer.village_id,
          address: customer.address ?? "",
        })
      } else {
        form.reset(DEFAULT_VALUES)
        setAddress(EMPTY_ADDRESS)
      }
    }
  }, [open, customer, form])

  function handleAddressChange(next: AddressValue) {
    setAddress(next)
    form.setValue("province_id", next.province_id)
    form.setValue("district_id", next.district_id)
    form.setValue("commune_id", next.commune_id)
    form.setValue("village_id", next.village_id)
    form.setValue("address", next.address)
  }

  async function onSubmit(values: CustomerFormValues) {
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
      type: values.type,
      credit_limit: Number(values.credit_limit),
      payment_terms: values.payment_terms?.trim() || null,
      notes: values.notes?.trim() || null,
      is_active: values.is_active,
    }
    try {
      if (isEditing) {
        await updateCustomer.mutateAsync({ id: customer.id, version: customer.version, input })
        toast.success("Customer updated.")
      } else {
        await createCustomer.mutateAsync(input)
        toast.success("Customer created.")
      }
      onOpenChange(false)
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

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{isEditing ? "Edit customer" : "Create customer"}</DialogTitle>
          <DialogDescription>
            {isEditing
              ? "Update contact info, type, or credit terms for this customer."
              : "Add a new customer. A unique code will be assigned automatically."}
          </DialogDescription>
        </DialogHeader>

        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} noValidate className="space-y-4">
            {isEditing && (
              <div>
                <FormLabel className="mb-1 block">Code</FormLabel>
                <Input value={customer.code} disabled />
                <p className="mt-1 text-xs text-slate-400">Auto-assigned on creation.</p>
              </div>
            )}

            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem className={isEditing ? "" : "col-span-2"}>
                    <FormLabel>Name</FormLabel>
                    <FormControl>
                      <Input {...field} placeholder="e.g. Sok Dara" />
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
                      <Input {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="type"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Customer type</FormLabel>
                    <FormControl>
                      <Select value={field.value} onValueChange={field.onChange}>
                        <SelectTrigger className="w-full">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          {CUSTOMER_TYPES.map((t) => (
                            <SelectItem key={t} value={t}>
                              {TYPE_LABELS[t]}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="email"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Email</FormLabel>
                    <FormControl>
                      <Input {...field} type="email" placeholder="customer@example.com" />
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
                required={{ province: true, district: true, commune: true }}
                addressPlaceholder="Street, house number, landmark"
              />
            </div>

            <div className="border-t border-slate-100 pt-4">
              <div className="grid grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="credit_limit"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Credit limit</FormLabel>
                      <FormControl>
                        <Input
                          type="number"
                          step="0.01"
                          {...field}
                          value={field.value as string | number}
                          placeholder="0.00"
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                {isEditing && (
                  <div>
                    <FormLabel className="mb-1 block">Current balance</FormLabel>
                    <Input value={customer.current_balance.toFixed(2)} disabled />
                  </div>
                )}
                <FormField
                  control={form.control}
                  name="payment_terms"
                  render={({ field }) => (
                    <FormItem className={isEditing ? "col-span-2" : ""}>
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
                  <FormItem className="mt-4">
                    <FormLabel>Notes</FormLabel>
                    <FormControl>
                      <Textarea {...field} rows={2} placeholder="Internal notes" />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <FormField
              control={form.control}
              name="is_active"
              render={({ field }) => (
                <FormItem className="flex flex-row items-center gap-2 space-y-0 border-t border-slate-100 pt-4">
                  <FormControl>
                    <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                  </FormControl>
                  <FormLabel className="font-normal">Active — available for sales</FormLabel>
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
                {isEditing ? "Save changes" : "Create customer"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  )
}
