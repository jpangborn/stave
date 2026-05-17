<?php

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_files', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignIdFor(Conversation::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Comment::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'uploader_id')->constrained('users');
            $table->string('disk')->default('digital-ocean');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->boolean('is_inline_image')->default(false);
            $table->timestamps();

            $table->index(['conversation_id', 'is_inline_image']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_files');
    }
};
