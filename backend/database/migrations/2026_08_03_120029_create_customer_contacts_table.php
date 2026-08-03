<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'customer_contacts',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('customer_id')
                    ->constrained()
                    ->restrictOnDelete();

                $table
                    ->string('name', 150);

                $table
                    ->string('designation', 100)
                    ->nullable();

                $table
                    ->string('department', 100)
                    ->nullable();

                $table
                    ->enum(
                        'contact_type',
                        [
                            'general',
                            'sales',
                            'accounts',
                            'management',
                            'support',
                            'purchase',
                            'other',
                        ]
                    )
                    ->default('general');

                $table
                    ->string('email', 150)
                    ->nullable();

                $table
                    ->string('phone', 30)
                    ->nullable();

                $table
                    ->string('alternate_phone', 30)
                    ->nullable();

                $table
                    ->boolean('is_primary')
                    ->default(false);

                $table
                    ->boolean('is_active')
                    ->default(true);

                $table
                    ->text('notes')
                    ->nullable();

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index([
                    'customer_id',
                    'contact_type',
                ]);

                $table->index([
                    'customer_id',
                    'is_primary',
                ]);

                $table->index([
                    'customer_id',
                    'is_active',
                ]);

                $table->index('email');
                $table->index('phone');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'customer_contacts'
        );
    }
};