<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('person_offices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('kind');
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->string('end_reason')->nullable();
            $table->timestamps();

            $table->index(['person_id', 'ended_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_offices');
    }
};
