<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'customers',
            function (
                Blueprint $table
            ): void {
                $table->id();

                $table
                    ->foreignId('company_id')
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $table
                    ->foreignId('branch_id')
                    ->nullable()
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $table
                    ->string('name', 150);

                $table
                    ->string('code', 50);

                $table
                    ->string(
                        'business_name',
                        150
                    )
                    ->nullable();

                $table
                    ->enum(
                        'customer_type',
                        [
                            'retail',
                            'wholesale',
                            'corporate',
                            'dealer',
                            'government',
                            'other',
                        ]
                    )
                    ->default('retail');

                $table
                    ->string('email', 150)
                    ->nullable();

                $table
                    ->string('phone', 30)
                    ->nullable();

                $table
                    ->string(
                        'alternate_phone',
                        30
                    )
                    ->nullable();

                $table
                    ->string('website', 255)
                    ->nullable();

                $table
                    ->string(
                        'tax_identification_number',
                        100
                    )
                    ->nullable();

                $table
                    ->string(
                        'trade_license_number',
                        100
                    )
                    ->nullable();

                $table
                    ->string(
                        'billing_address_line_1',
                        255
                    )
                    ->nullable();

                $table
                    ->string(
                        'billing_address_line_2',
                        255
                    )
                    ->nullable();

                $table
                    ->string(
                        'billing_city',
                        100
                    )
                    ->nullable();

                $table
                    ->string(
                        'billing_district',
                        100
                    )
                    ->nullable();

                $table
                    ->string(
                        'billing_postal_code',
                        20
                    )
                    ->nullable();

                $table
                    ->string(
                        'billing_country',
                        100
                    )
                    ->default('Bangladesh');

                $table
                    ->string(
                        'shipping_address_line_1',
                        255
                    )
                    ->nullable();

                $table
                    ->string(
                        'shipping_address_line_2',
                        255
                    )
                    ->nullable();

                $table
                    ->string(
                        'shipping_city',
                        100
                    )
                    ->nullable();

                $table
                    ->string(
                        'shipping_district',
                        100
                    )
                    ->nullable();

                $table
                    ->string(
                        'shipping_postal_code',
                        20
                    )
                    ->nullable();

                $table
                    ->string(
                        'shipping_country',
                        100
                    )
                    ->default('Bangladesh');

                $table
                    ->unsignedSmallInteger(
                        'payment_term_days'
                    )
                    ->default(0);

                $table
                    ->decimal(
                        'credit_limit',
                        18,
                        2
                    )
                    ->default(0);

                $table
                    ->decimal(
                        'opening_balance',
                        18,
                        2
                    )
                    ->default(0);

                $table
                    ->enum(
                        'opening_balance_type',
                        [
                            'receivable',
                            'payable',
                        ]
                    )
                    ->default('receivable');

                $table
                    ->text('notes')
                    ->nullable();

                $table
                    ->boolean('is_active')
                    ->default(true);

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $table
                    ->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->unique([
                    'company_id',
                    'code',
                ]);

                $table->index([
                    'company_id',
                    'is_active',
                ]);

                $table->index([
                    'company_id',
                    'name',
                ]);

                $table->index([
                    'company_id',
                    'customer_type',
                ]);

                $table->index(
                    'branch_id'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'customers'
        );
    }
};