// Mirrors the Postgres `user_role` enum (see supabase/migrations/0001_extensions_and_enums.sql)
export const USER_ROLES = [
  "admin",
  "manager",
  "cashier",
  "warehouse",
  "purchasing",
  "delivery",
  "salesperson",
  "accountant",
] as const

export type UserRole = (typeof USER_ROLES)[number]

// Mirrors the `profiles` table (supabase/migrations/0002_rbac_profiles.sql)
export interface Profile {
  id: string
  name: string
  email: string
  role: UserRole
  employment_status: "active" | "inactive" | "on_leave" | "terminated"
  branch_id: number | null
  team_id: number | null
  is_active: boolean
  created_at: string
  updated_at: string
}
