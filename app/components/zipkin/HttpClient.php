<?php

namespace App\components\zipkin;

use App\components\http\Request as SwfRequest;
use App\components\http\Request;
use App\facades\Log;
use Swlib\Saber\Request as SaberRequest;
use Zipkin\Span;
use const Zipkin\Tags\HTTP_HOST;
use const Zipkin\Tags\HTTP_METHOD;
use const Zipkin\Tags\HTTP_PATH;
use const Zipkin\Tags\HTTP_STATUS_CODE;

/**
 * Class HttpClient
 * @package App\components\zipkin
 */
class HttpClient
{
    /**
     * Send http request with zipkin trace
     *
     * @param SaberRequest $saberRequest
     * @param SwfRequest $swfRequest
     * @param string $spanName
     * @return mixed|\Psr\Http\Message\ResponseInterface|null
     * @throws \Exception
     */
    public function send(SaberRequest $saberRequest, $swfRequest = null, $spanName = null)
    {
        $swfRequest = $swfRequest ?? request();
        /** @var Tracer $swfTracer */
        $swfTracer = $swfRequest->getTracer();
        $request = $saberRequest;
        $path = $request->getUri()->getPath();

        return $swfTracer->span(
            isset($spanName) ? $spanName : $swfTracer->formatRoutePath($path),
            function (Span $span) use ($request, $swfTracer, $path) {
                //Inject trace context to api psr request
                $swfTracer->injectContextToRequest($span->getContext(), $request);

                if ($span->getContext()->isSampled()) {
                    $swfTracer->addTag($span, HTTP_HOST, $request->getUri()->getHost());
                    $swfTracer->addTag($span, HTTP_PATH, $path);
                    $swfTracer->addTag($span, Tracer::HTTP_QUERY_STRING, (string)$request->getUri()->getQuery());
                    $swfTracer->addTag($span, HTTP_METHOD, $request->getMethod());
                    $httpRequestBodyLen = $request->getBody()->getSize();
                    $swfTracer->addTag($span, Tracer::HTTP_REQUEST_BODY_SIZE, $httpRequestBodyLen);
                    $swfTracer->addTag($span, Tracer::HTTP_REQUEST_BODY, $swfTracer->formatHttpBody((string)$request->getBody(), $httpRequestBodyLen));
                    if ($httpRequestBodyLen > 0) {
                        $request->getBody()->seek(0);
                    }
                    $swfTracer->addTag($span, Tracer::HTTP_REQUEST_HEADERS, json_encode($request->getHeaders(), JSON_UNESCAPED_UNICODE));
                    $swfTracer->addTag(
                        $span,
                        Tracer::HTTP_REQUEST_PROTOCOL_VERSION,
                        $swfTracer->formatHttpProtocolVersion($request->getProtocolVersion())
                    );
                    $swfTracer->addTag($span, Tracer::HTTP_REQUEST_SCHEME, $request->getUri()->getScheme());
                }

                $response = null;
                try {
                    $response = $request->exec()->recv();
                    return $response;
                } catch (\Exception $e) {
                    Log::error('CURL ERROR ' . $e->getMessage());
                    throw new \Exception('CURL ERROR ' . $e->getMessage());
                } finally {
                    if ($response) {
                        if ($span->getContext()->isSampled()) {
                            $swfTracer->addTag($span, HTTP_STATUS_CODE, $response->getStatusCode());
                            $httpResponseBodyLen = $response->getBody()->getSize();
                            $swfTracer->addTag($span, Tracer::HTTP_RESPONSE_BODY_SIZE, $httpResponseBodyLen);
                            $swfTracer->addTag($span, Tracer::HTTP_RESPONSE_BODY, $swfTracer->formatHttpBody((string)$response->getBody(), $httpResponseBodyLen));
                            if ($httpResponseBodyLen > 0) {
                                $response->getBody()->seek(0);
                            }
                            $swfTracer->addTag($span, Tracer::HTTP_RESPONSE_HEADERS, json_encode($response->getHeaders(), JSON_UNESCAPED_UNICODE));
                            $swfTracer->addTag(
                                $span,
                                Tracer::HTTP_RESPONSE_PROTOCOL_VERSION,
                                $swfTracer->formatHttpProtocolVersion($response->getProtocolVersion())
                            );
                        }
                    }
                }
            }, null, \Zipkin\Kind\CLIENT);
    }
}
