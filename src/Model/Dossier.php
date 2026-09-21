<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper\Model;

use Libriciel\ComelusApiWrapper\ComelusException;

final class Dossier
{
    use ValidatorDossierTrait;
    use ValidatorTrait;

    /**
     * In bytes: Comélus refuses `strlen($name) >= 512`.
     */
    public const int MAX_LENGTH_DOSSIER_NAME = 511;

    /**
     * Comélus Dossier::NAME_PATTERN: letters, digits, spaces, punctuation, currency symbols, ° and +.
     */
    public const string NAME_PATTERN = '/^[\p{L}\p{M}\p{N}\p{Zs}\p{P}\p{Sc}°+]+$/Du';

    /**
     * @param array<File> $attachedFiles
     *
     * @throws ComelusException
     */
    public function __construct(
        private readonly string $name,
        private readonly string $description,
        private readonly string $mailingListId,
        private array $attachedFiles,
    ) {
        $this->validateDossierName($name);
        $this->validateNotEmpty($description, 'dossier description');
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

    /**
     * @return array<File>
     */
    public function getAttachedFiles(): array
    {
        return $this->attachedFiles;
    }

    public function addFile(File $file): void
    {
        $this->attachedFiles[] = $file;
    }
}
