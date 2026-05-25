<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->string('phone')->nullable()->after('email');
            $table->string('address_line1')->nullable()->after('phone');
            $table->string('address_city')->nullable()->after('address_line1');
            $table->string('address_state', 2)->nullable()->after('address_city');
            $table->string('address_zip', 16)->nullable()->after('address_state');

            $table->string('membership_status')->default('visitor')->after('gender');
            $table->date('membership_since')->nullable()->after('membership_status');
            $table->string('termination_reason')->nullable()->after('membership_since');

            $table->foreignId('pastoral_care_elder_id')
                ->nullable()
                ->after('termination_reason')
                ->constrained('people')
                ->nullOnDelete();

            $table->timestamp('last_active_at')->nullable()->after('pastoral_care_elder_id');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->dropForeign(['pastoral_care_elder_id']);
            $table->dropColumn([
                'phone',
                'address_line1',
                'address_city',
                'address_state',
                'address_zip',
                'membership_status',
                'membership_since',
                'termination_reason',
                'pastoral_care_elder_id',
                'last_active_at',
            ]);
        });
    }
};
