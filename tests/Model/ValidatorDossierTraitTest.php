<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper\Test\Model;

use Libriciel\ComelusApiWrapper\ComelusException;
use Libriciel\ComelusApiWrapper\Model\Dossier;
use Libriciel\ComelusApiWrapper\Model\ValidatorDossierTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ValidatorDossierTraitTest extends TestCase
{
    private function makeValidator(): object
    {
        return new class() {
            use ValidatorDossierTrait;
        };
    }

    public function testValidateDossierNameWithValidValuePasses(): void
    {
        $this->expectNotToPerformAssertions();

        $validator = $this->makeValidator();
        $validator->validateDossierName('Projet Alpha');
    }

    public function testValidateDossierNameLength(): void
    {
        $this->expectNotToPerformAssertions();

        $validator = $this->makeValidator();
        $long = str_repeat('a', Dossier::MAX_LENGTH_DOSSIER_NAME);
        $validator->validateDossierName($long);
    }

    public function testValidateDossierNameWithEmptyStringThrowsException(): void
    {
        $validator = $this->makeValidator();
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier name must be not empty');
        $validator->validateDossierName('');
    }

    public function testValidateDossierNameWithWhitespaceOnlyThrowsException(): void
    {
        $validator = $this->makeValidator();
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier name must be not empty');
        $validator->validateDossierName('   ');
    }

    public function testValidateDossierNameExceedingMaxLengthThrowsException(): void
    {
        $validator = $this->makeValidator();
        $tooLong = str_repeat('a', Dossier::MAX_LENGTH_DOSSIER_NAME + 1);
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("The field 'dossier name' must not exceed " . Dossier::MAX_LENGTH_DOSSIER_NAME . ' characters.');
        $validator->validateDossierName($tooLong);
    }

    public function testValidateMailingListIdWithValidUuidPasses(): void
    {
        $this->expectNotToPerformAssertions();

        $validator = $this->makeValidator();
        $uuid = Uuid::v7()->toRfc4122();
        $validator->validateMailingListId($uuid);
    }

    public function testValidateMailingListIdWithEmptyStringThrowsException(): void
    {
        $validator = $this->makeValidator();
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier mailingListId must be not empty');
        $validator->validateMailingListId('');
    }

    public function testValidateMailingListIdWithInvalidUuidThrowsException(): void
    {
        $validator = $this->makeValidator();
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier mailingListId is not a valid UUID');
        $validator->validateMailingListId('invalid-uuid');
    }
}
