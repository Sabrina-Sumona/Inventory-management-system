<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupplierContactManagementTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Branch $branch;

    private User $companyAdmin;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );

        $this->company = Company::query()->create([
            'name' => 'Desh Solar',
            'code' => 'DESH-SOLAR',
            'email' => 'info@deshsolar.com',
            'phone' => '01700000000',
            'is_active' => true,
        ]);

        $this->branch = Branch::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Desh Solar Head Office',
            'code' => 'DS-HO',
            'email' => 'headoffice@deshsolar.com',
            'phone' => '01700000001',
            'address' => 'Dhaka',
            'is_active' => true,
        ]);

        $this->companyAdmin = User::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Company Admin',
            'email' => 'admin@deshsolar.com',
            'password' => 'password',
        ]);

        $this->companyAdmin
            ->branches()
            ->attach(
                $this->branch->id,
                [
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

        $role = Role::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Company Admin',
            'code' => 'TEST-COMPANY-ADMIN',
            'description' => 'Test company administrator.',
            'is_system' => false,
            'is_active' => true,
        ]);

        $permissionIds = Permission::query()
            ->whereIn('code', [
                'supplier.view',
                'supplier.create',
                'supplier.update',
                'supplier.delete',
                'supplier-contact.view',
                'supplier-contact.create',
                'supplier-contact.update',
                'supplier-contact.delete',
            ])
            ->pluck('id')
            ->all();

        $role->permissions()->sync(
            $permissionIds
        );

        $this->companyAdmin
            ->roles()
            ->attach(
                $role->id,
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

        $this->supplier = Supplier::query()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Walton',
            'code' => 'WALTON',
            'business_name' => 'Walton Hi-Tech Industries PLC',
            'email' => 'supplier@walton.com',
            'phone' => '01700000002',
            'country' => 'Bangladesh',
            'payment_term_days' => 30,
            'credit_limit' => 1000000,
            'opening_balance' => 0,
            'opening_balance_type' => 'payable',
            'is_active' => true,
            'created_by' => $this->companyAdmin->id,
            'updated_by' => $this->companyAdmin->id,
        ]);

        Sanctum::actingAs(
            $this->companyAdmin
        );
    }

    public function test_authorized_user_can_list_supplier_contacts(): void
    {
        SupplierContact::query()->create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Rahim Ahmed',
            'designation' => 'Sales Manager',
            'contact_type' => 'sales',
            'email' => 'rahim@walton.com',
            'phone' => '01710000000',
            'is_primary' => true,
            'is_active' => true,
            'created_by' => $this->companyAdmin->id,
            'updated_by' => $this->companyAdmin->id,
        ]);

        $response = $this->getJson(
            '/api/supplier-contacts'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.pagination.total',
                1
            )
            ->assertJsonPath(
                'data.supplier_contacts.0.name',
                'Rahim Ahmed'
            );
    }

    public function test_authorized_user_can_create_supplier_contact(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'name' => 'Karim Hasan',
            'designation' => 'Account Officer',
            'department' => 'Accounts',
            'contact_type' => 'accounts',
            'email' => 'karim@walton.com',
            'phone' => '01720000000',
            'alternate_phone' => '01820000000',
            'is_primary' => true,
            'is_active' => true,
            'notes' => 'Primary accounts contact.',
        ];

        $response = $this->postJson(
            '/api/supplier-contacts',
            $payload
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.supplier_contact.name',
                'Karim Hasan'
            )
            ->assertJsonPath(
                'data.supplier_contact.is_primary',
                true
            );

        $this->assertDatabaseHas(
            'supplier_contacts',
            [
                'supplier_id' =>
                    $this->supplier->id,
                'name' => 'Karim Hasan',
                'contact_type' => 'accounts',
                'is_primary' => true,
            ]
        );
    }

    public function test_creating_new_primary_contact_removes_previous_primary_status(): void
    {
        $existingPrimary =
            SupplierContact::query()->create([
                'supplier_id' =>
                    $this->supplier->id,
                'name' => 'Previous Primary',
                'contact_type' => 'sales',
                'is_primary' => true,
                'is_active' => true,
                'created_by' =>
                    $this->companyAdmin->id,
                'updated_by' =>
                    $this->companyAdmin->id,
            ]);

        $response = $this->postJson(
            '/api/supplier-contacts',
            [
                'supplier_id' =>
                    $this->supplier->id,
                'name' => 'New Primary',
                'contact_type' => 'management',
                'is_primary' => true,
                'is_active' => true,
            ]
        );

        $response->assertCreated();

        $this->assertDatabaseHas(
            'supplier_contacts',
            [
                'id' => $existingPrimary->id,
                'is_primary' => false,
            ]
        );

        $this->assertDatabaseHas(
            'supplier_contacts',
            [
                'supplier_id' =>
                    $this->supplier->id,
                'name' => 'New Primary',
                'is_primary' => true,
            ]
        );
    }

    public function test_authorized_user_can_view_supplier_contact(): void
    {
        $contact =
            SupplierContact::query()->create([
                'supplier_id' =>
                    $this->supplier->id,
                'name' => 'Support Contact',
                'contact_type' => 'support',
                'is_primary' => false,
                'is_active' => true,
                'created_by' =>
                    $this->companyAdmin->id,
                'updated_by' =>
                    $this->companyAdmin->id,
            ]);

        $response = $this->getJson(
            "/api/supplier-contacts/{$contact->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.supplier_contact.id',
                $contact->id
            )
            ->assertJsonPath(
                'data.supplier_contact.name',
                'Support Contact'
            );
    }

    public function test_authorized_user_can_update_supplier_contact(): void
    {
        $contact =
            SupplierContact::query()->create([
                'supplier_id' =>
                    $this->supplier->id,
                'name' => 'Old Contact Name',
                'contact_type' => 'general',
                'is_primary' => false,
                'is_active' => true,
                'created_by' =>
                    $this->companyAdmin->id,
                'updated_by' =>
                    $this->companyAdmin->id,
            ]);

        $response = $this->patchJson(
            "/api/supplier-contacts/{$contact->id}",
            [
                'name' => 'Updated Contact Name',
                'contact_type' => 'sales',
                'designation' => 'Senior Sales Executive',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.supplier_contact.name',
                'Updated Contact Name'
            )
            ->assertJsonPath(
                'data.supplier_contact.contact_type',
                'sales'
            );

        $this->assertDatabaseHas(
            'supplier_contacts',
            [
                'id' => $contact->id,
                'name' => 'Updated Contact Name',
                'contact_type' => 'sales',
            ]
        );
    }

    public function test_authorized_user_can_soft_delete_supplier_contact(): void
    {
        $contact =
            SupplierContact::query()->create([
                'supplier_id' =>
                    $this->supplier->id,
                'name' => 'Delete Contact',
                'contact_type' => 'general',
                'is_primary' => false,
                'is_active' => true,
                'created_by' =>
                    $this->companyAdmin->id,
                'updated_by' =>
                    $this->companyAdmin->id,
            ]);

        $response = $this->deleteJson(
            "/api/supplier-contacts/{$contact->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            );

        $this->assertSoftDeleted(
            'supplier_contacts',
            [
                'id' => $contact->id,
            ]
        );
    }

    public function test_authorized_user_can_restore_supplier_contact(): void
    {
        $contact =
            SupplierContact::query()->create([
                'supplier_id' =>
                    $this->supplier->id,
                'name' => 'Restore Contact',
                'contact_type' => 'general',
                'is_primary' => false,
                'is_active' => true,
                'created_by' =>
                    $this->companyAdmin->id,
                'updated_by' =>
                    $this->companyAdmin->id,
            ]);

        $contact->delete();

        $response = $this->postJson(
            "/api/supplier-contacts/{$contact->id}/restore"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.supplier_contact.id',
                $contact->id
            );

        $this->assertDatabaseHas(
            'supplier_contacts',
            [
                'id' => $contact->id,
                'deleted_at' => null,
            ]
        );
    }

    public function test_supplier_contact_requires_valid_data(): void
    {
        $response = $this->postJson(
            '/api/supplier-contacts',
            [
                'supplier_id' =>
                    $this->supplier->id,
                'name' => '',
                'contact_type' =>
                    'invalid-type',
                'email' => 'invalid-email',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'contact_type',
                'email',
            ]);
    }

    public function test_user_without_permission_cannot_list_supplier_contacts(): void
    {
        $unauthorizedUser =
            User::query()->create([
                'company_id' =>
                    $this->company->id,
                'name' => 'Unauthorized User',
                'email' =>
                    'unauthorized@deshsolar.com',
                'password' => 'password',
            ]);

        Sanctum::actingAs(
            $unauthorizedUser
        );

        $response = $this->getJson(
            '/api/supplier-contacts'
        );

        $response->assertForbidden();
    }

    public function test_user_cannot_create_contact_for_inaccessible_supplier(): void
    {
        $otherCompany =
            Company::query()->create([
                'name' => 'Other Company',
                'code' => 'OTHER-COMPANY',
                'email' => 'info@other.com',
                'phone' => '01900000000',
                'is_active' => true,
            ]);

        $otherBranch =
            Branch::query()->create([
                'company_id' => $otherCompany->id,
                'name' => 'Other Branch',
                'code' => 'OTHER-BRANCH',
                'email' => 'branch@other.com',
                'phone' => '01900000001',
                'address' => 'Chattogram',
                'is_active' => true,
            ]);

        $otherSupplier =
            Supplier::query()->create([
                'company_id' => $otherCompany->id,
                'branch_id' => $otherBranch->id,
                'name' => 'Other Supplier',
                'code' => 'OTHER-SUPPLIER',
                'country' => 'Bangladesh',
                'payment_term_days' => 0,
                'credit_limit' => 0,
                'opening_balance' => 0,
                'opening_balance_type' => 'payable',
                'is_active' => true,
            ]);

        $response = $this->postJson(
            '/api/supplier-contacts',
            [
                'supplier_id' =>
                    $otherSupplier->id,
                'name' => 'Unauthorized Contact',
                'contact_type' => 'general',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'supplier_id',
            ]);
    }

    public function test_list_can_filter_contacts_by_supplier(): void
    {
        $secondSupplier =
            Supplier::query()->create([
                'company_id' =>
                    $this->company->id,
                'branch_id' =>
                    $this->branch->id,
                'name' => 'Second Supplier',
                'code' => 'SECOND-SUPPLIER',
                'country' => 'Bangladesh',
                'payment_term_days' => 0,
                'credit_limit' => 0,
                'opening_balance' => 0,
                'opening_balance_type' => 'payable',
                'is_active' => true,
            ]);

        SupplierContact::query()->create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Walton Contact',
            'contact_type' => 'sales',
            'is_primary' => true,
            'is_active' => true,
        ]);

        SupplierContact::query()->create([
            'supplier_id' => $secondSupplier->id,
            'name' => 'Second Supplier Contact',
            'contact_type' => 'general',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $response = $this->getJson(
            '/api/supplier-contacts?supplier_id=' .
            $this->supplier->id
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.pagination.total',
                1
            )
            ->assertJsonPath(
                'data.supplier_contacts.0.name',
                'Walton Contact'
            );
    }
}