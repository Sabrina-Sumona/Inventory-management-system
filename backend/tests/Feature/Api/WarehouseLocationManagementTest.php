<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WarehouseLocationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function company(): Company
    {
        return Company::where(
            'code',
            'DESH-SOLAR'
        )->firstOrFail();
    }

    private function branch(): Branch
    {
        return Branch::where(
            'code',
            'HEAD-OFFICE'
        )->firstOrFail();
    }

    private function warehouse(): Warehouse
    {
        return Warehouse::where(
            'code',
            'MAIN-WAREHOUSE'
        )->firstOrFail();
    }

    private function admin(): User
    {
        return User::where(
            'email',
            'admin@deshsolar.com'
        )->firstOrFail();
    }

    private function location(
        string $code
    ): WarehouseLocation {
        return WarehouseLocation::where(
            'code',
            $code
        )->firstOrFail();
    }

    private function createLocation(
        array $attributes = []
    ): WarehouseLocation {
        $warehouse = $this->warehouse();

        return WarehouseLocation::create([
            'company_id' => $warehouse->company_id,
            'branch_id' => $warehouse->branch_id,
            'warehouse_id' => $warehouse->id,
            'parent_id' => null,
            'name' => 'Secondary Storage Zone',
            'code' => 'ZONE-B',
            'type' => 'zone',
            'barcode' => null,
            'capacity' => null,
            'description' => null,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    public function test_guest_cannot_access_warehouse_locations(): void
    {
        $this->getJson(
            '/api/warehouse-locations'
        )->assertUnauthorized();
    }

    public function test_admin_can_list_accessible_locations(): void
    {
        Sanctum::actingAs($this->admin());

        $this->getJson(
            '/api/warehouse-locations'
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.pagination.total',
                4
            )
            ->assertJsonFragment([
                'code' => 'ZONE-A',
            ])
            ->assertJsonFragment([
                'code' => 'BIN-A1-01-01',
            ]);
    }

    public function test_admin_can_create_root_zone(): void
    {
        $warehouse = $this->warehouse();

        Sanctum::actingAs($this->admin());

        $response = $this->postJson(
            '/api/warehouse-locations',
            [
                'warehouse_id' => $warehouse->id,
                'parent_id' => null,
                'name' => 'Secondary Storage Zone',
                'code' => 'zone-b',
                'type' => 'ZONE',
                'barcode' => 'DS-ZONE-B',
                'capacity' => 500.750,
                'description' => 'Secondary storage area.',
                'is_active' => true,
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.location.code',
                'ZONE-B'
            )
            ->assertJsonPath(
                'data.location.type',
                'zone'
            )
            ->assertJsonPath(
                'data.location.parent_id',
                null
            )
            ->assertJsonPath(
                'data.location.warehouse.code',
                'MAIN-WAREHOUSE'
            );

        $this->assertDatabaseHas(
            'warehouse_locations',
            [
                'warehouse_id' => $warehouse->id,
                'name' => 'Secondary Storage Zone',
                'code' => 'ZONE-B',
                'type' => 'zone',
                'barcode' => 'DS-ZONE-B',
                'is_active' => true,
            ]
        );
    }

    public function test_admin_can_create_rack_under_zone(): void
    {
        $warehouse = $this->warehouse();
        $zone = $this->location('ZONE-A');

        Sanctum::actingAs($this->admin());

        $this->postJson(
            '/api/warehouse-locations',
            [
                'warehouse_id' => $warehouse->id,
                'parent_id' => $zone->id,
                'name' => 'Solar Rack A2',
                'code' => 'RACK-A2',
                'type' => 'rack',
                'is_active' => true,
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.location.parent.code',
                'ZONE-A'
            )
            ->assertJsonPath(
                'data.location.type',
                'rack'
            );

        $this->assertDatabaseHas(
            'warehouse_locations',
            [
                'warehouse_id' => $warehouse->id,
                'parent_id' => $zone->id,
                'code' => 'RACK-A2',
                'type' => 'rack',
            ]
        );
    }

    public function test_non_zone_location_requires_parent(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson(
            '/api/warehouse-locations',
            [
                'warehouse_id' =>
                    $this->warehouse()->id,
                'parent_id' => null,
                'name' => 'Invalid Rack',
                'code' => 'INVALID-RACK',
                'type' => 'rack',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'parent_id',
            ]);
    }

    public function test_location_parent_must_follow_hierarchy(): void
    {
        Sanctum::actingAs($this->admin());

        $rack = $this->location('RACK-A1');

        $this->postJson(
            '/api/warehouse-locations',
            [
                'warehouse_id' =>
                    $this->warehouse()->id,
                'parent_id' => $rack->id,
                'name' => 'Invalid Bin',
                'code' => 'INVALID-BIN',
                'type' => 'bin',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'parent_id',
            ]);
    }

    public function test_location_code_must_be_unique_inside_warehouse(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson(
            '/api/warehouse-locations',
            [
                'warehouse_id' =>
                    $this->warehouse()->id,
                'parent_id' => null,
                'name' => 'Duplicate Zone',
                'code' => 'ZONE-A',
                'type' => 'zone',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'code',
            ]);
    }

    public function test_barcode_must_be_unique(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson(
            '/api/warehouse-locations',
            [
                'warehouse_id' =>
                    $this->warehouse()->id,
                'parent_id' =>
                    $this->location('SHELF-A1-01')->id,
                'name' => 'Duplicate Barcode Bin',
                'code' => 'BIN-A1-01-02',
                'type' => 'bin',
                'barcode' => 'DS-BIN-A1-01-01',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'barcode',
            ]);
    }

    public function test_admin_can_view_location(): void
    {
        $location = $this->location(
            'BIN-A1-01-01'
        );

        Sanctum::actingAs($this->admin());

        $this->getJson(
            "/api/warehouse-locations/{$location->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.location.code',
                'BIN-A1-01-01'
            )
            ->assertJsonPath(
                'data.location.parent.code',
                'SHELF-A1-01'
            )
            ->assertJsonPath(
                'data.location.warehouse.code',
                'MAIN-WAREHOUSE'
            );
    }

    public function test_admin_can_update_location(): void
    {
        $location = $this->location(
            'BIN-A1-01-01'
        );

        Sanctum::actingAs($this->admin());

        $this->patchJson(
            "/api/warehouse-locations/{$location->id}",
            [
                'name' => 'Solar Component Bin',
                'barcode' => 'DS-COMPONENT-BIN-01',
                'capacity' => 125.500,
                'description' =>
                    'Stores small solar components.',
                'is_active' => true,
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.location.name',
                'Solar Component Bin'
            )
            ->assertJsonPath(
                'data.location.barcode',
                'DS-COMPONENT-BIN-01'
            );

        $this->assertDatabaseHas(
            'warehouse_locations',
            [
                'id' => $location->id,
                'name' => 'Solar Component Bin',
                'barcode' => 'DS-COMPONENT-BIN-01',
                'capacity' => '125.500',
            ]
        );
    }

    public function test_location_cannot_be_moved_under_its_descendant(): void
    {
        $zone = $this->location('ZONE-A');
        $rack = $this->location('RACK-A1');

        Sanctum::actingAs($this->admin());

        $this->patchJson(
            "/api/warehouse-locations/{$zone->id}",
            [
                'parent_id' => $rack->id,
                'type' => 'rack',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'parent_id',
            ]);
    }

    public function test_search_and_filters_return_matching_location(): void
    {
        Sanctum::actingAs($this->admin());

        $warehouse = $this->warehouse();

        $this->getJson(
            '/api/warehouse-locations'
            . '?search=bin'
            . '&warehouse_id=' . $warehouse->id
            . '&type=bin'
            . '&is_active=1'
            . '&sort_by=name'
            . '&sort_direction=asc'
            . '&per_page=10'
        )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.locations'
            )
            ->assertJsonPath(
                'data.locations.0.code',
                'BIN-A1-01-01'
            )
            ->assertJsonPath(
                'data.pagination.total',
                1
            );
    }

    public function test_viewer_can_view_but_cannot_modify_locations(): void
    {
        $viewerRole = Role::where(
            'code',
            'DESH-SOLAR-VIEWER'
        )->firstOrFail();

        $viewer = User::factory()->create([
            'company_id' => $this->company()->id,
        ]);

        $viewer->assignRole($viewerRole);
        $viewer->assignBranch($this->branch());
        $viewer->assignWarehouse(
            $this->warehouse()
        );

        $location = $this->location('ZONE-A');

        Sanctum::actingAs($viewer);

        $this->getJson(
            "/api/warehouse-locations/{$location->id}"
        )->assertOk();

        $this->postJson(
            '/api/warehouse-locations',
            [
                'warehouse_id' =>
                    $this->warehouse()->id,
                'parent_id' => null,
                'name' => 'Unauthorized Zone',
                'code' => 'UNAUTHORIZED-ZONE',
                'type' => 'zone',
            ]
        )->assertForbidden();

        $this->patchJson(
            "/api/warehouse-locations/{$location->id}",
            [
                'name' => 'Unauthorized Update',
            ]
        )->assertForbidden();

        $this->deleteJson(
            "/api/warehouse-locations/{$location->id}"
        )->assertForbidden();
    }

    public function test_user_cannot_access_location_in_unassigned_warehouse(): void
    {
        $admin = $this->admin();

        $warehouse = Warehouse::create([
            'company_id' => $this->company()->id,
            'branch_id' => $this->branch()->id,
            'name' => 'Unassigned Warehouse',
            'code' => 'UNASSIGNED-WAREHOUSE',
            'is_primary' => false,
            'is_active' => true,
        ]);

        $location = WarehouseLocation::create([
            'company_id' => $warehouse->company_id,
            'branch_id' => $warehouse->branch_id,
            'warehouse_id' => $warehouse->id,
            'parent_id' => null,
            'name' => 'Unassigned Zone',
            'code' => 'UNASSIGNED-ZONE',
            'type' => 'zone',
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson(
            "/api/warehouse-locations/{$location->id}"
        )->assertForbidden();
    }

    public function test_location_with_children_cannot_be_deleted(): void
    {
        $zone = $this->location('ZONE-A');

        Sanctum::actingAs($this->admin());

        $this->deleteJson(
            "/api/warehouse-locations/{$zone->id}"
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'location',
            ]);

        $this->assertNotSoftDeleted($zone);
    }

    public function test_childless_location_can_be_soft_deleted(): void
    {
        $bin = $this->location(
            'BIN-A1-01-01'
        );

        Sanctum::actingAs($this->admin());

        $this->deleteJson(
            "/api/warehouse-locations/{$bin->id}"
        )
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted($bin);
    }

    public function test_admin_can_restore_deleted_location(): void
    {
        $location = $this->createLocation();

        $location->delete();

        $this->assertSoftDeleted($location);

        Sanctum::actingAs($this->admin());

        $this->postJson(
            "/api/warehouse-locations/{$location->id}/restore"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.location.code',
                'ZONE-B'
            );

        $this->assertNotSoftDeleted(
            $location->fresh()
        );
    }
}