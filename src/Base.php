<?php

namespace Akrez\HttpProxyRewriter;

use Psr\Http\Message\ResponseInterface;

abstract class Base
{
    protected static function isContentTypes(array $contentTypes, ResponseInterface $response)
    {
        $responseContentTypes = (array) $response->getHeader('Content-Type');
        foreach ($responseContentTypes as $responseContentType) {
            if (static::startsWith($responseContentType, $contentTypes)) {
                return true;
            }
        }

        return false;
    }

    protected static function isContentType(string $contentType, ResponseInterface $response)
    {
        $contentTypes = (array) $response->getHeader('Content-Type');

        return $contentType === trim(preg_replace('@;.*@', '', reset($contentTypes)));
    }

    protected static function startsWith(string $haystack, array $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && stripos($haystack, $needle) === 0) {
                return true;
            }
        }

        return false;
    }
}
