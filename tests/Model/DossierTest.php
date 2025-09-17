<?php

namespace Libriciel\ComelusApiWrapper\Test\Model;

use Libriciel\ComelusApiWrapper\ComelusException;
use Libriciel\ComelusApiWrapper\Model\Dossier;
use Libriciel\ComelusApiWrapper\Model\File;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

class DossierTest extends TestCase
{
    // Test pour un dossier valide
    public function testCreateValidDossier(): void
    {
        $uuid = Uuid::v7()->toRfc4122();
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );
        $file = new File('Facture', 'facture.pdf', 1, $uploadedFile, []);

        $dossier = new Dossier(
            'Projet Alpha',
            'Description du projet',
            $uuid,
            [$file]
        );

        $this->assertEquals('Projet Alpha', $dossier->getName());
        $this->assertEquals('Description du projet', $dossier->getDescription());
        $this->assertEquals($uuid, $dossier->getMailingListId());
        $this->assertCount(1, $dossier->getAttachedFiles());
    }

    // Cas d'erreur : Nom vide pour le dossier
    public function testCreateDossierWithEmptyNameThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("dossier name must be not empty");

        $uuid = Uuid::v7()->toRfc4122();
        $file = new File(
            'Facture',
            'facture.pdf',
            1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            ),
            []
        );

        new Dossier('', 'Description', $uuid, [$file]);
    }

    // Cas d'erreur : UUID invalide pour le dossier
    public function testCreateDossierWithInvalidUuidThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("mailingListId is not id valid");

        $file = new File(
            'Facture',
            'facture.pdf',
            1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            ),
            []
        );

        new Dossier(
            'Projet Alpha',
            'Description du projet',
            'invalid-uuid',
            [$file]
        );
    }

    // Cas d'erreur : Aucun fichier pour le dossier
    public function testCreateDossierWithNoFilesThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("Dossier must contain at least one file");

        $uuid = Uuid::v7()->toRfc4122();

        new Dossier('Projet Alpha', 'Description du projet', $uuid, []);
    }
}