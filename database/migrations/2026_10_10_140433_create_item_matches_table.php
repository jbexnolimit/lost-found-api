<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_matches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lost_item_id')
                ->constrained('items')
                ->cascadeOnDelete();

            $table->foreignId('found_item_id')
                ->constrained('items')
                ->cascadeOnDelete();

            $table->decimal('match_score', 5, 2)->nullable();

            // pending, confirmed, rejected
            $table->string('status', 20)->default('pending');

            $table->timestamps();

            $table->unique(['lost_item_id', 'found_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_matches');
    }
};
