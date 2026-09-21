<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper\Test\Model;

use Libriciel\ComelusApiWrapper\ComelusException;
use Libriciel\ComelusApiWrapper\Model\ValidatorTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

final class ValidatorTraitTest extends TestCase
{
    private function makeValidator(): object
    {
        return new class() {
            use ValidatorTrait;
        };
    }

    public function testValidateLengthWithinLimitPasses(): void
    {
        $this->expectNotToPerformAssertions();

        $validator = $this->makeValidator();
        $value = str_repeat('a', 10);
        $validator->validateLength($value, 10, 'field');
    }

    public function testValidateLengthExceedingLimitThrowsException(): void
    {
        $validator = $this->makeValidator();
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("The field 'field' must not exceed 5 characters.");
        $validator->validateLength('abcdef', 5, 'field');
    }

    public function testValidatePositiveIntWithZeroAndPositivePasses(): void
    {
        $this->expectNotToPerformAssertions();

        $validator = $this->makeValidator();
        $validator->validatePositiveInt(0, 'rank');
        $validator->validatePositiveInt(42, 'rank');
    }

    public function testValidatePositiveIntWithNegativeThrowsException(): void
    {
        $validator = $this->makeValidator();
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('rank must be a positive integer');
        $validator->validatePositiveInt(-1, 'rank');
    }

    public function testValidateUploadedFileWithValidFilePasses(): void
    {
        $this->expectNotToPerformAssertions();

        $validator = $this->makeValidator();
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );
        $validator->validateUploadedFile($uploadedFile, 'file uploadedfile is not a valid uploaded file');
    }

    public function testValidateUploadedFileWithDeletedFileThrowsException(): void
    {
        $validator = $this->makeValidator();
        $path = tempnam(sys_get_temp_dir(), 'comelus');
        $this->assertIsString($path);
        $uploadedFile = new UploadedFile($path, 'deleted.pdf');
        unlink($path);

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('annexe uploadedFile file not found');
        $this->expectExceptionCode(400);

        $validator->validateUploadedFile($uploadedFile, 'annexe uploadedFile');
    }

    public function testIsValidUuidReturnsTrueForValidUuid(): void
    {
        $validator = $this->makeValidator();
        $uuid = Uuid::v7()->toRfc4122();
        $this->assertTrue($validator->isValidUuid($uuid));
    }

    public function testIsValidUuidReturnsFalseForInvalidUuid(): void
    {
        $validator = $this->makeValidator();
        $this->assertFalse($validator->isValidUuid('not-a-uuid'));
        $this->assertFalse($validator->isValidUuid('12345678-1234-1234-1234-12345678901Z'));
    }

    /**
     * Écart volontaire avec Comélus, qui accepte un texte blanc : il n'a jamais de sens.
     */
    public function testValidateNotEmptyWithBlankValueThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('field must be not empty');

        $this->makeValidator()->validateNotEmpty(" \t ", 'field');
    }

    public function testValidateNotEmptyWithEmptyValueThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('field must be not empty');

        $this->makeValidator()->validateNotEmpty('', 'field');
    }

    public function testValidateNotEmptyWithZeroThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('field must be not empty');

        $this->makeValidator()->validateNotEmpty('0', 'field');
    }

    public function testValidateStrictlyPositiveIntWithOnePasses(): void
    {
        $this->expectNotToPerformAssertions();

        $this->makeValidator()->validateStrictlyPositiveInt(1, 'rank');
    }

    public function testValidateStrictlyPositiveIntWithZeroThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('rank must be greater than 0');

        $this->makeValidator()->validateStrictlyPositiveInt(0, 'rank');
    }

    /**
     * Même règle que Uuid::isValid() de symfony/uid 6.4, utilisé par Comélus : toute version, variante RFC 4122.
     */
    public function testIsValidUuidAcceptsAnyVersion(): void
    {
        $validator = $this->makeValidator();

        $this->assertTrue($validator->isValidUuid('44edebc7-3299-2da1-94b2-0202245d78db'));
        $this->assertTrue($validator->isValidUuid('44edebc7-3299-0da1-94b2-0202245d78db'));
    }

    public function testIsValidUuidRejectsTrailingNewlineAndWrongVariant(): void
    {
        $validator = $this->makeValidator();

        $this->assertFalse($validator->isValidUuid("44edebc7-3299-4da1-94b2-0202245d78db\n"));
        $this->assertFalse($validator->isValidUuid('44edebc7-3299-4da1-c4b2-0202245d78db'));
    }
}
