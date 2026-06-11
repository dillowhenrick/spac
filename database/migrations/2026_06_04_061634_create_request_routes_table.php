<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->foreignId('routed_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->timestamp('routed_at');
            $table->timestamps();

            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_routes');
    }
};
