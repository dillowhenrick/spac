<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('response_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('request_responses')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->index('response_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('response_attachments');
    }
};
