<?php

use Libriciel\ComelusApiWrapper\ComelusException;
use Libriciel\ComelusApiWrapper\ComelusWrapper;
use Libriciel\ComelusApiWrapper\Model\Dossier;
use Libriciel\ComelusApiWrapper\Model\File;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class ComelusWrapperTest extends TestCase
{
    private function createResponse(int $statusCode, string $body, string $reasonPhrase = ''): ResponseInterface
    {
        $factory = new Psr17Factory();

        return $factory->createResponse($statusCode, $reasonPhrase)
            ->withBody($factory->createStream($body));
    }

    private function stubClient(ResponseInterface $response): ClientInterface
    {
        $client = $this->createStub(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        return $client;
    }

    public function testSetUrl()
    {
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $this->expectNotToPerformAssertions();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl('http://testurl.fr');
    }

    public function testSetUrlMalformed()
    {
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('malformed url : missing http|https');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);
        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl('testurl.fr');
    }

    public function testSetApiKey()
    {
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $this->expectNotToPerformAssertions();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');
    }

    public function testSetApiKeyMalformed()
    {
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('malformed apiKey : no whitespace allowed');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('my apiKey');
    }

    public function testCheckWrongApiKey()
    {
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $response = $this->createResponse(401, 'bad apKey', 'bad apKey');
        $client = $this->stubClient($response);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl('https://test.libriciel.fr');
        $comelusWrapper->setApiKey('aqaqzszcdcdcdc');

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('bad apKey');
        $this->expectExceptionCode(401);

        $comelusWrapper->check();
    }

    public function testCheck()
    {
        $response = $this->createResponse(200, '{"success":true}');
        $client = $this->stubClient($response);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');
        $comelusWrapper->setUrl('https://comelus.fr');

        $content = $comelusWrapper->check();
        $this->assertSame(['success' => true], $content);
    }

    public function testCheckMissingUrlAndApiKey()
    {
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('url and apiKey must be set');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $comelusWrapper->check();
    }

    public function testCheckMissingApiKey()
    {
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl('https://comelus.fr');

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('apiKey must be set');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $comelusWrapper->check();
    }

    public function testCheckMissingUrl()
    {
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('url must be set');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $comelusWrapper->check();
    }

    public function testGetMailingLists()
    {
        $jsonResponse = '[{"id":"dc85a85e-0cfe-47f0-9019-ab104b04a6eb", "name":"myMailingList"}]';
        $response = $this->createResponse(200, $jsonResponse);
        $client = $this->stubClient($response);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');
        $comelusWrapper->setUrl('https://comelus.fr');

        $content = $comelusWrapper->getMailingLists();

        $expected = [['id' => 'dc85a85e-0cfe-47f0-9019-ab104b04a6eb', 'name' => 'myMailingList']];
        $this->assertSame($expected, $content);
    }

    public function testGetDocumentStatus()
    {
        $expected = [
            [
                'id' => 'f7839360-1995-4ae4-97cf-686570795729',
                'recipient' => [
                    'id' => 'b221d819-2c88-4570-a1dd-21b64a670390',
                    'email' => 'recipient@libriciel.invalid',
                    'firstName' => 'Marc',
                    'lastName' => 'Dupont',
                ],
                'sendDate' => '2020-06-02T09:06:19+02:00',
                'readDate' => null,
            ]
        ];

        $response = $this->createResponse(200, json_encode($expected));
        $client = $this->stubClient($response);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');
        $comelusWrapper->setUrl('https://comelus.fr');

        $content = $comelusWrapper->getDossierStatus('f7839360-1995-4ae4-97cf-686570795729');

        $this->assertSame($expected, $content);
    }

    public function testGetDocuments()
    {
        $expected = [
            [
                'id' => 'e1268ac7-f98f-4d08-8140-7c48389bd308',
                'name' => 'Nouveau document',
                'mailingList' => [
                    'id' => 'dc85a85e-0cfe-47f0-9019-ab104b04a6eb',
                    'name' => 'Ville'
                ]
            ]
        ];

        $response = $this->createResponse(200, json_encode($expected));
        $client = $this->stubClient($response);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');
        $comelusWrapper->setUrl('https://comelus.fr');

        $content = $comelusWrapper->getDossiers();

        $this->assertSame($expected, $content);
    }

    public function testSendDocument()
    {
        $response = $this->createResponse(200, '{"success":true}');
        $client = $this->stubClient($response);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');
        $comelusWrapper->setUrl('https://comelus.fr');

        $content = $comelusWrapper->sendDocument('51edebc7-3299-4da1-94b2-0205745d78db');

        $this->assertSame(['success' => true], $content);
    }

    public function testCreateDocument()
    {
        $expected = [
            'success' => true,
            'id' => 'da781241-3c2c-420c-90f8-f2f83df99c74'
        ];

        $response = $this->createResponse(201, json_encode($expected));
        $client = $this->stubClient($response);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');
        $comelusWrapper->setUrl('https://comelus.fr');

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
            'success' => true,
            'id' => 'da781241-3c2c-420c-90f8-f2f83df99c74'
        ];

        $response = $this->createResponse(201, json_encode($expected));
        $client = $this->stubClient($response);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');
        $comelusWrapper->setUrl('https://comelus.fr');

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
            'success' => true,
            'id' => 'da781241-3c2c-420c-90f8-f2f83df99c74'
        ];

        $response = $this->createResponse(201, json_encode($expected));
        $client = $this->stubClient($response);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');
        $comelusWrapper->setUrl('https://comelus.fr');

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
            'success' => true,
            'id' => 'da781241-3c2c-420c-90f8-f2f83df99c74'
        ];

        $response = $this->createResponse(201, json_encode($expected));
        $client = $this->stubClient($response);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');
        $comelusWrapper->setUrl('https://comelus.fr');

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
            'success' => true,
            'id' => 'da781241-3c2c-420c-90f8-f2f83df99c74'
        ];

        $response = $this->createResponse(201, json_encode($expected));
        $client = $this->stubClient($response);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');
        $comelusWrapper->setUrl('https://comelus.fr');

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
            'success' => true,
            'id' => 'da781241-3c2c-420c-90f8-f2f83df99c74'
        ];

        $response = $this->createResponse(201, json_encode($expected));
        $client = $this->stubClient($response);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');
        $comelusWrapper->setUrl('https://comelus.fr');

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

    public function testCreateDocumentMisingDossierName()
    {
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl('https://comelus.fr');
        $comelusWrapper->setApiKey('MyApiKey');

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

    public function testCreateDocumentMisingDossierDescription()
    {
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl('https://comelus.fr');
        $comelusWrapper->setApiKey('MyApiKey');

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

    public function testCreateDocumentMisingDossierMailingListId()
    {
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl('https://comelus.fr');
        $comelusWrapper->setApiKey('MyApiKey');

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

    public function testCreateDocumentNotValidDossierMailingListId()
    {
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl('https://comelus.fr');
        $comelusWrapper->setApiKey('MyApiKey');

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
