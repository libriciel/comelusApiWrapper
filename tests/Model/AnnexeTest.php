<?php

namespace Libriciel\ComelusApiWrapper\Test\Model;

use Libriciel\ComelusApiWrapper\ComelusException;
use Libriciel\ComelusApiWrapper\Model\Annexe;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AnnexeTest extends TestCase
{
    public function testCreateValidAnnexe(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__ . '/../resources/test.pdf',
            'test.pdf'
        );
        $annexe = new Annexe('Annexe 1', 1, $uploadedFile);

        $this->assertEquals('Annexe 1', $annexe->getName());
        $this->assertEquals(1, $annexe->getRank());
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

        $this->assertEquals('', $annexe->getName());
        $this->assertEquals(1, $annexe->getRank());
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

        $this->assertEquals('', $annexe->getName());
        $this->assertEquals(1, $annexe->getRank());
        $this->assertInstanceOf(UploadedFile::class, $annexe->getUploadedFile());
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

        $this->assertEquals('test', $annexe->getName());
        $this->assertEquals(null, $annexe->getRank());
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

        $this->assertEquals($maxName, $annexe->getName());
        $this->assertInstanceOf(UploadedFile::class, $annexe->getUploadedFile());
    }

    public function testCreateAnnexeWithNameToLongThrowsException(): void
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

        $this->assertEquals(0, $annexe->getRank());
        $this->assertInstanceOf(UploadedFile::class, $annexe->getUploadedFile());
    }

    //    public function testCreateAnnexeWithNoUploadedFileThrowsException(): void
    //    {
    //        $this->expectException(ComelusException::class);
    //        $this->expectExceptionMessage("annexe uploadedfile must not be null");
    //
    //        new Annexe('Annexe 1', 1, null);
    //    }
}
