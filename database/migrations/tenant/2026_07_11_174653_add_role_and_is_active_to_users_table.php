<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('tenant');

        if (! $schema->hasTable('users')) {
            throw new \RuntimeException(
                'The tenant users table must exist before adding authentication fields.'
            );
        }

        $roleMissing = ! $schema->hasColumn('users', 'role');
        $activeMissing = ! $schema->hasColumn('users', 'is_active');

        if (! $roleMissing && ! $activeMissing) {
            return;
        }

        $schema->table('users', function (Blueprint $table) use (
            $roleMissing,
            $activeMissing
        ): void {
            if ($roleMissing) {
                $table
                    ->string('role', 50)
                    ->default('user')
                    ->after('password')
                    ->index();
            }

            if ($activeMissing) {
                $table
                    ->boolean('is_active')
                    ->default(true)
                    ->after('role')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('tenant');

        if (! $schema->hasTable('users')) {
            return;
        }

        $roleExists = $schema->hasColumn('users', 'role');
        $activeExists = $schema->hasColumn('users', 'is_active');

        if (! $roleExists && ! $activeExists) {
            return;
        }

        $schema->table('users', function (Blueprint $table) use (
            $roleExists,
            $activeExists
        ): void {
            if ($activeExists) {
                $table->dropColumn('is_active');
            }

            if ($roleExists) {
                $table->dropColumn('role');
            }
        });
    }
};