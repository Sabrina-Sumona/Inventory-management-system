<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_desh_solar_organization_structure_can_be_seeded(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::where(
            'code',
            'DESH-SOLAR'
        )->firstOrFail();

        $this->assertSame(
            'Desh Solar',
            $company->name
        );

        $this->assertSame(
            'https://deshsolar.com/',
            $company->website
        );

        $branch = Branch::where(
            'code',
            'HEAD-OFFICE'
        )->firstOrFail();

        $this->assertSame(
            'Desh Solar Head Office',
            $branch->name
        );

        $this->assertTrue(
            $branch->company->is($company)
        );

        $warehouse = Warehouse::where(
            'code',
            'MAIN-WAREHOUSE'
        )->firstOrFail();

        $this->assertSame(
            'Desh Solar Main Warehouse',
            $warehouse->name
        );

        $this->assertTrue(
            $warehouse->company->is($company)
        );

        $this->assertTrue(
            $warehouse->branch->is($branch)
        );
    }

    public function test_head_office_belongs_to_desh_solar(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::where(
            'code',
            'DESH-SOLAR'
        )->firstOrFail();

        $branch = Branch::where(
            'code',
            'HEAD-OFFICE'
        )->firstOrFail();

        $this->assertSame(
            $company->id,
            $branch->company_id
        );

        $this->assertTrue(
            $branch->is_head_office
        );

        $this->assertTrue(
            $branch->is_active
        );
    }

    public function test_main_warehouse_belongs_to_head_office(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::where(
            'code',
            'DESH-SOLAR'
        )->firstOrFail();

        $branch = Branch::where(
            'code',
            'HEAD-OFFICE'
        )->firstOrFail();

        $warehouse = Warehouse::where(
            'code',
            'MAIN-WAREHOUSE'
        )->firstOrFail();

        $this->assertSame(
            $company->id,
            $warehouse->company_id
        );

        $this->assertSame(
            $branch->id,
            $warehouse->branch_id
        );

        $this->assertTrue(
            $warehouse->is_primary
        );

        $this->assertTrue(
            $warehouse->is_active
        );
    }

    public function test_seeded_organization_has_one_branch_and_one_warehouse(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::where(
            'code',
            'DESH-SOLAR'
        )->firstOrFail();

        $this->assertSame(
            1,
            Branch::where(
                'company_id',
                $company->id
            )->count()
        );

        $this->assertSame(
            1,
            Warehouse::where(
                'company_id',
                $company->id
            )->count()
        );
    }
}