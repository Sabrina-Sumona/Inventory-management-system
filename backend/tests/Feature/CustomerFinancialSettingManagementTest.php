<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerFinancialSetting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerFinancialSettingManagementTest extends TestCase
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
                    'Customer Financial Setting Admin',

                'code' =>
                    'TEST-CUSTOMER-FINANCIAL-SETTING-ADMIN',

                'description' =>
                    'Customer financial setting management test role.',

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
                        'customer-financial-setting.view',
                        'customer-financial-setting.create',
                        'customer-financial-setting.update',
                        'customer-financial-setting.delete',
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
            $this->createCustomer();

        Sanctum::actingAs(
            $this->companyAdmin
        );
    }

    public function test_guest_cannot_access_customer_financial_settings(): void
    {
        auth()->forgetGuards();

        $this->getJson(
            '/api/customer-financial-settings'
        )->assertUnauthorized();
    }

    public function test_authorized_user_can_list_customer_financial_settings(): void
    {
        $this->createFinancialSetting([
            'currency_code' =>
                'BDT',

            'default_payment_method' =>
                'bank_transfer',

            'allow_credit_sale' =>
                true,

            'credit_limit' =>
                250000,
        ]);

        $response = $this->getJson(
            '/api/customer-financial-settings'
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
                'data.customer_financial_settings.0.customer.name',
                'Rahim Traders'
            )
            ->assertJsonPath(
                'data.customer_financial_settings.0.currency_code',
                'BDT'
            )
            ->assertJsonPath(
                'data.customer_financial_settings.0.allow_credit_sale',
                true
            );
    }

    public function test_authorized_user_can_create_customer_financial_setting(): void
    {
        $payload = [
            'customer_id' =>
                $this->customer->id,

            'currency_code' =>
                'bdt',

            'default_payment_method' =>
                'bank_transfer',

            'payment_term_days' =>
                30,

            'credit_limit' =>
                500000,

            'allow_credit_sale' =>
                true,

            'block_sale_on_credit_limit' =>
                true,

            'default_sales_discount_percent' =>
                5,

            'is_tax_applicable' =>
                true,

            'default_tax_percent' =>
                15,

            'is_withholding_tax_applicable' =>
                true,

            'withholding_tax_percent' =>
                5,

            'sales_price_basis' =>
                'exclusive_of_tax',

            'default_sales_order_term' =>
                'credit',

            'payment_instruction' =>
                'Payment through the approved bank account.',

            'notes' =>
                'Important corporate customer.',

            'is_active' =>
                true,
        ];

        $response = $this->postJson(
            '/api/customer-financial-settings',
            $payload
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.customer_financial_setting.customer_id',
                $this->customer->id
            )
            ->assertJsonPath(
                'data.customer_financial_setting.currency_code',
                'BDT'
            )
            ->assertJsonPath(
                'data.customer_financial_setting.payment_term_days',
                30
            )
            ->assertJsonPath(
                'data.customer_financial_setting.allow_credit_sale',
                true
            )
            ->assertJsonPath(
                'data.customer_financial_setting.is_tax_applicable',
                true
            );

        $this->assertDatabaseHas(
            'customer_financial_settings',
            [
                'customer_id' =>
                    $this->customer->id,

                'currency_code' =>
                    'BDT',

                'default_payment_method' =>
                    'bank_transfer',

                'payment_term_days' =>
                    30,

                'allow_credit_sale' =>
                    true,

                'is_tax_applicable' =>
                    true,

                'created_by' =>
                    $this->companyAdmin->id,

                'updated_by' =>
                    $this->companyAdmin->id,
            ]
        );
    }

    public function test_customer_can_have_only_one_financial_setting(): void
    {
        $this->createFinancialSetting();

        $response = $this->postJson(
            '/api/customer-financial-settings',
            [
                'customer_id' =>
                    $this->customer->id,

                'currency_code' =>
                    'BDT',

                'allow_credit_sale' =>
                    false,

                'credit_limit' =>
                    0,
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'customer_id',
            ]);

        $this->assertDatabaseCount(
            'customer_financial_settings',
            1
        );
    }

    public function test_authorized_user_can_view_customer_financial_setting(): void
    {
        $financialSetting =
            $this->createFinancialSetting([
                'payment_term_days' =>
                    45,

                'default_sales_order_term' =>
                    'partial_advance',
            ]);

        $response = $this->getJson(
            "/api/customer-financial-settings/{$financialSetting->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.customer_financial_setting.id',
                $financialSetting->id
            )
            ->assertJsonPath(
                'data.customer_financial_setting.customer.code',
                'CUS-RAHIM'
            )
            ->assertJsonPath(
                'data.customer_financial_setting.payment_term_days',
                45
            )
            ->assertJsonPath(
                'data.customer_financial_setting.default_sales_order_term',
                'partial_advance'
            );
    }

    public function test_authorized_user_can_update_customer_financial_setting(): void
    {
        $financialSetting =
            $this->createFinancialSetting([
                'allow_credit_sale' =>
                    false,

                'credit_limit' =>
                    0,

                'is_tax_applicable' =>
                    false,

                'default_tax_percent' =>
                    0,
            ]);

        $response = $this->patchJson(
            "/api/customer-financial-settings/{$financialSetting->id}",
            [
                'currency_code' =>
                    'usd',

                'default_payment_method' =>
                    'credit',

                'payment_term_days' =>
                    60,

                'allow_credit_sale' =>
                    true,

                'credit_limit' =>
                    750000,

                'block_sale_on_credit_limit' =>
                    false,

                'default_sales_discount_percent' =>
                    7.5,

                'is_tax_applicable' =>
                    true,

                'default_tax_percent' =>
                    10,

                'sales_price_basis' =>
                    'inclusive_of_tax',

                'default_sales_order_term' =>
                    'credit',

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
                'data.customer_financial_setting.currency_code',
                'USD'
            )
            ->assertJsonPath(
                'data.customer_financial_setting.payment_term_days',
                60
            )
            ->assertJsonPath(
                'data.customer_financial_setting.allow_credit_sale',
                true
            )
            ->assertJsonPath(
                'data.customer_financial_setting.block_sale_on_credit_limit',
                false
            )
            ->assertJsonPath(
                'data.customer_financial_setting.is_tax_applicable',
                true
            )
            ->assertJsonPath(
                'data.customer_financial_setting.is_active',
                false
            );

        $this->assertDatabaseHas(
            'customer_financial_settings',
            [
                'id' =>
                    $financialSetting->id,

                'currency_code' =>
                    'USD',

                'default_payment_method' =>
                    'credit',

                'payment_term_days' =>
                    60,

                'allow_credit_sale' =>
                    true,

                'block_sale_on_credit_limit' =>
                    false,

                'is_tax_applicable' =>
                    true,

                'is_active' =>
                    false,

                'updated_by' =>
                    $this->companyAdmin->id,
            ]
        );
    }

    public function test_authorized_user_can_delete_customer_financial_setting(): void
    {
        $financialSetting =
            $this->createFinancialSetting();

        $response = $this->deleteJson(
            "/api/customer-financial-settings/{$financialSetting->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'message',
                'Customer financial setting deleted successfully.'
            );

        $this->assertDatabaseMissing(
            'customer_financial_settings',
            [
                'id' =>
                    $financialSetting->id,
            ]
        );
    }

    public function test_credit_sale_requires_positive_credit_limit(): void
    {
        $response = $this->postJson(
            '/api/customer-financial-settings',
            [
                'customer_id' =>
                    $this->customer->id,

                'allow_credit_sale' =>
                    true,

                'credit_limit' =>
                    0,
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'credit_limit',
            ]);
    }

    public function test_credit_limit_must_be_zero_when_credit_sale_is_disabled(): void
    {
        $response = $this->postJson(
            '/api/customer-financial-settings',
            [
                'customer_id' =>
                    $this->customer->id,

                'allow_credit_sale' =>
                    false,

                'credit_limit' =>
                    100000,
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'credit_limit',
            ]);
    }

    public function test_tax_percentage_must_be_zero_when_tax_is_not_applicable(): void
    {
        $response = $this->postJson(
            '/api/customer-financial-settings',
            [
                'customer_id' =>
                    $this->customer->id,

                'allow_credit_sale' =>
                    false,

                'credit_limit' =>
                    0,

                'is_tax_applicable' =>
                    false,

                'default_tax_percent' =>
                    15,
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'default_tax_percent',
            ]);
    }

    public function test_withholding_tax_percentage_must_be_zero_when_not_applicable(): void
    {
        $response = $this->postJson(
            '/api/customer-financial-settings',
            [
                'customer_id' =>
                    $this->customer->id,

                'allow_credit_sale' =>
                    false,

                'credit_limit' =>
                    0,

                'is_withholding_tax_applicable' =>
                    false,

                'withholding_tax_percent' =>
                    5,
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'withholding_tax_percent',
            ]);
    }

    public function test_user_without_permission_cannot_access_customer_financial_settings(): void
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
            '/api/customer-financial-settings'
        )->assertForbidden();

        $this->postJson(
            '/api/customer-financial-settings',
            [
                'customer_id' =>
                    $this->customer->id,

                'allow_credit_sale' =>
                    false,

                'credit_limit' =>
                    0,
            ]
        )->assertForbidden();
    }

    public function test_user_cannot_create_financial_setting_for_inaccessible_customer(): void
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

                'city' =>
                    'Chattogram',

                'district' =>
                    'Chattogram',

                'is_active' =>
                    true,
            ]);

        $otherCustomer =
            $this->createCustomer([
                'company_id' =>
                    $otherCompany->id,

                'branch_id' =>
                    $otherBranch->id,

                'name' =>
                    'Other Customer',

                'code' =>
                    'OTHER-CUSTOMER',

                'business_name' =>
                    'Other Customer Ltd.',
            ]);

        $response = $this->postJson(
            '/api/customer-financial-settings',
            [
                'customer_id' =>
                    $otherCustomer->id,

                'currency_code' =>
                    'BDT',

                'allow_credit_sale' =>
                    false,

                'credit_limit' =>
                    0,
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'customer_id',
            ]);

        $this->assertDatabaseMissing(
            'customer_financial_settings',
            [
                'customer_id' =>
                    $otherCustomer->id,
            ]
        );
    }

    public function test_list_can_filter_customer_financial_settings(): void
    {
        $secondCustomer =
            $this->createCustomer([
                'name' =>
                    'Second Customer',

                'code' =>
                    'CUS-SECOND',

                'business_name' =>
                    'Second Customer Ltd.',

                'customer_type' =>
                    'retail',
            ]);

        $this->createFinancialSetting([
            'customer_id' =>
                $this->customer->id,

            'currency_code' =>
                'BDT',

            'default_payment_method' =>
                'bank_transfer',

            'allow_credit_sale' =>
                true,

            'credit_limit' =>
                300000,

            'is_tax_applicable' =>
                true,

            'default_tax_percent' =>
                15,

            'is_active' =>
                true,
        ]);

        $this->createFinancialSetting([
            'customer_id' =>
                $secondCustomer->id,

            'currency_code' =>
                'USD',

            'default_payment_method' =>
                'cash',

            'allow_credit_sale' =>
                false,

            'credit_limit' =>
                0,

            'is_tax_applicable' =>
                false,

            'default_tax_percent' =>
                0,

            'is_active' =>
                false,
        ]);

        $response = $this->getJson(
            '/api/customer-financial-settings'
            . '?customer_id='
            . $this->customer->id
            . '&currency_code=bdt'
            . '&default_payment_method=bank_transfer'
            . '&allow_credit_sale=1'
            . '&is_tax_applicable=1'
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
                'data.customer_financial_settings.0.customer_id',
                $this->customer->id
            )
            ->assertJsonPath(
                'data.customer_financial_settings.0.currency_code',
                'BDT'
            )
            ->assertJsonPath(
                'data.customer_financial_settings.0.default_payment_method',
                'bank_transfer'
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

            ...$overrides,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createFinancialSetting(
        array $overrides = []
    ): CustomerFinancialSetting {
        return CustomerFinancialSetting::query()->create([
            'customer_id' =>
                $this->customer->id,

            'currency_code' =>
                'BDT',

            'default_payment_method' =>
                'bank_transfer',

            'payment_term_days' =>
                30,

            'credit_limit' =>
                0,

            'allow_credit_sale' =>
                false,

            'block_sale_on_credit_limit' =>
                true,

            'default_sales_discount_percent' =>
                0,

            'is_tax_applicable' =>
                false,

            'default_tax_percent' =>
                0,

            'is_withholding_tax_applicable' =>
                false,

            'withholding_tax_percent' =>
                0,

            'sales_price_basis' =>
                'exclusive_of_tax',

            'default_sales_order_term' =>
                'standard',

            'payment_instruction' =>
                null,

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