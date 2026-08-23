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
            $table->string('subject');
            $table->text('body');
            $table->string('status')->default('open')->index();
            $table->string('priority')->default('normal')->index();
            $table->foreignId('requester_id')->index()->constrained('users')->cascadeOnDelete();
            $table->foreignId('assignee_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'created_at']);
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
