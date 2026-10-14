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
        Schema::table('item_matches', function (Blueprint $table) {
            $table->string('lost_reporter_response')
                ->nullable()
                ->after('status');

            $table->string('found_reporter_response')
                ->nullable()
                ->after('lost_reporter_response');

            $table->timestamp('lost_user_confirmed_at')
                ->nullable();

            $table->timestamp('found_user_confirmed_at')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('item_matches', function (Blueprint $table) {
            $table->dropColumn([
                'lost_reporter_response',
                'found_reporter_response',
                'lost_user_confirmed_at',
                'found_user_confirmed_at',
            ]);
        });
    }

};
