<?php

namespace App\Exceptions\EHealth;

use Illuminate\Http\Client\Response;
use RuntimeException;

class EHealthResponseException extends RuntimeException
{
    public function __construct(public readonly Response $response)
    {
        $errorMessage = $response->json('error.message') ?? $response->reason();

        if ($errorMessage === 'Invalid signature') {
            $message = __('forms.invalid_kep_password');
        } else {
            $message = sprintf(
                'Помилка EHealth (статус %d): %s',
                $response->status(),
                $errorMessage
            );
        }

        parent::__construct($message);
    }
}
