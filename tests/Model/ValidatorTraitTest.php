<?php

namespace Libriciel\ComelusApiWrapper\Test\Model;

use Libriciel\ComelusApiWrapper\ComelusException;
use Libriciel\ComelusApiWrapper\Model\ValidatorTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

class ValidatorTraitTest extends TestCase
{
    private function makeValidator(): object
    {
        return new class {
            use ValidatorTrait;
        };
    }

    public function testValidateStringWithNonEmptyValuePasses(): void
    {
        $validator = $this->makeValidator();
        $validator->validateString('Hello', 'test field');
        $this->assertTrue(true); // No exception thrown
    }

    public function testValidateStringWithWhitespaceOnlyThrowsException(): void
    {
        $validator = $this->makeValidator();
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('test field must be not empty');
        $validator->validateString('   ', 'test field');
    }

    public function testValidateLengthWithinLimitPasses(): void
    {
        $validator = $this->makeValidator();
        $value = str_repeat('a', 10);
        $validator->validateLength($value, 10, 'field');
        $this->assertTrue(true);
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
        $validator = $this->makeValidator();
        $validator->validatePositiveInt(0, 'rank');
        $validator->validatePositiveInt(42, 'rank');
        $this->assertTrue(true);
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
        $validator = $this->makeValidator();
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );
        $validator->validateUploadedFile($uploadedFile, 'file uploadedfile');
        $this->assertTrue(true);
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
}
