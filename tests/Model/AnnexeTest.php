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

    public function testCreateAnnexeWithEmptyNameThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("annexe name must be not empty");

        new Annexe(
            '',
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
        $this->expectExceptionMessage("annexe rank must be a positive integer");

        new Annexe(
            'Annexe 1',
            -1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            )
        );
    }

//    public function testCreateAnnexeWithNoUploadedFileThrowsException(): void
//    {
//        $this->expectException(ComelusException::class);
//        $this->expectExceptionMessage("annexe uploadedfile must not be null");
//
//        new Annexe('Annexe 1', 1, null);
//    }
}