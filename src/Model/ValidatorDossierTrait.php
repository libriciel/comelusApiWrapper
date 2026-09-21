<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper\Model;

use Libriciel\ComelusApiWrapper\ComelusException;

trait ValidatorDossierTrait
{
    use ValidatorTrait;

    /**
     * Same checks, in the same order, as Comélus (ApiV2Manager::checkDossierName()).
     */
    public function validateDossierName(string $name): void
    {
        $this->validateNotEmpty($name, 'dossier name');
        $this->validateLength($name, Dossier::MAX_LENGTH_DOSSIER_NAME, 'dossier name');

        if (!preg_match(Dossier::NAME_PATTERN, $name)) {
            throw new ComelusException('dossier name contains forbidden characters', 400);
        }
    }

    public function validateMailingListId(string $mailingListId): void
    {
        $this->validateNotEmpty($mailingListId, 'dossier mailingListId');

        if ($this->isValidUuid($mailingListId) === false) {
            throw new ComelusException('dossier mailingListId is not a valid UUID', 400);
        }
    }
}
