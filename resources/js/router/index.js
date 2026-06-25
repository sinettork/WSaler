import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/components/AppLayout.vue';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/Login.vue'),
        meta: { public: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/pages/Register.vue'),
        meta: { public: true },
    },
    {
        path: '/',
        component: AppLayout,
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: '/dashboard' },
            { path: 'dashboard', name: 'dashboard', component: () => import('@/pages/Dashboard.vue') },

            // Point of Sale + Sales history
            { path: 'pos', name: 'sales.pos', component: () => import('@/pages/sales/Pos.vue'), meta: { role: ['admin', 'manager', 'cashier', 'warehouse'] } },
            { path: 'sales', name: 'sales.index', component: () => import('@/pages/sales/SalesList.vue'), meta: { role: ['admin', 'manager', 'cashier', 'warehouse'] } },
            { path: 'sales/:id', name: 'sales.show', component: () => import('@/pages/sales/SaleDetail.vue'), meta: { role: ['admin', 'manager', 'cashier', 'warehouse'] } },

            // Admin
            { path: 'admin/users', name: 'admin.users', component: () => import('@/pages/admin/Users.vue'), meta: { role: 'admin' } },
            { path: 'admin/users/new', name: 'admin.users.create', component: () => import('@/pages/admin/UserForm.vue'), meta: { role: 'admin' } },
            { path: 'admin/users/:id/edit', name: 'admin.users.edit', component: () => import('@/pages/admin/UserForm.vue'), meta: { role: 'admin' } },

            // Master data
            { path: 'master/categories', name: 'master.categories', component: () => import('@/pages/master/Categories.vue') },
            { path: 'master/categories/new', name: 'master.categories.create', component: () => import('@/pages/master/CategoryForm.vue'), meta: { role: ['admin', 'manager'] } },
            { path: 'master/categories/:id/edit', name: 'master.categories.edit', component: () => import('@/pages/master/CategoryForm.vue'), meta: { role: ['admin', 'manager'] } },

            { path: 'master/brands', name: 'master.brands', component: () => import('@/pages/master/Brands.vue') },
            { path: 'master/brands/new', name: 'master.brands.create', component: () => import('@/pages/master/BrandForm.vue'), meta: { role: ['admin', 'manager'] } },
            { path: 'master/brands/:id/edit', name: 'master.brands.edit', component: () => import('@/pages/master/BrandForm.vue'), meta: { role: ['admin', 'manager'] } },

            { path: 'master/suppliers', name: 'master.suppliers', component: () => import('@/pages/master/Suppliers.vue') },
            { path: 'master/suppliers/new', name: 'master.suppliers.create', component: () => import('@/pages/master/SupplierForm.vue'), meta: { role: ['admin', 'manager'] } },
            { path: 'master/suppliers/:id/edit', name: 'master.suppliers.edit', component: () => import('@/pages/master/SupplierForm.vue'), meta: { role: ['admin', 'manager'] } },

            { path: 'master/customers', name: 'master.customers', component: () => import('@/pages/master/Customers.vue') },
            { path: 'master/customers/new', name: 'master.customers.create', component: () => import('@/pages/master/CustomerForm.vue'), meta: { role: ['admin', 'manager'] } },
            { path: 'master/customers/:id/edit', name: 'master.customers.edit', component: () => import('@/pages/master/CustomerForm.vue'), meta: { role: ['admin', 'manager'] } },

            // Products
            { path: 'products', name: 'products.index', component: () => import('@/pages/products/ProductList.vue') },
            { path: 'products/new', name: 'products.create', component: () => import('@/pages/products/ProductForm.vue'), meta: { role: ['admin', 'manager'] } },
            { path: 'products/:id', name: 'products.show', component: () => import('@/pages/products/ProductDetail.vue') },
            { path: 'products/:id/edit', name: 'products.edit', component: () => import('@/pages/products/ProductForm.vue'), meta: { role: ['admin', 'manager'] } },

            { path: 'batches', name: 'batches.index', component: () => import('@/pages/products/BatchList.vue') },
            { path: 'batches/new', name: 'batches.create', component: () => import('@/pages/products/BatchForm.vue'), meta: { role: ['admin', 'manager', 'warehouse', 'purchasing'] } },
            { path: 'batches/:id/edit', name: 'batches.edit', component: () => import('@/pages/products/BatchForm.vue'), meta: { role: ['admin', 'manager', 'warehouse'] } },

            { path: 'units', name: 'units.index', component: () => import('@/pages/products/UnitList.vue'), meta: { role: ['admin', 'manager'] } },
            { path: 'units/new', name: 'units.create', component: () => import('@/pages/products/UnitForm.vue'), meta: { role: ['admin', 'manager'] } },
            { path: 'units/:id/edit', name: 'units.edit', component: () => import('@/pages/products/UnitForm.vue'), meta: { role: ['admin', 'manager'] } },

            { path: 'warehouses', name: 'warehouses.index', component: () => import('@/pages/products/WarehouseList.vue'), meta: { role: ['admin', 'manager'] } },
            { path: 'warehouses/new', name: 'warehouses.create', component: () => import('@/pages/products/WarehouseForm.vue'), meta: { role: ['admin', 'manager'] } },
            { path: 'warehouses/:id/edit', name: 'warehouses.edit', component: () => import('@/pages/products/WarehouseForm.vue'), meta: { role: ['admin', 'manager'] } },
        ],
    },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/pages/NotFound.vue') },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition;
        if (to.name === from.name && to.path === from.path) return false;
        return { el: '#main-content', top: 80, behavior: 'smooth' };
    },
});

router.beforeEach((to, from, next) => {
    const auth = useAuthStore();
    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return next({ name: 'login' });
    }
    if (to.meta.role && !auth.hasRole(to.meta.role)) {
        return next({ name: 'dashboard' });
    }
    if (to.meta.public && auth.isAuthenticated) {
        return next({ name: 'dashboard' });
    }
    next();
});

export default router;
