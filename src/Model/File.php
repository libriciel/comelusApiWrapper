<?php

namespace Libriciel\ComelusApiWrapper\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class File
{
    use ValidatorTrait;

    public function __construct(
        private readonly string $category,
        private readonly string $name,
        private readonly int $rank,
        private readonly UploadedFile $uploadedfile,
        private ?array $annexes,
    ) {
        if ($category !== null) {
            $this->validateString($category, 'file category');
            $this->validateLength($category, 255, 'file category');
        }

        $this->validateString($name, 'file name');
        $this->validateLength($name, 512, 'file name');
        $this->validatePositiveInt($rank, 'file rank');
        $this->validateUploadedFile($uploadedfile, 'file uploadedfile');
        $this->annexes = $annexes ?? [];
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function getUploadedfile(): UploadedFile
    {
        return $this->uploadedfile;
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