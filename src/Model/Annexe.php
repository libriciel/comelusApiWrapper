<?php

namespace Libriciel\ComelusApiWrapper\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class Annexe
{
    use ValidatorTrait;

    public function __construct(
        private readonly string $name,
        private readonly int $rank,
        private readonly UploadedFile $uploadedfile,
    ) {
        $this->validateString($name, 'annexe name');
        $this->validateLength($name, 512, 'file name');
        $this->validatePositiveInt($rank, 'annexe rank');
        $this->validateUploadedFile($uploadedfile, 'annexe uploadedfile');
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
}