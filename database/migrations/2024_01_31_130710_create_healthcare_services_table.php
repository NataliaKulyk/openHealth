<?php

use App\Enums\Status;
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
        Schema::create('healthcare_services', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('division_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->uuid('legal_entity_uuid')->nullable();
            $table->string('speciality_type')->nullable();
            $table->string('providing_condition')->nullable();
            $table->string('license_id')->nullable();
            $table->enum('status', Status::only(['ACTIVE', 'INACTIVE']))->nullable();
            $table->jsonb('category');
            $table->jsonb('type')->nullable();
            $table->text('comment')->nullable();
            $table->jsonb('coverage_area')->nullable();
            $table->jsonb('available_time')->nullable();
            $table->jsonb('not_available')->nullable();
            $table->boolean('is_active')->default(false);
            $table->jsonb('licensed_healthcare_service')->nullable();
            $table->date('ehealth_inserted_at')->nullable();
            $table->string('ehealth_inserted_by')->nullable();
            $table->date('ehealth_updated_at')->nullable();
            $table->string('ehealth_updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('healthcare_services');
    }
};
