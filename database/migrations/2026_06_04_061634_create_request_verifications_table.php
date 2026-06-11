<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->foreignId('verified_by')->constrained('users')->cascadeOnDelete();
            $table->string('decision');
            $table->text('notes')->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();

            $table->index('request_id');
            $table->index('decision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_verifications');
    }
};
