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

    public function testCreateFileWithNameToLongThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("The field 'file name' must not exceed 512 characters.");

        $fileName = substr(str_repeat(md5((string) mt_rand()), 513), 0, 513);

        new File(
            'Facture',
            $fileName,
            1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            ),
            []
        );
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

    // Catégorie vide doit lever une exception
    public function testCreateFileWithEmptyCategoryThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("file category must be not empty");

        new File(
            '',
            'facture.pdf',
            1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            ),
            []
        );
    }

    // Catégorie trop longue (>255) doit lever une exception
    public function testCreateFileWithCategoryToLongThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("The field 'file category' must not exceed 255 characters.");

        $category = substr(str_repeat(md5((string) mt_rand()), 256), 0, 256);

        new File(
            $category,
            'facture.pdf',
            1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            ),
            []
        );
    }

    // Rang égal à 0 est autorisé
    public function testCreateFileWithZeroRankIsAllowed(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );

        $file = new File('Facture', 'facture.pdf', 0, $uploadedFile, []);

        $this->assertEquals(0, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
    }

    // Annexes null doivent être converties en tableau vide
    public function testCreateFileWithNullAnnexesDefaultsToEmptyArray(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );

        $file = new File('Facture', 'facture.pdf', 1, $uploadedFile, null);

        $this->assertIsArray($file->getAnnexes());
        $this->assertCount(0, $file->getAnnexes());
    }

    // addAnnexe ajoute bien l'annexe à la liste
    public function testAddAnnexeAppendsToAnnexes(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );
        $file = new File('Facture', 'facture.pdf', 1, $uploadedFile, []);

        $annexe1 = new Annexe('Annexe 1', 1, $uploadedFile);
        $annexe2 = new Annexe('Annexe 2', 2, $uploadedFile);

        $file->addAnnexe($annexe1);
        $file->addAnnexe($annexe2);

        $this->assertCount(2, $file->getAnnexes());
        $this->assertEquals('Annexe 1', $file->getAnnexes()[0]->getName());
        $this->assertEquals('Annexe 2', $file->getAnnexes()[1]->getName());
    }
}