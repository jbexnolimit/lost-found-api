<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            // Who submitted this report
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // "lost" or "found"
            $table->string('type', 10);

            // Item information
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->text('identifying_features')->nullable();

            // Private ownership verification information
            $table->string('serial_number')->nullable();

            // Incident information
            $table->date('incident_date')->nullable();
            $table->time('incident_time')->nullable();
            $table->string('location')->nullable();
            $table->string('last_known_location')->nullable();
            $table->text('additional_details')->nullable();

            // Contact and privacy
            $table->string('preferred_contact_method')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();

            // draft, open, under_review, resolved, closed
            $table->string('status', 30)->default('draft');

            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Useful for listing and filtering
            $table->index(['type', 'status', 'created_at']);
            $table->index(['category_id', 'type']);
            $table->index(['incident_date', 'type']);
            $table->index(['color', 'type']);
            $table->index(['brand', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
