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
import { useCreateUnit, useUpdateUnit } from "@/features/units/api"
import { unitSchema, type UnitFormValues } from "@/features/units/schemas"
import type { Unit } from "@/features/units/types"
import { parseSupabaseError } from "@/lib/supabase-errors"

const DEFAULT_VALUES: UnitFormValues = {
  name: "",
  short_code: "",
  base: false,
  conversion_factor_to_base: 1,
}

interface UnitFormDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  unit: Unit | null
}

export function UnitFormDialog({ open, onOpenChange, unit }: UnitFormDialogProps) {
  const isEditing = unit != null
  const createUnit = useCreateUnit()
  const updateUnit = useUpdateUnit()
  const isPending = createUnit.isPending || updateUnit.isPending

  const form = useForm<UnitFormValues>({
    resolver: zodResolver(unitSchema),
    defaultValues: DEFAULT_VALUES,
  })

  const isBase = form.watch("base")

  useEffect(() => {
    if (open) {
      form.reset(
        unit
          ? {
              name: unit.name,
              short_code: unit.short_code,
              base: unit.base,
              conversion_factor_to_base: unit.conversion_factor_to_base,
            }
          : DEFAULT_VALUES,
      )
    }
  }, [open, unit, form])

  async function onSubmit(values: UnitFormValues) {
    const input = {
      name: values.name,
      short_code: values.short_code,
      base: values.base,
      conversion_factor_to_base: values.base ? 1 : Number(values.conversion_factor_to_base),
    }
    try {
      if (isEditing) {
        await updateUnit.mutateAsync({ id: unit.id, input })
        toast.success("Unit updated.")
      } else {
        await createUnit.mutateAsync(input)
        toast.success("Unit created.")
      }
      onOpenChange(false)
    } catch (err) {
      const { message, fieldErrors } = parseSupabaseError(err as Error)
      for (const [field, msg] of Object.entries(fieldErrors)) {
        if (field === "name" || field === "short_code") {
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
          <DialogTitle>{isEditing ? "Edit unit" : "Create unit of measure"}</DialogTitle>
          <DialogDescription>
            {isEditing
              ? "Change the name, short code, or conversion factor for this unit."
              : "Add a new unit. Mark it as base if it is the reference unit for stock calculations."}
          </DialogDescription>
        </DialogHeader>

        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} noValidate className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Name</FormLabel>
                    <FormControl>
                      <Input {...field} placeholder="e.g. Kilogram" />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="short_code"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Short code</FormLabel>
                    <FormControl>
                      <Input {...field} placeholder="e.g. kg" />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <FormField
              control={form.control}
              name="base"
              render={({ field }) => (
                <FormItem className="flex flex-row items-center gap-2 space-y-0 border-t border-slate-100 pt-4">
                  <FormControl>
                    <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                  </FormControl>
                  <FormLabel className="font-normal">
                    Base unit — the reference unit for stock calculations
                  </FormLabel>
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="conversion_factor_to_base"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Conversion factor to base</FormLabel>
                  <FormControl>
                    <Input
                      type="number"
                      step="0.0001"
                      {...field}
                      value={field.value as string | number}
                      disabled={isBase}
                    />
                  </FormControl>
                  <FormMessage />
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
                {isEditing ? "Save changes" : "Create unit"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  )
}
