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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Not final: consumers replace it with a test double (idelibre: getMockBuilder(ComelusWrapper::class)).
 */
class ComelusWrapper
{
    private const string BASE_ROUTE_PATH = '/api/v2';

    private ?string $url = null;
    private ?string $apiKey = null;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * @param string $url comelus main url, https only
     *
     * @throws ComelusException
     */
    public function setUrl(string $url): void
    {
        $url = rtrim($url, '/');

        $httpRegex = '~^https://.+\z~';
        if (!preg_match($httpRegex, $url)) {
            throw new ComelusException('malformed url : https required', Response::HTTP_BAD_REQUEST);
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
     * @return array{string, string} url and apiKey
     *
     * @throws ComelusException
     */
    private function getCredentials(): array
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

        return [$this->url, $this->apiKey];
    }

    /**
     * @throws ComelusException
     * @return array<mixed>
     *
     */
    public function check(): array
    {
        return $this->sendRequest(self::BASE_ROUTE_PATH . '/check');
    }

    /**
     * @throws ComelusException
     * @return array<mixed>
     *
     */
    public function getMailingLists(): array
    {
        return $this->sendRequest(self::BASE_ROUTE_PATH . '/mailing-lists');
    }

    /**
     * @throws ComelusException
     * @return array<mixed>
     *
     */
    public function getDossiers(): array
    {
        return $this->sendRequest(self::BASE_ROUTE_PATH . '/dossiers');
    }

    /**
     * @return array<mixed>
     *
     * @throws ComelusException
     */
    public function getDossierStatus(string $dossierId): array
    {
        return $this->sendRequest(self::BASE_ROUTE_PATH . "/dossier/$dossierId/status");
    }

    /**
     * @throws ComelusException
     * @return array<mixed>
     *
     */
    public function createDossier(Dossier $dossier): array
    {
        [$url, $apiKey] = $this->getCredentials();

        $builder = $this->prepareMultipart($dossier);

        $request = $this->requestFactory->createRequest('POST', $url . self::BASE_ROUTE_PATH . '/dossier')
            ->withHeader('Content-Type', 'multipart/form-data; boundary="' . $builder->getBoundary() . '"')
            ->withAddedHeader('Authorization', 'Bearer ' . $apiKey)
            ->withBody($builder->build())
        ;

        return $this->send($request, Response::HTTP_CREATED);
    }

    /**
     * @throws ComelusException
     */
    private function prepareMultipart(Dossier $dossier): MultipartStreamBuilder
    {
        $builder = new MultipartStreamBuilder($this->streamFactory);

        $builder->addResource('name', $dossier->getName());
        $builder->addResource('description', $dossier->getDescription());
        $builder->addResource('mailingListId', $dossier->getMailingListId());

        foreach ($dossier->getAttachedFiles() as $key => $file) {
            $builder->addResource("attachedFiles[{$key}][category]", $file->getCategory());
            $builder->addResource("attachedFiles[{$key}][name]", $file->getName());
            $builder->addResource("attachedFiles[{$key}][rank]", strval($file->getRank()));
            $this->addFile($builder, "attachedFiles[{$key}][uploadedfile]", $file->getUploadedFile());

            foreach ($file->getAnnexes() as $keyAnnexe => $annexe) {
                $builder->addResource("attachedFiles[{$key}][annexes][{$keyAnnexe}][name]", $annexe->getName());
                $builder->addResource("attachedFiles[{$key}][annexes][{$keyAnnexe}][rank]", strval($annexe->getRank()));
                $this->addFile(
                    $builder,
                    "attachedFiles[{$key}][annexes][{$keyAnnexe}][uploadedfile]",
                    $annexe->getUploadedFile()
                );
            }
        }

        return $builder;
    }

    /**
     * The file is checked when the File/Annexe is built, but it may have been deleted since.
     *
     * @throws ComelusException
     */
    private function addFile(MultipartStreamBuilder $builder, string $fieldName, UploadedFile $uploadedFile): void
    {
        $resource = is_readable($uploadedFile->getPathname()) ? fopen($uploadedFile->getPathname(), 'r') : false;
        if ($resource === false) {
            throw new ComelusException("$fieldName file not readable", Response::HTTP_BAD_REQUEST);
        }

        // Unknown MIME type: no header, MultipartStreamBuilder then guesses it from the file name
        $mimeType = $uploadedFile->getMimeType();

        $builder->addResource($fieldName, $resource, [
            'filename' => $uploadedFile->getClientOriginalName(),
            'headers' => $mimeType !== null ? ['Content-Type' => $mimeType] : [],
        ]);
    }

    /**
     * @throws ComelusException
     * @return array<mixed>
     *
     */
    public function sendDocument(string $documentId): array
    {
        return $this->sendRequest(self::BASE_ROUTE_PATH . "/dossier/$documentId/send", 'POST');
    }

    /**
     * @throws ComelusException
     * @return array<mixed>
     *
     */
    private function sendRequest(string $path, string $method = 'GET'): array
    {
        [$url, $apiKey] = $this->getCredentials();
        $request = $this->requestFactory->createRequest($method, $url . $path)
            ->withHeader('Authorization', 'Bearer ' . $apiKey);

        return $this->send($request, Response::HTTP_OK);
    }

    /**
     * @throws ComelusException
     * @return array<mixed>
     *
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
