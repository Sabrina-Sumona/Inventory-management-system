<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'supplier_financial_settings',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('supplier_id')
                    ->unique()
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'currency_code',
                    3
                )->default('BDT');

                $table->string(
                    'default_payment_method',
                    30
                )->default('bank_transfer');

                $table->unsignedSmallInteger(
                    'payment_term_days'
                )->default(0);

                $table->decimal(
                    'credit_limit',
                    18,
                    2
                )->default(0);

                $table->boolean(
                    'allow_credit_purchase'
                )->default(false);

                $table->boolean(
                    'block_purchase_on_credit_limit'
                )->default(true);

                $table->decimal(
                    'default_purchase_discount_percent',
                    5,
                    2
                )->default(0);

                $table->boolean(
                    'is_tax_applicable'
                )->default(false);

                $table->decimal(
                    'default_tax_percent',
                    5,
                    2
                )->default(0);

                $table->boolean(
                    'is_withholding_tax_applicable'
                )->default(false);

                $table->decimal(
                    'withholding_tax_percent',
                    5,
                    2
                )->default(0);

                $table->string(
                    'purchase_price_basis',
                    30
                )->default('exclusive_of_tax');

                $table->string(
                    'default_purchase_order_term',
                    50
                )->default('standard');

                $table->text(
                    'payment_instruction'
                )->nullable();

                $table->text(
                    'notes'
                )->nullable();

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->index([
                    'currency_code',
                    'is_active',
                ]);

                $table->index(
                    'default_payment_method'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'supplier_financial_settings'
        );
    }
};