<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper\Test\Model;

use Libriciel\ComelusApiWrapper\ComelusException;
use Libriciel\ComelusApiWrapper\Model\Annexe;
use Libriciel\ComelusApiWrapper\Model\File;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FileTest extends TestCase
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

        $this->assertSame('Facture', $file->getCategory());
        $this->assertSame('facture.pdf', $file->getName());
        $this->assertSame(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
        $this->assertCount(0, $file->getAnnexes());  // Aucune annexe associée
    }

    public function testCreateValidFileWithCategoryEmptyWithoutAnnexe(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );

        // Création d'un fichier sans annexe
        $file = new File('', 'facture.pdf', 1, $uploadedFile, []);

        $this->assertSame('', $file->getCategory());
        $this->assertSame('facture.pdf', $file->getName());
        $this->assertSame(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
        $this->assertCount(0, $file->getAnnexes());  // Aucune annexe associée
    }

    public function testCreateValidFileWithCategoryNullWithoutAnnexe(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );

        // Création d'un fichier sans annexe
        $file = new File(null, 'facture.pdf', 1, $uploadedFile, []);

        $this->assertSame('', $file->getCategory());
        $this->assertSame('facture.pdf', $file->getName());
        $this->assertSame(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
        $this->assertCount(0, $file->getAnnexes());  // Aucune annexe associée
    }

    public function testCreateValidFileWithNameEmptyWithoutAnnexe(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );

        // Création d'un fichier sans annexe
        $file = new File('Facture', '', 1, $uploadedFile, []);

        $this->assertSame('Facture', $file->getCategory());
        $this->assertSame('', $file->getName());
        $this->assertSame(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
        $this->assertCount(0, $file->getAnnexes());  // Aucune annexe associée
    }

    public function testCreateValidFileWithNameNullWithoutAnnexe(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );

        // Création d'un fichier sans annexe
        $file = new File('Facture', null, 1, $uploadedFile, []);

        $this->assertSame('Facture', $file->getCategory());
        $this->assertSame('', $file->getName());
        $this->assertSame(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
        $this->assertCount(0, $file->getAnnexes());  // Aucune annexe associée
    }

    public function testCreateValidFileWithRankNullWithoutAnnexe(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );

        // Création d'un fichier sans annexe
        $file = new File('Facture', 'facture.pdf', null, $uploadedFile, []);

        $this->assertSame('Facture', $file->getCategory());
        $this->assertSame('facture.pdf', $file->getName());
        $this->assertNull($file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
        $this->assertCount(0, $file->getAnnexes());  // Aucune annexe associée
    }

    public function testCreateValidFileWithoutCategoryNameRankAnnexe(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );

        // Création d'un fichier sans annexe
        $file = new File(null, null, null, $uploadedFile, []);

        $this->assertSame('', $file->getCategory());
        $this->assertSame('', $file->getName());
        $this->assertNull($file->getRank());
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

        $this->assertSame('Facture', $file->getCategory());
        $this->assertSame('facture.pdf', $file->getName());
        $this->assertSame(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
        $this->assertCount(1, $file->getAnnexes());  // Une annexe associée
        $this->assertSame('Annexe 1', $file->getAnnexes()[0]->getName());  // Vérification de l'annexe
    }

    public function testCreateValidFileWithNameAnnexeIsEmpty(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );
        $annexe = new Annexe('', 1, $uploadedFile);

        // Création d'un fichier avec une annexe
        $file = new File('Facture', 'facture.pdf', 1, $uploadedFile, [$annexe]);

        $this->assertSame('Facture', $file->getCategory());
        $this->assertSame('facture.pdf', $file->getName());
        $this->assertSame(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
        $this->assertCount(1, $file->getAnnexes());  // Une annexe associée
        $this->assertSame('', $file->getAnnexes()[0]->getName());  // Vérification de l'annexe
        $this->assertSame(1, $file->getAnnexes()[0]->getRank());
    }

    public function testCreateValidFileWithNameAnnexeIsNull(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );
        $annexe = new Annexe(null, 1, $uploadedFile);

        // Création d'un fichier avec une annexe
        $file = new File('Facture', 'facture.pdf', 1, $uploadedFile, [$annexe]);

        $this->assertSame('Facture', $file->getCategory());
        $this->assertSame('facture.pdf', $file->getName());
        $this->assertSame(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
        $this->assertCount(1, $file->getAnnexes());  // Une annexe associée
        $this->assertSame('', $file->getAnnexes()[0]->getName());  // Vérification de l'annexe
        $this->assertSame(1, $file->getAnnexes()[0]->getRank());
    }

    public function testCreateValidFileWithRankAnnexeIsNull(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );
        $annexe = new Annexe('Annexe 1', null, $uploadedFile);

        // Création d'un fichier avec une annexe
        $file = new File('Facture', 'facture.pdf', 1, $uploadedFile, [$annexe]);

        $this->assertSame('Facture', $file->getCategory());
        $this->assertSame('facture.pdf', $file->getName());
        $this->assertSame(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
        $this->assertCount(1, $file->getAnnexes());  // Une annexe associée
        $this->assertSame('Annexe 1', $file->getAnnexes()[0]->getName());  // Vérification de l'annexe
        $this->assertNull($file->getAnnexes()[0]->getRank());
    }

    public function testCreateFileWithEmptyCategory(): void
    {
        $file = new File(
            '',
            'test',
            1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            ),
            []
        );

        $this->assertSame('', $file->getCategory());
        $this->assertSame('test', $file->getName());
        $this->assertSame(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
    }

    public function testCreateFileWithNullCategory(): void
    {
        $file = new File(
            null,
            'test',
            1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            ),
            []
        );

        $this->assertSame('', $file->getCategory());
        $this->assertSame('test', $file->getName());
        $this->assertSame(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
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

        $this->assertSame('Facture', $file->getCategory());
        $this->assertSame('', $file->getName());
        $this->assertSame(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
    }

    public function testCreateFileWithNullName(): void
    {
        $file = new File(
            'Facture',
            null,
            1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            ),
            []
        );

        $this->assertSame('Facture', $file->getCategory());
        $this->assertSame('', $file->getName());
        $this->assertSame(1, $file->getRank());
        $this->assertInstanceOf(UploadedFile::class, $file->getUploadedFile());
    }

    public function testCreateFileWithBlankCategoryAndNameIsEmpty(): void
    {
        $file = new File('   ', "\t", 1, new UploadedFile(__DIR__ . '/../resources/test.pdf', 'test.pdf'));

        $this->assertSame('', $file->getCategory());
        $this->assertSame('', $file->getName());
    }

    public function testCreateFileWithNameTooLongThrowsException(): void
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
        $this->expectExceptionMessage('file rank must be a positive integer');

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

    // Catégorie trop longue (>255) doit lever une exception
    public function testCreateFileWithCategoryTooLongThrowsException(): void
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

        $this->assertSame(0, $file->getRank());
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
        $this->assertSame('Annexe 1', $file->getAnnexes()[0]->getName());
        $this->assertSame('Annexe 2', $file->getAnnexes()[1]->getName());
    }
}
