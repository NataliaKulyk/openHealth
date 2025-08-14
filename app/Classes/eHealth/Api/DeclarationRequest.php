<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest as Request;
use App\Classes\eHealth\EHealthResponse;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use stdClass;

class DeclarationRequest extends Request
{
    protected const string URL = '/api/v3/declaration_requests';

    /**
     * Create Declaration Request (as part of Declaration creation process) only for an existing person.
     *
     * @param  string  $url
     * @param  array  $data
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function create(string $url = self::URL, array $data = []): PromiseInterface|EHealthResponse
    {
        return $this->post($url, $data);
    }

    /**
     * Resend sms on previously created Declaration Request V3.
     *
     * @param  string  $id
     * @param  array  $data
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function resendAuthOtp(string $id, array $data = []): PromiseInterface|EHealthResponse
    {
        return $this->post(self::URL . "/$id/actions/resend_otp", $data);
    }


    /**
     * Upload to the (Signed URL's). All links are generated for one one-page document.
     *
     *
     * @param  string  $uploadUrl
     * @param  UploadedFile  $document
     * @return PromiseInterface|Response
     * @throws ConnectionException
     */
    public function uploadDocument(string $uploadUrl, UploadedFile $document): PromiseInterface|Response
    {
        $filePath = $document->getRealPath();
        $fileMime = $document->getMimeType();
        $fileContents = file_get_contents($filePath);

        return Http::withHeaders(['Content-Type' => $fileMime])
            ->withBody($fileContents, $fileMime)
            ->put(trim($uploadUrl));
    }

    /**
     * Approve previously created Declaration Request.
     *
     * @param  string  $id
     * @param  array  $data
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function approve(string $id, array $data = []): PromiseInterface|EHealthResponse
    {
        return $this->patch(self::URL . "/$id/actions/approve", $data ?: new stdClass());
    }
}
