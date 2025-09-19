<?php

namespace Libriciel\ComelusApiWrapper\Test\Model;

use Libriciel\ComelusApiWrapper\ComelusException;
use Libriciel\ComelusApiWrapper\Model\Annexe;
use Libriciel\ComelusApiWrapper\Model\File;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileTest extends TestCase
{
    // Test pour un fichier valide sans annexe
    public function testCreateValidFileWithoutAnnexe(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );

        // Création d'un fichier sans annexe
        $file = new File('Facture', 'facture.pdf', 1, $uploadedFile, []);

        $this->assertEquals('Facture', $file->getCategory());
        $this->assertEquals('facture.pdf', $file->getName());
        $this->assertEquals(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
        $this->assertCount(0, $file->getAnnexes());  // Aucune annexe associée
    }

    // Test pour un fichier valide avec annexe
    public function testCreateValidFileWithAnnexe(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );
        $annexe = new Annexe('Annexe 1', 1, $uploadedFile);

        // Création d'un fichier avec une annexe
        $file = new File('Facture', 'facture.pdf', 1, $uploadedFile, [$annexe]);

        $this->assertEquals('Facture', $file->getCategory());
        $this->assertEquals('facture.pdf', $file->getName());
        $this->assertEquals(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
        $this->assertCount(1, $file->getAnnexes());  // Une annexe associée
        $this->assertEquals('Annexe 1', $file->getAnnexes()[0]->getName());  // Vérification de l'annexe
    }

    public function testCreateFileWithEmptyName(): void
    {
        $file = new File(
            'Facture',
            '',
            1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            ),
            []
        );

        $this->assertEquals('', $file->getName());
        $this->assertEquals(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
    }

    // Cas d'erreur : Rang négatif pour le fichier
    public function testCreateFileWithNegativeRankThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("file rank must be a positive integer");

        new File(
            'Facture',
            'facture.pdf',
            -1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            ),
            []
        );
    }
}