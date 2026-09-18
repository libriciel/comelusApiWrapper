<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper\Test\Model;

use Libriciel\ComelusApiWrapper\ComelusException;
use Libriciel\ComelusApiWrapper\Model\Annexe;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AnnexeTest extends TestCase
{
    public function testCreateValidAnnexe(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );
        $annexe = new Annexe('Annexe 1', 1, $uploadedFile);

        $this->assertSame('Annexe 1', $annexe->getName());
        $this->assertSame(1, $annexe->getRank());
        $this->assertInstanceOf(UploadedFile::class, $annexe->getUploadedFile());
    }

    public function testCreateAnnexeWithEmptyName(): void
    {
        $annexe = new Annexe(
            '',
            1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            )
        );

        $this->assertSame('', $annexe->getName());
        $this->assertSame(1, $annexe->getRank());
        $this->assertInstanceOf(UploadedFile::class, $annexe->getUploadedFile());
    }

    public function testCreateAnnexeWithNullName(): void
    {
        $annexe = new Annexe(
            null,
            1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            )
        );

        $this->assertSame('', $annexe->getName());
        $this->assertSame(1, $annexe->getRank());
        $this->assertInstanceOf(UploadedFile::class, $annexe->getUploadedFile());
    }

    public function testCreateAnnexeWithBlankNameIsEmpty(): void
    {
        $annexe = new Annexe('   ', 1, new UploadedFile(__DIR__ . '/../resources/test.pdf', 'test.pdf'));

        $this->assertSame('', $annexe->getName());
    }

    public function testCreateAnnexeNamedZero(): void
    {
        $annexe = new Annexe('0', 1, new UploadedFile(__DIR__ . '/../resources/test.pdf', 'test.pdf'));

        $this->assertSame('0', $annexe->getName());
    }

    public function testCreateAnnexeWithNullRank(): void
    {
        $annexe = new Annexe(
            'test',
            null,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            )
        );

        $this->assertSame('test', $annexe->getName());
        $this->assertNull($annexe->getRank());
        $this->assertInstanceOf(UploadedFile::class, $annexe->getUploadedFile());
    }

    public function testCreateAnnexeWithMaxNameLengthAllowed(): void
    {
        $maxName = str_repeat('a', 512);
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );

        $annexe = new Annexe($maxName, 1, $uploadedFile);

        $this->assertSame($maxName, $annexe->getName());
        $this->assertInstanceOf(UploadedFile::class, $annexe->getUploadedFile());
    }

    public function testCreateAnnexeWithNameTooLongThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("The field 'annexe name' must not exceed 512 characters.");

        $annexeName = substr(str_repeat(md5((string) mt_rand()), 513), 0, 513);

        new Annexe(
            $annexeName,
            1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            )
        );
    }

    public function testCreateAnnexeWithNegativeRankThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('annexe rank must be a positive integer');

        new Annexe(
            'Annexe 1',
            -1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            )
        );
    }

    public function testCreateAnnexeWithZeroRankIsAllowed(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );

        $annexe = new Annexe('Annexe 0', 0, $uploadedFile);

        $this->assertSame(0, $annexe->getRank());
        $this->assertInstanceOf(UploadedFile::class, $annexe->getUploadedFile());
    }
}
