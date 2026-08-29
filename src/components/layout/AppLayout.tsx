import { useEffect, useState } from "react"
import { Outlet } from "react-router-dom"

import { AppNavbar } from "@/components/layout/AppNavbar"
import { AppSidebar } from "@/components/layout/AppSidebar"
import { cn } from "@/lib/utils"

/** Mirrors legacy-php-vue/resources/js/components/AppLayout.vue */
export function AppLayout() {
  const [mobileOpen, setMobileOpen] = useState(false)

  useEffect(() => {
    function onResize() {
      if (window.innerWidth >= 768) setMobileOpen(false)
    }
    window.addEventListener("resize", onResize)
    return () => window.removeEventListener("resize", onResize)
  }, [])

  return (
    <div className="flex h-screen overflow-hidden bg-slate-50">
      {mobileOpen && (
        <div
          className="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm md:hidden"
          onClick={() => setMobileOpen(false)}
          aria-hidden="true"
        />
      )}

      <div
        className={cn(
          "fixed inset-y-0 left-0 z-50 transition-transform duration-200 ease-out md:relative md:translate-x-0",
          mobileOpen ? "translate-x-0" : "-translate-x-full md:translate-x-0",
        )}
      >
        <AppSidebar />
      </div>

      <div className="flex w-full flex-1 flex-col overflow-hidden">
        <AppNavbar onToggleSidebar={() => setMobileOpen((v) => !v)} />
        <main id="main-content" className="flex-1 overflow-auto" tabIndex={-1}>
          <div className="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  )
}
