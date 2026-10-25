<?php

declare(strict_types=1);

namespace SenLtd\ShortUrl\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * One JSON log line per request. Stream is injectable so tests can
 * capture without touching STDOUT.
 */
final class JsonRequestLogger implements MiddlewareInterface
{
    /** @var resource */
    private $stream;

    /**
     * @param resource|null $stream
     */
    public function __construct($stream = null)
    {
        if ($stream === null) {
            $stream = fopen('php://stdout', 'w') ?: fopen('php://stderr', 'w');
        }
        $this->stream = $stream;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start    = microtime(true);
        $response = $handler->handle($request);
        $elapsed  = (microtime(true) - $start) * 1000.0;

        $line = [
            'ts'        => gmdate('c'),
            'method'    => $request->getMethod(),
            'path'      => $request->getUri()->getPath(),
            'query'     => $request->getUri()->getQuery(),
            'status'    => $response->getStatusCode(),
            'elapsedMs' => round($elapsed, 2),
            'bytes'     => $response->getBody()->getSize() ?? 0,
        ];

        fwrite($this->stream, json_encode($line, JSON_UNESCAPED_SLASHES) . "\n");
        return $response;
    }
}
