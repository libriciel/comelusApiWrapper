<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper\Test;

use Libriciel\ComelusApiWrapper\ComelusException;
use Libriciel\ComelusApiWrapper\ComelusWrapper;
use Libriciel\ComelusApiWrapper\Model\Annexe;
use Libriciel\ComelusApiWrapper\Model\Dossier;
use Libriciel\ComelusApiWrapper\Model\File;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class ComelusWrapperTest extends TestCase
{
    private ?RequestInterface $sentRequest = null;

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

    /**
     * Client qui renvoie $response et garde la requête envoyée dans $this->sentRequest.
     */
    private function capturingClient(ResponseInterface $response): ClientInterface
    {
        $client = $this->createStub(ClientInterface::class);
        $client->method('sendRequest')->willReturnCallback(
            function (RequestInterface $request) use ($response): ResponseInterface {
                $this->sentRequest = $request;

                return $response;
            }
        );

        return $client;
    }

    private function failingClient(): ClientInterface
    {
        $client = $this->createStub(ClientInterface::class);
        $client->method('sendRequest')->willThrowException(
            new class('Could not resolve host', 6) extends RuntimeException implements ClientExceptionInterface {
            }
        );

        return $client;
    }

    private function createWrapper(ClientInterface $client): ComelusWrapper
    {
        $factory = new Psr17Factory();
        $comelusWrapper = new ComelusWrapper($client, $factory, $factory);
        $comelusWrapper->setUrl('https://comelus.fr');
        $comelusWrapper->setApiKey('myapiKey');

        return $comelusWrapper;
    }

    private function createDossierWithOneFile(): Dossier
    {
        $uploadedFile = new UploadedFile(__DIR__ . '/resources/test.pdf', 'test.pdf');

        return new Dossier(
            'myDocument',
            'Nouveau document',
            '44edebc7-3299-4da1-94b2-0202245d78db',
            [new File('Projets', 'Délibération', 1, $uploadedFile, [])]
        );
    }

    public function testSetUrl(): void
    {
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $this->expectNotToPerformAssertions();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl('https://testurl.fr');
    }

    public function testSetUrlMalformed(): void
    {
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('malformed url : https required');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);
        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl('testurl.fr');
    }

    public function testSetApiKey(): void
    {
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $this->expectNotToPerformAssertions();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');
    }

    public function testSetApiKeyMalformed(): void
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

    public function testSetUrlWithHttpThrowsException(): void
    {
        $comelusWrapper = new ComelusWrapper($this->createStub(ClientInterface::class), new Psr17Factory(), new Psr17Factory());

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('malformed url : https required');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $comelusWrapper->setUrl('http://comelus.fr');
    }

    public function testSetUrlWithTrailingNewlineThrowsException(): void
    {
        $comelusWrapper = new ComelusWrapper($this->createStub(ClientInterface::class), new Psr17Factory(), new Psr17Factory());

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('malformed url : https required');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $comelusWrapper->setUrl("https://comelus.fr\n");
    }

    public function testSetUrlRemovesTrailingSlash(): void
    {
        $factory = new Psr17Factory();
        $comelusWrapper = new ComelusWrapper($this->capturingClient($this->createResponse(200, '{"success":true}')), $factory, $factory);
        $comelusWrapper->setUrl('https://comelus.fr/');
        $comelusWrapper->setApiKey('myapiKey');

        $comelusWrapper->check();

        $this->assertNotNull($this->sentRequest);
        $this->assertSame('https://comelus.fr/api/v2/check', (string) $this->sentRequest->getUri());
    }

    public function testSetUrlWithOnlySlashesAfterSchemeThrowsException(): void
    {
        $comelusWrapper = new ComelusWrapper($this->createStub(ClientInterface::class), new Psr17Factory(), new Psr17Factory());

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('malformed url : https required');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $comelusWrapper->setUrl('https:///');
    }

    public function testSetApiKeyWithTrailingNewlineThrowsException(): void
    {
        $comelusWrapper = new ComelusWrapper($this->createStub(ClientInterface::class), new Psr17Factory(), new Psr17Factory());

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('malformed apiKey : no whitespace allowed');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $comelusWrapper->setApiKey("myapiKey\n");
    }

    public function testCheckWrongApiKey(): void
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

    public function testCheck(): void
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

    public function testCheckMissingUrlAndApiKey(): void
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

    public function testCheckMissingApiKey(): void
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

    public function testCheckMissingUrl(): void
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

    public function testGetMailingLists(): void
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

    public function testGetDocumentStatus(): void
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

        $response = $this->createResponse(200, json_encode($expected, JSON_THROW_ON_ERROR));
        $client = $this->stubClient($response);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');
        $comelusWrapper->setUrl('https://comelus.fr');

        $content = $comelusWrapper->getDossierStatus('f7839360-1995-4ae4-97cf-686570795729');

        $this->assertSame($expected, $content);
    }

    public function testGetDocuments(): void
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

        $response = $this->createResponse(200, json_encode($expected, JSON_THROW_ON_ERROR));
        $client = $this->stubClient($response);
        $requestFactory = new Psr17Factory();
        $streamFactory = new Psr17Factory();

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey('myapiKey');
        $comelusWrapper->setUrl('https://comelus.fr');

        $content = $comelusWrapper->getDossiers();

        $this->assertSame($expected, $content);
    }

    public function testSendDocument(): void
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

    public function testCreateDocument(): void
    {
        $expected = [
            'success' => true,
            'id' => 'da781241-3c2c-420c-90f8-f2f83df99c74'
        ];

        $response = $this->createResponse(201, json_encode($expected, JSON_THROW_ON_ERROR));
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

    public function testCreateDocumentMissingCategory(): void
    {
        $expected = [
            'success' => true,
            'id' => 'da781241-3c2c-420c-90f8-f2f83df99c74'
        ];

        $response = $this->createResponse(201, json_encode($expected, JSON_THROW_ON_ERROR));
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

    public function testCreateDocumentMissingCategoryNull(): void
    {
        $expected = [
            'success' => true,
            'id' => 'da781241-3c2c-420c-90f8-f2f83df99c74'
        ];

        $response = $this->createResponse(201, json_encode($expected, JSON_THROW_ON_ERROR));
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

    public function testCreateDocumentMissingDossierName(): void
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
            '',
            'Description du projet',
            Uuid::v7()->toRfc4122(),
            [
                $file
            ]
        );
        $comelusWrapper->createDossier($dossier);
    }

    public function testCreateDocumentMissingDossierDescription(): void
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
            '',
            Uuid::v7()->toRfc4122(),
            [
                $file
            ]
        );
        $comelusWrapper->createDossier($dossier);
    }

    public function testCreateDocumentMissingDossierMailingListId(): void
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

    public function testCreateDocumentNotValidDossierMailingListId(): void
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

    public function testCheckSendsGetRequestWithBearerToken(): void
    {
        $comelusWrapper = $this->createWrapper($this->capturingClient($this->createResponse(200, '{"success":true}')));

        $comelusWrapper->check();

        $this->assertNotNull($this->sentRequest);
        $this->assertSame('GET', $this->sentRequest->getMethod());
        $this->assertSame('https://comelus.fr/api/v2/check', (string) $this->sentRequest->getUri());
        $this->assertSame('Bearer myapiKey', $this->sentRequest->getHeaderLine('Authorization'));
    }

    public function testSendDocumentSendsPostRequest(): void
    {
        $comelusWrapper = $this->createWrapper($this->capturingClient($this->createResponse(200, '{"success":true}')));

        $comelusWrapper->sendDocument('51edebc7-3299-4da1-94b2-0205745d78db');

        $this->assertNotNull($this->sentRequest);
        $this->assertSame('POST', $this->sentRequest->getMethod());
        $this->assertSame(
            'https://comelus.fr/api/v2/dossier/51edebc7-3299-4da1-94b2-0205745d78db/send',
            (string) $this->sentRequest->getUri()
        );
    }

    public function testCheckNetworkErrorThrowsComelusException(): void
    {
        $comelusWrapper = $this->createWrapper($this->failingClient());

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('Could not resolve host');
        $this->expectExceptionCode(6);

        $comelusWrapper->check();
    }

    public function testCheckErrorResponseUsesMessageFromJsonBody(): void
    {
        $response = $this->createResponse(403, '{"message":"Structure désactivée"}', 'Forbidden');
        $comelusWrapper = $this->createWrapper($this->stubClient($response));

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('Structure désactivée');
        $this->expectExceptionCode(403);

        $comelusWrapper->check();
    }

    public function testCheckErrorResponseWithoutJsonUsesReasonPhrase(): void
    {
        $response = $this->createResponse(502, '<html>Bad Gateway</html>', 'Bad Gateway');
        $comelusWrapper = $this->createWrapper($this->stubClient($response));

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('Bad Gateway');
        $this->expectExceptionCode(502);

        $comelusWrapper->check();
    }

    public function testCreateDossierSendsMultipartRequestWithFilesAndAnnexes(): void
    {
        $uploadedFile = new UploadedFile(__DIR__ . '/resources/test.pdf', 'test.pdf');
        $file = new File('Projets', 'Délibération', 1, $uploadedFile, []);
        $file->addAnnexe(new Annexe('Annexe 1', 1, $uploadedFile));
        $file->addAnnexe(new Annexe('Annexe 2', 2, $uploadedFile));
        $dossier = new Dossier('myDocument', 'Nouveau document', '44edebc7-3299-4da1-94b2-0202245d78db', [$file]);

        $response = $this->createResponse(201, '{"success":true,"id":"da781241-3c2c-420c-90f8-f2f83df99c74"}');
        $comelusWrapper = $this->createWrapper($this->capturingClient($response));

        $content = $comelusWrapper->createDossier($dossier);

        $this->assertSame(['success' => true, 'id' => 'da781241-3c2c-420c-90f8-f2f83df99c74'], $content);
        $this->assertNotNull($this->sentRequest);
        $this->assertSame('POST', $this->sentRequest->getMethod());
        $this->assertSame('https://comelus.fr/api/v2/dossier', (string) $this->sentRequest->getUri());
        $this->assertSame('Bearer myapiKey', $this->sentRequest->getHeaderLine('Authorization'));
        $this->assertStringStartsWith('multipart/form-data; boundary=', $this->sentRequest->getHeaderLine('Content-Type'));

        $body = (string) $this->sentRequest->getBody();
        foreach ([
            'name' => 'myDocument',
            'mailingListId' => '44edebc7-3299-4da1-94b2-0202245d78db',
            'attachedFiles[0][category]' => 'Projets',
            'attachedFiles[0][name]' => 'Délibération',
            'attachedFiles[0][rank]' => '1',
            'attachedFiles[0][annexes][0][name]' => 'Annexe 1',
            'attachedFiles[0][annexes][0][rank]' => '1',
            'attachedFiles[0][annexes][1][name]' => 'Annexe 2',
            'attachedFiles[0][annexes][1][rank]' => '2',
        ] as $field => $value) {
            $this->assertStringContainsString("name=\"{$field}\"\r\n\r\n{$value}\r\n", $body);
        }
        $this->assertStringContainsString('name="attachedFiles[0][uploadedfile]"; filename="test.pdf"', $body);
        $this->assertStringContainsString('name="attachedFiles[0][annexes][1][uploadedfile]"; filename="test.pdf"', $body);
    }

    public function testCreateDossierNetworkErrorThrowsComelusException(): void
    {
        $comelusWrapper = $this->createWrapper($this->failingClient());

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('Could not resolve host');
        $this->expectExceptionCode(6);

        $comelusWrapper->createDossier($this->createDossierWithOneFile());
    }

    public function testCreateDossierErrorResponseUsesMessageFromJsonBody(): void
    {
        $response = $this->createResponse(400, '{"message":"mailingList not found"}', 'Bad Request');
        $comelusWrapper = $this->createWrapper($this->stubClient($response));

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('mailingList not found');
        $this->expectExceptionCode(400);

        $comelusWrapper->createDossier($this->createDossierWithOneFile());
    }

    public function testCheckEmptyResponseThrowsComelusException(): void
    {
        $comelusWrapper = $this->createWrapper($this->stubClient($this->createResponse(200, '')));

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('invalid response : Syntax error');
        $this->expectExceptionCode(500);

        $comelusWrapper->check();
    }

    public function testGetMailingListsHtmlResponseThrowsComelusException(): void
    {
        $comelusWrapper = $this->createWrapper($this->stubClient($this->createResponse(200, '<html>Maintenance</html>')));

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('invalid response : Syntax error');
        $this->expectExceptionCode(500);

        $comelusWrapper->getMailingLists();
    }

    public function testCheckNonArrayJsonResponseThrowsComelusException(): void
    {
        $comelusWrapper = $this->createWrapper($this->stubClient($this->createResponse(200, '"ok"')));

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('invalid response : JSON object expected');
        $this->expectExceptionCode(500);

        $comelusWrapper->check();
    }

    public function testCreateDossierEmptyResponseThrowsComelusException(): void
    {
        $comelusWrapper = $this->createWrapper($this->stubClient($this->createResponse(201, '')));

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('invalid response : Syntax error');
        $this->expectExceptionCode(500);

        $comelusWrapper->createDossier($this->createDossierWithOneFile());
    }

    public function testCheckErrorResponseWithNonStringMessageUsesReasonPhrase(): void
    {
        $response = $this->createResponse(422, '{"message":["name is required","rank must be positive"]}', 'Unprocessable Content');
        $comelusWrapper = $this->createWrapper($this->stubClient($response));

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('Unprocessable Content');
        $this->expectExceptionCode(422);

        $comelusWrapper->check();
    }

    public function testCheckErrorResponseWithNumericMessageUsesReasonPhrase(): void
    {
        $response = $this->createResponse(500, '{"message":42}', 'Internal Server Error');
        $comelusWrapper = $this->createWrapper($this->stubClient($response));

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('Internal Server Error');
        $this->expectExceptionCode(500);

        $comelusWrapper->check();
    }

    public function testCreateDossierErrorResponseWithEmptyMessageUsesReasonPhrase(): void
    {
        $response = $this->createResponse(403, '{"message":""}', 'Forbidden');
        $comelusWrapper = $this->createWrapper($this->stubClient($response));

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('Forbidden');
        $this->expectExceptionCode(403);

        $comelusWrapper->createDossier($this->createDossierWithOneFile());
    }

    public function testCreateDossierWithFileDeletedBeforeSendingThrowsComelusException(): void
    {
        $path = $this->copyTestPdf();
        $dossier = new Dossier(
            'myDocument',
            'Nouveau document',
            '44edebc7-3299-4da1-94b2-0202245d78db',
            [new File('Projets', 'Délibération', 1, new UploadedFile($path, 'test.pdf'), [])]
        );
        unlink($path);
        $comelusWrapper = $this->createWrapper($this->stubClient($this->createResponse(201, '{"success":true}')));

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('attachedFiles[0][uploadedfile] file not readable');
        $this->expectExceptionCode(400);

        $comelusWrapper->createDossier($dossier);
    }

    public function testCreateDossierWithAnnexeDeletedBeforeSendingThrowsComelusException(): void
    {
        $path = $this->copyTestPdf();
        $file = new File('Projets', 'Délibération', 1, new UploadedFile(__DIR__ . '/resources/test.pdf', 'test.pdf'), []);
        $file->addAnnexe(new Annexe('Annexe', 1, new UploadedFile($path, 'annexe.pdf')));
        $dossier = new Dossier('myDocument', 'Nouveau document', '44edebc7-3299-4da1-94b2-0202245d78db', [$file]);
        unlink($path);
        $comelusWrapper = $this->createWrapper($this->stubClient($this->createResponse(201, '{"success":true}')));

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('attachedFiles[0][annexes][0][uploadedfile] file not readable');
        $this->expectExceptionCode(400);

        $comelusWrapper->createDossier($dossier);
    }

    private function copyTestPdf(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'comelus');
        $this->assertNotFalse($path);
        copy(__DIR__ . '/resources/test.pdf', $path);

        return $path;
    }

    public function testCreateDossierWithoutCategorySendsEmptyCategory(): void
    {
        $uploadedFile = new UploadedFile(__DIR__ . '/resources/test.pdf', 'test.pdf');
        $file = new File(null, 'Délibération', 0, $uploadedFile, []);
        $file->addAnnexe(new Annexe('Annexe', 1, $uploadedFile));
        $dossier = new Dossier('myDocument', 'Nouveau document', '44edebc7-3299-4da1-94b2-0202245d78db', [$file]);

        $response = $this->createResponse(201, '{"success":true,"id":"da781241-3c2c-420c-90f8-f2f83df99c74"}');
        $comelusWrapper = $this->createWrapper($this->capturingClient($response));

        $comelusWrapper->createDossier($dossier);

        $this->assertNotNull($this->sentRequest);
        $body = (string) $this->sentRequest->getBody();
        foreach ([
            'attachedFiles[0][category]' => '',
            'attachedFiles[0][name]' => 'Délibération',
            'attachedFiles[0][rank]' => '0',
            'attachedFiles[0][annexes][0][name]' => 'Annexe',
            'attachedFiles[0][annexes][0][rank]' => '1',
        ] as $field => $value) {
            $this->assertStringContainsString("name=\"{$field}\"\r\n\r\n{$value}\r\n", $body);
        }
    }

    /**
     * getMimeType() renvoie null quand le type n'est pas reconnu : aucun en-tête vide ne doit partir,
     * MultipartStreamBuilder déduit alors le type du nom de fichier.
     */
    public function testCreateDossierWithUnknownMimeTypeGuessesContentTypeFromFilename(): void
    {
        $uploadedFile = new class(__DIR__ . '/resources/test.pdf', 'test.pdf') extends UploadedFile {
            public function getMimeType(): ?string
            {
                return null;
            }
        };
        $dossier = new Dossier(
            'myDocument',
            'Nouveau document',
            '44edebc7-3299-4da1-94b2-0202245d78db',
            [new File('Projets', 'Délibération', 1, $uploadedFile, [])]
        );
        $response = $this->createResponse(201, '{"success":true}');
        $comelusWrapper = $this->createWrapper($this->capturingClient($response));

        $comelusWrapper->createDossier($dossier);

        $this->assertNotNull($this->sentRequest);
        $body = (string) $this->sentRequest->getBody();
        $this->assertStringNotContainsString("Content-Type: \r\n", $body);
        $this->assertStringContainsString(
            "name=\"attachedFiles[0][uploadedfile]\"; filename=\"test.pdf\"\r\nContent-Type: application/pdf\r\n",
            $body
        );
    }
}
