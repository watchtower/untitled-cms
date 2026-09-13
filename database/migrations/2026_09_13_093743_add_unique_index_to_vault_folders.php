<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\Driver\Exception\CommandException;

return new class extends Migration
{
    protected $connection = 'mongodb';

    private const INDEX_NAME = 'vault_folders_parent_name_unique';

    /**
     * Enforce unique folder names per parent at the database level.
     * deleted_at is part of the key so trashed folders don't block new ones.
     */
    public function up(): void
    {
        try {
            DB::connection($this->connection)
                ->getCollection('vault_folders')
                ->createIndex(
                    ['parent_id' => 1, 'name' => 1, 'deleted_at' => 1],
                    ['name' => self::INDEX_NAME, 'unique' => true]
                );
        } catch (CommandException $e) {
            // 85 = IndexOptionsConflict, 86 = IndexKeySpecsConflict: index already exists.
            if (in_array($e->getCode(), [85, 86], true) || str_contains($e->getMessage(), 'already exists')) {
                return;
            }

            // 11000 = DuplicateKey: existing data violates the constraint.
            if ($e->getCode() === 11000 || str_contains($e->getMessage(), 'E11000')) {
                throw new RuntimeException(
                    'Cannot add '.self::INDEX_NAME.': vault_folders contains active folders with the same name under the same parent. '
                    .'Rename or trash the duplicates, then re-run the migration. Original error: '.$e->getMessage(),
                    0,
                    $e
                );
            }

            throw $e;
        }
    }

    public function down(): void
    {
        try {
            DB::connection($this->connection)
                ->getCollection('vault_folders')
                ->dropIndex(self::INDEX_NAME);
        } catch (CommandException $e) {
            if (str_contains($e->getMessage(), 'index not found')) {
                return;
            }
            throw $e;
        }
    }
};
