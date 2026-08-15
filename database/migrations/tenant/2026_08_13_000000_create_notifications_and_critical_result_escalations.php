<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create(
            'notification_messages',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('idempotency_key', 191)->unique();
                $table->string('recipient_type', 40);
                $table->unsignedBigInteger('recipient_id');
                $table->uuid('recipient_uuid')->nullable();
                $table->string('category', 60);
                $table->string('event_key', 100);
                $table->string('priority', 20)->default('normal');
                $table->string('title', 180);
                $table->text('body');
                $table->string('action_url', 500)->nullable();
                $table->json('data')->nullable();
                $table->timestampTz('read_at')->nullable();
                $table->timestampsTz();

                $table->index(
                    [
                        'recipient_type',
                        'recipient_id',
                        'created_at',
                    ],
                    'notification_recipient_created_idx'
                );
                $table->index(
                    ['category', 'created_at'],
                    'notification_category_created_idx'
                );
            }
        );

        Schema::connection('tenant')->create(
            'notification_deliveries',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('notification_message_id')
                    ->nullable()
                    ->constrained('notification_messages')
                    ->nullOnDelete();
                $table->string('idempotency_key', 191)->unique();
                $table->string('recipient_type', 40);
                $table->unsignedBigInteger('recipient_id');
                $table->uuid('recipient_uuid')->nullable();
                $table->string('channel', 20);
                $table->char('destination_hash', 64);
                $table->text('destination');
                $table->longText('payload');
                $table->string('status', 24)->default('pending');
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->unsignedSmallInteger('max_attempts')->default(5);
                $table->timestampTz('next_attempt_at')->nullable();
                $table->timestampTz('processing_started_at')->nullable();
                $table->timestampTz('sent_at')->nullable();
                $table->timestampTz('failed_at')->nullable();
                $table->string('provider', 60)->nullable();
                $table->string('provider_message_id', 191)->nullable();
                $table->string('last_error_code', 80)->nullable();
                $table->text('last_error')->nullable();
                $table->timestampsTz();

                $table->index(
                    ['status', 'next_attempt_at'],
                    'notification_delivery_due_idx'
                );
                $table->index(
                    [
                        'recipient_type',
                        'recipient_id',
                        'created_at',
                    ],
                    'notification_delivery_recipient_idx'
                );
            }
        );

        Schema::connection('tenant')->create(
            'notification_preferences',
            function (Blueprint $table): void {
                $table->id();
                $table->string('recipient_type', 40);
                $table->unsignedBigInteger('recipient_id');
                $table->string('category', 60);
                $table->boolean('in_app_enabled')->default(true);
                $table->boolean('email_enabled')->default(true);
                $table->boolean('sms_enabled')->default(false);
                $table->boolean('whatsapp_enabled')->default(false);
                $table->timestampsTz();

                $table->unique(
                    [
                        'recipient_type',
                        'recipient_id',
                        'category',
                    ],
                    'notification_preference_recipient_unique'
                );
            }
        );

        Schema::connection('tenant')->create(
            'critical_result_escalations',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('source_key', 191)->unique();
                $table->foreignId('test_result_id')
                    ->constrained('test_results')
                    ->restrictOnDelete();
                $table->uuid('test_result_uuid');
                $table->uuid('order_uuid');
                $table->unsignedSmallInteger('critical_count');
                $table->string('status', 24)->default('open');
                $table->unsignedSmallInteger('escalation_level')
                    ->default(1);
                $table->timestampTz('detected_at');
                $table->timestampTz('acknowledgement_due_at');
                $table->foreignId('acknowledged_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestampTz('acknowledged_at')->nullable();
                $table->text('acknowledgement_note')->nullable();
                $table->foreignId('resolved_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestampTz('resolved_at')->nullable();
                $table->text('resolution_note')->nullable();
                $table->timestampTz('last_escalated_at')->nullable();
                $table->timestampsTz();

                $table->index(
                    ['status', 'acknowledgement_due_at'],
                    'critical_escalation_due_idx'
                );
                $table->index(
                    ['order_uuid', 'created_at'],
                    'critical_escalation_order_idx'
                );
                $table->index(
                    ['test_result_id', 'created_at'],
                    'critical_escalation_result_idx'
                );
            }
        );

        Schema::connection('tenant')->create(
            'portal_password_resets',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('account_type', 20);
                $table->unsignedBigInteger('account_id');
                $table->char('token_hash', 64)->unique();
                $table->string('requested_by_type', 30);
                $table->unsignedBigInteger('requested_by_id')
                    ->nullable();
                $table->timestampTz('expires_at');
                $table->timestampTz('used_at')->nullable();
                $table->timestampTz('revoked_at')->nullable();
                $table->timestampsTz();

                $table->index(
                    ['account_type', 'account_id'],
                    'portal_password_reset_account_idx'
                );
                $table->index(
                    ['expires_at', 'used_at', 'revoked_at'],
                    'portal_password_reset_state_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::connection('tenant')
            ->dropIfExists('portal_password_resets');
        Schema::connection('tenant')
            ->dropIfExists('critical_result_escalations');
        Schema::connection('tenant')
            ->dropIfExists('notification_preferences');
        Schema::connection('tenant')
            ->dropIfExists('notification_deliveries');
        Schema::connection('tenant')
            ->dropIfExists('notification_messages');
    }
};
