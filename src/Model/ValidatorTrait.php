<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper\Model;

use Libriciel\ComelusApiWrapper\ComelusException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait ValidatorTrait
{
    public function validateString(string $value, string $fieldName): void
    {
        if (trim($value) === '') {
            throw new ComelusException("$fieldName must be not empty", 400);
        }
    }

    /**
     * Same rule as Comélus (`!$value`): '' and '0' are refused, a blank value is accepted.
     */
    public function validateNotEmpty(string $value, string $fieldName): void
    {
        if ($value === '' || $value === '0') {
            throw new ComelusException("$fieldName must be not empty", 400);
        }
    }

    /**
     * Optional text field: null or blank means "not provided" and is sent to the API as an empty field.
     */
    public function normalizeOptionalString(?string $value): string
    {
        return $value === null || trim($value) === '' ? '' : $value;
    }

    public function validateLength(string $value, int $maxLength, string $fieldName): void
    {
        if (strlen($value) > $maxLength) {
            throw new ComelusException(
                sprintf("The field '%s' must not exceed %d characters.", $fieldName, $maxLength),
                400
            );
        }
    }

    public function validatePositiveInt(int $value, string $fieldName): void
    {
        if ($value < 0) {
            throw new ComelusException("$fieldName must be a positive integer", 400);
        }
    }

    public function validateStrictlyPositiveInt(int $value, string $fieldName): void
    {
        if ($value < 1) {
            throw new ComelusException("$fieldName must be greater than 0", 400);
        }
    }

    public function validateUploadedFile(UploadedFile $uploadedfile, string $fieldName): void
    {
        if (!file_exists($uploadedfile->getPathname())) {
            throw new ComelusException("$fieldName file not found", 400);
        }
    }

    public function isValidUuid(string $uuid): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[13-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        ) === 1;
    }

}
