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

            // eHealth ID (assigned after initialization)
            $table->uuid('uuid')->nullable()->unique();

            // Relations
            $table->uuid('contractor_legal_entity_id')->index();
            $table->uuid('contractor_owner_id')->index();
            $table->uuid('previous_request_id')->nullable()->index();
            $table->uuid('parent_contract_id')->nullable()->index();

            // Basic fields
            $table->string('contractor_base')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('id_form')->nullable();
            $table->string('status')->default('NEW')->index();
            $table->text('status_reason')->nullable();
            $table->string('type')->nullable(); // CAPITATION / REIMBURSEMENT

            // JSONB fields for detailed structures
            $table->jsonb('contractor_payment_details')->nullable();
            $table->jsonb('external_contractors')->nullable();
            $table->jsonb('contractor_employee_divisions')->nullable();

            // Raw response data storage
            $table->jsonb('data')->nullable();

            // Dates
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // NHS (NSZU) side data
            $table->uuid('nhs_legal_entity_id')->nullable();
            $table->uuid('nhs_signer_id')->nullable();
            $table->string('nhs_signer_base')->nullable();
            $table->double('nhs_contract_price')->nullable();
            $table->string('nhs_payment_method')->nullable();
            $table->date('nhs_signed_date')->nullable();

            // Metadata
            $table->uuid('ehealth_inserted_by')->nullable();
            $table->timestamp('ehealth_inserted_at')->nullable();
            $table->boolean('contractor_signed')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_requests');
    }
};
