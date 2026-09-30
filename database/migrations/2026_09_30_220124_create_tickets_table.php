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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            // Filled right after insert from the id (see TicketObserver), hence nullable.
            $table->string('reference', 12)->nullable()->unique();
            $table->string('title');
            $table->text('description');
            $table->string('status', 20)->default('open')->index();
            $table->string('priority', 20)->default('medium')->index();
            // A category holding tickets cannot be deleted.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Used by tickets:close-stale (status = resolved AND updated_at < cutoff).
            $table->index(['status', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
