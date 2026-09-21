<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class Annexe
{
    use ValidatorTrait;

    public function __construct(
        private readonly string $name,
        private readonly int $rank,
        private readonly UploadedFile $uploadedFile,
    ) {
        $this->validateNotEmpty($this->name, 'annexe name');
        $this->validateLength($this->name, 512, 'annexe name');
        $this->validateStrictlyPositiveInt($this->rank, 'annexe rank');
        $this->validateUploadedFile($this->uploadedFile, 'annexe uploadedFile');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function getUploadedFile(): UploadedFile
    {
        return $this->uploadedFile;
    }
}
