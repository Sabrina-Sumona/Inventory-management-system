<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerContactManagementTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Branch $branch;

    private User $companyAdmin;

    private Customer $customer;

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

                'code' =>
                    'DS-HO',

                'email' =>
                    'headoffice@deshsolar.com',

                'phone' =>
                    '01700000001',

                'address' =>
                    'Dhaka',

                'city' =>
                    'Dhaka',

                'district' =>
                    'Dhaka',

                'is_head_office' =>
                    true,

                'is_active' =>
                    true,
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

                'is_active' =>
                    true,
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

        $role =
            Role::query()->create([
                'company_id' =>
                    $this->company->id,

                'name' =>
                    'Customer Contact Admin',

                'code' =>
                    'TEST-CUSTOMER-CONTACT-ADMIN',

                'description' =>
                    'Customer contact management test role.',

                'is_system' =>
                    false,

                'is_active' =>
                    true,
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
                        'customer-contact.view',
                        'customer-contact.create',
                        'customer-contact.update',
                        'customer-contact.delete',
                    ]
                )
                ->pluck('id')
                ->all();

        $role
            ->permissions()
            ->sync(
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

        $this->customer =
            Customer::query()->create([
                'company_id' =>
                    $this->company->id,

                'branch_id' =>
                    $this->branch->id,

                'name' =>
                    'Rahim Traders',

                'code' =>
                    'CUS-RAHIM',

                'business_name' =>
                    'Rahim Traders Ltd.',

                'customer_type' =>
                    'corporate',

                'billing_country' =>
                    'Bangladesh',

                'shipping_country' =>
                    'Bangladesh',

                'payment_term_days' =>
                    30,

                'credit_limit' =>
                    500000,

                'opening_balance' =>
                    0,

                'opening_balance_type' =>
                    'receivable',

                'is_active' =>
                    true,

                'created_by' =>
                    $this->companyAdmin->id,

                'updated_by' =>
                    $this->companyAdmin->id,
            ]);

        Sanctum::actingAs(
            $this->companyAdmin
        );
    }

    public function test_guest_cannot_access_customer_contacts(): void
    {
        auth()->forgetGuards();

        $response = $this->getJson(
            '/api/customer-contacts'
        );

        $response->assertUnauthorized();
    }

    public function test_authorized_user_can_list_customer_contacts(): void
    {
        $this->createCustomerContact([
            'name' =>
                'Rahim Ahmed',

            'designation' =>
                'Managing Director',

            'contact_type' =>
                'management',

            'email' =>
                'rahim@example.com',

            'is_primary' =>
                true,
        ]);

        $response = $this->getJson(
            '/api/customer-contacts'
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
                'data.customer_contacts.0.name',
                'Rahim Ahmed'
            )
            ->assertJsonPath(
                'data.customer_contacts.0.customer.name',
                'Rahim Traders'
            );
    }

    public function test_authorized_user_can_create_customer_contact(): void
    {
        $payload = [
            'customer_id' =>
                $this->customer->id,

            'name' =>
                'Karim Hasan',

            'designation' =>
                'Accounts Manager',

            'department' =>
                'Accounts',

            'contact_type' =>
                'accounts',

            'email' =>
                'karim@example.com',

            'phone' =>
                '01710000000',

            'alternate_phone' =>
                '01810000000',

            'is_primary' =>
                true,

            'is_active' =>
                true,

            'notes' =>
                'Primary accounts contact.',
        ];

        $response = $this->postJson(
            '/api/customer-contacts',
            $payload
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.customer_contact.name',
                'Karim Hasan'
            )
            ->assertJsonPath(
                'data.customer_contact.contact_type',
                'accounts'
            )
            ->assertJsonPath(
                'data.customer_contact.is_primary',
                true
            )
            ->assertJsonPath(
                'data.customer_contact.customer_id',
                $this->customer->id
            );

        $this->assertDatabaseHas(
            'customer_contacts',
            [
                'customer_id' =>
                    $this->customer->id,

                'name' =>
                    'Karim Hasan',

                'contact_type' =>
                    'accounts',

                'is_primary' =>
                    true,

                'created_by' =>
                    $this->companyAdmin->id,

                'updated_by' =>
                    $this->companyAdmin->id,
            ]
        );
    }

    public function test_creating_new_primary_contact_removes_previous_primary_status(): void
    {
        $existingPrimary =
            $this->createCustomerContact([
                'name' =>
                    'Previous Primary Contact',

                'contact_type' =>
                    'management',

                'is_primary' =>
                    true,
            ]);

        $response = $this->postJson(
            '/api/customer-contacts',
            [
                'customer_id' =>
                    $this->customer->id,

                'name' =>
                    'New Primary Contact',

                'contact_type' =>
                    'accounts',

                'is_primary' =>
                    true,

                'is_active' =>
                    true,
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.customer_contact.is_primary',
                true
            );

        $this->assertDatabaseHas(
            'customer_contacts',
            [
                'id' =>
                    $existingPrimary->id,

                'is_primary' =>
                    false,
            ]
        );

        $this->assertDatabaseHas(
            'customer_contacts',
            [
                'customer_id' =>
                    $this->customer->id,

                'name' =>
                    'New Primary Contact',

                'is_primary' =>
                    true,
            ]
        );
    }

    public function test_authorized_user_can_view_customer_contact(): void
    {
        $contact =
            $this->createCustomerContact([
                'name' =>
                    'Support Contact',

                'contact_type' =>
                    'support',
            ]);

        $response = $this->getJson(
            "/api/customer-contacts/{$contact->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.customer_contact.id',
                $contact->id
            )
            ->assertJsonPath(
                'data.customer_contact.name',
                'Support Contact'
            )
            ->assertJsonPath(
                'data.customer_contact.customer.code',
                'CUS-RAHIM'
            );
    }

    public function test_authorized_user_can_update_customer_contact(): void
    {
        $contact =
            $this->createCustomerContact([
                'name' =>
                    'Old Contact Name',

                'contact_type' =>
                    'general',

                'is_primary' =>
                    false,
            ]);

        $response = $this->patchJson(
            "/api/customer-contacts/{$contact->id}",
            [
                'name' =>
                    'Updated Contact Name',

                'designation' =>
                    'Senior Purchase Officer',

                'department' =>
                    'Purchase',

                'contact_type' =>
                    'purchase',

                'is_primary' =>
                    true,

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
                'data.customer_contact.name',
                'Updated Contact Name'
            )
            ->assertJsonPath(
                'data.customer_contact.contact_type',
                'purchase'
            )
            ->assertJsonPath(
                'data.customer_contact.is_primary',
                true
            )
            ->assertJsonPath(
                'data.customer_contact.is_active',
                false
            );

        $this->assertDatabaseHas(
            'customer_contacts',
            [
                'id' =>
                    $contact->id,

                'name' =>
                    'Updated Contact Name',

                'contact_type' =>
                    'purchase',

                'is_primary' =>
                    true,

                'is_active' =>
                    false,

                'updated_by' =>
                    $this->companyAdmin->id,
            ]
        );
    }

    public function test_updating_contact_as_primary_removes_previous_primary_status(): void
    {
        $existingPrimary =
            $this->createCustomerContact([
                'name' =>
                    'Existing Primary',

                'is_primary' =>
                    true,
            ]);

        $secondContact =
            $this->createCustomerContact([
                'name' =>
                    'Second Contact',

                'is_primary' =>
                    false,
            ]);

        $response = $this->patchJson(
            "/api/customer-contacts/{$secondContact->id}",
            [
                'is_primary' =>
                    true,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.customer_contact.is_primary',
                true
            );

        $this->assertDatabaseHas(
            'customer_contacts',
            [
                'id' =>
                    $existingPrimary->id,

                'is_primary' =>
                    false,
            ]
        );

        $this->assertDatabaseHas(
            'customer_contacts',
            [
                'id' =>
                    $secondContact->id,

                'is_primary' =>
                    true,
            ]
        );
    }

    public function test_authorized_user_can_soft_delete_customer_contact(): void
    {
        $contact =
            $this->createCustomerContact([
                'name' =>
                    'Delete Contact',
            ]);

        $response = $this->deleteJson(
            "/api/customer-contacts/{$contact->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'message',
                'Customer contact deleted successfully.'
            );

        $this->assertSoftDeleted(
            'customer_contacts',
            [
                'id' =>
                    $contact->id,
            ]
        );
    }

    public function test_authorized_user_can_restore_customer_contact(): void
    {
        $contact =
            $this->createCustomerContact([
                'name' =>
                    'Restore Contact',

                'is_primary' =>
                    false,
            ]);

        $contact->delete();

        $response = $this->postJson(
            "/api/customer-contacts/{$contact->id}/restore"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'message',
                'Customer contact restored successfully.'
            )
            ->assertJsonPath(
                'data.customer_contact.id',
                $contact->id
            );

        $this->assertDatabaseHas(
            'customer_contacts',
            [
                'id' =>
                    $contact->id,

                'deleted_at' =>
                    null,

                'updated_by' =>
                    $this->companyAdmin->id,
            ]
        );
    }

    public function test_customer_contact_requires_valid_data(): void
    {
        $response = $this->postJson(
            '/api/customer-contacts',
            [
                'customer_id' =>
                    $this->customer->id,

                'name' =>
                    '',

                'contact_type' =>
                    'invalid-type',

                'email' =>
                    'invalid-email',

                'is_primary' =>
                    'invalid',

                'is_active' =>
                    'invalid',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'contact_type',
                'email',
                'is_primary',
                'is_active',
            ]);
    }

    public function test_user_without_permission_cannot_access_customer_contacts(): void
    {
        $unauthorizedUser =
            User::query()->create([
                'company_id' =>
                    $this->company->id,

                'name' =>
                    'Unauthorized User',

                'email' =>
                    'unauthorized@deshsolar.com',

                'password' =>
                    'password',

                'is_active' =>
                    true,
            ]);

        $unauthorizedUser
            ->branches()
            ->attach(
                $this->branch->id,
                [
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

        Sanctum::actingAs(
            $unauthorizedUser
        );

        $this->getJson(
            '/api/customer-contacts'
        )->assertForbidden();

        $this->postJson(
            '/api/customer-contacts',
            [
                'customer_id' =>
                    $this->customer->id,

                'name' =>
                    'Forbidden Contact',
            ]
        )->assertForbidden();
    }

    public function test_user_cannot_create_contact_for_inaccessible_customer(): void
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
                    'Other Customer',

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

        $response = $this->postJson(
            '/api/customer-contacts',
            [
                'customer_id' =>
                    $otherCustomer->id,

                'name' =>
                    'Unauthorized Contact',

                'contact_type' =>
                    'general',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'customer_id',
            ]);
    }

    public function test_list_can_filter_customer_contacts(): void
    {
        $secondCustomer =
            Customer::query()->create([
                'company_id' =>
                    $this->company->id,

                'branch_id' =>
                    $this->branch->id,

                'name' =>
                    'Second Customer',

                'code' =>
                    'CUS-SECOND',

                'customer_type' =>
                    'retail',

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

        $this->createCustomerContact([
            'customer_id' =>
                $this->customer->id,

            'name' =>
                'Rahim Accounts Contact',

            'contact_type' =>
                'accounts',

            'is_primary' =>
                true,

            'is_active' =>
                true,
        ]);

        $this->createCustomerContact([
            'customer_id' =>
                $secondCustomer->id,

            'name' =>
                'Second Customer Contact',

            'contact_type' =>
                'sales',

            'is_primary' =>
                false,

            'is_active' =>
                true,
        ]);

        $response = $this->getJson(
            '/api/customer-contacts'
            . '?customer_id='
            . $this->customer->id
            . '&contact_type=accounts'
            . '&is_primary=1'
            . '&is_active=1'
            . '&search=rahim'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.pagination.total',
                1
            )
            ->assertJsonPath(
                'data.customer_contacts.0.name',
                'Rahim Accounts Contact'
            )
            ->assertJsonPath(
                'data.customer_contacts.0.customer_id',
                $this->customer->id
            );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createCustomerContact(
        array $overrides = []
    ): CustomerContact {
        return CustomerContact::query()->create([
            'customer_id' =>
                $this->customer->id,

            'name' =>
                'Default Customer Contact',

            'designation' =>
                null,

            'department' =>
                null,

            'contact_type' =>
                'general',

            'email' =>
                null,

            'phone' =>
                null,

            'alternate_phone' =>
                null,

            'is_primary' =>
                false,

            'is_active' =>
                true,

            'notes' =>
                null,

            'created_by' =>
                $this->companyAdmin->id,

            'updated_by' =>
                $this->companyAdmin->id,

            ...$overrides,
        ]);
    }
}