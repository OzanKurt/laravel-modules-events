<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_document_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendee_id')->constrained('events_attendees')->cascadeOnDelete();
            $table->foreignId('requirement_id')->constrained('events_requirements')->cascadeOnDelete();
            $table->string('kind')->nullable();
            $table->string('filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('byte_size');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_document_uploads');
    }
};
