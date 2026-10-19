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
        Schema::create('item_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lost_item_id')->constrained('lost_items')->cascadeOnDelete();
            $table->foreignId('reporter_user_id')->constrained('users')->restrictOnDelete();
            $table->text('message');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'closed'])->default('pending');
            $table->timestamps();

            // Composite Indexes untuk query & validasi anti-duplikat pending
            $table->index(['lost_item_id', 'status']);
            $table->index(['reporter_user_id', 'lost_item_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_reports');
    }
};
