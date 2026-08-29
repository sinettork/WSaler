import type { ReactNode } from "react"

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"

interface FormSectionProps {
  title: string
  children: ReactNode
}

/** A titled card grouping related fields within a full-page form, mirroring legacy BaseCard sections. */
export function FormSection({ title, children }: FormSectionProps) {
  return (
    <Card>
      <CardHeader className="border-b border-slate-100 pb-4">
        <CardTitle className="text-sm font-semibold text-slate-900">{title}</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">{children}</CardContent>
    </Card>
  )
}
