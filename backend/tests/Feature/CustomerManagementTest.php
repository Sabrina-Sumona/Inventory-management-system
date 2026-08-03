<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Branch $branch;

    private User $companyAdmin;

    private Role $companyAdminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );

        $this->company =
            Company::query()->create([
                'name' => 'Desh Solar',
                'code' => 'DESH-SOLAR',
                'email' => 'info@deshsolar.com',
                'phone' => '01700000000',
                'is_active' => true,
            ]);

        $this->branch =
            Branch::query()->create([
                'company_id' =>
                    $this->company->id,

                'name' =>
                    'Desh Solar Head Office',

                'code' => 'DS-HO',

                'email' =>
                    'headoffice@deshsolar.com',

                'phone' =>
                    '01700000001',

                'address' => 'Dhaka',

                'city' => 'Dhaka',

                'district' => 'Dhaka',

                'is_head_office' => true,

                'is_active' => true,
            ]);

        $this->companyAdmin =
            User::query()->create([
                'company_id' =>
                    $this->company->id,

                'name' =>
                    'Company Admin',

                'email' =>
                    'admin@deshsolar.com',

                'password' =>
                    'password',

                'is_active' => true,
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

        $this->companyAdminRole =
            Role::query()->create([
                'company_id' =>
                    $this->company->id,

                'name' =>
                    'Company Admin',

                'code' =>
                    'TEST-CUSTOMER-COMPANY-ADMIN',

                'description' =>
                    'Customer management test role.',

                'is_system' => false,

                'is_active' => true,
            ]);

        $permissionIds =
            Permission::query()
                ->whereIn(
                    'code',
                    [
                        'customer.view',
                        'customer.create',
                        'customer.update',
                        'customer.delete',
                    ]
                )
                ->pluck('id')
                ->all();

        $this->companyAdminRole
            ->permissions()
            ->sync(
                $permissionIds
            );

        $this->companyAdmin
            ->roles()
            ->attach(
                $this->companyAdminRole->id,
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

        Sanctum::actingAs(
            $this->companyAdmin
        );
    }

    public function test_guest_cannot_access_customers(): void
    {
        auth()->forgetGuards();

        $response = $this->getJson(
            '/api/customers'
        );

        $response->assertUnauthorized();
    }

    public function test_company_admin_can_list_accessible_customers(): void
    {
        $this->createCustomer([
            'name' => 'Rahim Traders',
            'code' => 'CUS-001',
        ]);

        $response = $this->getJson(
            '/api/customers'
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
                'data.customers.0.name',
                'Rahim Traders'
            )
            ->assertJsonPath(
                'data.customers.0.code',
                'CUS-001'
            );
    }

    public function test_company_admin_can_create_customer(): void
    {
        $payload = [
            'branch_id' =>
                $this->branch->id,

            'name' =>
                'Solar Solution Ltd.',

            'code' =>
                'CUS-002',

            'business_name' =>
                'Solar Solution Bangladesh Ltd.',

            'customer_type' =>
                'corporate',

            'email' =>
                'accounts@solarsolution.com',

            'phone' =>
                '01710000000',

            'alternate_phone' =>
                '01810000000',

            'website' =>
                'https://solarsolution.com',

            'tax_identification_number' =>
                'TIN-123456',

            'trade_license_number' =>
                'TL-123456',

            'billing_address_line_1' =>
                'House 10, Road 5',

            'billing_city' =>
                'Dhaka',

            'billing_district' =>
                'Dhaka',

            'billing_postal_code' =>
                '1207',

            'billing_country' =>
                'Bangladesh',

            'shipping_address_line_1' =>
                'Warehouse Road',

            'shipping_city' =>
                'Gazipur',

            'shipping_district' =>
                'Gazipur',

            'shipping_postal_code' =>
                '1700',

            'shipping_country' =>
                'Bangladesh',

            'payment_term_days' =>
                30,

            'credit_limit' =>
                500000,

            'opening_balance' =>
                25000,

            'opening_balance_type' =>
                'receivable',

            'notes' =>
                'Important corporate customer.',

            'is_active' =>
                true,
        ];

        $response = $this->postJson(
            '/api/customers',
            $payload
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.customer.name',
                'Solar Solution Ltd.'
            )
            ->assertJsonPath(
                'data.customer.customer_type',
                'corporate'
            )
            ->assertJsonPath(
                'data.customer.company_id',
                $this->company->id
            )
            ->assertJsonPath(
                'data.customer.branch_id',
                $this->branch->id
            );

        $this->assertDatabaseHas(
            'customers',
            [
                'company_id' =>
                    $this->company->id,

                'branch_id' =>
                    $this->branch->id,

                'name' =>
                    'Solar Solution Ltd.',

                'code' =>
                    'CUS-002',

                'customer_type' =>
                    'corporate',

                'opening_balance_type' =>
                    'receivable',

                'created_by' =>
                    $this->companyAdmin->id,

                'updated_by' =>
                    $this->companyAdmin->id,
            ]
        );
    }

    public function test_customer_creation_validates_required_fields(): void
    {
        $response = $this->postJson(
            '/api/customers',
            []
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'code',
                'customer_type',
            ]);
    }

    public function test_customer_code_must_be_unique_inside_company(): void
    {
        $this->createCustomer([
            'name' =>
                'First Customer',

            'code' =>
                'CUS-UNIQUE',
        ]);

        $response = $this->postJson(
            '/api/customers',
            [
                'branch_id' =>
                    $this->branch->id,

                'name' =>
                    'Second Customer',

                'code' =>
                    'CUS-UNIQUE',

                'customer_type' =>
                    'retail',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'code',
            ]);
    }

    public function test_company_admin_can_view_accessible_customer(): void
    {
        $customer =
            $this->createCustomer([
                'name' =>
                    'View Customer',

                'code' =>
                    'CUS-VIEW',
            ]);

        $response = $this->getJson(
            "/api/customers/{$customer->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.customer.id',
                $customer->id
            )
            ->assertJsonPath(
                'data.customer.name',
                'View Customer'
            )
            ->assertJsonPath(
                'data.customer.company.name',
                'Desh Solar'
            )
            ->assertJsonPath(
                'data.customer.branch.name',
                'Desh Solar Head Office'
            );
    }

    public function test_company_admin_can_update_customer(): void
    {
        $customer =
            $this->createCustomer([
                'name' =>
                    'Old Customer Name',

                'code' =>
                    'CUS-UPDATE',

                'customer_type' =>
                    'retail',
            ]);

        $response = $this->patchJson(
            "/api/customers/{$customer->id}",
            [
                'name' =>
                    'Updated Customer Name',

                'customer_type' =>
                    'wholesale',

                'credit_limit' =>
                    750000,

                'payment_term_days' =>
                    45,

                'is_active' =>
                    false,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.customer.name',
                'Updated Customer Name'
            )
            ->assertJsonPath(
                'data.customer.customer_type',
                'wholesale'
            )
            ->assertJsonPath(
                'data.customer.is_active',
                false
            );

        $this->assertDatabaseHas(
            'customers',
            [
                'id' =>
                    $customer->id,

                'name' =>
                    'Updated Customer Name',

                'customer_type' =>
                    'wholesale',

                'payment_term_days' =>
                    45,

                'is_active' =>
                    false,

                'updated_by' =>
                    $this->companyAdmin->id,
            ]
        );
    }

    public function test_search_and_filters_return_matching_customers(): void
    {
        $this->createCustomer([
            'name' =>
                'Rahim Enterprise',

            'code' =>
                'CUS-RAHIM',

            'customer_type' =>
                'corporate',

            'is_active' =>
                true,
        ]);

        $this->createCustomer([
            'name' =>
                'Karim Retail Store',

            'code' =>
                'CUS-KARIM',

            'customer_type' =>
                'retail',

            'is_active' =>
                false,
        ]);

        $response = $this->getJson(
            '/api/customers'
            . '?search=rahim'
            . '&customer_type=corporate'
            . '&is_active=1'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.pagination.total',
                1
            )
            ->assertJsonPath(
                'data.customers.0.name',
                'Rahim Enterprise'
            );
    }

    public function test_user_cannot_assign_customer_to_unassigned_branch(): void
    {
        $unassignedBranch =
            Branch::query()->create([
                'company_id' =>
                    $this->company->id,

                'name' =>
                    'Unassigned Branch',

                'code' =>
                    'UNASSIGNED-BRANCH',

                'email' =>
                    'unassigned@deshsolar.com',

                'phone' =>
                    '01910000000',

                'address' =>
                    'Chattogram',

                'is_active' =>
                    true,
            ]);

        $response = $this->postJson(
            '/api/customers',
            [
                'branch_id' =>
                    $unassignedBranch->id,

                'name' =>
                    'Unauthorized Branch Customer',

                'code' =>
                    'CUS-NO-BRANCH',

                'customer_type' =>
                    'retail',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'branch_id',
            ]);
    }

    public function test_company_user_cannot_access_customer_from_another_company(): void
    {
        $otherCompany =
            Company::query()->create([
                'name' =>
                    'Other Company',

                'code' =>
                    'OTHER-COMPANY',

                'email' =>
                    'info@othercompany.com',

                'phone' =>
                    '01900000000',

                'is_active' =>
                    true,
            ]);

        $otherBranch =
            Branch::query()->create([
                'company_id' =>
                    $otherCompany->id,

                'name' =>
                    'Other Branch',

                'code' =>
                    'OTHER-BRANCH',

                'email' =>
                    'branch@othercompany.com',

                'phone' =>
                    '01900000001',

                'address' =>
                    'Chattogram',

                'is_active' =>
                    true,
            ]);

        $otherCustomer =
            Customer::query()->create([
                'company_id' =>
                    $otherCompany->id,

                'branch_id' =>
                    $otherBranch->id,

                'name' =>
                    'Other Company Customer',

                'code' =>
                    'OTHER-CUSTOMER',

                'customer_type' =>
                    'corporate',

                'billing_country' =>
                    'Bangladesh',

                'shipping_country' =>
                    'Bangladesh',

                'payment_term_days' =>
                    0,

                'credit_limit' =>
                    0,

                'opening_balance' =>
                    0,

                'opening_balance_type' =>
                    'receivable',

                'is_active' =>
                    true,
            ]);

        $response = $this->getJson(
            "/api/customers/{$otherCustomer->id}"
        );

        $response->assertForbidden();

        $listResponse = $this->getJson(
            '/api/customers'
        );

        $listResponse
            ->assertOk()
            ->assertJsonMissing([
                'id' =>
                    $otherCustomer->id,

                'name' =>
                    'Other Company Customer',
            ]);
    }

    public function test_viewer_can_view_but_cannot_modify_customer(): void
    {
        $viewer =
            User::query()->create([
                'company_id' =>
                    $this->company->id,

                'name' =>
                    'Customer Viewer',

                'email' =>
                    'viewer@deshsolar.com',

                'password' =>
                    'password',

                'is_active' =>
                    true,
            ]);

        $viewer
            ->branches()
            ->attach(
                $this->branch->id,
                [
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

        $viewerRole =
            Role::query()->create([
                'company_id' =>
                    $this->company->id,

                'name' =>
                    'Customer Viewer',

                'code' =>
                    'TEST-CUSTOMER-VIEWER',

                'description' =>
                    'Read-only customer role.',

                'is_system' =>
                    false,

                'is_active' =>
                    true,
            ]);

        $viewPermission =
            Permission::query()
                ->where(
                    'code',
                    'customer.view'
                )
                ->firstOrFail();

        $viewerRole
            ->permissions()
            ->sync([
                $viewPermission->id,
            ]);

        $viewer
            ->roles()
            ->attach(
                $viewerRole->id,
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

        $customer =
            $this->createCustomer([
                'name' =>
                    'Viewer Customer',

                'code' =>
                    'CUS-VIEWER',
            ]);

        Sanctum::actingAs(
            $viewer
        );

        $this->getJson(
            '/api/customers'
        )->assertOk();

        $this->getJson(
            "/api/customers/{$customer->id}"
        )->assertOk();

        $this->postJson(
            '/api/customers',
            [
                'branch_id' =>
                    $this->branch->id,

                'name' =>
                    'Forbidden Customer',

                'code' =>
                    'CUS-FORBIDDEN',

                'customer_type' =>
                    'retail',
            ]
        )->assertForbidden();

        $this->patchJson(
            "/api/customers/{$customer->id}",
            [
                'name' =>
                    'Forbidden Update',
            ]
        )->assertForbidden();

        $this->deleteJson(
            "/api/customers/{$customer->id}"
        )->assertForbidden();
    }

    public function test_company_admin_can_soft_delete_and_restore_customer(): void
    {
        $customer =
            $this->createCustomer([
                'name' =>
                    'Delete Customer',

                'code' =>
                    'CUS-DELETE',
            ]);

        $deleteResponse =
            $this->deleteJson(
                "/api/customers/{$customer->id}"
            );

        $deleteResponse
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'message',
                'Customer deleted successfully.'
            );

        $this->assertSoftDeleted(
            'customers',
            [
                'id' =>
                    $customer->id,
            ]
        );

        $restoreResponse =
            $this->postJson(
                "/api/customers/{$customer->id}/restore"
            );

        $restoreResponse
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'message',
                'Customer restored successfully.'
            )
            ->assertJsonPath(
                'data.customer.id',
                $customer->id
            );

        $this->assertDatabaseHas(
            'customers',
            [
                'id' =>
                    $customer->id,

                'deleted_at' =>
                    null,

                'updated_by' =>
                    $this->companyAdmin->id,
            ]
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createCustomer(
        array $overrides = []
    ): Customer {
        return Customer::query()->create([
            'company_id' =>
                $this->company->id,

            'branch_id' =>
                $this->branch->id,

            'name' =>
                'Default Customer',

            'code' =>
                'CUS-'
                . strtoupper(
                    fake()->unique()->bothify(
                        '####'
                    )
                ),

            'business_name' =>
                null,

            'customer_type' =>
                'retail',

            'email' =>
                null,

            'phone' =>
                null,

            'alternate_phone' =>
                null,

            'website' =>
                null,

            'tax_identification_number' =>
                null,

            'trade_license_number' =>
                null,

            'billing_address_line_1' =>
                null,

            'billing_address_line_2' =>
                null,

            'billing_city' =>
                null,

            'billing_district' =>
                null,

            'billing_postal_code' =>
                null,

            'billing_country' =>
                'Bangladesh',

            'shipping_address_line_1' =>
                null,

            'shipping_address_line_2' =>
                null,

            'shipping_city' =>
                null,

            'shipping_district' =>
                null,

            'shipping_postal_code' =>
                null,

            'shipping_country' =>
                'Bangladesh',

            'payment_term_days' =>
                0,

            'credit_limit' =>
                0,

            'opening_balance' =>
                0,

            'opening_balance_type' =>
                'receivable',

            'notes' =>
                null,

            'is_active' =>
                true,

            'created_by' =>
                $this->companyAdmin->id,

            'updated_by' =>
                $this->companyAdmin->id,

            ...$overrides,
        ]);
    }
}