<?php

declare(strict_types=1);

namespace App\Repositories;

final class Repository
{
    public static function address(): AddressRepository
    {
        return app(AddressRepository::class);
    }

    public static function phone(): PhoneRepository
    {
        return app(PhoneRepository::class);
    }

    public static function employee(): EmployeeRepository
    {
        return app(EmployeeRepository::class);
    }

    public static function employeeRole(): EmployeeRoleRepository
    {
        return app(EmployeeRoleRepository::class);
    }

    public static function division(): DivisionRepository
    {
        return app(DivisionRepository::class);
    }

    public static function healthcareService(): HealthcareServiceRepository
    {
        return app(HealthcareServiceRepository::class);
    }

    public static function declarationRequest(): DeclarationRequestRepository
    {
        return app(DeclarationRequestRepository::class);
    }

    public static function declaration(): DeclarationRepository
    {
        return app(DeclarationRepository::class);
    }

    public static function user(): UserRepository
    {
        return app(UserRepository::class);
    }

    public static function personRequest(): PersonRequestRepository
    {
        return app(PersonRequestRepository::class);
    }

    public static function person(): PersonRepository
    {
        return app(PersonRepository::class);
    }

    public static function equipment(): EquipmentRepository
    {
        return app(EquipmentRepository::class);
    }

    public static function legalEntity(): LegalEntityRepository
    {
        return app(LegalEntityRepository::class);
    }

    public static function contract()
    {
        return app(ContractRepository::class);
    }

    public static function confidantPerson()
    {
        return app(ConfidantPersonRepository::class);
    }

    public static function revision()
    {
        return app(RevisionRepository::class);
    }
}
