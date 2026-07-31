<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
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

            $table->string('name', 150);
            $table->string('code', 50);
            $table->string('business_name', 150)->nullable();

            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('alternate_phone', 30)->nullable();
            $table->string('website', 255)->nullable();

            $table->string('tax_identification_number', 100)->nullable();
            $table->string('trade_license_number', 100)->nullable();

            $table->string('address_line_1', 255)->nullable();
            $table->string('address_line_2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('Bangladesh');

            $table
                ->unsignedSmallInteger('payment_term_days')
                ->default(0);

            $table
                ->decimal('credit_limit', 18, 2)
                ->default(0);

            $table
                ->decimal('opening_balance', 18, 2)
                ->default(0);

            $table
                ->enum('opening_balance_type', [
                    'payable',
                    'receivable',
                ])
                ->default('payable');

            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

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

            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};