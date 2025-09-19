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
        $this->expectExceptionMessage("dossier mailingListId is not id valid");

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

    // Nom de dossier longueur maximale autorisée (512)
    public function testCreateDossierWithMaxNameLengthAllowed(): void
    {
        $uuid = Uuid::v7()->toRfc4122();
        $maxName = str_repeat('a', 512);
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );
        $file = new File('Facture', 'facture.pdf', 1, $uploadedFile, []);

        $dossier = new Dossier($maxName, 'Desc', $uuid, [$file]);

        $this->assertEquals($maxName, $dossier->getName());
        $this->assertEquals('Desc', $dossier->getDescription());
    }

    // Nom de dossier trop long (>512) doit lever une exception
    public function testCreateDossierWithNameTooLongThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("The field 'dossier name' must not exceed 512 characters.");

        $uuid = Uuid::v7()->toRfc4122();
        $tooLongName = substr(str_repeat(md5((string) mt_rand()), 513), 0, 513);
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );
        $file = new File('Facture', 'facture.pdf', 1, $uploadedFile, []);

        new Dossier($tooLongName, 'Desc', $uuid, [$file]);
    }

    // Description vide doit lever une exception
    public function testCreateDossierWithEmptyDescriptionThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("dossier description must be not empty");

        $uuid = Uuid::v7()->toRfc4122();
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );
        $file = new File('Facture', 'facture.pdf', 1, $uploadedFile, []);

        new Dossier('Nom', '', $uuid, [$file]);
    }

    // addFile ajoute bien un fichier à la liste
    public function testAddFileAppendsToAttachedFiles(): void
    {
        $uuid = Uuid::v7()->toRfc4122();
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );
        $file1 = new File('Facture', 'facture1.pdf', 1, $uploadedFile, []);
        $file2 = new File('Recu', 'recu.pdf', 2, $uploadedFile, []);

        $dossier = new Dossier('Nom', 'Desc', $uuid, [$file1]);
        $this->assertCount(1, $dossier->getAttachedFiles());

        $dossier->addFile($file2);
        $this->assertCount(2, $dossier->getAttachedFiles());
        $this->assertEquals('recu.pdf', $dossier->getAttachedFiles()[1]->getName());
    }
}