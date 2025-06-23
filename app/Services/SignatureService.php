<?php

namespace App\Services;

use App\Classes\Cipher\Api\CipherApi;
use App\Classes\Cipher\Exceptions\ApiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SignatureService
{
    protected CipherApi $cipherApi;

    public function __construct(CipherApi $cipherApi)
    {
        $this->cipherApi = $cipherApi;
    }

    /**
     * Sends data for signing using Cipher API.
     * Throws a user-friendly ValidationException on failure.
     */
    public function signData(
        array $dataToSign,
        string $password,
        string $knedp,
        string $base64FileContent,
        string $signatoryInitiator,
        string $taxId
    ): string|array {
        try {
            return $this->cipherApi->sendSession(
                json_encode($dataToSign, JSON_THROW_ON_ERROR),
                $password,
                $base64FileContent,
                $knedp,
                $signatoryInitiator,
                $taxId
            );
        } catch (ApiException $e) {
            $errors = $e->getErrors();
            $errorMessage = collect($errors)->flatten()->first() ?? __('forms.invalid_kep_password_or_file');

            throw ValidationException::withMessages([
                                                        'form.password' => $errorMessage,
                                                    ]);
        } catch (\Exception $e) {
            Log::error('Unexpected error in SignatureService: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw ValidationException::withMessages([
                                                        'form.password' => __('api.cipher.unexpected_error_short'),
                                                    ]);
        }
    }

    /**
     * Retrieves supported certificate authorities from Cipher API, cached for 7 days.
     *
     * @return array An array of certificate authorities.
     */
    public function getCertificateAuthorities(): array
    {
        return Cache::remember('knedp_certificate_authority', now()->addDays(7), function () {
            try {
                return $this->cipherApi->getCertificateAuthorityApi();
            } catch (ApiException $e) {
                Log::error("Error fetching certificate authorities from Cipher API: " . $e->getMessage(), ['errors' => $e->getErrors()]);
                return [];
            } catch (\Exception $e) {
                Log::error("General error fetching certificate authorities: " . $e->getMessage(), ['exception' => $e]);
                return [];
            }
        });
    }
}
