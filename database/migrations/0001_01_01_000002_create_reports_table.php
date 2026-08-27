<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('block_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('block_code', 50)->nullable();
            $table->string('photo_url', 255)->nullable();
            $table->enum('status', ['OPEN', 'ON_PROGRESS', 'CLOSED'])->default('OPEN');
            $table->text('admin_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reported_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->string('voice_note_url', 255)->nullable();
            $table->json('checklist_answers')->nullable();
            $table->timestamp('sla_deadline')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['status', 'category_id', 'reported_at']);
            $table->index(['block_id', 'reported_at']);
            $table->index(['user_id', 'reported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};