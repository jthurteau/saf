<?php

declare(strict_types=1);

namespace Saf\Psr\Request;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Plates\PlatesRenderer;

use Saf\Debug;
use Saf\Util\Errors;
use Saf\Util\Http\Status;
use Saf\Util\Http\Mime;

class ExceptionHandler implements RequestHandlerInterface
{
    protected static array $mimeMap = [
        HtmlResponse::class => Mime::HTML_TYPES,
        JsonResponse::class => Mime::AJAX_TYPES
    ];

    protected ?TemplateRendererInterface $template = null;

    public function __construct($renderer)
    {
        $this->template = $renderer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $e = $request->getAttribute('throwable');
        $status = Status::STATUS_500_ERROR;
        $htmlTemplate = 'error::error'; //$request->getAttribute('template', 'error::error');

        $format = Mime::detectClientFormat($request, self::$mimeMap);
        $useHtml = in_array($format, self::$mimeMap[HtmlResponse::class]) 
            || !in_array($format, self::$mimeMap[JsonResponse::class]);
        $response = 
            $useHtml
            ? new HtmlResponse($this->template->render($htmlTemplate, [
                'request' => $request,
                'error' => $e,
            ]))
            : new JsonResponse(
                [
                    'success' => false,
                ] + (Errors::isDebugging() ? Errors::chainErrorResponse($e) : [])
            );
        return $response->withStatus($status);
    }

    protected static function autoHost(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $requestPort = $uri->getPort();
        $port =
            $requestPort
            ? (
                $scheme == 'https'
                ? ($requestPort == '443' ? '' : ":{$requestPort}")
                : ($requestPort == '80' ? '' : ":{$requestPort}")
            ) : '';
        return "{$scheme}://{$host}{$port}";
    }
}
