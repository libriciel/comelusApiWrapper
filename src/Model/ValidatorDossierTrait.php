<?php

namespace Libriciel\ComelusApiWrapper\Model;

use Libriciel\ComelusApiWrapper\ComelusException;

trait ValidatorDossierTrait
{
    use ValidatorTrait;

    public function validateDossierName(?string $name): void
    {
        $this->validateString($name, 'dossier name');
        $this->validateLength($name,  Dossier::MAX_LENGTH_DOSSIER_NAME, 'dossier name');
    }

    public function validateMailingListId(string $mailingListId): void
    {
        $this->validateString($mailingListId, 'dossier mailingListId');

        if ($this->isValidUuid($mailingListId) === false) {
            throw new ComelusException("dossier mailingListId is not id valid", 400);
        }
    }
}