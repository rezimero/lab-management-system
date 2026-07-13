<?php

namespace App\Services\Tenant\Provisioning;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class TenantDatabaseManager
{
    /**
     * Generate a unique and MySQL-safe tenant database name.
     */
    public function generateName(string $domain): string
    {
        $slug = preg_replace(
            '/[^a-z0-9]+/',
            '_',
            strtolower(trim($domain))
        );

        $slug = trim((string) $slug, '_');
        $slug = substr($slug, 0, 40);

        if ($slug === '') {
            $slug = 'tenant';
        }

        $database = sprintf(
            'tenant_%s_%s',
            strtolower(Str::random(8)),
            $slug
        );

        $this->assertSafeName($database);

        return $database;
    }

    /**
     * Create a physical tenant database.
     */
    public function create(string $database): void
    {
        $this->assertSafeName($database);

        DB::connection('landlord')->statement(
            sprintf(
                'CREATE DATABASE `%s` '
                .'CHARACTER SET utf8mb4 '
                .'COLLATE utf8mb4_unicode_ci',
                $database
            )
        );
    }

    /**
     * Drop a physical tenant database.
     */
    public function drop(string $database): void
    {
        $this->assertSafeName($database);

        DB::connection('landlord')->statement(
            sprintf(
                'DROP DATABASE IF EXISTS `%s`',
                $database
            )
        );
    }

    /**
     * Reject unsafe raw SQL database identifiers.
     */
    public function assertSafeName(string $database): void
    {
        if (
            ! preg_match(
                '/^[a-zA-Z0-9_]+$/',
                $database
            )
        ) {
            throw new RuntimeException(
                'Unsafe tenant database name detected.'
            );
        }
    }
}