<?php

namespace Tests\Feature;

use App\Http\Requests\StoreWarehouseLocationRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class OrganizationStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_desh_solar_organization_structure_can_be_seeded(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::where('code', 'DESH-SOLAR')->first();

        $this->assertNotNull($company);
        $this->assertSame('Desh Solar', $company->name);
        $this->assertSame('https://deshsolar.com/', $company->website);

        $branch = Branch::where('code', 'HEAD-OFFICE')->first();

        $this->assertNotNull($branch);
        $this->assertTrue($branch->company->is($company));

        $warehouse = Warehouse::where('code', 'MAIN-WAREHOUSE')->first();

        $this->assertNotNull($warehouse);
        $this->assertTrue($warehouse->company->is($company));
        $this->assertTrue($warehouse->branch->is($branch));

        $this->assertSame(
            4,
            WarehouseLocation::where(
                'warehouse_id',
                $warehouse->id
            )->count()
        );
    }

    public function test_warehouse_location_hierarchy_is_correct(): void
    {
        $this->seed(DatabaseSeeder::class);

        $bin = WarehouseLocation::with(
            'parent.parent.parent'
        )
            ->where('code', 'BIN-A1-01-01')
            ->firstOrFail();

        $this->assertSame('bin', $bin->type);
        $this->assertSame('Shelf A1-01', $bin->parent->name);
        $this->assertSame(
            'Solar Panel Rack A1',
            $bin->parent->parent->name
        );
        $this->assertSame(
            'Main Storage Zone',
            $bin->parent->parent->parent->name
        );
    }

    public function test_invalid_warehouse_location_type_is_rejected(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::where('code', 'DESH-SOLAR')
            ->firstOrFail();

        $branch = Branch::where('code', 'HEAD-OFFICE')
            ->firstOrFail();

        $warehouse = Warehouse::where('code', 'MAIN-WAREHOUSE')
            ->firstOrFail();

        $request = StoreWarehouseLocationRequest::create(
            '/api/warehouse-locations',
            'POST',
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouse->id,
                'parent_id' => null,
                'name' => 'Invalid Location',
                'code' => 'INVALID-LOCATION',
                'type' => 'room',
                'is_active' => true,
            ]
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'type',
            $validator->errors()->toArray()
        );
    }

    public function test_location_code_must_be_unique_inside_a_warehouse(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::where('code', 'DESH-SOLAR')
            ->firstOrFail();

        $branch = Branch::where('code', 'HEAD-OFFICE')
            ->firstOrFail();

        $warehouse = Warehouse::where('code', 'MAIN-WAREHOUSE')
            ->firstOrFail();

        $request = StoreWarehouseLocationRequest::create(
            '/api/warehouse-locations',
            'POST',
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouse->id,
                'parent_id' => null,
                'name' => 'Duplicate Zone',
                'code' => 'ZONE-A',
                'type' => 'zone',
                'is_active' => true,
            ]
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'code',
            $validator->errors()->toArray()
        );
    }
}