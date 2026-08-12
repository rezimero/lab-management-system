<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create(
            'referring_clinicians',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name', 180);
                $table->string('email')->nullable();
                $table->string('phone', 40)->nullable();
                $table->string('organisation', 180)->nullable();
                $table->string('registration_number', 120)->nullable();
                $table->string('specialty', 120)->nullable();
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

                $table->index(
                    ['is_active', 'name'],
                    'referring_clinicians_active_name_idx'
                );
                $table->index(
                    ['email', 'is_active'],
                    'referring_clinicians_email_active_idx'
                );
            }
        );

        Schema::connection('tenant')->create(
            'patient_portal_accounts',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('patient_id')
                    ->unique()
                    ->constrained('patients')
                    ->restrictOnDelete();
                $table->string('email')->unique();
                $table->string('password');
                $table->timestampTz('email_verified_at')->nullable();
                $table->timestampTz('activated_at')->nullable();
                $table->timestampTz('last_login_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestampsTz();

                $table->index(
                    ['is_active', 'activated_at'],
                    'patient_portal_accounts_active_idx'
                );
            }
        );

        Schema::connection('tenant')->create(
            'clinician_portal_accounts',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('referring_clinician_id')
                    ->unique()
                    ->constrained('referring_clinicians')
                    ->restrictOnDelete();
                $table->string('email')->unique();
                $table->string('password');
                $table->timestampTz('email_verified_at')->nullable();
                $table->timestampTz('activated_at')->nullable();
                $table->timestampTz('last_login_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestampsTz();

                $table->index(
                    ['is_active', 'activated_at'],
                    'clinician_portal_accounts_active_idx'
                );
            }
        );

        Schema::connection('tenant')->create(
            'portal_access_tokens',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('account_type', 20);
                $table->unsignedBigInteger('account_id');
                $table->char('token_hash', 64)->unique();
                $table->string('name', 120)->default('portal_session');
                $table->json('abilities')->nullable();
                $table->timestampTz('last_used_at')->nullable();
                $table->timestampTz('expires_at');
                $table->timestampTz('revoked_at')->nullable();
                $table->timestampsTz();

                $table->index(
                    ['account_type', 'account_id'],
                    'portal_access_tokens_account_idx'
                );
                $table->index(
                    ['expires_at', 'revoked_at'],
                    'portal_access_tokens_expiry_idx'
                );
            }
        );

        Schema::connection('tenant')->create(
            'portal_invitations',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('account_type', 20);
                $table->unsignedBigInteger('account_id');
                $table->char('token_hash', 64)->unique();
                $table->timestampTz('expires_at');
                $table->timestampTz('used_at')->nullable();
                $table->timestampTz('revoked_at')->nullable();
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestampsTz();

                $table->index(
                    ['account_type', 'account_id'],
                    'portal_invitations_account_idx'
                );
                $table->index(
                    ['expires_at', 'used_at', 'revoked_at'],
                    'portal_invitations_state_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('portal_invitations');
        Schema::connection('tenant')->dropIfExists('portal_access_tokens');
        Schema::connection('tenant')->dropIfExists('clinician_portal_accounts');
        Schema::connection('tenant')->dropIfExists('patient_portal_accounts');
        Schema::connection('tenant')->dropIfExists('referring_clinicians');
    }
};
