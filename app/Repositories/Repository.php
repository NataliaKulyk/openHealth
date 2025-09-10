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

    public static function document(): DocumentRepository
    {
        return app(DocumentRepository::class);
    }

    public static function authenticationMethod(): AuthenticationMethodRepository
    {
        return app(AuthenticationMethodRepository::class);
    }

    public static function confidantPerson(): ConfidantPersonRepository
    {
        return app(ConfidantPersonRepository::class);
    }

    public static function employee(): EmployeeRepository
    {
        return app(EmployeeRepository::class);
    }

    public static function education(): EducationRepository
    {
        return app(EducationRepository::class);
    }

    public static function speciality(): SpecialityRepository
    {
        return app(SpecialityRepository::class);
    }

    public static function qualification(): QualificationRepository
    {
        return app(QualificationRepository::class);
    }

    public static function scienceDegree(): ScienceDegreeRepository
    {
        return app(ScienceDegreeRepository::class);
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
}
