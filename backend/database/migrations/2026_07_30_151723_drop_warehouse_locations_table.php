<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }

    public function down(): void
    {
        Schema::create('warehouse_locations', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('company_id')
                ->constrained()
                ->restrictOnDelete();

            $table
                ->foreignId('branch_id')
                ->constrained()
                ->restrictOnDelete();

            $table
                ->foreignId('warehouse_id')
                ->constrained()
                ->restrictOnDelete();

            $table
                ->foreignId('parent_id')
                ->nullable()
                ->constrained('warehouse_locations')
                ->nullOnDelete();

            $table->string('name');
            $table->string('code');
            $table->string('type')->default('location');
            $table->string('barcode')->nullable();
            $table->decimal('capacity', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'warehouse_id',
                'code',
            ]);

            $table->index('barcode');
            $table->index('is_active');
        });
    }
};