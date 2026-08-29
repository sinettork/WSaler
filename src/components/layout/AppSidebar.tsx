import { ChevronRight, ShoppingCart } from "lucide-react"
import { useEffect, useState } from "react"
import { useTranslation } from "react-i18next"
import { NavLink, useLocation } from "react-router-dom"

import { cn } from "@/lib/utils"
import { NAV_SECTIONS } from "@/components/layout/nav-config"
import { useAuth } from "@/hooks/useAuth"

const STORAGE_KEY = "wsaler_sidebar_expanded_sections"

function loadExpanded(): Record<string, boolean> {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return {}
    const parsed = JSON.parse(raw)
    return parsed && typeof parsed === "object" ? parsed : {}
  } catch {
    return {}
  }
}

function saveExpanded(state: Record<string, boolean>) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state))
  } catch {
    // storage unavailable, ignore
  }
}

/** Mirrors legacy-php-vue/resources/js/components/AppSidebar.vue */
export function AppSidebar() {
  const { t } = useTranslation()
  const { profile, hasRole } = useAuth()
  const location = useLocation()
  const [expanded, setExpanded] = useState<Record<string, boolean>>(() => loadExpanded())

  function isSectionActive(sectionId: string) {
    const section = NAV_SECTIONS.find((s) => s.id === sectionId)
    if (!section) return false
    return section.items.some(
      (item) => location.pathname === item.path || location.pathname.startsWith(`${item.path}/`),
    )
  }

  // Auto-expand sections containing the active route whenever it changes.
  useEffect(() => {
    setExpanded((prev) => {
      let changed = false
      const next = { ...prev }
      for (const section of NAV_SECTIONS) {
        if (isSectionActive(section.id) && !next[section.id]) {
          next[section.id] = true
          changed = true
        }
      }
      if (changed) saveExpanded(next)
      return changed ? next : prev
    })
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [location.pathname])

  function toggleSection(id: string) {
    setExpanded((prev) => {
      const next = { ...prev, [id]: !prev[id] }
      saveExpanded(next)
      return next
    })
  }

  function visibleItems(section: (typeof NAV_SECTIONS)[number]) {
    return section.items.filter((item) => !item.roles || hasRole(item.roles))
  }

  const showPos = hasRole(["admin", "manager", "cashier", "warehouse"])

  return (
    <aside className="flex h-full w-60 shrink-0 flex-col border-r border-sidebar-border bg-sidebar-bg text-sidebar-fg">
      <div className="flex items-center gap-3 border-b border-sidebar-border px-5 py-5">
        <div className="flex size-9 shrink-0 items-center justify-center rounded-md bg-brand-600 text-base font-bold text-white shadow-sm shadow-brand-600/40">
          W
        </div>
        <div className="min-w-0">
          <div className="truncate text-sm font-semibold leading-tight text-white">
            {t("app.name")}
          </div>
          <div className="truncate text-xs leading-tight text-sidebar-muted">
            {t("app.tagline")}
          </div>
        </div>
      </div>

      <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Primary">
        {showPos && (
          <NavLink
            to="/pos"
            className={({ isActive }) =>
              cn(
                "flex items-center gap-3 rounded-md px-3 py-2 text-[13px] font-semibold transition-colors",
                isActive
                  ? "bg-brand-600 text-white hover:bg-brand-700"
                  : "bg-brand-600/10 text-brand-400 hover:bg-brand-600 hover:text-white",
              )
            }
          >
            <ShoppingCart className="size-4 shrink-0" aria-hidden="true" />
            <span>{t("nav.pos")}</span>
          </NavLink>
        )}

        <NavLink
          to="/dashboard"
          className={({ isActive }) =>
            cn(
              "flex items-center gap-3 rounded-md border-l-2 py-2 pl-3 pr-3 text-[13px] transition-colors",
              isActive
                ? "border-sidebar-accent bg-sidebar-active font-medium text-white"
                : "border-transparent text-sidebar-fg hover:bg-sidebar-active hover:text-white",
            )
          }
        >
          <ChevronRight className="size-4 shrink-0" aria-hidden="true" />
          <span>{t("nav.dashboard")}</span>
        </NavLink>

        {NAV_SECTIONS.map((section) => {
          const items = visibleItems(section)
          if (items.length === 0) return null
          const isExpanded = Boolean(expanded[section.id])

          return (
            <div key={section.id} className="pt-3">
              <button
                type="button"
                className="flex w-full items-center justify-between px-3 py-1.5 text-sm font-medium text-sidebar-muted transition-colors hover:text-white"
                aria-expanded={isExpanded}
                aria-controls={`sidebar-section-${section.id}`}
                onClick={() => toggleSection(section.id)}
              >
                <span>{t(section.labelKey)}</span>
                <ChevronRight
                  className={cn(
                    "size-3 shrink-0 transition-transform",
                    isExpanded && "rotate-90",
                  )}
                  aria-hidden="true"
                />
              </button>

              <div
                id={`sidebar-section-${section.id}`}
                className="overflow-hidden transition-all duration-200 ease-out"
                style={{ maxHeight: isExpanded ? "500px" : "0px", opacity: isExpanded ? 1 : 0 }}
              >
                <div className="mt-1 space-y-0.5">
                  {items.map((item) => (
                    <NavLink
                      key={item.path}
                      to={item.path}
                      className={({ isActive }) =>
                        cn(
                          "flex items-center gap-3 rounded-md border-l-2 py-1.5 pl-9 pr-3 text-[13px] transition-colors",
                          isActive
                            ? "border-sidebar-accent bg-sidebar-active font-medium text-white"
                            : "border-transparent text-sidebar-fg hover:bg-sidebar-active hover:text-white",
                        )
                      }
                    >
                      <item.icon className="size-3.5 shrink-0 opacity-80" aria-hidden="true" />
                      <span className="truncate">{t(item.labelKey)}</span>
                    </NavLink>
                  ))}
                </div>
              </div>
            </div>
          )
        })}
      </nav>

      <div className="border-t border-sidebar-border px-3 py-3">
        <div className="flex items-center gap-3 px-3 py-2">
          <div className="relative flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white">
            {profile?.name?.[0]?.toUpperCase() ?? "?"}
            <span
              className="absolute -bottom-0.5 -right-0.5 size-2.5 rounded-full border-2 border-sidebar-bg bg-status-fresh"
              aria-label="Online"
            />
          </div>
          <div className="min-w-0 flex-1">
            <div className="truncate text-[13px] font-medium text-white">
              {profile?.name ?? "User"}
            </div>
            <div className="truncate text-xs capitalize text-sidebar-muted">
              {profile?.role ?? ""}
            </div>
          </div>
        </div>
      </div>
    </aside>
  )
}
