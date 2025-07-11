<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('liturgy_elements', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('liturgy');
            $table->string('type');
            $table->string('reading_type')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->string('name');
            $table->string('description')->nullable();
            $table
                ->foreignIdFor(User::class, 'assignee_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->nullableMorphs('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liturgy_elements');
    }
};
