<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contracts', static function(Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('legal_entity_id')->constrained('legal_entities');
            $table->uuid('contractor_legal_entity_id');
            $table->uuid('contractor_owner_id');
            $table->string('contractor_base');
            $table->jsonb('contractor_payment_details');
            $table->string('contractor_rmsp_amount')->nullable();
            $table->boolean('external_contractor_flag')->default(false);
            $table->jsonb('external_contractors')->nullable();
            $table->jsonb('contractor_employee_divisions')->nullable();
            $table->jsonb('contractor_divisions')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->uuid('nhs_legal_entity_id')->nullable();
            $table->uuid('nhs_signer_id')->nullable();
            $table->string('nhs_signer_base')->nullable();
            $table->string('issue_city')->nullable(); // The city is filled in when signing
            $table->double('nhs_contract_price')->nullable(); // The price can be null
            $table->string('contract_number')->nullable(); // The number is assigned later
            $table->uuid('contract_id')->nullable(); // The ID of the current contract will appear later
            $table->uuid('assignee_id')->nullable();
            $table->string('status');
            $table->string('status_reason')->nullable();
            $table->string('nhs_payment_method')->nullable();
            $table->date('nhs_signed_date')->nullable();
            $table->uuid('previous_request_id')->nullable();
            $table->jsonb('data')->nullable();
            $table->string('id_form')->nullable();
            $table->uuid('inserted_by')->nullable();
            $table->timestamp('inserted_at');
            $table->uuid('updated_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->jsonb('medical_programs')->nullable();
            $table->string('type')->nullable();
            $table->boolean('contractor_signed')->default(false);
            $table->text('misc')->nullable();
            $table->string('statute_md5')->nullable();
            $table->string('additional_document_md5')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
