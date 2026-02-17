<?php

use Akrez\HttpProxy\Factories\InlineFactory;
use Akrez\HttpProxyRewriter\Rewriters\TextCssRewriter;
use Akrez\HttpProxyRewriter\Rewriters\TextHtmlRewriter;
use Akrez\HttpProxyRewriter\Senders\RewriteSender;
use GuzzleHttp\Psr7\ServerRequest;

require_once '../vendor/autoload.php';

function rewriter()
{
    $serverRequest = ServerRequest::fromGlobals();

    $sender = new RewriteSender;

    $serverParams = $serverRequest->getServerParams() + [
        'REQUEST_SCHEME' => null,
        'HTTP_HOST' => null,
        'SCRIPT_NAME' => null,
    ];
    $scriptUrl = $serverParams['REQUEST_SCHEME'].'://'.$serverParams['HTTP_HOST'].$serverParams['SCRIPT_NAME'];
    $sender->setScriptUrl($scriptUrl);

    $sender->setRewritersClassName([
        TextHtmlRewriter::class,
        TextCssRewriter::class,
    ]);

    return InlineFactory::emitSender($sender);
}

rewriter();
