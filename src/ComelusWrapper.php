<?php

namespace Libriciel\ComelusApiWrapper;

use Http\Message\MultipartStream\MultipartStreamBuilder;
use Libriciel\ComelusApiWrapper\Model\ComelusDocument;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

class ComelusWrapper
{

    /**
     * @var string
     */
    private string $url;

    /*
     * @var string
     */
    private $apiKey;

    public function __construct(
        private  readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    )
    {}

    /**
     * @param string $url comelus main url with http|s     *
     * @throws ComelusException
     */
    public function setUrl(string $url): void
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
    public function setApiKey(string $apiKey): void
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
    private function isInit(): void
    {
        if (!$this->url || !$this->apiKey) {
            throw new ComelusException("url and apiKey must be set", 400);
        }
    }

    /**
     * @throws ComelusException
     */
    public function check(): array
    {
        return $this->sendRequest('/api/v110/check');
    }


    /**
     * @throws ComelusException
     */
    public function getMailingLists(): array
    {
        return $this->sendRequest('/api/v110/mailing-list');
    }

    /**
     * @throws ComelusException
     */
    public function getDocuments(): array
    {
        return $this->sendRequest('/api/v110/document');
    }

    /**
     * @param string $documentId
     * @return array
     * @throws ComelusException
     */
    public function getDocumentStatus(string $documentId): array
    {
        return $this->sendRequest("/api/v110/document/$documentId/status");
    }


    /**
     * @param ComelusDocument $comelusDocument
     * @return mixed
     * @throws ComelusException
     */
    public function createDocument(ComelusDocument $comelusDocument): mixed
    {
        $this->isInit();


        if (!$this->isValid($comelusDocument)) {
            throw new ComelusException('name, mailingListId, $files, desriptions and filesInfos cannot be empty', 400);
        }

        $builder = $this->prepareMultipart(
            $comelusDocument->getName(),
            $comelusDocument->getMailingListId(),
            $comelusDocument->getDescription(),
            $comelusDocument->getFiles(),
            $comelusDocument->getFilesInfos()
        );


        $request = $this->requestFactory->createRequest(
            "POST",
            "$this->url" . '/api/v110/document'
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

    private function isValid(ComelusDocument $comelusDocument): bool
    {
        return !empty($comelusDocument->getName())
            && !empty($comelusDocument->getMailingListId())
            && !empty($comelusDocument->getFiles())
            && !empty($comelusDocument->getDescription())
            && !empty($comelusDocument->getFilesInfos());
    }


    private function prepareMultipart(string $name, string $mailingListId, string $description, array $files, array $filesInfos): MultipartStreamBuilder
    {
        $builder = new MultipartStreamBuilder($this->streamFactory);

        /** @var UploadedFileInterface $file */
        foreach ($files as $key => $file) {

            $builder->addResource(
                "attachedFiles[$key]",
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
        $builder->addResource('filesInfos', json_encode($filesInfos));

        return $builder;
    }

    /**
     * @param string $documentId
     * @return array
     * @throws ComelusException
     */
    public function sendDocument(string $documentId): array
    {
        return $this->sendRequest("/api/v110/document/$documentId/send", 'POST');
    }

    /**
     *
     * @param string $path
     * @param string $method
     * @return array
     * @throws ComelusException
     */
    private function sendRequest(string $path, string $method = 'GET'): array
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
