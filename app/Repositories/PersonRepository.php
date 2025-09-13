<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Person\Person;
use App\Models\Person\PersonRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class PersonRepository
{
    /**
     * Save person request response to DB.
     *
     * @param  array  $response  Response from API
     * @param  string  $modelClass
     * @param  string|null  $personUuid
     * @return void
     * @throws Throwable
     */
    public function savePersonResponseData(array $response, string $modelClass, ?string $personUuid = null): void
    {
        DB::transaction(static function () use ($response, $modelClass, $personUuid) {
            $personRequest = self::createOrUpdate($response, $modelClass, $personUuid);

            $documents = $response['person']['documents'] ?? $response['documents'] ?? null;
            if ($documents) {
                Repository::document()->addDocuments($personRequest, $documents);
            }

            $addresses = $response['person']['addresses'] ?? [$response['addresses']] ?? null;
            if ($addresses) {
                Repository::address()->addAddresses($personRequest, $addresses);
            }

            $phones = $response['person']['phones'] ?? $response['patient']['phones'] ?? null;
            if ($phones) {
                Repository::phone()->addPhones($personRequest, $phones);
            }

            $authenticationMethods = $response['person']['authentication_methods'] ?? $response['patient']['authentication_methods'] ?? null;
            if ($authenticationMethods) {
                Repository::authenticationMethod()->addAuthenticationMethod($personRequest, $authenticationMethods);
            }

            if (!empty($response['confidant_person'])) {
                $confidantData = [
                    'documents_relationship' => $response['documents_relationship'],
                    'confidantPersonInfo' => $response['confidant_person'][0]
                ];

                Repository::confidantPerson()->addConfidantPerson($personRequest, $confidantData);
            }

            if (!empty($response['person']['confidant_person'])) {
                Repository::confidantPerson()->addConfidantPerson(
                    $personRequest,
                    $response['person']['confidant_person']
                );
            }
        });
    }

    /**
     * Create or update data in DB.
     *
     * @param  array  $data
     * @param  string  $modelClass
     * @param  string|null  $personUuid
     * @return PersonRequest|Person
     */
    protected static function createOrUpdate(
        array $data,
        string $modelClass,
        ?string $personUuid = null
    ): PersonRequest|Person {
        if (isset($data['patient'])) {
            $data['person'] = $data['patient'];
        }

        $personData = [
            'uuid' => $personUuid ?? $data['id'] ?? null,
            'first_name' => $data['person']['first_name'],
            'last_name' => $data['person']['last_name'],
            'second_name' => $data['person']['second_name'] ?? null,
            'birth_date' => Carbon::parse($data['person']['birth_date'])->format('Y-m-d'),
            'birth_country' => $data['person']['birth_country'],
            'birth_settlement' => $data['person']['birth_settlement'],
            'gender' => $data['person']['gender'],
            'email' => $data['person']['email'] ?? null,
            'no_tax_id' => $data['person']['no_tax_id'],
            'tax_id' => $data['person']['tax_id'] ?? null,
            'secret' => $data['person']['secret'],
            'unzr' => $data['person']['unzr'] ?? null,
            'emergency_contact' => $data['person']['emergency_contact'],
            'patient_signed' => $data['patient_signed'] ?? false,
            'process_disclosure_data_consent' => $data['process_disclosure_data_consent'] ?? true
        ];

        if ($modelClass === PersonRequest::class) {
            $personData['status'] = $data['status'] ?? 'APPLICATION';
        }

        // Update or create data based on email
        return $modelClass::updateOrCreate(['email' => $personData['email'] ?? null], $personData);
    }

    /**
     * Establish a connection between PersonRequest and Person.
     *
     * @param  array  $response
     * @return void
     */
    public function createRelation(array $response): void
    {
        $personRequest = PersonRequest::where('uuid', $response['id'])->firstOrFail();
        $person = Person::where('uuid', $response['person_id'])->firstOrFail();

        $personRequest->person()->associate($person);
        $personRequest->save();
    }

    /**
     * Update verification status by provided ID or UUID.
     *
     * @param  int|string  $personId
     * @param  string  $verificationStatus
     * @return void
     */
    public function updateVerificationStatusById(int|string $personId, string $verificationStatus): void
    {
        $query = Person::query();

        if (is_numeric($personId)) {
            $query->where('id', $personId);
        } else {
            $query->where('uuid', $personId);
        }

        $query->update(['verification_status' => $verificationStatus]);
    }
}
