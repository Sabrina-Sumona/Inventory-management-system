<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'supplier_contacts',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('supplier_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('name');

                $table->string(
                    'designation',
                    100
                )->nullable();

                $table->string(
                    'department',
                    100
                )->nullable();

                $table->string(
                    'contact_type',
                    30
                )->default('general');

                $table->string('email')
                    ->nullable();

                $table->string(
                    'phone',
                    30
                )->nullable();

                $table->string(
                    'alternate_phone',
                    30
                )->nullable();

                $table->boolean('is_primary')
                    ->default(false);

                $table->boolean('is_active')
                    ->default(true);

                $table->text('notes')
                    ->nullable();

                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index([
                    'supplier_id',
                    'is_active',
                ]);

                $table->index([
                    'supplier_id',
                    'is_primary',
                ]);

                $table->index([
                    'supplier_id',
                    'contact_type',
                ]);

                $table->index([
                    'supplier_id',
                    'name',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'supplier_contacts'
        );
    }
};