<?php

namespace Akrez\HttpProxyRewriter;

use Akrez\HttpProxyRewriter\Senders\DomainSender;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class Domainer extends Base
{
    public function __construct(protected DomainSender $domainSender) {}

    abstract public function convert($content, $mainPageUrl);

    abstract public function isMine(RequestInterface $newRequest, ResponseInterface $response);
}
