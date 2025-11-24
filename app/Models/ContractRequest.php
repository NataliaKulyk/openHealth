<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractRequest extends Model
{
    protected $fillable = [
        'uuid',
        'contractor_legal_entity_id',
        'contractor_owner_id',
        'contractor_base',
        'contractor_payment_details',
        'contractor_rmsp_amount',
        'external_contractor_flag',
        'external_contractors',
        'contractor_employee_divisions',
        'start_date',
        'end_date',
        'nhs_legal_entity_id',
        'nhs_signer_id',
        'nhs_signer_base',
        'assignee_id',
        'issue_city',
        'status',
        'status_reason',
        'nhs_contract_price',
        'nhs_payment_method',
        'nhs_signed_date',
        'previous_request_id',
        'misc',
        'contract_number',
        'contract_id',
        'parent_contract_id',
        'printout_content',
        'data',
        'id_form',
        'ehealth_inserted_by',
        'ehealth_inserted_at',
        'ehealth_updated_by',
        'ehealth_updated_at',
        'type',
        'contractor_signed'
    ];
}
