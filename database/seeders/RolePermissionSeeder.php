<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createPermissions();
        $this->createRoles();
    }

    private function createPermissions(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'view dashboard', 'description' => 'View dashboard'],
            ['name' => 'view analytics', 'description' => 'View analytics'],
            ['name' => 'view financial summary', 'description' => 'View financial summary'],

            // Product Management
            ['name' => 'view products', 'description' => 'View products'],
            ['name' => 'create products', 'description' => 'Create products'],
            ['name' => 'edit products', 'description' => 'Edit products'],
            ['name' => 'delete products', 'description' => 'Delete products'],
            ['name' => 'import products', 'description' => 'Import products'],
            ['name' => 'export products', 'description' => 'Export products'],

            // Inventory Management
            ['name' => 'view inventory', 'description' => 'View inventory'],
            ['name' => 'stock adjustment', 'description' => 'Stock adjustment'],
            ['name' => 'stock transfer', 'description' => 'Stock transfer'],
            ['name' => 'approve adjustments', 'description' => 'Approve adjustments'],
            ['name' => 'view inventory valuation', 'description' => 'View inventory valuation'],

            // Batch & Expiry Management
            ['name' => 'view batches', 'description' => 'View batches'],
            ['name' => 'create batches', 'description' => 'Create batches'],
            ['name' => 'edit batches', 'description' => 'Edit batch information'],
            ['name' => 'dispose expired stock', 'description' => 'Dispose expired stock'],

            // Purchasing
            ['name' => 'create purchase orders', 'description' => 'Create purchase orders'],
            ['name' => 'approve purchase orders', 'description' => 'Approved purchase orders'],
            ['name' => 'receive goods', 'description' => 'Receive goods'],
            ['name' => 'process purchase returns', 'description' => 'Process purchase returns'],

            // Sales
            ['name' => 'create quotations', 'description' => 'Create quotations'],
            ['name' => 'create sales orders', 'description' => 'Create sales orders'],
            ['name' => 'create invoices', 'description' => 'Create invoices'],
            ['name' => 'process sales returns', 'description' => 'Process sales returns'],
            ['name' => 'cancel sales', 'description' => 'Cancel sales'],

            // POS
            ['name' => 'access pos', 'description' => 'Access POS'],
            ['name' => 'apply discounts', 'description' => 'Apply discounts'],
            ['name' => 'void transactions', 'description' => 'Void transactions'],
            ['name' => 'reprint receipts', 'description' => 'Reprint receipts'],
            ['name' => 'open cash drawer', 'description' => 'Open cash drawer'],

            // Customer Management
            ['name' => 'view customers', 'description' => 'View customers'],
            ['name' => 'create customers', 'description' => 'Create customers'],
            ['name' => 'edit customers', 'description' => 'Edit customers'],
            ['name' => 'manage credit limits', 'description' => 'Manage credit limits'],

            // Supplier Management
            ['name' => 'view suppliers', 'description' => 'View suppliers'],
            ['name' => 'create suppliers', 'description' => 'Create suppliers'],
            ['name' => 'edit suppliers', 'description' => 'Edit suppliers'],
            ['name' => 'delete suppliers', 'description' => 'Delete suppliers'],

            // Warehouse Management
            ['name' => 'view warehouses', 'description' => 'View warehouses'],
            ['name' => 'create warehouses', 'description' => 'Create warehouses'],
            ['name' => 'manage transfers', 'description' => 'Manage transfers'],

            // Accounting
            ['name' => 'view financial reports', 'description' => 'View financial reports'],
            ['name' => 'manage expenses', 'description' => 'Manage expenses'],
            ['name' => 'manage payments', 'description' => 'Manage payments'],
            ['name' => 'view profit & loss', 'description' => 'View profit & loss'],

            // Reports
            ['name' => 'view reports', 'description' => 'View reports'],
            ['name' => 'export reports', 'description' => 'Export reports'],
            ['name' => 'print reports', 'description' => 'Print reports'],

            // User Management
            ['name' => 'view users', 'description' => 'View users'],
            ['name' => 'create users', 'description' => 'Create users'],
            ['name' => 'edit users', 'description' => 'Edit users'],
            ['name' => 'delete users', 'description' => 'Delete users'],
            ['name' => 'assign roles', 'description' => 'Assign roles'],
            ['name' => 'manage permissions', 'description' => 'Manage permissions'],

            // Sales Performance
            ['name' => 'salespeople.view', 'description' => 'View salespeople'],
            ['name' => 'salespeople.manage', 'description' => 'Manage salespeople'],
            ['name' => 'teams.view', 'description' => 'View teams'],
            ['name' => 'teams.manage', 'description' => 'Manage teams'],
            ['name' => 'territories.view', 'description' => 'View territories'],
            ['name' => 'territories.manage', 'description' => 'Manage territories'],
            ['name' => 'assignments.view', 'description' => 'View customer assignments'],
            ['name' => 'assignments.manage', 'description' => 'Manage customer assignments'],
            ['name' => 'targets.view', 'description' => 'View sales targets'],
            ['name' => 'targets.manage', 'description' => 'Manage sales targets'],
            ['name' => 'target_templates.view', 'description' => 'View target templates'],
            ['name' => 'target_templates.manage', 'description' => 'Manage target templates'],
            ['name' => 'approvals.review', 'description' => 'Review approvals'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['guard_name' => 'api', 'description' => $perm['description']]
            );
        }
    }

    private function createRoles(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'description' => 'Full system access with all permissions',
                'permissions' => Permission::all()->pluck('name')->toArray(),
            ],
            [
                'name' => 'administrator',
                'description' => 'Administrator with most permissions except user/role management',
                'permissions' => [
                    'view dashboard', 'view analytics', 'view financial summary',
                    'view products', 'create products', 'edit products', 'delete products', 'import products', 'export products',
                    'view inventory', 'stock adjustment', 'stock transfer', 'approve adjustments', 'view inventory valuation',
                    'view batches', 'create batches', 'edit batches', 'dispose expired stock',
                    'create purchase orders', 'approve purchase orders', 'receive goods', 'process purchase returns',
                    'create quotations', 'create sales orders', 'create invoices', 'process sales returns', 'cancel sales',
                    'access pos', 'apply discounts', 'void transactions', 'reprint receipts', 'open cash drawer',
                    'view customers', 'create customers', 'edit customers', 'manage credit limits',
                    'view suppliers', 'create suppliers', 'edit suppliers', 'delete suppliers',
                    'view warehouses', 'create warehouses', 'manage transfers',
                    'view financial reports', 'manage expenses', 'manage payments', 'view profit & loss',
                    'view reports', 'export reports', 'print reports',
                    'view users', 'edit users',
                    'salespeople.view', 'salespeople.manage',
                    'teams.view', 'teams.manage',
                    'territories.view', 'territories.manage',
                    'assignments.view', 'assignments.manage',
                    'targets.view', 'targets.manage',
                    'target_templates.view', 'target_templates.manage',
                    'approvals.review',
                ],
            ],
            [
                'name' => 'manager',
                'description' => 'Manager with approval and reporting permissions',
                'permissions' => [
                    'view dashboard', 'view analytics', 'view financial summary',
                    'view products', 'edit products', 'export products',
                    'view inventory', 'approve adjustments', 'view inventory valuation',
                    'view batches', 'edit batches', 'dispose expired stock',
                    'create purchase orders', 'approve purchase orders', 'receive goods', 'process purchase returns',
                    'create quotations', 'create sales orders', 'create invoices', 'process sales returns', 'cancel sales',
                    'access pos', 'apply discounts', 'void transactions', 'reprint receipts',
                    'view customers', 'create customers', 'edit customers', 'manage credit limits',
                    'view suppliers', 'create suppliers', 'edit suppliers',
                    'view warehouses', 'manage transfers',
                    'view financial reports', 'manage expenses', 'manage payments', 'view profit & loss',
                    'view reports', 'export reports', 'print reports',
                    'salespeople.view', 'salespeople.manage',
                    'teams.view', 'teams.manage',
                    'territories.view', 'territories.manage',
                    'assignments.view', 'assignments.manage',
                    'targets.view', 'targets.manage',
                    'target_templates.view', 'target_templates.manage',
                    'approvals.review',
                ],
            ],
            [
                'name' => 'sales_staff',
                'description' => 'Sales staff with quotation, order, and customer access',
                'permissions' => [
                    'view products', 'export products',
                    'view inventory',
                    'view batches',
                    'create quotations', 'create sales orders', 'create invoices', 'process sales returns',
                    'view customers', 'create customers', 'edit customers',
                    'view suppliers',
                    'view reports', 'export reports', 'print reports',
                    'salespeople.view',
                    'teams.view',
                    'territories.view',
                    'assignments.view',
                    'targets.view',
                ],
            ],
            [
                'name' => 'cashier',
                'description' => 'Cashier with POS and basic sales access',
                'permissions' => [
                    'view products',
                    'access pos', 'apply discounts', 'reprint receipts', 'open cash drawer',
                    'create invoices', 'process sales returns',
                    'view customers', 'create customers',
                    'view reports',
                ],
            ],
            [
                'name' => 'purchasing_staff',
                'description' => 'Purchasing staff with PO and receiving access',
                'permissions' => [
                    'view products', 'export products',
                    'view inventory',
                    'view batches', 'create batches', 'edit batches',
                    'create purchase orders', 'receive goods', 'process purchase returns',
                    'view suppliers', 'create suppliers', 'edit suppliers',
                    'view warehouses',
                    'view reports', 'export reports',
                ],
            ],
            [
                'name' => 'warehouse_staff',
                'description' => 'Warehouse staff with inventory and batch management',
                'permissions' => [
                    'view products',
                    'view inventory', 'stock adjustment', 'stock transfer',
                    'view batches', 'create batches', 'edit batches', 'dispose expired stock',
                    'receive goods', 'process purchase returns',
                    'view warehouses', 'manage transfers',
                    'view reports', 'export reports',
                ],
            ],
            [
                'name' => 'delivery_staff',
                'description' => 'Delivery staff with delivery and transfer access',
                'permissions' => [
                    'view products',
                    'view inventory', 'stock transfer',
                    'view warehouses', 'manage transfers',
                    'view reports',
                ],
            ],
            [
                'name' => 'accountant',
                'description' => 'Accountant with financial and accounting access',
                'permissions' => [
                    'view dashboard', 'view analytics', 'view financial summary',
                    'view products',
                    'view inventory', 'view inventory valuation',
                    'view batches',
                    'create purchase orders', 'approve purchase orders', 'process purchase returns',
                    'create invoices', 'process sales returns',
                    'view customers', 'manage credit limits',
                    'view suppliers', 'create suppliers', 'edit suppliers',
                    'view warehouses',
                    'view financial reports', 'manage expenses', 'manage payments', 'view profit & loss',
                    'view reports', 'export reports', 'print reports',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissionNames = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::firstOrCreate(
                ['name' => $roleData['name']],
                ['guard_name' => 'api', 'description' => $roleData['description']]
            );

            $permissions = Permission::whereIn('name', $permissionNames)->get();
            $role->permissions()->sync($permissions);
        }
    }
}