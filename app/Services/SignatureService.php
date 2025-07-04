<?php

namespace App\Services;

use App\Classes\Cipher\Api\CipherApi;
use App\Classes\Cipher\Exceptions\ApiException;
use Illuminate\Http\UploadedFile;
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
     * The file processing logic is now handled inside this service.
     */
    public function signData(
        array $dataToSign,
        string $password,
        string $knedp,
        ?UploadedFile $keyFile,
        string $signatoryInitiator,
        string $taxId
    ): string|array {
        try {
            $base64FileContent = $this->getBase64KepFileContent($keyFile);

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
            throw ValidationException::withMessages(['form.password' => $errorMessage]);
        } catch (\Exception $e) {
            Log::error('Unexpected error in SignatureService: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw ValidationException::withMessages(['form.password' => __('api.cipher.unexpected_error_short')]);
        }
    }

    /**
     * ADDED: Processes the uploaded KEP file and returns its base64 content.
     * This logic was moved from the Form Object.
     */
    private function getBase64KepFileContent(?UploadedFile $keyFile): string
    {
        if (!$keyFile || !$keyFile->exists()) {
            throw new \RuntimeException(__('Please upload a KEP file.'));
        }

        $fileContents = file_get_contents($keyFile->getRealPath());

        if ($fileContents === false) {
            throw new \RuntimeException(__('Could not read KEP file content.'));
        }

        return base64_encode($fileContents);
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
