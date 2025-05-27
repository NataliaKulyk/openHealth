<?php

declare(strict_types=1);

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
        Schema::create('observations', static function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('encounter_id')->nullable()->constrained('encounters')->cascadeOnDelete();
            $table->enum('status', ['valid', 'entered_in_error'])->comment('dictionary - eHealth/observation_statuses');
            $table->foreignId('diagnostic_report_id')->nullable()->constrained('identifiers')->cascadeOnDelete();
            $table->foreignId('code_id')->constrained('codeable_concepts')->cascadeOnDelete();
            $table->timestamp('effective_date_time')->nullable();
            $table->timestamp('issued');
            $table->boolean('primary_source');
            $table->foreignId('performer_id')->nullable()->constrained('identifiers')->cascadeOnDelete();
            $table->foreignId('report_origin_id')->nullable()->constrained('codeable_concepts')->cascadeOnDelete();
            $table->foreignId('interpretation_id')->nullable()->constrained('codeable_concepts')->cascadeOnDelete();
            $table->text('comment')->nullable();
            $table->foreignId('body_site_id')->nullable()->constrained('codeable_concepts')->cascadeOnDelete();
            $table->foreignId('method_id')->nullable()->constrained('codeable_concepts')->cascadeOnDelete();
            $table->foreignId('value_quantity_id')->nullable()->constrained('quantities')->cascadeOnDelete();
            $table->foreignId('value_codeable_concept_id')->nullable()->constrained('codeable_concepts')->cascadeOnDelete();
            $table->string('value_string')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->timestamp('value_date_time')->nullable();
            $table->foreignId('reaction_on_id')->nullable()->constrained('identifiers')->cascadeOnDelete();
            $table->foreignId('context_id')->constrained('identifiers')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('observation_categories', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('observation_id')->constrained('observations')->cascadeOnDelete();
            $table->foreignId('codeable_concept_id')->constrained('codeable_concepts')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('observation_components', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('observation_id')->constrained('observations')->cascadeOnDelete();
            $table->foreignId('codeable_concept_id')->constrained('codeable_concepts')->cascadeOnDelete();
            $table->foreignId('interpretation_id')->nullable()->constrained('codeable_concepts')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('observation_components');
        Schema::dropIfExists('observation_categories');
        Schema::dropIfExists('observations');
    }
};
