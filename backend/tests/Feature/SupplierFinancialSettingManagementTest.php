<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierFinancialSetting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupplierFinancialSettingManagementTest extends TestCase
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
            'description' =>
                'Test company administrator.',
            'is_system' => false,
            'is_active' => true,
        ]);

        $permissionIds = Permission::query()
            ->whereIn('code', [
                'supplier.view',
                'supplier.create',
                'supplier.update',
                'supplier.delete',

                'supplier-financial-setting.view',
                'supplier-financial-setting.create',
                'supplier-financial-setting.update',
                'supplier-financial-setting.delete',
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
            'business_name' =>
                'Walton Hi-Tech Industries PLC',
            'email' => 'supplier@walton.com',
            'phone' => '01700000002',
            'country' => 'Bangladesh',
            'payment_term_days' => 30,
            'credit_limit' => 1000000,
            'opening_balance' => 0,
            'opening_balance_type' => 'payable',
            'is_active' => true,
            'created_by' =>
                $this->companyAdmin->id,
            'updated_by' =>
                $this->companyAdmin->id,
        ]);

        Sanctum::actingAs(
            $this->companyAdmin
        );
    }

    public function test_authorized_user_can_list_supplier_financial_settings(): void
    {
        $setting =
            $this->createFinancialSetting();

        $response = $this->getJson(
            '/api/supplier-financial-settings'
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
                'data.supplier_financial_settings.0.id',
                $setting->id
            )
            ->assertJsonPath(
                'data.supplier_financial_settings.0.supplier_id',
                $this->supplier->id
            )
            ->assertJsonPath(
                'data.supplier_financial_settings.0.currency_code',
                'BDT'
            );
    }

    public function test_authorized_user_can_create_supplier_financial_setting(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'currency_code' => 'bdt',
            'default_payment_method' =>
                'bank_transfer',
            'payment_term_days' => 45,
            'credit_limit' => 500000,
            'allow_credit_purchase' => true,
            'block_purchase_on_credit_limit' =>
                true,
            'default_purchase_discount_percent' =>
                5,
            'is_tax_applicable' => true,
            'default_tax_percent' => 15,
            'is_withholding_tax_applicable' =>
                true,
            'withholding_tax_percent' => 5,
            'purchase_price_basis' =>
                'exclusive_of_tax',
            'default_purchase_order_term' =>
                'credit',
            'payment_instruction' =>
                'Pay through the approved bank account.',
            'notes' =>
                'Preferred supplier financial terms.',
            'is_active' => true,
        ];

        $response = $this->postJson(
            '/api/supplier-financial-settings',
            $payload
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.supplier_financial_setting.supplier_id',
                $this->supplier->id
            )
            ->assertJsonPath(
                'data.supplier_financial_setting.currency_code',
                'BDT'
            )
            ->assertJsonPath(
                'data.supplier_financial_setting.allow_credit_purchase',
                true
            )
            ->assertJsonPath(
                'data.supplier_financial_setting.default_tax_percent',
                '15.00'
            );

        $this->assertDatabaseHas(
            'supplier_financial_settings',
            [
                'supplier_id' =>
                    $this->supplier->id,
                'currency_code' => 'BDT',
                'default_payment_method' =>
                    'bank_transfer',
                'payment_term_days' => 45,
                'allow_credit_purchase' =>
                    true,
                'is_tax_applicable' => true,
                'is_active' => true,
            ]
        );
    }

    public function test_supplier_can_have_only_one_financial_setting(): void
    {
        $this->createFinancialSetting();

        $response = $this->postJson(
            '/api/supplier-financial-settings',
            [
                'supplier_id' =>
                    $this->supplier->id,
                'currency_code' => 'BDT',
                'default_payment_method' =>
                    'cash',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'supplier_id',
            ]);

        $this->assertDatabaseCount(
            'supplier_financial_settings',
            1
        );
    }

    public function test_authorized_user_can_view_supplier_financial_setting(): void
    {
        $setting =
            $this->createFinancialSetting();

        $response = $this->getJson(
            "/api/supplier-financial-settings/{$setting->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.supplier_financial_setting.id',
                $setting->id
            )
            ->assertJsonPath(
                'data.supplier_financial_setting.supplier.id',
                $this->supplier->id
            )
            ->assertJsonPath(
                'data.supplier_financial_setting.default_payment_method',
                'bank_transfer'
            );
    }

    public function test_authorized_user_can_update_supplier_financial_setting(): void
    {
        $setting =
            $this->createFinancialSetting();

        $response = $this->patchJson(
            "/api/supplier-financial-settings/{$setting->id}",
            [
                'currency_code' => 'usd',
                'default_payment_method' =>
                    'credit',
                'payment_term_days' => 60,
                'credit_limit' => 750000,
                'allow_credit_purchase' =>
                    true,
                'default_purchase_discount_percent' =>
                    7.5,
                'is_tax_applicable' => true,
                'default_tax_percent' => 10,
                'default_purchase_order_term' =>
                    'credit',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.supplier_financial_setting.currency_code',
                'USD'
            )
            ->assertJsonPath(
                'data.supplier_financial_setting.payment_term_days',
                60
            )
            ->assertJsonPath(
                'data.supplier_financial_setting.credit_limit',
                '750000.00'
            );

        $this->assertDatabaseHas(
            'supplier_financial_settings',
            [
                'id' => $setting->id,
                'currency_code' => 'USD',
                'default_payment_method' =>
                    'credit',
                'payment_term_days' => 60,
                'allow_credit_purchase' =>
                    true,
                'is_tax_applicable' => true,
            ]
        );
    }

    public function test_authorized_user_can_delete_supplier_financial_setting(): void
    {
        $setting =
            $this->createFinancialSetting();

        $response = $this->deleteJson(
            "/api/supplier-financial-settings/{$setting->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data',
                null
            );

        $this->assertDatabaseMissing(
            'supplier_financial_settings',
            [
                'id' => $setting->id,
            ]
        );
    }

    public function test_credit_purchase_requires_positive_credit_limit(): void
    {
        $response = $this->postJson(
            '/api/supplier-financial-settings',
            [
                'supplier_id' =>
                    $this->supplier->id,
                'allow_credit_purchase' =>
                    true,
                'credit_limit' => 0,
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
            '/api/supplier-financial-settings',
            [
                'supplier_id' =>
                    $this->supplier->id,
                'is_tax_applicable' => false,
                'default_tax_percent' => 15,
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
            '/api/supplier-financial-settings',
            [
                'supplier_id' =>
                    $this->supplier->id,
                'is_withholding_tax_applicable' =>
                    false,
                'withholding_tax_percent' => 5,
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'withholding_tax_percent',
            ]);
    }

    public function test_user_without_permission_cannot_list_supplier_financial_settings(): void
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
            '/api/supplier-financial-settings'
        );

        $response->assertForbidden();
    }

    public function test_user_cannot_create_financial_setting_for_inaccessible_supplier(): void
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
                'company_id' =>
                    $otherCompany->id,
                'name' => 'Other Branch',
                'code' => 'OTHER-BRANCH',
                'email' =>
                    'branch@other.com',
                'phone' => '01900000001',
                'address' => 'Chattogram',
                'is_active' => true,
            ]);

        $otherSupplier =
            Supplier::query()->create([
                'company_id' =>
                    $otherCompany->id,
                'branch_id' =>
                    $otherBranch->id,
                'name' => 'Other Supplier',
                'code' => 'OTHER-SUPPLIER',
                'country' => 'Bangladesh',
                'payment_term_days' => 0,
                'credit_limit' => 0,
                'opening_balance' => 0,
                'opening_balance_type' =>
                    'payable',
                'is_active' => true,
            ]);

        $response = $this->postJson(
            '/api/supplier-financial-settings',
            [
                'supplier_id' =>
                    $otherSupplier->id,
                'currency_code' => 'BDT',
                'default_payment_method' =>
                    'cash',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'supplier_id',
            ]);
    }

    public function test_list_can_filter_financial_settings_by_supplier(): void
    {
        $firstSetting =
            $this->createFinancialSetting();

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
                'opening_balance_type' =>
                    'payable',
                'is_active' => true,
            ]);

        SupplierFinancialSetting::query()->create([
            'supplier_id' =>
                $secondSupplier->id,
            'currency_code' => 'USD',
            'default_payment_method' =>
                'cash',
            'payment_term_days' => 0,
            'credit_limit' => 0,
            'allow_credit_purchase' =>
                false,
            'block_purchase_on_credit_limit' =>
                true,
            'default_purchase_discount_percent' =>
                0,
            'is_tax_applicable' => false,
            'default_tax_percent' => 0,
            'is_withholding_tax_applicable' =>
                false,
            'withholding_tax_percent' => 0,
            'purchase_price_basis' =>
                'exclusive_of_tax',
            'default_purchase_order_term' =>
                'standard',
            'is_active' => true,
            'created_by' =>
                $this->companyAdmin->id,
            'updated_by' =>
                $this->companyAdmin->id,
        ]);

        $response = $this->getJson(
            '/api/supplier-financial-settings?supplier_id=' .
            $this->supplier->id
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.pagination.total',
                1
            )
            ->assertJsonPath(
                'data.supplier_financial_settings.0.id',
                $firstSetting->id
            )
            ->assertJsonPath(
                'data.supplier_financial_settings.0.supplier_id',
                $this->supplier->id
            );
    }

    private function createFinancialSetting(): SupplierFinancialSetting
    {
        return SupplierFinancialSetting::query()
            ->create([
                'supplier_id' =>
                    $this->supplier->id,
                'currency_code' => 'BDT',
                'default_payment_method' =>
                    'bank_transfer',
                'payment_term_days' => 30,
                'credit_limit' => 500000,
                'allow_credit_purchase' =>
                    true,
                'block_purchase_on_credit_limit' =>
                    true,
                'default_purchase_discount_percent' =>
                    5,
                'is_tax_applicable' => true,
                'default_tax_percent' => 15,
                'is_withholding_tax_applicable' =>
                    true,
                'withholding_tax_percent' => 5,
                'purchase_price_basis' =>
                    'exclusive_of_tax',
                'default_purchase_order_term' =>
                    'credit',
                'payment_instruction' =>
                    'Pay through bank transfer.',
                'notes' =>
                    'Test financial setting.',
                'is_active' => true,
                'created_by' =>
                    $this->companyAdmin->id,
                'updated_by' =>
                    $this->companyAdmin->id,
            ]);
    }
}