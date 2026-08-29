import { Navigate, Route, Routes } from "react-router-dom"

import { RequireAuth } from "@/components/auth/RequireAuth"
import { RequireGuest } from "@/components/auth/RequireGuest"
import { RequireRole } from "@/components/auth/RequireRole"
import { AppLayout } from "@/components/layout/AppLayout"
import { ComingSoonPage } from "@/components/layout/ComingSoonPage"
import { NotFoundPage } from "@/components/layout/NotFoundPage"
import { DashboardPage } from "@/features/dashboard/DashboardPage"
import { LoginPage } from "@/features/auth/LoginPage"
import { RegisterPage } from "@/features/auth/RegisterPage"

// Route table mirrors legacy-php-vue/resources/js/router/index.js.
// Modules not yet ported to React render ComingSoonPage at their real path
// so navigation/guards can be exercised end-to-end ahead of each feature
// build-out.
function App() {
  return (
    <Routes>
      <Route element={<RequireGuest />}>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
      </Route>

      <Route element={<RequireAuth />}>
        <Route element={<AppLayout />}>
          <Route index element={<Navigate to="/dashboard" replace />} />
          <Route path="dashboard" element={<DashboardPage />} />

          {/* Point of Sale + Sales history */}
          <Route element={<RequireRole roles={["admin", "manager", "cashier", "warehouse"]} />}>
            <Route path="pos" element={<ComingSoonPage titleKey="nav.pos" />} />
            <Route path="sales" element={<ComingSoonPage titleKey="nav.sales" />} />
            <Route path="sales/:id" element={<ComingSoonPage titleKey="nav.sales" />} />
          </Route>

          {/* Admin */}
          <Route element={<RequireRole roles={["admin"]} />}>
            <Route path="admin/users" element={<ComingSoonPage titleKey="nav.users" />} />
            <Route path="admin/users/new" element={<ComingSoonPage titleKey="nav.users" />} />
            <Route path="admin/users/:id/edit" element={<ComingSoonPage titleKey="nav.users" />} />
          </Route>

          {/* Master data */}
          <Route path="master/categories" element={<ComingSoonPage titleKey="nav.categories" />} />
          <Route path="master/brands" element={<ComingSoonPage titleKey="nav.brands" />} />
          <Route path="master/customers" element={<ComingSoonPage titleKey="nav.customers" />} />
          <Route element={<RequireRole roles={["admin", "manager", "purchasing"]} />}>
            <Route path="master/suppliers" element={<ComingSoonPage titleKey="nav.suppliers" />} />
          </Route>

          {/* Products */}
          <Route path="products" element={<ComingSoonPage titleKey="nav.products" />} />
          <Route path="products/:id" element={<ComingSoonPage titleKey="nav.products" />} />
          <Route path="batches" element={<ComingSoonPage titleKey="nav.batches" />} />
          <Route path="inventory/operations" element={<ComingSoonPage titleKey="nav.inventoryOperations" />} />

          <Route element={<RequireRole roles={["admin", "manager"]} />}>
            <Route path="units" element={<ComingSoonPage titleKey="nav.units" />} />
            <Route path="warehouses" element={<ComingSoonPage titleKey="nav.warehouses" />} />
            <Route path="settings/app" element={<ComingSoonPage titleKey="nav.appSettings" />} />
          </Route>

          {/* Settings */}
          <Route path="settings" element={<ComingSoonPage titleKey="nav.userSettings" />} />
        </Route>
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  )
}

export default App
