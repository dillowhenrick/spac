<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_self_assigned')->default(false);
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->index('request_id');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_assignments');
    }
};
