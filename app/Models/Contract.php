<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\EHealthDateCast;
use App\Enums\Contract\Status;
use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use HasCamelCasing;

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'start_date',
        'end_date',
        'status',
        'contractor_legal_entity_id',
        'contractor_owner_id',
        'contractor_payment_details',
        'bank_name',
        'MFO',
        'payer_account',
        'contractor_rmsp_amount',
        'external_contractor_flag',
        'external_contractors',
        'nhs_signer_id',
        'nhs_signer_base',
        'nhs_payment_method',
        'is_active',
        'is_suspended',
        'issue_city',
        'nhs_contract_price',
        'contract_number',
        'contract_request_id',
        'contract_id',
        'status_reason',
        'inserted_by',
        'inserted_at',
        'updated_at',
        'id_form',
        'nhs_signed_date',
        'type',
        'reason',
        'contractor_base',
        'signed_content_location',
        'skip_provision_deactivation',
        'statute_md5',
        'additional_document_md5',
        'legal_entity_id',
        'nhs_legal_entity_id',
        'previous_request_id',
        'medical_programs',
        'data'
    ];

    protected $casts = [
        'contractor_payment_details' => 'array',
        'external_contractors' => 'array',
        'medical_programs' => 'array',
        'data' => 'array',
        'start_date' => EHealthDateCast::class,
        'end_date' => EHealthDateCast::class,
        'status' => Status::class
    ];

    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }
}
