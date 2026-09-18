<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class File
{
    use ValidatorTrait;

    public function __construct(
        private ?string $category,
        private ?string $name,
        private ?int $rank,
        private UploadedFile $uploadedFile,
        private ?array $annexes = [],
    ) {
        if (!empty($this->category)) {
            $this->validateString($this->category, 'file category');
            $this->validateLength($this->category, 255, 'file category');
        }

        if (!empty($this->name)) {
            $this->validateString($this->name, 'file name');
            $this->validateLength($this->name, 512, 'file name');
        }

        if ($this->rank !== null) {
            $this->validatePositiveInt($this->rank, 'file rank');
        }

        $this->validateUploadedFile($this->uploadedFile, 'file uploadedFile');

        // Normalize optional fields: if null, convert to empty string; keep empty string as-is
        $this->category = $this->category ?? '';
        $this->name = $this->name ?? '';
        $this->annexes ??= [];
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getName(): ?string
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

    public function getAnnexes(): ?array
    {
        return $this->annexes;
    }

    public function addAnnexe(Annexe $annexe): void
    {
        $this->annexes[] = $annexe;
    }
}
