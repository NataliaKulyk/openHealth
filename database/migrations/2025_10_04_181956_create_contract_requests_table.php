<?php

declare(strict_types=1);

use App\Enums\Contract\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_requests', static function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignId('contractor_legal_entity_id')->constrained('legal_entities');
            $table->foreignId('contractor_owner_id')->constrained('employees');
            $table->string('contractor_base');
            $table->jsonb('contractor_payment_details');
            $table->integer('contractor_rmsp_amount')->nullable();
            $table->boolean('external_contractor_flag')->nullable();
            $table->jsonb('external_contractors')->nullable();
            $table->jsonb('contractor_employee_divisions')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('nhs_legal_entity_id')->nullable()->constrained('legal_entities');
            $table->uuid('nhs_signer_id')->nullable();
            $table->string('nhs_signer_base')->nullable();
            $table->uuid('assignee_id')->nullable();
            $table->string('issue_city')->nullable();
            $table->enum('status', [Status::values()]);
            $table->text('status_reason')->nullable();
            $table->double('nhs_contract_price')->nullable();
            $table->string('nhs_payment_method')->nullable();
            $table->date('nhs_signed_date')->nullable();
            $table->foreignId('previous_request_id')->nullable()->constrained('contract_requests');
            $table->text('misc')->nullable();
            $table->string('contract_number')->nullable();
            $table->uuid('contract_id')->nullable();
            $table->foreignId('parent_contract_id')->nullable()->constrained('contracts');
            $table->text('printout_content')->nullable();
            $table->jsonb('data')->nullable();
            $table->string('id_form');
            $table->uuid('ehealth_inserted_by')->nullable();
            $table->timestamp('ehealth_inserted_at')->nullable();
            $table->uuid('ehealth_updated_by')->nullable();
            $table->timestamp('ehealth_updated_at')->nullable();
            $table->string('type');
            $table->boolean('contractor_signed');
            $table->timestamps();
        });

        Schema::create('contract_request_division', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_request_id')->constrained();
            $table->foreignId('division_id')->constrained();
            $table->timestamps();
        });

        Schema::create('medical_programs', static function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignId('contract_request_id');
            $table->uuid('ehealth_inserted_by')->nullable();
            $table->uuid('ehealth_updated_by')->nullable();
            $table->timestamp('ehealth_inserted_at')->nullable();
            $table->timestamp('ehealth_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_programs');
        Schema::dropIfExists('contractor_divisions');
        Schema::dropIfExists('contract_requests');
    }
};
