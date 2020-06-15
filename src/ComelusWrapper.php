<?php

namespace Libriciel\ComelusApiWrapper;

use Http\Message\MultipartStream\MultipartStreamBuilder;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

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
     * @param string $url comelus main url with http|s
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
     * @param string $apiKey apikey from comelus structure
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
        return $this->sendRequest('/api/v1/check');
    }


    /**
     * @throws ComelusException
     */
    public function getMailingLists(): array
    {
        return $this->sendRequest('/api/v1/mailing-list');
    }

    /**
     * @throws ComelusException
     */
    public function getDocuments(): array
    {
        return $this->sendRequest('/api/v1/document');
    }

    /**
     * @param string $documentId
     * @return array
     * @throws ComelusException
     */
    public function getDocumentStatus(string $documentId): array
    {
        return $this->sendRequest("/api/v1/document/$documentId/status");
    }


    /**
     * @param string $name
     * @param string $mailingListId
     * @param string $description
     * @param UploadedFileInterface[] $files
     * @throws ComelusException
     */
    public function createDocument(string $name, string $mailingListId, string $description, array $files)
    {
        $this->isInit();
        if (empty($name) || empty($mailingListId) || empty($files) || empty($description)) {
            throw new ComelusException('name, mailingListId, $files, desriptions cannot be empty', 400);
        }

        $builder = $this->prepareMultipart($name, $mailingListId, $description, $files);


        $request = $this->requestFactory->createRequest(
            "POST",
            "$this->url" . '/api/v1/document'
        )
            ->withHeader('Content-Type', 'multipart/form-data; boundary="' . $builder->getBoundary() . '"')
            ->withAddedHeader('Authorization', 'Bearer ' . $this->apiKey)
            ->withBody($builder->build());


        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new ComelusException($e->getMessage(), $e->getCode());
        }

        if ($response->getStatusCode() != 201) {
            $message = $response->getReasonPhrase();
            if ($response->getBody()) {
                $content = json_decode($response->getBody()->getContents(), true);
                $message = $content['message'] ?? $response->getReasonPhrase();

            }
            throw new ComelusException($message, $response->getStatusCode());
        }

        return json_decode($response->getBody()->getContents(), true);
    }


    private function prepareMultipart(string $name, string $mailingListId, string $description, array $files): MultipartStreamBuilder
    {
        $builder = new MultipartStreamBuilder($this->streamFactory);

        foreach ($files as $file) {
            $builder->addResource(
                'attachedFiles[]',
                $file->getStream(),
                [
                    'filename' => $file->getClientFilename(),
                    'headers' => ['Content-Type' => $file->getClientMediaType()]
                ]
            );
        }

        $builder->addResource('name', $name);
        $builder->addResource('mailingListId', $mailingListId);
        $builder->addResource('description', $description);

        return $builder;
    }

    /**
     * @param string $documentId
     * @return array
     * @throws ComelusException
     */
    public function sendDocument(string $documentId)
    {
        return $this->sendRequest("/api/v1/document/$documentId/send", 'POST');
    }


    /**
     *
     * @param string $path
     * @param string $method
     * @return array
     * @throws ComelusException
     */
    private function sendRequest(string $path, $method = 'GET'): array
    {
        $this->isInit();
        $request = $this->requestFactory->createRequest(
            $method,
            "$this->url" . $path
        )->withHeader('Authorization', 'Bearer ' . $this->apiKey);

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new ComelusException($e->getMessage(), $e->getCode());
        }

        if ($response->getStatusCode() != 200) {
            $message = $response->getReasonPhrase();
            if ($response->getBody()) {
                $content = json_decode($response->getBody()->getContents(), true);
                $message = $content['message'] ?? $response->getReasonPhrase();

            }
            throw new ComelusException($message, $response->getStatusCode());
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
