<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_have_roles(): void
    {
        $company = Company::create([
            'name' => 'Desh Solar',
            'code' => 'DESH-SOLAR',
            'website' => 'https://deshsolar.com/',
            'timezone' => 'Asia/Dhaka',
            'currency' => 'BDT',
            'is_active' => true,
        ]);

        $role = $company->roles()->create([
            'name' => 'Inventory Manager',
            'code' => 'DESH-SOLAR-INVENTORY-MANAGER',
            'description' => 'Manages company inventory operations.',
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->assertTrue($role->company->is($company));
        $this->assertCount(1, $company->roles);
        $this->assertFalse($role->is_system);
        $this->assertTrue($role->is_active);
    }

    public function test_global_system_role_can_exist_without_company(): void
    {
        $role = Role::create([
            'company_id' => null,
            'name' => 'Super Admin',
            'code' => 'SUPER-ADMIN',
            'description' => 'Global system administrator.',
            'is_system' => true,
            'is_active' => true,
        ]);

        $this->assertNull($role->company);
        $this->assertTrue($role->is_system);
        $this->assertTrue($role->is_active);
    }
}