<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
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

    private function admin(): User
    {
        return User::where(
            'email',
            'admin@deshsolar.com'
        )->firstOrFail();
    }

    private function headOffice(): Branch
    {
        return Branch::where(
            'code',
            'HEAD-OFFICE'
        )->firstOrFail();
    }

    private function viewer(): User
    {
        $role = Role::where(
            'code',
            'DESH-SOLAR-VIEWER'
        )->firstOrFail();

        $user = User::factory()->create([
            'company_id' => $this->company()->id,
            'name' => 'Supplier Viewer',
            'email' => 'supplier.viewer@deshsolar.test',
        ]);

        $user->assignRole(
            role: $role,
            assignedBy: $this->admin(),
        );

        $user->assignBranch(
            branch: $this->headOffice(),
            isPrimary: true,
            assignedBy: $this->admin(),
        );

        return $user;
    }

    private function secondBranch(): Branch
    {
        return Branch::create([
            'company_id' => $this->company()->id,
            'name' => 'Chattogram Branch',
            'code' => 'CHATTOGRAM-BRANCH',
            'email' => 'chattogram@deshsolar.test',
            'phone' => '01700000001',
            'address' => 'Chattogram, Bangladesh',
            'city' => 'Chattogram',
            'district' => 'Chattogram',
            'postal_code' => '4000',
            'is_head_office' => false,
            'is_active' => true,
        ]);
    }

    private function supplier(
        array $overrides = []
    ): Supplier {
        return Supplier::create([
            'company_id' => $this->company()->id,
            'branch_id' => $this->headOffice()->id,
            'name' => 'Solar Equipment Supplier',
            'code' => 'SUP-001',
            'business_name' => 'Solar Equipment Supplier Ltd.',
            'email' => 'supplier@example.test',
            'phone' => '01700000002',
            'alternate_phone' => null,
            'website' => 'https://supplier.example.test',
            'tax_identification_number' => 'TIN-10001',
            'trade_license_number' => 'TL-10001',
            'address_line_1' => '123 Supplier Road',
            'address_line_2' => null,
            'city' => 'Dhaka',
            'district' => 'Dhaka',
            'postal_code' => '1205',
            'country' => 'Bangladesh',
            'payment_term_days' => 30,
            'credit_limit' => 500000,
            'opening_balance' => 10000,
            'opening_balance_type' => 'payable',
            'notes' => 'Preferred supplier.',
            'is_active' => true,
            'created_by' => $this->admin()->id,
            'updated_by' => $this->admin()->id,
            ...$overrides,
        ]);
    }

    private function validPayload(
        array $overrides = []
    ): array {
        return [
            'company_id' => $this->company()->id,
            'branch_id' => $this->headOffice()->id,
            'name' => 'New Supplier',
            'code' => 'SUP-NEW',
            'business_name' => 'New Supplier Limited',
            'email' => 'new.supplier@example.test',
            'phone' => '01700000003',
            'alternate_phone' => '01800000003',
            'website' => 'https://new-supplier.example.test',
            'tax_identification_number' => 'TIN-NEW-001',
            'trade_license_number' => 'TL-NEW-001',
            'address_line_1' => '45 Business Avenue',
            'address_line_2' => 'Level 3',
            'city' => 'Dhaka',
            'district' => 'Dhaka',
            'postal_code' => '1212',
            'country' => 'Bangladesh',
            'payment_term_days' => 45,
            'credit_limit' => 750000,
            'opening_balance' => 25000,
            'opening_balance_type' => 'payable',
            'notes' => 'New supplier record.',
            'is_active' => true,
            ...$overrides,
        ];
    }

    public function test_guest_cannot_access_suppliers(): void
    {
        $supplier = $this->supplier();

        $this->getJson('/api/suppliers')
            ->assertUnauthorized();

        $this->getJson(
            "/api/suppliers/{$supplier->id}"
        )->assertUnauthorized();
    }

    public function test_company_admin_can_list_accessible_suppliers(): void
    {
        $supplier = $this->supplier();

        Sanctum::actingAs($this->admin());

        $this->getJson('/api/suppliers')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.pagination.total',
                1
            )
            ->assertJsonPath(
                'data.suppliers.0.id',
                $supplier->id
            )
            ->assertJsonPath(
                'data.suppliers.0.code',
                'SUP-001'
            )
            ->assertJsonPath(
                'data.suppliers.0.branch.code',
                'HEAD-OFFICE'
            );
    }

    public function test_company_admin_can_create_supplier(): void
    {
        Sanctum::actingAs($this->admin());

        $response = $this->postJson(
            '/api/suppliers',
            $this->validPayload()
        );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.supplier.name',
                'New Supplier'
            )
            ->assertJsonPath(
                'data.supplier.code',
                'SUP-NEW'
            )
            ->assertJsonPath(
                'data.supplier.company.code',
                'DESH-SOLAR'
            )
            ->assertJsonPath(
                'data.supplier.branch.code',
                'HEAD-OFFICE'
            )
            ->assertJsonPath(
                'data.supplier.creator.id',
                $this->admin()->id
            );

        $this->assertDatabaseHas('suppliers', [
            'company_id' => $this->company()->id,
            'branch_id' => $this->headOffice()->id,
            'name' => 'New Supplier',
            'code' => 'SUP-NEW',
            'created_by' => $this->admin()->id,
            'updated_by' => $this->admin()->id,
            'is_active' => true,
        ]);
    }

    public function test_supplier_creation_validates_required_fields(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/suppliers', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'code',
            ]);
    }

    public function test_supplier_code_must_be_unique_inside_company(): void
    {
        $this->supplier([
            'code' => 'SUP-DUPLICATE',
        ]);

        Sanctum::actingAs($this->admin());

        $this->postJson(
            '/api/suppliers',
            $this->validPayload([
                'code' => 'SUP-DUPLICATE',
            ])
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'code',
            ]);
    }

    public function test_company_admin_can_view_accessible_supplier(): void
    {
        $supplier = $this->supplier();

        Sanctum::actingAs($this->admin());

        $this->getJson(
            "/api/suppliers/{$supplier->id}"
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.supplier.id',
                $supplier->id
            )
            ->assertJsonPath(
                'data.supplier.business_name',
                'Solar Equipment Supplier Ltd.'
            )
            ->assertJsonPath(
                'data.supplier.opening_balance_type',
                'payable'
            );
    }

    public function test_company_admin_can_update_supplier(): void
    {
        $supplier = $this->supplier();

        Sanctum::actingAs($this->admin());

        $this->patchJson(
            "/api/suppliers/{$supplier->id}",
            [
                'name' => 'Updated Supplier',
                'payment_term_days' => 60,
                'credit_limit' => 900000,
                'is_active' => false,
            ]
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.supplier.name',
                'Updated Supplier'
            )
            ->assertJsonPath(
                'data.supplier.payment_term_days',
                60
            )
            ->assertJsonPath(
                'data.supplier.is_active',
                false
            )
            ->assertJsonPath(
                'data.supplier.updater.id',
                $this->admin()->id
            );

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Supplier',
            'payment_term_days' => 60,
            'is_active' => false,
            'updated_by' => $this->admin()->id,
        ]);
    }

    public function test_search_and_filters_return_matching_suppliers(): void
    {
        $this->supplier([
            'name' => 'Active Panel Supplier',
            'code' => 'SUP-PANEL',
            'email' => 'panel@example.test',
            'is_active' => true,
            'opening_balance_type' => 'payable',
        ]);

        $this->supplier([
            'name' => 'Inactive Battery Supplier',
            'code' => 'SUP-BATTERY',
            'email' => 'battery@example.test',
            'is_active' => false,
            'opening_balance_type' => 'receivable',
        ]);

        Sanctum::actingAs($this->admin());

        $this->getJson(
            '/api/suppliers'
            . '?search=battery'
            . '&is_active=0'
            . '&opening_balance_type=receivable'
            . '&sort_by=name'
            . '&sort_direction=asc'
            . '&per_page=10'
        )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.suppliers'
            )
            ->assertJsonPath(
                'data.suppliers.0.code',
                'SUP-BATTERY'
            )
            ->assertJsonPath(
                'data.pagination.total',
                1
            );
    }

    public function test_user_cannot_assign_supplier_to_unassigned_branch(): void
    {
        $secondBranch = $this->secondBranch();

        Sanctum::actingAs($this->admin());

        $this->postJson(
            '/api/suppliers',
            $this->validPayload([
                'branch_id' => $secondBranch->id,
            ])
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'branch_id',
            ]);
    }

    public function test_company_user_cannot_access_supplier_from_another_company(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Company',
            'code' => 'OTHER-COMPANY',
            'email' => 'info@other-company.test',
            'website' => null,
            'phone' => null,
            'address' => 'Dhaka, Bangladesh',
            'timezone' => 'Asia/Dhaka',
            'currency' => 'BDT',
            'is_active' => true,
        ]);

        $otherBranch = Branch::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Head Office',
            'code' => 'OTHER-HEAD-OFFICE',
            'email' => null,
            'phone' => null,
            'address' => 'Dhaka, Bangladesh',
            'city' => 'Dhaka',
            'district' => 'Dhaka',
            'postal_code' => '1200',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $otherSupplier = Supplier::create([
            'company_id' => $otherCompany->id,
            'branch_id' => $otherBranch->id,
            'name' => 'External Supplier',
            'code' => 'EXT-SUP-001',
            'country' => 'Bangladesh',
            'payment_term_days' => 0,
            'credit_limit' => 0,
            'opening_balance' => 0,
            'opening_balance_type' => 'payable',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->admin());

        $this->getJson(
            "/api/suppliers/{$otherSupplier->id}"
        )->assertForbidden();

        $response = $this->getJson(
            '/api/suppliers'
        );

        $response->assertOk();

        $supplierIds = collect(
            $response->json('data.suppliers')
        )->pluck('id');

        $this->assertFalse(
            $supplierIds->contains(
                $otherSupplier->id
            )
        );
    }

    public function test_viewer_can_view_but_cannot_modify_supplier(): void
    {
        $supplier = $this->supplier();
        $viewer = $this->viewer();

        Sanctum::actingAs($viewer);

        $this->getJson('/api/suppliers')
            ->assertOk();

        $this->getJson(
            "/api/suppliers/{$supplier->id}"
        )->assertOk();

        $this->postJson(
            '/api/suppliers',
            $this->validPayload([
                'code' => 'VIEWER-SUP',
            ])
        )->assertForbidden();

        $this->patchJson(
            "/api/suppliers/{$supplier->id}",
            [
                'name' => 'Unauthorized Update',
            ]
        )->assertForbidden();

        $this->deleteJson(
            "/api/suppliers/{$supplier->id}"
        )->assertForbidden();
    }

    public function test_company_admin_can_soft_delete_and_restore_supplier(): void
    {
        $supplier = $this->supplier();

        Sanctum::actingAs($this->admin());

        $this->deleteJson(
            "/api/suppliers/{$supplier->id}"
        )
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted(
            'suppliers',
            [
                'id' => $supplier->id,
            ]
        );

        $this->postJson(
            "/api/suppliers/{$supplier->id}/restore"
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.supplier.id',
                $supplier->id
            );

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'deleted_at' => null,
            'updated_by' => $this->admin()->id,
        ]);
    }
}