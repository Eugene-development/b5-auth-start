<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure projects has temporary ULID column and fill it
        if (!Schema::hasColumn('projects', 'new_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->ulid('new_id')->nullable()->after('id');
            });

            DB::table('projects')->select('id')->orderBy('id')->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('projects')->where('id', $row->id)->update(['new_id' => (string) Str::ulid()]);
                }
            }, 'id');
        }

        // Add new_project_id to referencing tables if missing
        foreach (['factories', 'emails', 'phones', 'project_project_status'] as $tableName) {
            if (!Schema::hasColumn($tableName, 'new_project_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->char('new_project_id', 26)->nullable()->after('project_id');
                });
            }
        }

        // Populate new_project_id via join if nulls exist
        DB::statement('UPDATE factories f JOIN projects p ON f.project_id = p.id SET f.new_project_id = p.new_id WHERE f.new_project_id IS NULL');
        DB::statement('UPDATE emails e JOIN projects p ON e.project_id = p.id SET e.new_project_id = p.new_id WHERE e.new_project_id IS NULL');
        DB::statement('UPDATE phones ph JOIN projects p ON ph.project_id = p.id SET ph.new_project_id = p.new_id WHERE ph.new_project_id IS NULL');
        DB::statement('UPDATE project_project_status pps JOIN projects p ON pps.project_id = p.id SET pps.new_project_id = p.new_id WHERE pps.new_project_id IS NULL');

        // Drop existing FKs on project_id by known names if they exist
        $this->dropForeignIfExists('factories', 'factories_project_id_foreign');
        $this->dropForeignIfExists('emails', 'emails_project_id_foreign');
        $this->dropForeignIfExists('phones', 'phones_project_id_foreign');
        $this->dropForeignIfExists('project_project_status', 'project_project_status_project_id_foreign');

        // Replace project_id columns to CHAR(26) and copy values, then drop helper col
        foreach (['factories', 'emails', 'phones', 'project_project_status'] as $tableName) {
            if (Schema::hasColumn($tableName, 'project_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // ensure project_id exists with desired type; drop to recreate if not CHAR(26)
                    $table->dropColumn('project_id');
                    $table->char('project_id', 26)->nullable()->after('new_project_id');
                });
            }
        }

        DB::statement('UPDATE factories SET project_id = new_project_id WHERE project_id IS NULL');
        DB::statement('UPDATE emails SET project_id = new_project_id WHERE project_id IS NULL');
        DB::statement('UPDATE phones SET project_id = new_project_id WHERE project_id IS NULL');
        DB::statement('UPDATE project_project_status SET project_id = new_project_id WHERE project_id IS NULL');

        // Drop helper new_project_id columns
        foreach (['factories', 'emails', 'phones', 'project_project_status'] as $tableName) {
            if (Schema::hasColumn($tableName, 'new_project_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropColumn('new_project_id');
                });
            }
        }

        // Convert projects.id to ULID and align collation to ascii_bin
        // 1) Remove AUTO_INCREMENT to allow PK drop
        DB::statement('ALTER TABLE projects MODIFY id BIGINT UNSIGNED NOT NULL');
        // 2) Ensure new_id is ascii_bin and then swap
        DB::statement('ALTER TABLE projects MODIFY new_id CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL');
        DB::statement('ALTER TABLE projects DROP PRIMARY KEY');
        DB::statement('ALTER TABLE projects DROP COLUMN id');
        DB::statement("ALTER TABLE projects CHANGE COLUMN new_id id CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL");
        DB::statement('ALTER TABLE projects ADD PRIMARY KEY (id)');

        // Ensure referencing project_id columns have same collation and are NOT NULL
        DB::statement('ALTER TABLE factories MODIFY project_id CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL');
        DB::statement('ALTER TABLE emails MODIFY project_id CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL');
        DB::statement('ALTER TABLE phones MODIFY project_id CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL');
        DB::statement('ALTER TABLE project_project_status MODIFY project_id CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL');

        // Recreate FKs if missing
        $this->addForeignIfMissing('factories', 'factories_project_id_foreign', 'project_id', 'projects', 'id', 'cascade');
        $this->addForeignIfMissing('emails', 'emails_project_id_foreign', 'project_id', 'projects', 'id', 'cascade');
        $this->addForeignIfMissing('phones', 'phones_project_id_foreign', 'project_id', 'projects', 'id', 'cascade');
        $this->addForeignIfMissing('project_project_status', 'project_project_status_project_id_foreign', 'project_id', 'projects', 'id', 'cascade');

        // Convert emails.id and phones.id to ULID (ascii_bin) if still bigint
        foreach (['emails', 'phones'] as $tableName) {
            if (!Schema::hasColumn($tableName, 'new_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->ulid('new_id')->nullable()->after('id');
                });
                DB::table($tableName)->select('id')->orderBy('id')->chunkById(500, function ($rows) use ($tableName) {
                    foreach ($rows as $row) {
                        DB::table($tableName)->where('id', $row->id)->update(['new_id' => (string) Str::ulid()]);
                    }
                }, 'id');
            }

            // If id is still bigint, swap to ULID
            $col = DB::selectOne("SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'id'", [$tableName]);
            if ($col && strtolower($col->DATA_TYPE) !== 'char') {
                // Remove AUTO_INCREMENT, then drop PK
                DB::statement("ALTER TABLE {$tableName} MODIFY id BIGINT UNSIGNED NOT NULL");
                DB::statement("ALTER TABLE {$tableName} DROP PRIMARY KEY");
                DB::statement("ALTER TABLE {$tableName} DROP COLUMN id");
                DB::statement("ALTER TABLE {$tableName} CHANGE COLUMN new_id id CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL");
                DB::statement("ALTER TABLE {$tableName} ADD PRIMARY KEY (id)");
            } else {
                // cleanup helper column if present
                if (Schema::hasColumn($tableName, 'new_id')) {
                    Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                        $table->dropColumn('new_id');
                    });
                }
            }
        }
    }

    public function down(): void
    {
        // Откат не реализован из-за риска потери данных.
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        $exists = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::raw('DATABASE()'))
            ->where('CONSTRAINT_NAME', $constraint)
            ->exists();
        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }

    private function addForeignIfMissing(string $table, string $constraint, string $column, string $refTable, string $refColumn, string $onDelete = 'cascade'): void
    {
        $exists = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::raw('DATABASE()'))
            ->where('CONSTRAINT_NAME', $constraint)
            ->exists();
        if (!$exists) {
            DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraint}` FOREIGN KEY (`{$column}`) REFERENCES `{$refTable}`(`{$refColumn}`) ON DELETE {$onDelete}");
        }
    }
};
