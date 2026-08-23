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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('requester')->index();
            $table->foreignId('team_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->boolean('on_call')->default(false)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
            $table->dropIndex(['role']);
            $table->dropIndex(['on_call']);
            $table->dropColumn(['role', 'title', 'on_call']);
        });
    }
};
