<?php

namespace Akrez\HttpProxyRewriter\Rewriters;

use Akrez\HttpProxyRewriter\Domainer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class DomainRewriter extends Domainer
{
    public function isMine(RequestInterface $newRequest, ResponseInterface $response)
    {
        return $this->isContentTypes([
            'text/html',
            'text/css',
            'text/javascript',
            'application/json',
            'application/ld+json',
            'application/javascript',
        ], $response);
    }

    public function convert($content, $mainPageUrl)
    {
        $proxyHost = $this->domainSender->getProxyHost();

        return preg_replace_callback(
            '~(\\\\/\\\\/|//)([A-Za-z0-9][A-Za-z0-9.-]*)~',
            fn ($m) => $m[1] . $m[2] . '.' . $proxyHost,
            $content
        );
    }
}
