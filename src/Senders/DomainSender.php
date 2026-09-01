<?php

namespace Akrez\HttpProxyRewriter\Senders;

use Akrez\HttpProxy\Sender;
use Akrez\HttpRunner\SapiEmitter;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use League\Uri\Uri as LeagueUri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class DomainSender extends Sender
{
    protected string $proxyHost;

    protected array $domainersClassName = [];

    protected array $mapToHost = [];

    public function setMapToHost(array $mapToHost)
    {
        $this->mapToHost = $mapToHost;

        return $this;
    }

    public function getMapToHost(): array
    {
        return $this->mapToHost;
    }

    public function setProxyHost(?string $proxyHost)
    {
        $this->proxyHost = $proxyHost;

        return $this;
    }

    public function getProxyHost(): ?string
    {
        return $this->proxyHost;
    }

    public function setDomainersClassName(array $domainersClassName)
    {
        $this->domainersClassName = $domainersClassName;

        return $this;
    }

    public function encryptUrl(string $urlString, ?string $mainUrlString = null)
    {
        try {
            $url = $mainUrlString ?
                LeagueUri::parse($urlString, $mainUrlString) :
                LeagueUri::new($urlString);

            $newUri = new Uri($url->toString());

            $mapToHost = $this->getMapToHost();
            $hostToMap = array_flip($mapToHost);
            $newHost = ($hostToMap[$newUri->getHost()] ?? $newUri->getHost());

            $newUri = $newUri->withHost($newHost.'.'.$this->proxyHost);

            return $newUri->__toString();

        } catch (\Throwable $th) {
            return $urlString;
        }
    }

    protected function emitRequest(RequestInterface $newRequest)
    {
        $clientConfig = [
            'verify' => false,
            'allow_redirects' => false,
            'referer' => false,
            'decode_content' => true,
            'http_errors' => false,
            'timeout' => $this->timeout,
            'connect_timeout' => $this->timeout,
            'read_timeout' => $this->timeout,
        ];
        $response = (new Client($clientConfig))->send($newRequest);

        $response = $this->locationAfterReceivedResponse($newRequest, $response);
        $response = $this->contentAfterReceivedResponse($newRequest, $response);
        $response = $response->withoutHeader('transfer-encoding');

        (new SapiEmitter($this->bufferSize))->emit($response);
    }

    protected function locationAfterReceivedResponse(RequestInterface $newRequest, ResponseInterface $response)
    {
        $locationHeaders = $response->getHeader('location');
        if ($locationHeaders) {
            $response = $response->withoutHeader('location');
            foreach ($locationHeaders as $locationHeader) {
                $newLocation = $this->encryptUrl($locationHeader, $newRequest->getUri()->__toString());
                $response = $response->withAddedHeader('location', $newLocation);
            }
        }

        return $response;
    }

    protected function contentAfterReceivedResponse(RequestInterface $newRequest, ResponseInterface $response)
    {
        $newRequestUrl = $newRequest->getUri()->__toString();

        $newContent = null;
        foreach ($this->domainersClassName as $domainerClassName) {
            $domainer = new $domainerClassName($this);
            if ($domainer->isMine($newRequest, $response)) {
                $newContent = ($newContent === null ? $response->getBody()->getContents() : $newContent);
                $newContent = $domainer->convert($newContent, $newRequestUrl);
            }
        }
        if ($newContent) {
            $response = new Response(
                $response->getStatusCode(),
                $response->getHeaders(),
                $newContent,
                $response->getProtocolVersion(),
                $response->getReasonPhrase()
            );
            if ($response->hasHeader('Content-Length')) {
                $response = $response
                    ->withoutHeader('Content-Length')
                    ->withHeader('Content-Length', $response->getBody()->getSize());
            }
        }

        return $response;
    }
}
