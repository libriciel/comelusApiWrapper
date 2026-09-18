<?php

namespace Libriciel\ComelusApiWrapper\Model;

use Libriciel\ComelusApiWrapper\ComelusException;

class Dossier
{
    use ValidatorDossierTrait;
    use ValidatorTrait;

    public const int MAX_LENGTH_DOSSIER_NAME = 512;

    public function __construct(
        private readonly string $name,
        private readonly string $description,
        private readonly string $mailingListId,
        private array $attachedFiles,
    ) {
        $this->validateDossierName($name);
        $this->validateString($description, 'dossier description');
        $this->validateMailingListId($mailingListId);

        if (count($attachedFiles) < 1) {
            throw new ComelusException('Dossier must contain at least one file', 400);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMailingListId(): string
    {
        return $this->mailingListId;
    }

    public function getAttachedFiles(): array
    {
        return $this->attachedFiles;
    }

    public function addFile(File $file): void
    {
        $this->attachedFiles[] = $file;
    }
}
