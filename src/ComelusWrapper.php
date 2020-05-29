<?php

namespace Libriciel\LsMessageWrapper;

use Http\Message\MultipartStream\MultipartStreamBuilder;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;

class ComelusWrapper
{

    /**
     * @var ClientInterface
     */
    private $client;
    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $apiKey;
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    public function __construct(ClientInterface $client, RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory)
    {

        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @param string $url
     *
     * @throws ComelusException
     */
    public function setUrl(string $url)
    {
        $httpRegex = "~^((https?)://).+$~";
        if (!preg_match($httpRegex, $url)) {
            throw new ComelusException("malformed url : missing http|https", 400);
        }
        $this->url = $url;
    }


    /**
     * @param string $apiKey
     * @throws ComelusException
     */
    public function setApiKey(string $apiKey)
    {
        $noWhitespace = "~^^\S+$~";

        if (!preg_match($noWhitespace, $apiKey)) {
            throw new ComelusException("malformed apiKey : no whitespace allowed", 400);
        }
        $this->apiKey = $apiKey;
    }


    /**
     *
     * @throws ComelusException
     */
    private function isInit()
    {
        if (!$this->url || !$this->apiKey) {
            throw new ComelusException("url and apiKey must be set", 400);
        }
    }

    /**
     * @throws ComelusException
     */
    public function check()
    {
        return $this->sendGetRequest('/api/v1/info');
    }


    /**
     * @throws ComelusException
     */
    public function getMailingLists(): array
    {
        return $this->sendGetRequest('/api/v1/mailingList');
    }

    /**
     * @throws ComelusException
     */
    public function getDocuments(): array
    {
        return $this->sendGetRequest('/api/v1/document');
    }

    /**
     * @param string $documentId
     * @return array
     * @throws ComelusException
     */
    public function getDocumentStatus(string $documentId): array
    {
        return $this->sendGetRequest("/api/v1/document/$documentId/status");
    }


    /**
     * @param string $name
     * @param string $mailingListId
     * @param string $description
     * @param array $files
     * @throws ComelusException
     */
    public function createDocument(string $name, string $mailingListId, string $description, array $files ){
        $this->isInit();
        if (empty($name) || $mailingListId || $files || $description) {
            throw new ComelusException('name, mailingListId, $files, desriptions cannot be empty', 400);
        }

        $builder =  new MultipartStreamBuilder($this->streamFactory);

        $body = [
            'name' => $name,
            'mailingList_id' => $mailingListId,
            'description' => $description
        ];

        $request = $this->requestFactory->createRequest(
            "GET",
            "$this->url" . '/api/v1/document'
        )->withHeader('Authorization', 'Bearer ' . $this->apiKey);

        $request->getBody()->write(json_encode($body));


    }




    /**
     *
     * @return array
     * @throws ComelusException
     */
    private function sendGetRequest(string $path): array {
        $this->isInit();
        $request = $this->requestFactory->createRequest(
            "GET",
            "$this->url" . $path
        )->withHeader('Authorization', 'Bearer ' . $this->apiKey);

        try {
            $response = $this->client->sendRequest($request);

            if ($response->getStatusCode() != 200) {
                throw new ComelusException($response->getReasonPhrase(), $response->getStatusCode());
            }
            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientExceptionInterface $e) {
            throw new ComelusException($e->getMessage(), $e->getCode());
        }
    }



}
