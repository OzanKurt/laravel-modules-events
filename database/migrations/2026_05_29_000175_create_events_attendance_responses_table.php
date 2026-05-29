<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_attendance_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendee_id')->constrained('events_attendees')->cascadeOnDelete();
            $table->foreignId('attendance_form_id')->constrained('events_attendance_forms')->cascadeOnDelete();
            $table->json('answers');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_attendance_responses');
    }
};
