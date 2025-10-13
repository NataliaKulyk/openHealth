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
        Schema::create('legal_entity_type_roles', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legal_entity_type_id')->constrained()->cascadeOnDelete();

            $table->primary(['role_id', 'legal_entity_type_id']);

            $table->timestamps();

            $table->unique(['role_id', 'legal_entity_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_entity_type_roles');
    }
};
