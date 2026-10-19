<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lost_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('item_name');
            $table->enum('category', ['lost', 'found']);
            $table->string('location');
            $table->string('current_storage');
            $table->string('contact_info');
            $table->string('reporter_contact_info')->nullable();
            $table->text('chronology');
            $table->enum('status', ['pending', 'approved', 'rejected', 'resolve_requested', 'resolved'])->default('pending');
            $table->string('image_path')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // Indeks Performa sesuai spesifikasi PRD
            $table->index('status');
            $table->index('approved_at');
            $table->index(['status', 'approved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lost_items');
    }
};
