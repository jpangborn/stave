<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('user_access_roles', function (Blueprint $table): void {
            $table->foreignId('church_id')->nullable()->constrained()->cascadeOnDelete();
        });

        // The new unique index must exist before the old one is dropped:
        // InnoDB refuses to drop an index that backs the user_id foreign key.
        Schema::table('user_access_roles', function (Blueprint $table): void {
            $table->unique(['user_id', 'church_id', 'role']);
        });

        Schema::table('user_access_roles', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('user_access_roles', function (Blueprint $table): void {
            $table->unique(['user_id', 'role']);
        });

        Schema::table('user_access_roles', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'church_id', 'role']);
        });

        Schema::table('user_access_roles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('church_id');
        });
    }
};
