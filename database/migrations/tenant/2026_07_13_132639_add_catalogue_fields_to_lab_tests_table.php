<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'lab_tests';

    /**
     * Reconcile the laboratory catalogue schema.
     */
    public function up(): void
    {
        $schema = Schema::connection('tenant');

        if (! $schema->hasTable(self::TABLE)) {
            throw new \RuntimeException(
                'The lab_tests table does not exist.'
            );
        }

        $this->addMissingColumns($schema);
        $this->addMissingIndexes($schema);
    }

    /**
     * This compatibility migration is intentionally non-destructive.
     *
     * Some tenant databases already contained these columns before
     * the migration was introduced, so rollback must not remove them.
     */
    public function down(): void
    {
    }

    /**
     * Add only catalogue columns that are currently missing.
     */
    private function addMissingColumns(
        Builder $schema
    ): void {
        if (! $schema->hasColumn(self::TABLE, 'name')) {
            $schema->table(
                self::TABLE,
                static function (Blueprint $table): void {
                    $table->string('name', 255)
                        ->default('Unnamed Test');
                }
            );
        }

        if (
            ! $schema->hasColumn(
                self::TABLE,
                'description'
            )
        ) {
            $schema->table(
                self::TABLE,
                static function (Blueprint $table): void {
                    $table->text('description')
                        ->nullable();
                }
            );
        }

        if (
            ! $schema->hasColumn(
                self::TABLE,
                'category'
            )
        ) {
            $schema->table(
                self::TABLE,
                static function (Blueprint $table): void {
                    $table->string('category', 100)
                        ->default('General');
                }
            );
        }

        if (! $schema->hasColumn(self::TABLE, 'price')) {
            $schema->table(
                self::TABLE,
                static function (Blueprint $table): void {
                    $table->decimal('price', 12, 2)
                        ->nullable()
                        ->default(0);
                }
            );
        }

        if (
            ! $schema->hasColumn(
                self::TABLE,
                'turnaround_time_hours'
            )
        ) {
            $schema->table(
                self::TABLE,
                static function (Blueprint $table): void {
                    $table->unsignedInteger(
                        'turnaround_time_hours'
                    )->nullable();
                }
            );
        }

        if (
            ! $schema->hasColumn(
                self::TABLE,
                'parameters'
            )
        ) {
            $schema->table(
                self::TABLE,
                static function (Blueprint $table): void {
                    $table->json('parameters')
                        ->nullable();
                }
            );
        }

        if (
            ! $schema->hasColumn(
                self::TABLE,
                'is_active'
            )
        ) {
            $schema->table(
                self::TABLE,
                static function (Blueprint $table): void {
                    $table->boolean('is_active')
                        ->default(true);
                }
            );
        }
    }

    /**
     * Add indexes only when their columns are not already indexed.
     */
    private function addMissingIndexes(
        Builder $schema
    ): void {
        $this->addIndexIfMissing(
            $schema,
            'category',
            'lab_tests_category_index'
        );

        $this->addIndexIfMissing(
            $schema,
            'is_active',
            'lab_tests_is_active_index'
        );
    }

    private function addIndexIfMissing(
        Builder $schema,
        string $column,
        string $index
    ): void {
        if (
            ! $schema->hasColumn(self::TABLE, $column)
            || $this->columnIsIndexed($column)
        ) {
            return;
        }

        $schema->table(
            self::TABLE,
            static function (Blueprint $table) use (
                $column,
                $index
            ): void {
                $table->index($column, $index);
            }
        );
    }

    private function columnIsIndexed(
        string $column
    ): bool {
        return DB::connection('tenant')
            ->table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', self::TABLE)
            ->where('column_name', $column)
            ->exists();
    }
};