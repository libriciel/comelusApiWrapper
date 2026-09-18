<?php

namespace Libriciel\ComelusApiWrapper\Model;

use Libriciel\ComelusApiWrapper\ComelusException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait ValidatorTrait
{
    public function validateString(string $value, string $fieldName): void
    {
        if (empty(trim($value))) {
            throw new ComelusException("$fieldName must be not empty", 400);
        }
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

    public function validateUploadedFile(UploadedFile $uploadedfile, string $fieldName): void
    {
        if ($uploadedfile === null) {
            throw new ComelusException("$fieldName must not be null", 400);
        }

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