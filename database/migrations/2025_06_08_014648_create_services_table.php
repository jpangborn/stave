<?php

use App\Models\Template;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("services", function (Blueprint $table) {
            $table->id();
            $table->string("title")->nullable();
            $table->date("date");
            $table
                ->foreignIdFor(Template::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->text("notes")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("services");
    }
};
