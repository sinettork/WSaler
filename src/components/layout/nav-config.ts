import type { LucideIcon } from "lucide-react"
import {
  Boxes,
  Building2,
  Package,
  Receipt,
  Scale,
  Settings,
  Tag,
  Truck,
  User,
  Users,
} from "lucide-react"

import type { UserRole } from "@/types/auth"

export interface NavItem {
  path: string
  labelKey: string
  icon: LucideIcon
  /** null = visible to every authenticated role */
  roles: UserRole[] | null
}

export interface NavSection {
  id: string
  labelKey: string
  items: NavItem[]
}

// Mirrors legacy-php-vue/resources/js/components/AppSidebar.vue `sections`
export const NAV_SECTIONS: NavSection[] = [
  {
    id: "sales",
    labelKey: "nav.sales",
    items: [
      {
        path: "/sales",
        labelKey: "nav.sales",
        icon: Receipt,
        roles: ["admin", "manager", "cashier", "warehouse"],
      },
    ],
  },
  {
    id: "admin",
    labelKey: "nav.admin",
    items: [{ path: "/admin/users", labelKey: "nav.users", icon: Users, roles: ["admin"] }],
  },
  {
    id: "master",
    labelKey: "nav.master",
    items: [
      { path: "/master/categories", labelKey: "nav.categories", icon: Tag, roles: null },
      { path: "/master/brands", labelKey: "nav.brands", icon: Boxes, roles: null },
      {
        path: "/master/suppliers",
        labelKey: "nav.suppliers",
        icon: Truck,
        roles: ["admin", "manager", "purchasing"],
      },
      { path: "/master/customers", labelKey: "nav.customers", icon: Users, roles: null },
    ],
  },
  {
    id: "inventory",
    labelKey: "nav.products",
    items: [
      { path: "/products", labelKey: "nav.products", icon: Package, roles: null },
      { path: "/batches", labelKey: "nav.batches", icon: Boxes, roles: null },
      {
        path: "/inventory/operations",
        labelKey: "nav.inventoryOperations",
        icon: Receipt,
        roles: null,
      },
      { path: "/units", labelKey: "nav.units", icon: Scale, roles: ["admin", "manager"] },
      {
        path: "/warehouses",
        labelKey: "nav.warehouses",
        icon: Building2,
        roles: ["admin", "manager"],
      },
    ],
  },
  {
    id: "settings",
    labelKey: "nav.settings",
    items: [
      { path: "/settings", labelKey: "nav.userSettings", icon: User, roles: null },
      {
        path: "/settings/app",
        labelKey: "nav.appSettings",
        icon: Settings,
        roles: ["admin", "manager"],
      },
    ],
  },
]
