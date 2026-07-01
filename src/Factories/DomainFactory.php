<?php

namespace Akrez\HttpProxyRewriter\Factories;

use Akrez\HttpProxy\Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class DomainFactory extends Factory
{
    protected ?string $proxyHost;

    public function __construct(protected ServerRequestInterface $globalServerRequest) {}

    public function setProxyHost(?string $proxyHost)
    {
        $this->proxyHost = $proxyHost;
    }

    public function getProxyHost(): ?string
    {
        return $this->proxyHost;
    }

    public function make(): ?RequestInterface
    {
        $newServerRequest = clone $this->globalServerRequest;
        $uri = $newServerRequest->getUri();

        $newHost = $this->removeSuffixDomain($uri->getHost());
        if (! $newHost) {
            return null;
        }
        $uri = $uri->withHost($newHost);

        return $newServerRequest->withUri($uri);
    }

    protected function removeSuffixDomain(string $host): ?string
    {
        $suffix = '.'.$this->proxyHost;
        if (substr($host, -strlen($suffix)) !== $suffix) {
            return null;
        }

        return substr($host, 0, -strlen($suffix));
    }
}
