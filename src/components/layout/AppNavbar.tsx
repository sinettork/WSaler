import { Bell, ChevronDown, Globe, LogOut, Menu, Search } from "lucide-react"
import { useEffect, useRef, useState } from "react"
import { useTranslation } from "react-i18next"
import { useNavigate } from "react-router-dom"

import { setLocale, type SupportedLocale } from "@/i18n"
import { useAuth } from "@/hooks/useAuth"

/** Mirrors legacy-php-vue/resources/js/components/AppNavbar.vue */
export function AppNavbar({ onToggleSidebar }: { onToggleSidebar: () => void }) {
  const { t, i18n } = useTranslation()
  const { profile, logout } = useAuth()
  const navigate = useNavigate()

  const [searchQuery, setSearchQuery] = useState("")
  const [menuOpen, setMenuOpen] = useState(false)
  const [langMenuOpen, setLangMenuOpen] = useState(false)
  const menuRef = useRef<HTMLDivElement>(null)
  const langMenuRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setMenuOpen(false)
      }
      if (langMenuRef.current && !langMenuRef.current.contains(event.target as Node)) {
        setLangMenuOpen(false)
      }
    }
    document.addEventListener("click", handleClickOutside)
    return () => document.removeEventListener("click", handleClickOutside)
  }, [])

  async function handleLogout() {
    setMenuOpen(false)
    await logout()
    navigate("/login", { replace: true })
  }

  function onSearchSubmit(e: React.FormEvent) {
    e.preventDefault()
    const q = searchQuery.trim()
    if (!q) return
    navigate(`/products?search=${encodeURIComponent(q)}`)
  }

  function selectLocale(locale: SupportedLocale) {
    setLocale(locale)
    setLangMenuOpen(false)
  }

  const currentLocale = i18n.language?.startsWith("km") ? "km" : "en"

  return (
    <header className="flex items-center gap-4 border-b border-slate-200 bg-white px-4 py-3 sm:px-6">
      <button
        type="button"
        className="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 md:hidden"
        aria-label="Toggle menu"
        onClick={onToggleSidebar}
      >
        <Menu className="size-5" aria-hidden="true" />
      </button>

      <div className="max-w-md flex-1">
        <form onSubmit={onSearchSubmit} className="relative">
          <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
          <input
            type="search"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder={t("common.search_products")}
            className="w-full rounded-md border border-slate-300 bg-white py-1.5 pl-9 pr-4 font-mono text-[13px] text-slate-900 placeholder-slate-400 transition-colors focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500/30"
          />
        </form>
      </div>

      <div className="flex-1" />

      <button
        type="button"
        className="relative rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"
        aria-label={t("common.notifications")}
      >
        <Bell className="size-5" aria-hidden="true" />
        <span
          className="absolute right-1.5 top-1.5 size-2 rounded-full bg-status-critical ring-2 ring-white"
          aria-hidden="true"
        />
      </button>

      <div className="relative" ref={langMenuRef}>
        <button
          type="button"
          className="flex items-center gap-1 rounded-md px-2 py-1 text-[13px] text-slate-700 transition-colors hover:bg-slate-100"
          aria-haspopup="true"
          aria-expanded={langMenuOpen}
          aria-label={t("common.language")}
          onClick={() => setLangMenuOpen((v) => !v)}
        >
          <Globe className="size-4" aria-hidden="true" />
          <span className="hidden font-medium sm:inline">
            {currentLocale === "km" ? "ខ្មែរ" : "EN"}
          </span>
          <ChevronDown className="size-3 text-slate-400" aria-hidden="true" />
        </button>
        {langMenuOpen && (
          <div className="absolute right-0 z-50 mt-2 w-36 origin-top-right rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/5">
            <button
              type="button"
              className="flex w-full items-center justify-between px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
              onClick={() => selectLocale("en")}
            >
              <span>English</span>
              {currentLocale === "en" && <span className="text-brand-600">✓</span>}
            </button>
            <button
              type="button"
              className="flex w-full items-center justify-between px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
              onClick={() => selectLocale("km")}
            >
              <span>ភាសាខ្មែរ</span>
              {currentLocale === "km" && <span className="text-brand-600">✓</span>}
            </button>
          </div>
        )}
      </div>

      <div className="relative" ref={menuRef}>
        <button
          type="button"
          className="flex items-center gap-2 rounded-md px-2 py-1 transition-colors hover:bg-slate-100"
          aria-haspopup="true"
          aria-expanded={menuOpen}
          onClick={() => setMenuOpen((v) => !v)}
        >
          <div className="relative flex size-8 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white">
            {profile?.name?.[0]?.toUpperCase() ?? "?"}
            <span
              className="absolute -bottom-0.5 -right-0.5 size-2.5 rounded-full border-2 border-white bg-status-fresh"
              aria-label="Online"
            />
          </div>
          <div className="hidden text-left sm:block">
            <div className="text-sm font-medium leading-tight text-slate-900">
              {profile?.name ?? "User"}
            </div>
            <div className="text-xs capitalize leading-tight text-slate-500">
              {profile?.role ?? ""}
            </div>
          </div>
          <ChevronDown className="size-4 text-slate-400" aria-hidden="true" />
        </button>

        {menuOpen && (
          <div className="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/5">
            <div className="border-b border-slate-100 px-3 py-2">
              <div className="truncate text-sm font-medium text-slate-900">{profile?.name}</div>
              <div className="truncate text-xs text-slate-500">{profile?.email}</div>
            </div>
            <button
              type="button"
              className="flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 transition-colors hover:bg-slate-50"
              onClick={handleLogout}
            >
              <LogOut className="size-4" aria-hidden="true" />
              {t("common.logout")}
            </button>
          </div>
        )}
      </div>
    </header>
  )
}
