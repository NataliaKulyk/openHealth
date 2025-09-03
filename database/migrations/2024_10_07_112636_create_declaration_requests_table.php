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
        Schema::create('declaration_requests', static function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique()->nullable();
            $table->string('authorize_with')->nullable()->comment("identifier of person's auth method");
            $table->jsonb('data_to_be_signed')->nullable();
            $table->string('channel')->nullable();
            $table->integer('current_declaration_count')->nullable();
            $table->string('parent_declaration_id')
                ->nullable()
                ->comment('identifier of parent declaration in reorganized legal entity');
            $table->string('declaration_number')->nullable();
            $table->foreignId('division_id')
                ->comment('Registered Medical Service Provider Division identifier.')
                ->constrained();
            $table->foreignId('employee_id')
                ->comment('Employee ID with type=DOCTOR selected from available Employees as a third contract party.')
                ->constrained();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('legal_entity_id')->constrained();
            $table->foreignId('person_id')->constrained('persons');
            $table->enum('status', ['DRAFT', 'NEW', 'APPROVED', 'SIGNED', 'REJECTED', 'CANCELLED'])->nullable();
            $table->string('status_reason')->nullable();
            $table->integer('system_declaration_limit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('declaration_requests');
    }
};
