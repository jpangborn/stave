<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->timestamp('last_comment_at')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'last_comment_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
