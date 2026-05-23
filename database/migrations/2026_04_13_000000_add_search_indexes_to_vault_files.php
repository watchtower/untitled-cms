<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\Driver\Exception\CommandException;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        // Fix #5: guard against duplicate text index.
        // MongoDB only allows one text index per collection; createIndex throws
        // CommandException if a conflicting index already exists.
        try {
            DB::connection($this->connection)
                ->getCollection('vault_files')
                ->createIndex(
                    ['original_name' => 'text', 'alt_text' => 'text'],
                    ['name' => 'vault_files_search_text']
                );
        } catch (CommandException $e) {
            // Error code 85 = IndexOptionsConflict, 86 = IndexKeySpecsConflict.
            // Both mean the index (or a conflicting text index) already exists — safe to skip.
            if (in_array($e->getCode(), [85, 86], true) || str_contains($e->getMessage(), 'already exists')) {
                return;
            }
            throw $e;
        }
    }

    public function down(): void
    {
        try {
            DB::connection($this->connection)
                ->getCollection('vault_files')
                ->dropIndex('vault_files_search_text');
        } catch (CommandException $e) {
            // Index may not exist in down() if up() was skipped — ignore gracefully.
            if (str_contains($e->getMessage(), 'index not found')) {
                return;
            }
            throw $e;
        }
    }
};
