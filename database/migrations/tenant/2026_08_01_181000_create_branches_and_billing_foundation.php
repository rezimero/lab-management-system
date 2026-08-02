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
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 40)->unique();
            $table->string('name', 180);
            $table->string('type', 32);
            $table->foreignId('parent_branch_id')
                ->nullable()
                ->constrained('branches')
                ->restrictOnDelete();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('country', 120)->default('Nigeria');
            $table->boolean('is_head_office')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['type', 'is_active']);
            $table->index(['is_head_office', 'is_active']);
        });

        Schema::create('branch_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['branch_id', 'user_id']);
            $table->index(['user_id', 'is_primary']);
        });

        Schema::create('price_lists', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 60)->unique();
            $table->string('name', 180);
            $table->char('currency', 3)->default('NGN');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['is_default', 'is_active']);
        });

        Schema::create('price_list_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_list_id')
                ->constrained('price_lists')
                ->cascadeOnDelete();
            $table->foreignId('lab_test_id')
                ->constrained('lab_tests')
                ->restrictOnDelete();
            $table->decimal('price', 14, 2);
            $table->timestampsTz();

            $table->unique(['price_list_id', 'lab_test_id']);
        });

        Schema::create('corporate_accounts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 60)->unique();
            $table->string('name', 180);
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->text('billing_address')->nullable();
            $table->unsignedSmallInteger('payment_terms_days')->default(0);
            $table->decimal('credit_limit', 14, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::table('laboratory_orders', function (Blueprint $table): void {
            $table->foreignId('collection_branch_id')
                ->nullable()
                ->after('ordered_by')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->foreignId('processing_branch_id')
                ->nullable()
                ->after('collection_branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->foreignId('price_list_id')
                ->nullable()
                ->after('processing_branch_id')
                ->constrained('price_lists')
                ->nullOnDelete();

            $table->index(['collection_branch_id', 'status']);
            $table->index(['processing_branch_id', 'status']);
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invoice_number', 60)->unique();
            $table->foreignId('laboratory_order_id')
                ->nullable()
                ->unique()
                ->constrained('laboratory_orders')
                ->restrictOnDelete();
            $table->foreignId('patient_id')
                ->nullable()
                ->constrained('patients')
                ->nullOnDelete();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->foreignId('corporate_account_id')
                ->nullable()
                ->constrained('corporate_accounts')
                ->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->char('currency', 3)->default('NGN');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('balance_due', 14, 2);
            $table->text('notes')->nullable();
            $table->timestampTz('issued_at')->nullable();
            $table->timestampTz('due_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('voided_at')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestampsTz();

            $table->index(['status', 'due_at']);
            $table->index(['branch_id', 'status']);
            $table->index(['corporate_account_id', 'status']);
        });

        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->cascadeOnDelete();
            $table->foreignId('laboratory_order_item_id')
                ->nullable()
                ->constrained('laboratory_order_items')
                ->nullOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('line_total', 14, 2);
            $table->timestampsTz();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('payment_number', 60)->unique();
            $table->string('receipt_number', 60)->unique();
            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('method', 40);
            $table->string('reference', 160)->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('paid_at');
            $table->foreignId('received_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestampsTz();

            $table->index(['invoice_id', 'paid_at']);
            $table->index(['method', 'paid_at']);
        });

        $now = now();
        $branchId = DB::connection('tenant')
            ->table('branches')
            ->insertGetId([
                'uuid' => (string) Str::uuid(),
                'code' => 'HO',
                'name' => 'Laboratory Head Office',
                'type' => 'laboratory',
                'country' => 'Nigeria',
                'is_head_office' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $priceListId = DB::connection('tenant')
            ->table('price_lists')
            ->insertGetId([
                'uuid' => (string) Str::uuid(),
                'code' => 'STANDARD',
                'name' => 'Standard Price List',
                'currency' => 'NGN',
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        DB::connection('tenant')
            ->table('users')
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($branchId, $now): void {
                foreach ($users as $user) {
                    DB::connection('tenant')
                        ->table('branch_user')
                        ->insertOrIgnore([
                            'branch_id' => $branchId,
                            'user_id' => $user->id,
                            'is_primary' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                }
            });

        DB::connection('tenant')
            ->table('lab_tests')
            ->orderBy('id')
            ->chunkById(200, function ($labTests) use ($priceListId, $now): void {
                foreach ($labTests as $labTest) {
                    DB::connection('tenant')
                        ->table('price_list_items')
                        ->insertOrIgnore([
                            'price_list_id' => $priceListId,
                            'lab_test_id' => $labTest->id,
                            'price' => $labTest->price,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                }
            });

        DB::connection('tenant')
            ->table('laboratory_orders')
            ->update([
                'collection_branch_id' => $branchId,
                'processing_branch_id' => $branchId,
                'price_list_id' => $priceListId,
            ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');

        Schema::table('laboratory_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('price_list_id');
            $table->dropConstrainedForeignId('processing_branch_id');
            $table->dropConstrainedForeignId('collection_branch_id');
        });

        Schema::dropIfExists('corporate_accounts');
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('branches');
    }
};
