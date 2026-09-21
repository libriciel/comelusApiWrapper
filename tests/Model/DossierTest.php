<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper\Test\Model;

use Libriciel\ComelusApiWrapper\ComelusException;
use Libriciel\ComelusApiWrapper\Model\Dossier;
use Libriciel\ComelusApiWrapper\Model\File;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

final class DossierTest extends TestCase
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

        $this->assertSame('Projet Alpha', $dossier->getName());
        $this->assertSame('Description du projet', $dossier->getDescription());
        $this->assertSame($uuid, $dossier->getMailingListId());
        $this->assertCount(1, $dossier->getAttachedFiles());
    }

    // Cas d'erreur : Nom vide pour le dossier
    public function testCreateDossierWithEmptyNameThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier name must be not empty');

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
        $this->expectExceptionMessage('dossier mailingListId is not a valid UUID');

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
        $this->expectExceptionMessage('Dossier must contain at least one file');

        $uuid = Uuid::v7()->toRfc4122();

        new Dossier('Projet Alpha', 'Description du projet', $uuid, []);
    }

    // Description vide doit lever une exception
    public function testCreateDossierWithEmptyDescriptionThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier description must be not empty');

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
        $this->assertSame('recu.pdf', $dossier->getAttachedFiles()[1]->getName());
    }

    /**
     * Comélus refuse `!$name` et `!$dossierDescription` (ApiV2Manager) : "0" est refusé comme ''.
     */
    public function testCreateDossierNamedZeroThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier name must be not empty');
        $this->expectExceptionCode(400);

        new Dossier('0', 'Description', Uuid::v7()->toRfc4122(), [$this->file()]);
    }

    public function testCreateDossierWithZeroDescriptionThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier description must be not empty');
        $this->expectExceptionCode(400);

        new Dossier('Nom', '0', Uuid::v7()->toRfc4122(), [$this->file()]);
    }

    public function testCreateDossierWithBlankDescriptionThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier description must be not empty');

        new Dossier('Nom', '   ', Uuid::v7()->toRfc4122(), [$this->file()]);
    }

    /**
     * Comélus refuse `strlen($name) >= 512` : 511 octets au plus.
     */
    public function testCreateDossierWithMaxNameLengthAllowed(): void
    {
        $maxName = str_repeat('a', 511);

        $dossier = new Dossier($maxName, 'Desc', Uuid::v7()->toRfc4122(), [$this->file()]);

        $this->assertSame($maxName, $dossier->getName());
    }

    public function testCreateDossierWithNameTooLongThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("The field 'dossier name' must not exceed 511 characters.");

        new Dossier(str_repeat('a', 512), 'Desc', Uuid::v7()->toRfc4122(), [$this->file()]);
    }

    private function file(): File
    {
        return new File('Facture', 'facture.pdf', 1, new UploadedFile(__DIR__ . '/../resources/test.pdf', 'test.pdf'));
    }
}
