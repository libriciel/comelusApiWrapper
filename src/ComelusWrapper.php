<?php

declare(strict_types=1);

namespace Libriciel\ComelusApiWrapper;

use Http\Message\MultipartStream\MultipartStreamBuilder;
use JsonException;
use Libriciel\ComelusApiWrapper\Model\Annexe;
use Libriciel\ComelusApiWrapper\Model\Dossier;
use Libriciel\ComelusApiWrapper\Model\File;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
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
        $noWhitespace = '~^\S+\z~';

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

    /**
     * @throws ComelusException
     */
    public function createDossier(Dossier $dossier): array
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

        return $this->send($request, Response::HTTP_CREATED);
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

        return $this->send($request, Response::HTTP_OK);
    }

    /**
     * @throws ComelusException
     */
    private function send(RequestInterface $request, int $expectedStatusCode): array
    {
        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new ComelusException($e->getMessage(), $e->getCode(), $e);
        }

        $body = $response->getBody()->getContents();

        if ($response->getStatusCode() !== $expectedStatusCode) {
            throw new ComelusException($this->getErrorMessage($body, $response->getReasonPhrase()), $response->getStatusCode());
        }

        try {
            $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ComelusException('invalid response : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, $e);
        }

        if (!is_array($data)) {
            throw new ComelusException('invalid response : JSON object expected', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $data;
    }

    /**
     * Error message sent by the API ({"message": "..."}), or the HTTP reason phrase ("Forbidden"...) as a fallback.
     */
    private function getErrorMessage(string $body, string $reasonPhrase): string
    {
        $content = json_decode($body, true);
        $message = is_array($content) ? ($content['message'] ?? null) : null;

        return is_string($message) && $message !== '' ? $message : $reasonPhrase;
    }
}
