<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('organization_type');
            $table->unsignedBigInteger('organization_id');
            $table->string('role');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index('organization_type');
            $table->index('organization_id');
            $table->index('role');
            $table->index(['organization_type', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
