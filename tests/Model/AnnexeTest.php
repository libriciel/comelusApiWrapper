<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper\Test\Model;

use Libriciel\ComelusApiWrapper\ComelusException;
use Libriciel\ComelusApiWrapper\Model\Annexe;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use TypeError;

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
        $this->expectExceptionMessage('annexe rank must be greater than 0');

        new Annexe(
            'Annexe 1',
            -1,
            new UploadedFile(
                __DIR__ . '/../resources/test.pdf',
                'test.pdf'
            )
        );
    }

    /**
     * Comélus refuse `!$annexe['name']` (ApiV2Manager) : '' et '0' sont refusés.
     */
    #[DataProvider('emptyNameProvider')]
    public function testCreateAnnexeWithEmptyNameThrowsException(string $name): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('annexe name must be not empty');
        $this->expectExceptionCode(400);

        new Annexe($name, 1, $this->uploadedFile());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function emptyNameProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'zero' => ['0'];
    }

    public function testCreateAnnexeWithBlankNameIsKept(): void
    {
        $annexe = new Annexe('   ', 1, $this->uploadedFile());

        $this->assertSame('   ', $annexe->getName());
    }

    public function testCreateAnnexeWithNullNameThrowsTypeError(): void
    {
        $this->expectException(TypeError::class);

        /** @phpstan-ignore argument.type */
        new Annexe(null, 1, $this->uploadedFile());
    }

    public function testCreateAnnexeWithNullRankThrowsTypeError(): void
    {
        $this->expectException(TypeError::class);

        /** @phpstan-ignore argument.type */
        new Annexe('Annexe 1', null, $this->uploadedFile());
    }

    /**
     * Comélus refuse `!$annexe['rank']` : contrairement au fichier, le rang 0 est refusé.
     */
    public function testCreateAnnexeWithZeroRankThrowsException(): void
    {
        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('annexe rank must be greater than 0');
        $this->expectExceptionCode(400);

        new Annexe('Annexe 1', 0, $this->uploadedFile());
    }

    private function uploadedFile(): UploadedFile
    {
        return new UploadedFile(__DIR__ . '/../resources/test.pdf', 'test.pdf');
    }
}
