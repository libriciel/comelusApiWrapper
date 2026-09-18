<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper;

use Http\Message\MultipartStream\MultipartStreamBuilder;
use Libriciel\ComelusApiWrapper\Model\Annexe;
use Libriciel\ComelusApiWrapper\Model\Dossier;
use Libriciel\ComelusApiWrapper\Model\File;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

class ComelusWrapper
{
    private const string BASE_ROUTE_PATH = '/api/v2';

    private $url;
    private $apiKey;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * @param string $url comelus main url with http|s
     *
     * @throws ComelusException
     */
    public function setUrl(string $url): void
    {
        $httpRegex = '~^((https?)://).+$~';
        if (!preg_match($httpRegex, $url)) {
            throw new ComelusException('malformed url : missing http|https', Response::HTTP_BAD_REQUEST);
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
            throw new ComelusException('malformed apiKey : no whitespace allowed', Response::HTTP_BAD_REQUEST);
        }
        $this->apiKey = $apiKey;
    }

    /**
     * @throws ComelusException
     */
    private function isInit(): void
    {
        if (!$this->url && !$this->apiKey) {
            throw new ComelusException('url and apiKey must be set', Response::HTTP_BAD_REQUEST);
        }

        if (!$this->url) {
            throw new ComelusException('url must be set', Response::HTTP_BAD_REQUEST);
        }

        if (!$this->apiKey) {
            throw new ComelusException('apiKey must be set', Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws ComelusException
     */
    public function check(): array
    {
        return $this->sendRequest(self::BASE_ROUTE_PATH . '/check');
    }

    /**
     * @throws ComelusException
     */
    public function getMailingLists(): array
    {
        return $this->sendRequest(self::BASE_ROUTE_PATH . '/mailing-lists');
    }

    /**
     * @throws ComelusException
     */
    public function getDossiers(): array
    {
        return $this->sendRequest(self::BASE_ROUTE_PATH . '/dossiers');
    }

    /**
     * @param string $dossierId
     * @return array
     * @throws ComelusException
     */
    public function getDossierStatus(string $dossierId): array
    {
        return $this->sendRequest(self::BASE_ROUTE_PATH . "/dossier/$dossierId/status");
    }

    public function createDossier(Dossier $dossier)
    {
        $this->isInit();

        $builder = $this->prepareMultipart($dossier);

        $request = $this->requestFactory->createRequest(
            'POST',
            "$this->url" . self::BASE_ROUTE_PATH . '/dossier'
        )
            ->withHeader('Content-Type', 'multipart/form-data; boundary="' . $builder->getBoundary() . '"')
            ->withAddedHeader('Authorization', 'Bearer ' . $this->apiKey)
            ->withBody($builder->build())
        ;

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new ComelusException($e->getMessage(), $e->getCode());
        }

        if ($response->getStatusCode() != Response::HTTP_CREATED) {
            $content = json_decode($response->getBody()->getContents(), true);
            $message = $content['message'] ?? $response->getReasonPhrase();

            throw new ComelusException($message, $response->getStatusCode());
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    private function prepareMultipart(Dossier $dossier): MultipartStreamBuilder
    {
        $builder = new MultipartStreamBuilder($this->streamFactory);

        $builder->addResource('name', $dossier->getName());
        $builder->addResource('description', $dossier->getDescription());
        $builder->addResource('mailingListId', $dossier->getMailingListId());

        /** @var File $file */
        foreach ($dossier->getAttachedFiles() as $key => $file) {
            $builder->addResource("attachedFiles[{$key}][category]", $file->getCategory());
            $builder->addResource("attachedFiles[{$key}][name]", $file->getName());
            $builder->addResource("attachedFiles[{$key}][rank]", strval($file->getRank()));
            $builder->addResource(
                "attachedFiles[{$key}][uploadedfile]",
                fopen($file->getUploadedFile()->getPathname(), 'r'),
                [
                    'filename' => $file->getUploadedFile()->getClientOriginalName(),
                    'headers' => ['Content-Type' => $file->getUploadedFile()->getMimeType()]
                ]
            );

            /** @var Annexe $annexe */
            foreach ($file->getAnnexes() as $keyAnnexe => $annexe) {
                $builder->addResource("attachedFiles[{$key}][annexes][{$keyAnnexe}][name]", $annexe->getName());
                $builder->addResource("attachedFiles[{$key}][annexes][{$keyAnnexe}][rank]", strval($annexe->getRank()));
                $builder->addResource(
                    "attachedFiles[{$key}][annexes][{$keyAnnexe}][uploadedfile]",
                    fopen($annexe->getUploadedFile()->getPathname(), 'r'),
                    [
                        'filename' => $annexe->getUploadedFile()->getClientOriginalName(),
                        'headers' => ['Content-Type' => $annexe->getUploadedFile()->getMimeType()]
                    ]
                );
            }
        }

        return $builder;
    }

    /**
     * @throws ComelusException
     */
    public function sendDocument(string $documentId): array
    {
        return $this->sendRequest(self::BASE_ROUTE_PATH . "/dossier/$documentId/send", 'POST');
    }

    /**
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

        if ($response->getStatusCode() != Response::HTTP_OK) {
            $content = json_decode($response->getBody()->getContents(), true);
            $message = $content['message'] ?? $response->getReasonPhrase();

            throw new ComelusException($message, $response->getStatusCode());
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
