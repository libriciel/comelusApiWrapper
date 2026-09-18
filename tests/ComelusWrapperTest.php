<?php

use Libriciel\ComelusApiWrapper\ComelusException;
use Libriciel\ComelusApiWrapper\ComelusWrapper;
use Libriciel\ComelusApiWrapper\Model\Dossier;
use Libriciel\ComelusApiWrapper\Model\File;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class ComelusWrapperTest extends TestCase
{
    private function mockResponse(int $statusCode, ?string $reasonPhrase = "", ?StreamInterface $body = null): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getReasonPhrase')->willReturn($reasonPhrase);
        $response->method('getBody')->willReturn($body);

        return $response;
    }

    private function mockBody(string $json): StreamInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($json);

        return $stream;
    }

    private function mockRequestFactory(): RequestFactoryInterface
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withAddedHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();

        $request->method('getBody')->willReturn($this->createMock(StreamInterface::class));

        $factory = $this->createMock(RequestFactoryInterface::class);
        $factory->method('createRequest')->willReturn($request);

        return $factory;
    }

    private function mockClient(?ResponseInterface $response, Exception $exception = null): ClientInterface
    {
        $client = $this->createMock(ClientInterface::class);
        if (!$exception) {
            $client->method('sendRequest')->willReturn($response);
        } else {
            $client->method('sendRequest')->willThrowException($exception);
        }

        return $client;
    }

    public function testSetUrl()
    {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl("http://testurl.fr");

        $this->assertTrue(true);
    }

    public function testSetUrlMalformed()
    {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("malformed url : missing http|https");
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);
        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl("testurl.fr");
    }

    public function testSetApiKey()
    {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");

        $this->assertTrue(true);
    }

    public function testSetApiKeyMalformed()
    {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("malformed apiKey : no whitespace allowed");
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("my apiKey");
    }

    public function testCheckWrongApiKey()
    {
        $requestFactory = $this->mockRequestFactory();

        $streamFactory = new Psr17Factory();
        $stream = $streamFactory->createStream('bad apKey');
        $response = $this->mockResponse(401, $stream->getContents(), $stream);

        $client = $this->mockClient($response);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl('https://test.libriciel.fr');
        $comelusWrapper->setApiKey('aqaqzszcdcdcdc');

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("bad apKey");
        $this->expectExceptionCode(401);

        $comelusWrapper->check();
    }

    public function testCheck()
    {
        $response = $this->mockResponse(200, '', $this->mockBody('{"success":true}'));
        $client = $this->mockClient($response);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");
        $comelusWrapper->setUrl("https://comelus.fr");

        $content = $comelusWrapper->check();
        $this->assertSame(["success" => true], $content);
    }

    public function testCheckMissingUrlAndApiKey()
    {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("url and apiKey must be set");
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $comelusWrapper->check();
    }

    public function testCheckMissingApiKey()
    {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl("https://comelus.fr");

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("apiKey must be set");
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $comelusWrapper->check();
    }

    public function testCheckMissingUrl()
    {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("url must be set");
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $comelusWrapper->check();
    }

    public function testGetMailingLists()
    {
        $jsonResponse = '[{"id":"dc85a85e-0cfe-47f0-9019-ab104b04a6eb", "name":"myMailingList"}]';
        $response = $this->mockResponse(200, '', $this->mockBody($jsonResponse));
        $client = $this->mockClient($response);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");
        $comelusWrapper->setUrl("https://comelus.fr");

        $content = $comelusWrapper->getMailingLists();

        $expected = [["id" => "dc85a85e-0cfe-47f0-9019-ab104b04a6eb", "name" => "myMailingList"]];
        $this->assertSame($expected, $content);
    }

    public function testGetDocumentStatus()
    {
        $expected = [
            [
                "id" => "f7839360-1995-4ae4-97cf-686570795729",
                "recipient" => [
                    "id" => "b221d819-2c88-4570-a1dd-21b64a670390",
                    "email" => "recipient@libriciel.invalid",
                    "firstName" => "Marc",
                    "lastName" => "Dupont",
                ],
                "sendDate" => "2020-06-02T09:06:19+02:00",
                "readDate" => null,
            ]
        ];

        $response = $this->mockResponse(200, '', $this->mockBody(json_encode($expected)));
        $client = $this->mockClient($response);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");
        $comelusWrapper->setUrl("https://comelus.fr");

        $content = $comelusWrapper->getDossierStatus('f7839360-1995-4ae4-97cf-686570795729');

        $this->assertSame($expected, $content);
    }

    public function testGetDocuments()
    {
        $expected = [
            [
                "id" => "e1268ac7-f98f-4d08-8140-7c48389bd308",
                "name" => "Nouveau document",
                "mailingList" => [
                    "id" => "dc85a85e-0cfe-47f0-9019-ab104b04a6eb",
                    "name" => "Ville"
                ]
            ]
        ];

        $response = $this->mockResponse(200, '', $this->mockBody(json_encode($expected)));
        $client = $this->mockClient($response);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");
        $comelusWrapper->setUrl("https://comelus.fr");

        $content = $comelusWrapper->getDossiers();

        $this->assertSame($expected, $content);
    }

    public function testSendDocument()
    {
        $response = $this->mockResponse(200, '', $this->mockBody('{"success":true}'));
        $client = $this->mockClient($response);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");
        $comelusWrapper->setUrl("https://comelus.fr");

        $content = $comelusWrapper->sendDocument('51edebc7-3299-4da1-94b2-0205745d78db');

        $this->assertSame(["success" => true], $content);
    }

    public function testCreateDocument()
    {
        $expected = [
            "success" => true,
            "id" => "da781241-3c2c-420c-90f8-f2f83df99c74"
        ];

        $response = $this->mockResponse(201, '', $this->mockBody(json_encode($expected)));
        $client = $this->mockClient($response);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");
        $comelusWrapper->setUrl("https://comelus.fr");

        $file = $this->createMock(UploadedFileInterface::class);

        $file->method('getStream')->willReturn(Stream::create("my stream"));
        $file->method('getClientFilename')->willReturn("deliberation.pdf");
        $file->method('getClientMediaType')->willReturn("application/pdf");

        $uploadedFile = new UploadedFile(
            __DIR__ . '/resources/test.pdf',
            'test.pdf'
        );
        $file = new File('Facture', 'facture dossier', 1, $uploadedFile, []);
        $dossier = new Dossier(
            'myDocument',
            'Nouveau document',
            '44edebc7-3299-4da1-94b2-0202245d78db',
            [
                $file
            ]
        );

        $content = $comelusWrapper->createDossier($dossier);
        $this->assertSame($expected, $content);
    }

    public function testCreateDocumentMisingCategory()
    {
        $expected = [
            "success" => true,
            "id" => "da781241-3c2c-420c-90f8-f2f83df99c74"
        ];

        $response = $this->mockResponse(201, '', $this->mockBody(json_encode($expected)));
        $client = $this->mockClient($response);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");
        $comelusWrapper->setUrl("https://comelus.fr");

        $file = $this->createMock(UploadedFileInterface::class);

        $file->method('getStream')->willReturn(Stream::create("my stream"));
        $file->method('getClientFilename')->willReturn("deliberation.pdf");
        $file->method('getClientMediaType')->willReturn("application/pdf");

        $uploadedFile = new UploadedFile(
            __DIR__ . '/resources/test.pdf',
            'test.pdf'
        );
        $file = new File('', 'test', 1, $uploadedFile, []);
        $dossier = new Dossier(
            'myDocument',
            'Nouveau document',
            '44edebc7-3299-4da1-94b2-0202245d78db',
            [
                $file
            ]
        );

        $content = $comelusWrapper->createDossier($dossier);
        $this->assertSame($expected, $content);
    }

    public function testCreateDocumentMisingCategoryNull()
    {
        $expected = [
            "success" => true,
            "id" => "da781241-3c2c-420c-90f8-f2f83df99c74"
        ];

        $response = $this->mockResponse(201, '', $this->mockBody(json_encode($expected)));
        $client = $this->mockClient($response);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");
        $comelusWrapper->setUrl("https://comelus.fr");

        $file = $this->createMock(UploadedFileInterface::class);

        $file->method('getStream')->willReturn(Stream::create("my stream"));
        $file->method('getClientFilename')->willReturn("deliberation.pdf");
        $file->method('getClientMediaType')->willReturn("application/pdf");

        $uploadedFile = new UploadedFile(
            __DIR__ . '/resources/test.pdf',
            'test.pdf'
        );
        $file = new File(null, 'test', 1, $uploadedFile, []);
        $dossier = new Dossier(
            'myDocument',
            'Nouveau document',
            '44edebc7-3299-4da1-94b2-0202245d78db',
            [
                $file
            ]
        );

        $content = $comelusWrapper->createDossier($dossier);
        $this->assertSame($expected, $content);
    }

    public function testCreateDocumentMisingName()
    {
        $expected = [
            "success" => true,
            "id" => "da781241-3c2c-420c-90f8-f2f83df99c74"
        ];

        $response = $this->mockResponse(201, '', $this->mockBody(json_encode($expected)));
        $client = $this->mockClient($response);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");
        $comelusWrapper->setUrl("https://comelus.fr");

        $file = $this->createMock(UploadedFileInterface::class);

        $file->method('getStream')->willReturn(Stream::create("my stream"));
        $file->method('getClientFilename')->willReturn("deliberation.pdf");
        $file->method('getClientMediaType')->willReturn("application/pdf");

        $uploadedFile = new UploadedFile(
            __DIR__ . '/resources/test.pdf',
            'test.pdf'
        );
        $file = new File('Facture', '', 1, $uploadedFile, []);
        $dossier = new Dossier(
            'myDocument',
            'Nouveau document',
            '44edebc7-3299-4da1-94b2-0202245d78db',
            [
                $file
            ]
        );

        $content = $comelusWrapper->createDossier($dossier);
        $this->assertSame($expected, $content);
    }

    public function testCreateDocumentMisingNameNull()
    {
        $expected = [
            "success" => true,
            "id" => "da781241-3c2c-420c-90f8-f2f83df99c74"
        ];

        $response = $this->mockResponse(201, '', $this->mockBody(json_encode($expected)));
        $client = $this->mockClient($response);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");
        $comelusWrapper->setUrl("https://comelus.fr");

        $file = $this->createMock(UploadedFileInterface::class);

        $file->method('getStream')->willReturn(Stream::create("my stream"));
        $file->method('getClientFilename')->willReturn("deliberation.pdf");
        $file->method('getClientMediaType')->willReturn("application/pdf");

        $uploadedFile = new UploadedFile(
            __DIR__ . '/resources/test.pdf',
            'test.pdf'
        );
        $file = new File('Facture', null, 1, $uploadedFile, []);
        $dossier = new Dossier(
            'myDocument',
            'Nouveau document',
            '44edebc7-3299-4da1-94b2-0202245d78db',
            [
                $file
            ]
        );

        $content = $comelusWrapper->createDossier($dossier);
        $this->assertSame($expected, $content);
    }

    public function testCreateDocumentMisingRankNull()
    {
        $expected = [
            "success" => true,
            "id" => "da781241-3c2c-420c-90f8-f2f83df99c74"
        ];

        $response = $this->mockResponse(201, '', $this->mockBody(json_encode($expected)));
        $client = $this->mockClient($response);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");
        $comelusWrapper->setUrl("https://comelus.fr");

        $file = $this->createMock(UploadedFileInterface::class);

        $file->method('getStream')->willReturn(Stream::create("my stream"));
        $file->method('getClientFilename')->willReturn("deliberation.pdf");
        $file->method('getClientMediaType')->willReturn("application/pdf");

        $uploadedFile = new UploadedFile(
            __DIR__ . '/resources/test.pdf',
            'test.pdf'
        );
        $file = new File('Facture', 'test', null, $uploadedFile, []);
        $dossier = new Dossier(
            'myDocument',
            'Nouveau document',
            '44edebc7-3299-4da1-94b2-0202245d78db',
            [
                $file
            ]
        );

        $content = $comelusWrapper->createDossier($dossier);
        $this->assertSame($expected, $content);
    }

    public function testCreateDocumentMisingDossierName() {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl("https://comelus.fr");
        $comelusWrapper->setApiKey("MyApiKey");

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier name must be not empty');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $uploadedFile = new UploadedFile(
            __DIR__ . '/resources/test.pdf',
            'test.pdf'
        );
        $file = new File('Facture', 'facture.pdf', 1, $uploadedFile, []);
        $dossier = new Dossier(
            ' ',
            'Description du projet',
            Uuid::v7()->toRfc4122(),
            [
                $file
            ]
        );
        $comelusWrapper->createDossier($dossier);
    }

    public function testCreateDocumentMisingDossierDescription() {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl("https://comelus.fr");
        $comelusWrapper->setApiKey("MyApiKey");

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier description must be not empty');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $uploadedFile = new UploadedFile(
            __DIR__ . '/resources/test.pdf',
            'test.pdf'
        );
        $file = new File('Facture', 'facture.pdf', 1, $uploadedFile, []);
        $dossier = new Dossier(
            'Projet Alpha',
            ' ',
            Uuid::v7()->toRfc4122(),
            [
                $file
            ]
        );
        $comelusWrapper->createDossier($dossier);
    }

    public function testCreateDocumentMisingDossierMailingListId() {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl("https://comelus.fr");
        $comelusWrapper->setApiKey("MyApiKey");

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier mailingListId must be not empty');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $uploadedFile = new UploadedFile(
            __DIR__ . '/resources/test.pdf',
            'test.pdf'
        );
        $file = new File('Facture', 'facture.pdf', 1, $uploadedFile, []);
        $dossier = new Dossier(
            'Projet Alpha',
            'Description du projet',
            '',
            [
                $file
            ]
        );
        $comelusWrapper->createDossier($dossier);
    }

    public function testCreateDocumentNotValidDossierMailingListId() {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl("https://comelus.fr");
        $comelusWrapper->setApiKey("MyApiKey");

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('dossier mailingListId is not a valid UUID');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $uploadedFile = new UploadedFile(
            __DIR__ . '/resources/test.pdf',
            'test.pdf'
        );
        $file = new File('Facture', 'facture.pdf', 1, $uploadedFile, []);
        $dossier = new Dossier(
            'Projet Alpha',
            'Description du projet',
            '447-3299-4da1-94b2-0202245d78db',
            [
                $file
            ]
        );

        $comelusWrapper->createDossier($dossier);
    }
}