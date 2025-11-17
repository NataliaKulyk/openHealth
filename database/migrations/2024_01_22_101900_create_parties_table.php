<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique()->nullable();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('second_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('tax_id')->nullable()->index();
            $table->boolean('no_tax_id')->nullable()->default(false);
            $table->text('about_myself')->nullable();
            $table->integer('working_experience')->nullable();

            // --- Verification Section ---
            $table->string('verification_status')->nullable()->comment('Overall verification status');
            $table->string('drfo_status')->nullable()->index()->comment('DRFO verification status');
            $table->string('dracs_death_status')->nullable()->index()->comment('DRACS (death) verification status');
            $table->string('mvs_passport_status')->nullable()->index()->comment('MVS (passport) verification status');
            $table->string('dms_passport_status')->nullable()->index()->comment('DMS (passport) verification status');
            $table->string('dracs_name_change_status')->nullable()->index()->comment('DRACS (name change) verification status');
            // --- End Section ---

            $table->integer('declaration_count')->nullable();
            $table->integer('declaration_limit')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
