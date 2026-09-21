<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper\Test\Model;

use Libriciel\ComelusApiWrapper\ComelusException;
use Libriciel\ComelusApiWrapper\Model\Dossier;
use Libriciel\ComelusApiWrapper\Test\Model\Fixtures\ValidatorDossierTraitHost;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ValidatorDossierTraitTest extends TestCase
{
    private function makeValidator(): ValidatorDossierTraitHost
    {
        return new ValidatorDossierTraitHost();
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

    public function testValidateDossierNameWithBlankValueThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier name must be not empty');

        $this->makeValidator()->validateDossierName('   ');
    }

    public function testValidateDossierNameWithZeroThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier name must be not empty');

        $this->makeValidator()->validateDossierName('0');
    }

    /**
     * La longueur est comptée en octets, comme Comélus (strlen) : 255 « é » + « a » = 511 octets.
     */
    public function testValidateDossierNameLengthIsCountedInBytes(): void
    {
        $this->expectNotToPerformAssertions();

        $this->makeValidator()->validateDossierName(str_repeat('é', 255) . 'a');
    }

    public function testValidateDossierNameWith512BytesThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("The field 'dossier name' must not exceed 511 characters.");

        $this->makeValidator()->validateDossierName(str_repeat('é', 256));
    }

    public function testValidateDossierNameWithAllowedCharactersPasses(): void
    {
        $this->expectNotToPerformAssertions();

        $this->makeValidator()->validateDossierName('Séance n°12 : budget 2026 (1/2) – 100 € + TVA, « vote » ; 50 % ? !');
    }

    /**
     * Comélus : Dossier::NAME_PATTERN, lettres, chiffres, espaces, ponctuation, symboles monétaires, ° et +.
     */
    #[DataProvider('forbiddenNameProvider')]
    public function testValidateDossierNameWithForbiddenCharactersThrowsException(string $name): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier name contains forbidden characters');
        $this->expectExceptionCode(400);

        $this->makeValidator()->validateDossierName($name);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function forbiddenNameProvider(): iterable
    {
        yield 'chevrons' => ['Conseil <municipal>'];
        yield 'égal' => ['a = b'];
        yield 'barre verticale' => ['a | b'];
        yield 'retour à la ligne' => ["Conseil\nmunicipal"];
        yield 'retour à la ligne final' => ["Conseil municipal\n"];
        yield 'tabulation' => ["Conseil\tmunicipal"];
        yield 'emoji' => ['Conseil 🙂'];
    }

    public function testValidateMailingListIdWithAnyUuidVersionPasses(): void
    {
        $this->expectNotToPerformAssertions();

        $this->makeValidator()->validateMailingListId('44edebc7-3299-2da1-94b2-0202245d78db');
        $this->makeValidator()->validateMailingListId('44EDEBC7-3299-0DA1-94B2-0202245D78DB');
    }

    public function testValidateMailingListIdWithTrailingNewlineThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier mailingListId is not a valid UUID');

        $this->makeValidator()->validateMailingListId("44edebc7-3299-4da1-94b2-0202245d78db\n");
    }

    public function testValidateMailingListIdWithZeroThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier mailingListId must be not empty');

        $this->makeValidator()->validateMailingListId('0');
    }
}
