<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class Annexe
{
    use ValidatorTrait;

    private readonly string $name;

    public function __construct(
        ?string $name,
        private readonly ?int $rank,
        private readonly UploadedFile $uploadedFile,
    ) {
        $this->name = $this->normalizeOptionalString($name);

        if ($this->name !== '') {
            $this->validateLength($this->name, 512, 'annexe name');
        }

        if ($this->rank !== null) {
            $this->validatePositiveInt($this->rank, 'annexe rank');
        }

        $this->validateUploadedFile($this->uploadedFile, 'annexe uploadedFile');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRank(): ?int
    {
        return $this->rank;
    }

    public function getUploadedFile(): UploadedFile
    {
        return $this->uploadedFile;
    }
}
