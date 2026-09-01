<?php

use Akrez\HttpProxy\Factories\InlineFactory;
use Akrez\HttpProxyRewriter\Factories\DomainFactory;
use Akrez\HttpProxyRewriter\Rewriters\DomainRewriter;
use Akrez\HttpProxyRewriter\Rewriters\TextCssRewriter;
use Akrez\HttpProxyRewriter\Rewriters\TextHtmlRewriter;
use Akrez\HttpProxyRewriter\Senders\DomainSender;
use Akrez\HttpProxyRewriter\Senders\RewriteSender;
use GuzzleHttp\Psr7\ServerRequest;

require_once '../vendor/autoload.php';

function rewriter()
{
    $serverRequest = ServerRequest::fromGlobals();
    $serverParams = $serverRequest->getServerParams() + [
        'REQUEST_SCHEME' => null,
        'HTTP_HOST' => null,
        'SCRIPT_NAME' => null,
    ];
    $scriptUrl = $serverParams['REQUEST_SCHEME'].'://'.$serverParams['HTTP_HOST'].$serverParams['SCRIPT_NAME'];

    $sender = new RewriteSender;
    $sender->setScriptUrl($scriptUrl);
    $sender->setRewritersClassName([
        TextHtmlRewriter::class,
        TextCssRewriter::class,
    ]);

    return InlineFactory::emitSender($sender);
}

function domain(string $proxyHost, array $mapToHost = [])
{
    $sender = new DomainSender;
    $sender->setProxyHost($proxyHost);
    $sender->setDomainersClassName([
        DomainRewriter::class,
    ]);
    $sender->setMapToHost($mapToHost);

    $serverRequest = ServerRequest::fromGlobals();
    $factory = new DomainFactory($serverRequest);
    $factory->setProxyHost($proxyHost);
    $factory->setMapToHost($mapToHost);

    return DomainFactory::emitSender($sender, $factory);
}

domain('akrezofski.ir');
