<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Permission catalog. Each entry: [code, display_name, module, sort]
     * Codes MUST match the `meta.permission` values used in the frontend
     * (`src/router/route.js`) so the menu can be filtered by them.
     */
    private array $permissions = [
        // Sales
        ['pos', 'Point of Sale', 'sales', 10],
        ['sales.list', 'Sales List', 'sales', 20],
        ['sales.report', 'Sales Report', 'sales', 30],
        // Patients
        ['patient.pod', 'Pod Patients', 'patient', 40],
        ['patient.list', 'Patient List', 'patient', 50],
        ['patient.history', 'Patient Histories', 'patient', 60],
        ['patient.temp-prescription', 'Temp Prescriptions', 'patient', 70],
        // Pharmacy
        ['drug.list', 'Drug List', 'pharmacy', 80],
        ['drug.stock', 'Drug Stock', 'pharmacy', 90],
        ['drug.report', 'Drug Report', 'pharmacy', 100],
        ['brand.list', 'Brands', 'pharmacy', 110],
        ['company.list', 'Companies', 'pharmacy', 120],
        // System
        ['user.manage', 'User Management', 'system', 130],
        ['role.manage', 'Role Management', 'system', 140],
    ];

    /**
     * Roles: name => [display_name, description, permission codes].
     */
    private array $roles = [
        'admin' => [
            'Administrator',
            'Full access to every module',
            'ALL',
        ],
        'cashier' => [
            'Cashier',
            'Point of sale and sales reports',
            ['pos', 'sales.list', 'sales.report', 'drug.list', 'drug.stock'],
        ],
        'doctor' => [
            'Doctor',
            'Patient care and prescriptions',
            ['patient.pod', 'patient.list', 'patient.history', 'patient.temp-prescription', 'drug.list', 'brand.list'],
        ],
    ];

    public function run(): void
    {
        $permissionModels = [];
        foreach ($this->permissions as [$name, $displayName, $module, $sort]) {
            $permissionModels[$name] = Permission::updateOrCreate(
                ['name' => $name],
                ['display_name' => $displayName, 'module' => $module, 'sort' => $sort]
            );
        }

        foreach ($this->roles as $name => [$displayName, $description, $codes]) {
            $role = Role::updateOrCreate(
                ['name' => $name],
                ['display_name' => $displayName, 'description' => $description]
            );

            if ($codes === 'ALL') {
                $role->permissions()->sync(collect($permissionModels)->pluck('id')->all());
            } else {
                $role->permissions()->sync(
                    collect($codes)
                        ->map(fn ($code) => $permissionModels[$code] ?? null)
                        ->filter()
                        ->pluck('id')
                );
            }
        }

        // Preserve current behaviour: existing users keep full access until
        // an administrator assigns them a restricted role.
        $adminRole = Role::where('name', 'admin')->first();
        User::doesntHave('roles')->each(fn (User $user) => $user->roles()->attach($adminRole));
    }
}