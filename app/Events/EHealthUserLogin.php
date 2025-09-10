<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use App\Models\LegalEntity;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched right before an eHealth user is logged into the application.
 * Contains all necessary data for pre-login processing, like employee synchronization.
 */
class EHealthUserLogin
{
    use Dispatchable, SerializesModels;

    /**
     * @param User $user The user model.
     * @param LegalEntity $legalEntity The legal entity context.
     * @param string $authUserUUID The user's UUID from the eHealth token.
     */
    public function __construct(
        public User $user,
        public LegalEntity $legalEntity,
        public string $authUserUUID,
        public bool $isFirstLogin = false
    ) {
    }
}
