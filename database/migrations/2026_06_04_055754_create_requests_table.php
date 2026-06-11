<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('target_institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('reference_number')->unique();
            $table->string('legal_process');
            $table->string('nature_of_case')->nullable();
            $table->text('additional_context')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->date('records_from')->nullable();
            $table->date('records_to')->nullable();

            // Warrant SLA fields (A.M. No. 17-11-03-SC)
            $table->timestamp('legal_process_signed_at')->nullable();
            $table->timestamp('warrant_expires_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('request_due_at')->nullable();

            $table->timestamps();

            $table->index('requester_id');
            $table->index('agency_id');
            $table->index('target_institution_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
