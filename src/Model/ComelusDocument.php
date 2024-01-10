<?php

namespace Libriciel\ComelusApiWrapper\Model;

class ComelusDocument
{
    private string $name;
    private string $mailingListId;
    private string $description;
    private array $files;
    private array $filesInfos;


    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    public function getMailingListId(): string
    {
        return $this->mailingListId;
    }
    public function setMailingListId(string $mailingListId): self
    {
        $this->mailingListId = $mailingListId;
        return $this;
    }
    public function getDescription(): string
    {
        return $this->description;
    }
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }
    public function getFiles(): array
    {
        return $this->files;
    }
    public function setFiles(array $files): self
    {
        $this->files = $files;
        return $this;
    }
    public function getFilesInfos(): array
    {
        return $this->filesInfos;
    }
    public function setFilesInfos(array $filesInfos): self
    {
        $this->filesInfos = $filesInfos;
        return $this;
    }


}
