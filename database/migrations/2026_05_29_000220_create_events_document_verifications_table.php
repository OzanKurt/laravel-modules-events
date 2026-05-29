<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_document_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_upload_id')->constrained('events_document_uploads')->cascadeOnDelete();
            $table->string('status');
            $table->foreignId('decided_by')->nullable()->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_document_verifications');
    }
};
