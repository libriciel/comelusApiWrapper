<?php

use Libriciel\ComelusApiWrapper\Model\ComelusDocument;
use Libriciel\ComelusApiWrapper\ComelusException;
use Libriciel\ComelusApiWrapper\ComelusWrapper;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Nyholm\Psr7\UploadedFile;

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
        $request->method('withHeader')->will($this->returnSelf());
        $request->method('withAddedHeader')->will($this->returnSelf());
        $request->method('withBody')->will($this->returnSelf());


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

    /**
     * @throws ComelusException
     */
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
        $this->expectExceptionCode(400);
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
        $this->expectExceptionCode(400);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("my apiKey");
    }


    /**
     * @throws ComelusException
     */
    public function testCheckWrongApiKey()
    {
        $requestFactory = $this->mockRequestFactory();

        $response = $this->mockResponse(401, 'bad apKey', null);
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

    /**
     * @throws ComelusException
     */
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


    public function testCheckMissingApiKey()
    {

        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl("https://comelus.fr");

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage("url and apiKey must be set");
        $this->expectExceptionCode(400);

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
                    "email" => "recipient@email.com",
                    "firstName" => "Marc",
                    "lastName" => "Dupont",
                ],
                "sendDate" => "2020-06-02T09:06:19+02:00",
                "readDate" => null,
            ]];

        $response = $this->mockResponse(200, '', $this->mockBody(json_encode($expected)));
        $client = $this->mockClient($response);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");
        $comelusWrapper->setUrl("https://comelus.fr");

        $content = $comelusWrapper->getDocumentStatus('f7839360-1995-4ae4-97cf-686570795729');

        $this->assertSame($expected, $content);


    }


    public function testGetDocuments()
    {
        $expected = [[
            "id" => "e1268ac7-f98f-4d08-8140-7c48389bd308",
            "name" => "Nouveau document",
            "mailingList" => [
                "id" => "dc85a85e-0cfe-47f0-9019-ab104b04a6eb",
                "name" => "Ville"
            ]]];

        $response = $this->mockResponse(200, '', $this->mockBody(json_encode($expected)));
        $client = $this->mockClient($response);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");
        $comelusWrapper->setUrl("https://comelus.fr");

        $content = $comelusWrapper->getDocuments();

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
        $expected = ["success" => true,
            "id" => "da781241-3c2c-420c-90f8-f2f83df99c74"];

        $response = $this->mockResponse(201, '', $this->mockBody(json_encode($expected)));
        $client = $this->mockClient($response);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setApiKey("myapiKey");
        $comelusWrapper->setUrl("https://comelus.fr");

        $file = $this->createMock(UploadedFileInterface::class);

        $file->method('getStream')->willReturn("my stream");
        $file->method('getClientFilename')->willReturn("deliberation.pdf");
        $file->method('getClientMediaType')->willReturn("application/pdf");


        file_put_contents("/tmp/deliberation.pdf", "my streasqdsqd sqdsqd sqdsqd qsdsqd qsdm");

        $comelusDocument = new ComelusDocument();
        $comelusDocument->setName('deliberation.pdf')
            ->setMailingListId('uuid')
            ->setDescription("description")
            ->setFiles(["test" => new UploadedFile(fopen("/tmp/deliberation.pdf", "r"), 100, 0, "deliberation.pdf", "application/pdf")])
            ->setFilesInfos( [["name" => "deliberation.pdf", "rank" => 1, "linkedFile" => "test"]]);


        $content = $comelusWrapper->createDocument($comelusDocument);

        $this->assertSame($expected, $content);

    }

    public function testCreateDocumentMisingFields() {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->mockRequestFactory();
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $comelusDocument = $this->createMock(ComelusDocument::class);


        $comelusWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);
        $comelusWrapper->setUrl("https://comelus.fr");
        $comelusWrapper->setApiKey("MyApiKey");

        $this->expectException(ComelusException::class);
        $this->expectExceptionMessage('name, mailingListId, $files, desriptions and filesInfos cannot be empty');
        $this->expectExceptionCode(400);

        $comelusDocument->method('getName')->willReturn('name');
        $comelusDocument->method('getMailingListId')->willReturn('uuid');
        $comelusDocument->method('getDescription')->willReturn('description');
        $comelusDocument->method('getFiles')->willReturn([]);
        $comelusDocument->method('getFilesInfos')->willReturn([]);

        $comelusWrapper->createDocument($comelusDocument);
    }


}
