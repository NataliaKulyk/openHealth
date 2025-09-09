<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Person\PersonRequest;

class PersonRequestRepository
{
    /**
     * Update person request status by provided UUID.
     *
     * @param  array  $response
     * @return void
     */
    public function updateStatusByUuid(array $response): void
    {
        PersonRequest::where('uuid', $response['id'])->update([
            'status' => $response['status']
        ]);
    }
}
