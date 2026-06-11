<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->foreignId('drafted_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('request_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_responses');
    }
};
