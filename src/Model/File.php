<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper\Model;

use Libriciel\ComelusApiWrapper\ComelusException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class File
{
    use ValidatorTrait;

    private readonly string $category;

    /** @var array<Annexe> */
    private array $annexes;

    /**
     * @param ?string $category optional: null or blank is sent empty, the dossier then has no category
     * @param ?array<Annexe> $annexes
     *
     * @throws ComelusException
     */
    public function __construct(
        ?string $category,
        private readonly string $name,
        private readonly int $rank,
        private readonly UploadedFile $uploadedFile,
        ?array $annexes = [],
    ) {
        $this->category = $this->normalizeOptionalString($category);

        if ($this->category !== '') {
            $this->validateLength($this->category, 255, 'file category');
        }

        $this->validateNotEmpty($this->name, 'file name');
        $this->validateLength($this->name, 512, 'file name');
        $this->validatePositiveInt($this->rank, 'file rank');
        $this->validateUploadedFile($this->uploadedFile, 'file uploadedFile');

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

    public function getUploadedFile(): UploadedFile
    {
        return $this->uploadedFile;
    }

    /**
     * @return array<Annexe>
     */
    public function getAnnexes(): array
    {
        return $this->annexes;
    }

    public function addAnnexe(Annexe $annexe): void
    {
        $this->annexes[] = $annexe;
    }
}
