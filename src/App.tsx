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
import { CategoriesPage } from "@/features/categories/CategoriesPage"
import { CategoryFormPage } from "@/features/categories/CategoryFormPage"
import { BrandsPage } from "@/features/brands/BrandsPage"
import { BrandFormPage } from "@/features/brands/BrandFormPage"
import { UnitsPage } from "@/features/units/UnitsPage"
import { UnitFormPage } from "@/features/units/UnitFormPage"
import { WarehousesPage } from "@/features/warehouses/WarehousesPage"
import { WarehouseFormPage } from "@/features/warehouses/WarehouseFormPage"
import { SuppliersPage } from "@/features/suppliers/SuppliersPage"
import { SupplierFormPage } from "@/features/suppliers/SupplierFormPage"
import { CustomersPage } from "@/features/customers/CustomersPage"
import { CustomerFormPage } from "@/features/customers/CustomerFormPage"

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
          <Route path="master/categories" element={<CategoriesPage />} />
          <Route path="master/categories/new" element={<CategoryFormPage />} />
          <Route path="master/categories/:id/edit" element={<CategoryFormPage />} />
          <Route path="master/brands" element={<BrandsPage />} />
          <Route path="master/brands/new" element={<BrandFormPage />} />
          <Route path="master/brands/:id/edit" element={<BrandFormPage />} />
          <Route path="master/customers" element={<CustomersPage />} />
          <Route path="master/customers/new" element={<CustomerFormPage />} />
          <Route path="master/customers/:id/edit" element={<CustomerFormPage />} />
          <Route element={<RequireRole roles={["admin", "manager", "purchasing"]} />}>
            <Route path="master/suppliers" element={<SuppliersPage />} />
            <Route path="master/suppliers/new" element={<SupplierFormPage />} />
            <Route path="master/suppliers/:id/edit" element={<SupplierFormPage />} />
          </Route>

          {/* Products */}
          <Route path="products" element={<ComingSoonPage titleKey="nav.products" />} />
          <Route path="products/:id" element={<ComingSoonPage titleKey="nav.products" />} />
          <Route path="batches" element={<ComingSoonPage titleKey="nav.batches" />} />
          <Route path="inventory/operations" element={<ComingSoonPage titleKey="nav.inventoryOperations" />} />

          <Route element={<RequireRole roles={["admin", "manager"]} />}>
            <Route path="units" element={<UnitsPage />} />
            <Route path="units/new" element={<UnitFormPage />} />
            <Route path="units/:id/edit" element={<UnitFormPage />} />
            <Route path="warehouses" element={<WarehousesPage />} />
            <Route path="warehouses/new" element={<WarehouseFormPage />} />
            <Route path="warehouses/:id/edit" element={<WarehouseFormPage />} />
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
