<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create(
            'portal_audit_events',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('event', 80);
                $table->string('actor_type', 40);
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->uuid('actor_uuid')->nullable();
                $table->json('actor_snapshot')->nullable();
                $table->string('subject_type', 40);
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->uuid('subject_uuid')->nullable();
                $table->json('metadata')->nullable();
                $table->timestampTz('created_at')->useCurrent();

                $table->index(
                    ['event', 'created_at'],
                    'portal_audit_event_created_idx'
                );
                $table->index(
                    ['actor_type', 'actor_id', 'created_at'],
                    'portal_audit_actor_created_idx'
                );
                $table->index(
                    ['subject_type', 'subject_uuid', 'created_at'],
                    'portal_audit_subject_created_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::connection('tenant')
            ->dropIfExists('portal_audit_events');
    }
};
