<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table(
            'lab_tests',
            function (Blueprint $table): void {
                $table->uuid('uuid')
                    ->nullable()
                    ->unique()
                    ->after('id');
            }
        );

        DB::connection('tenant')
            ->table('lab_tests')
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunkById(200, function ($tests): void {
                foreach ($tests as $test) {
                    DB::connection('tenant')
                        ->table('lab_tests')
                        ->where('id', $test->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            });

        Schema::connection('tenant')->create(
            'inventory_categories',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('code', 60)->unique();
                $table->string('name', 180);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $this->actorColumns($table);
                $table->timestampsTz();
                $table->softDeletesTz();
                $table->index(['is_active', 'name']);
            }
        );

        Schema::connection('tenant')->create(
            'inventory_units',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('code', 40)->unique();
                $table->string('name', 120);
                $table->string('symbol', 30);
                $table->unsignedTinyInteger('decimal_places')->default(0);
                $table->boolean('is_active')->default(true);
                $this->actorColumns($table);
                $table->timestampsTz();
                $table->softDeletesTz();
                $table->index(['is_active', 'name']);
            }
        );

        Schema::connection('tenant')->create(
            'suppliers',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('code', 60)->unique();
                $table->string('name', 180);
                $table->string('contact_name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone', 40)->nullable();
                $table->text('address')->nullable();
                $table->string('tax_identifier', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $this->actorColumns($table);
                $table->timestampsTz();
                $table->softDeletesTz();
                $table->index(['is_active', 'name']);
            }
        );

        Schema::connection('tenant')->create(
            'inventory_items',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('sku', 80)->unique();
                $table->string('name', 180);
                $table->string('item_type', 32);
                $table->foreignId('inventory_category_id')
                    ->constrained('inventory_categories')
                    ->restrictOnDelete();
                $table->foreignId('inventory_unit_id')
                    ->constrained('inventory_units')
                    ->restrictOnDelete();
                $table->string('manufacturer', 180)->nullable();
                $table->string('catalogue_number', 120)->nullable();
                $table->text('description')->nullable();
                $table->boolean('tracks_lots')->default(true);
                $table->boolean('tracks_expiry')->default(false);
                $table->decimal('default_reorder_level', 18, 6)->default(0);
                $table->boolean('is_active')->default(true);
                $this->actorColumns($table);
                $table->timestampsTz();
                $table->softDeletesTz();
                $table->index(['item_type', 'is_active']);
                $table->index(['inventory_category_id', 'is_active']);
            }
        );

        Schema::connection('tenant')->create(
            'inventory_branch_items',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('branch_id')
                    ->constrained('branches')
                    ->restrictOnDelete();
                $table->foreignId('inventory_item_id')
                    ->constrained('inventory_items')
                    ->restrictOnDelete();
                $table->decimal('reorder_level', 18, 6)->nullable();
                $table->boolean('is_active')->default(true);
                $this->actorColumns($table);
                $table->timestampsTz();
                $table->unique(['branch_id', 'inventory_item_id']);
                $table->index(['branch_id', 'is_active']);
            }
        );

        Schema::connection('tenant')->create(
            'inventory_receipts',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('receipt_number', 80)->unique();
                $table->foreignId('branch_id')
                    ->constrained('branches')
                    ->restrictOnDelete();
                $table->foreignId('supplier_id')
                    ->nullable()
                    ->constrained('suppliers')
                    ->restrictOnDelete();
                $table->string('supplier_reference', 160)->nullable();
                $table->timestampTz('received_at');
                $table->text('notes')->nullable();
                $table->foreignId('received_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestampTz('created_at')->useCurrent();
                $table->index(['branch_id', 'received_at']);
                $table->index(['supplier_id', 'received_at']);
            }
        );

        Schema::connection('tenant')->create(
            'inventory_lots',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('internal_lot_code', 100)->unique();
                $table->foreignId('inventory_receipt_id')
                    ->constrained('inventory_receipts')
                    ->restrictOnDelete();
                $table->foreignId('inventory_item_id')
                    ->constrained('inventory_items')
                    ->restrictOnDelete();
                $table->string('external_lot_number', 160)->nullable();
                $table->string('batch_number', 160)->nullable();
                $table->date('manufactured_at')->nullable();
                $table->date('expires_at')->nullable();
                $table->decimal('quantity_received', 18, 6);
                $table->decimal('unit_cost', 14, 4)->nullable();
                $table->char('currency', 3)->default('NGN');
                $table->timestampTz('created_at')->useCurrent();
                $table->index(['inventory_item_id', 'expires_at']);
                $table->index(['external_lot_number']);
                $table->index(['batch_number']);
            }
        );

        Schema::connection('tenant')->create(
            'inventory_adjustments',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('adjustment_number', 80)->unique();
                $table->foreignId('branch_id')
                    ->constrained('branches')
                    ->restrictOnDelete();
                $table->string('reason', 255);
                $table->text('notes')->nullable();
                $table->timestampTz('adjusted_at');
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestampTz('created_at')->useCurrent();
                $table->index(['branch_id', 'adjusted_at']);
            }
        );

        Schema::connection('tenant')->create(
            'inventory_consumptions',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('consumption_number', 80)->unique();
                $table->foreignId('laboratory_order_item_id')
                    ->constrained('laboratory_order_items')
                    ->restrictOnDelete();
                $table->foreignId('branch_id')
                    ->constrained('branches')
                    ->restrictOnDelete();
                $table->timestampTz('consumed_at');
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestampTz('created_at')->useCurrent();
                $table->index(['laboratory_order_item_id', 'consumed_at'], 'inv_consumptions_order_consumed_idx');
                $table->index(['branch_id', 'consumed_at']);
            }
        );

        Schema::connection('tenant')->create(
            'lab_test_inventory_requirements',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('lab_test_id')
                    ->constrained('lab_tests')
                    ->cascadeOnDelete();
                $table->foreignId('inventory_item_id')
                    ->constrained('inventory_items')
                    ->restrictOnDelete();
                $table->decimal('quantity', 18, 6);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $this->actorColumns($table);
                $table->timestampsTz();
                $table->unique(['lab_test_id', 'inventory_item_id'], 'lab_test_inventory_requirement_unique');
            }
        );

        Schema::connection('tenant')->create(
            'inventory_stock_balances',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('branch_id')
                    ->constrained('branches')
                    ->restrictOnDelete();
                $table->foreignId('inventory_item_id')
                    ->constrained('inventory_items')
                    ->restrictOnDelete();
                $table->foreignId('inventory_lot_id')
                    ->constrained('inventory_lots')
                    ->restrictOnDelete();
                $table->decimal('quantity_on_hand', 18, 6)->default(0);
                $table->timestampTz('last_movement_at')->nullable();
                $table->timestampsTz();
                $table->unique(['branch_id', 'inventory_lot_id']);
                $table->index(['branch_id', 'inventory_item_id']);
            }
        );

        Schema::connection('tenant')->create(
            'inventory_stock_movements',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('branch_id')
                    ->constrained('branches')
                    ->restrictOnDelete();
                $table->foreignId('inventory_item_id')
                    ->constrained('inventory_items')
                    ->restrictOnDelete();
                $table->foreignId('inventory_lot_id')
                    ->constrained('inventory_lots')
                    ->restrictOnDelete();
                $table->string('movement_type', 32);
                $table->decimal('quantity_delta', 18, 6);
                $table->decimal('quantity_after', 18, 6);
                $table->foreignId('inventory_receipt_id')
                    ->nullable()
                    ->constrained('inventory_receipts')
                    ->restrictOnDelete();
                $table->foreignId('inventory_adjustment_id')
                    ->nullable()
                    ->constrained('inventory_adjustments')
                    ->restrictOnDelete();
                $table->foreignId('inventory_consumption_id')
                    ->nullable()
                    ->constrained('inventory_consumptions')
                    ->restrictOnDelete();
                $table->string('reason', 255)->nullable();
                $table->timestampTz('occurred_at');
                $table->foreignId('performed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestampTz('created_at')->useCurrent();
                $table->index(['branch_id', 'occurred_at']);
                $table->index(['inventory_item_id', 'occurred_at']);
                $table->index(['inventory_lot_id', 'occurred_at']);
                $table->index(['movement_type', 'occurred_at']);
            }
        );
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('inventory_stock_movements');
        Schema::connection('tenant')->dropIfExists('inventory_stock_balances');
        Schema::connection('tenant')->dropIfExists('lab_test_inventory_requirements');
        Schema::connection('tenant')->dropIfExists('inventory_consumptions');
        Schema::connection('tenant')->dropIfExists('inventory_adjustments');
        Schema::connection('tenant')->dropIfExists('inventory_lots');
        Schema::connection('tenant')->dropIfExists('inventory_receipts');
        Schema::connection('tenant')->dropIfExists('inventory_branch_items');
        Schema::connection('tenant')->dropIfExists('inventory_items');
        Schema::connection('tenant')->dropIfExists('suppliers');
        Schema::connection('tenant')->dropIfExists('inventory_units');
        Schema::connection('tenant')->dropIfExists('inventory_categories');

        Schema::connection('tenant')->table(
            'lab_tests',
            function (Blueprint $table): void {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            }
        );
    }

    private function actorColumns(Blueprint $table): void
    {
        $table->foreignId('created_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();
        $table->foreignId('updated_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();
    }
};
