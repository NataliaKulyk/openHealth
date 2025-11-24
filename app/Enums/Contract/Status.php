<?php

declare(strict_types=1);

namespace App\Enums\Contract;

use App\Traits\EnumUtils;

/**
 * Enum for eHealth Contract Request statuses.
 * Based on the provided documentation.
 */
enum Status: string
{
    use EnumUtils;

    case NEW = 'NEW';
    case APPROVED = 'APPROVED';
    case PENDING_NHS_SIGN = 'PENDING_NHS_SIGN';
    case TERMINATED = 'TERMINATED';
    case DECLINED = 'DECLINED';
    case NHS_SIGNED = 'NHS_SIGNED';
    case SIGNED = 'SIGNED';

    /**
     * Gets the translatable label for the status.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::NEW => __('statuses.contract.new'),
            self::APPROVED => __('statuses.contract.approved'),
            self::PENDING_NHS_SIGN => __('statuses.contract.pending_nhs_sign'),
            self::TERMINATED => __('statuses.contract.terminated'),
            self::DECLINED => __('statuses.contract.declined'),
            self::NHS_SIGNED => __('statuses.contract.nhs_signed'),
            self::SIGNED => __('statuses.contract.signed'),
        };
    }

    /**
     * Gets the color class for UI badges.
     * (e.g., 'blue', 'green', 'red', 'yellow', 'gray')
     */
    public function getColor(): string
    {
        return match ($this) {
            self::NEW => 'gray',
            self::APPROVED, self::PENDING_NHS_SIGN => 'yellow',
            self::NHS_SIGNED => 'blue',
            self::SIGNED => 'green',
            self::DECLINED => 'red',
            self::TERMINATED => 'dark',
        };
    }

    /**
     * Gets the list of associated document links for this status,
     * based on the provided documentation image.
     */
    public function getDocumentLinks(): array
    {
        return match ($this) {
            self::NEW, self::APPROVED, self::PENDING_NHS_SIGN, self::TERMINATED, self::DECLINED => [
                'contract_request_statute.pdf/media',
                'additional_document.pdf/media',
            ],
            self::NHS_SIGNED, self::SIGNED => [
                'contract_request_statute.pdf/media',
                'contract_request_additional_document.pdf/media',
                'signed_content/signed_content',
            ],
        };
    }
}
