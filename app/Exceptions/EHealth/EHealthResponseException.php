<?php

namespace App\Exceptions\EHealth;

use Exception;
use Illuminate\Http\Client\Response;

class EHealthResponseException extends Exception
{
    public function __construct(public readonly Response $response)
    {
        $message = $this->extractErrorMessage($this->response);
        $code = $this->response->status();
        parent::__construct($message, $code);
    }

    /**
     * Helper method to extract the most relevant error message.
     */
    protected function extractErrorMessage(Response $response): string
    {
        $errorMessage = $response->json('error.message') ?? $response->reason();

        if ($errorMessage === 'Invalid signature') {
            return __('forms.invalid_kep_password');
        }

        return sprintf(
            'Помилка EHealth (статус %d): %s',
            $response->status(),
            $errorMessage
        );
    }
}
