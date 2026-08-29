import type { Session, User } from "@supabase/supabase-js"
import { create } from "zustand"

import { supabase } from "@/lib/supabase"
import type { Profile, UserRole } from "@/types/auth"

interface AuthState {
  session: Session | null
  user: User | null
  profile: Profile | null
  /** true until the initial session + profile fetch completes */
  isInitializing: boolean
  /** true while a login/register/logout request is in flight */
  isSubmitting: boolean
  error: string | null

  initialize: () => Promise<void>
  login: (email: string, password: string) => Promise<void>
  register: (name: string, email: string, password: string) => Promise<{ needsEmailConfirmation: boolean }>
  logout: () => Promise<void>
  refreshProfile: () => Promise<void>
  clearError: () => void

  isAuthenticated: () => boolean
  hasRole: (role: UserRole | UserRole[]) => boolean
  hasPermission: (permission: string | string[]) => boolean
}

let permissionsCache: string[] = []
let initPromise: Promise<void> | null = null

async function fetchProfile(userId: string): Promise<Profile | null> {
  const { data, error } = await supabase.from("profiles").select("*").eq("id", userId).maybeSingle()
  if (error) {
    console.error("Failed to load profile:", error.message)
    return null
  }
  return data as Profile | null
}

async function fetchPermissions(role: UserRole): Promise<string[]> {
  if (role === "admin") return ["*"] // admin is a wildcard, matches auth_has_permission()'s SQL shortcut
  const { data, error } = await supabase
    .from("role_permissions")
    .select("permissions(name)")
    .eq("role", role)
  if (error) {
    console.error("Failed to load permissions:", error.message)
    return []
  }
  return (data ?? [])
    .map((row) => (row as unknown as { permissions: { name: string } | null }).permissions?.name)
    .filter((name): name is string => Boolean(name))
}

export const useAuthStore = create<AuthState>((set, get) => ({
  session: null,
  user: null,
  profile: null,
  isInitializing: true,
  isSubmitting: false,
  error: null,

  initialize: async () => {
    // Guard against double-invocation (e.g. StrictMode double effect mount).
    if (initPromise) return initPromise
    initPromise = (async () => {
      const { data } = await supabase.auth.getSession()
      const session = data.session
      let profile: Profile | null = null
      if (session?.user) {
        profile = await fetchProfile(session.user.id)
        permissionsCache = profile ? await fetchPermissions(profile.role) : []
      }
      set({ session, user: session?.user ?? null, profile, isInitializing: false })

      supabase.auth.onAuthStateChange((_event, newSession) => {
        void (async () => {
          if (newSession?.user) {
            const p = await fetchProfile(newSession.user.id)
            permissionsCache = p ? await fetchPermissions(p.role) : []
            set({ session: newSession, user: newSession.user, profile: p })
          } else {
            permissionsCache = []
            set({ session: null, user: null, profile: null })
          }
        })()
      })
    })()
    return initPromise
  },

  login: async (email, password) => {
    set({ isSubmitting: true, error: null })
    try {
      const { data, error } = await supabase.auth.signInWithPassword({ email, password })
      if (error) throw error
      let profile: Profile | null = null
      if (data.user) {
        profile = await fetchProfile(data.user.id)
        permissionsCache = profile ? await fetchPermissions(profile.role) : []
      }
      set({ session: data.session, user: data.user, profile, isSubmitting: false })
    } catch (err) {
      set({ isSubmitting: false, error: err instanceof Error ? err.message : "Login failed." })
      throw err
    }
  },

  register: async (name, email, password) => {
    set({ isSubmitting: true, error: null })
    try {
      const { data, error } = await supabase.auth.signUp({
        email,
        password,
        options: { data: { name } },
      })
      if (error) throw error

      // If email confirmation is required, Supabase returns a user but no
      // session yet — the `profiles` row is still created by the
      // handle_new_user() trigger (0016_auth_provisioning.sql), just the
      // client isn't signed in until the user confirms their email.
      const needsEmailConfirmation = !data.session
      let profile: Profile | null = null
      if (data.session?.user) {
        profile = await fetchProfile(data.session.user.id)
        permissionsCache = profile ? await fetchPermissions(profile.role) : []
      }
      set({
        session: data.session,
        user: data.user,
        profile,
        isSubmitting: false,
      })
      return { needsEmailConfirmation }
    } catch (err) {
      set({ isSubmitting: false, error: err instanceof Error ? err.message : "Registration failed." })
      throw err
    }
  },

  logout: async () => {
    set({ isSubmitting: true })
    await supabase.auth.signOut()
    permissionsCache = []
    set({ session: null, user: null, profile: null, isSubmitting: false })
  },

  refreshProfile: async () => {
    const userId = get().user?.id
    if (!userId) return
    const profile = await fetchProfile(userId)
    permissionsCache = profile ? await fetchPermissions(profile.role) : []
    set({ profile })
  },

  clearError: () => set({ error: null }),

  isAuthenticated: () => Boolean(get().session),

  hasRole: (role) => {
    const currentRole = get().profile?.role
    if (!currentRole) return false
    return Array.isArray(role) ? role.includes(currentRole) : currentRole === role
  },

  hasPermission: (permission) => {
    const profile = get().profile
    if (!profile) return false
    if (profile.role === "admin" || permissionsCache.includes("*")) return true
    const list = Array.isArray(permission) ? permission : [permission]
    return list.some((p) => permissionsCache.includes(p))
  },
}))
