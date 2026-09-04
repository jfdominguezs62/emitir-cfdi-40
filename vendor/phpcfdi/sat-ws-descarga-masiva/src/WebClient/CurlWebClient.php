<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\WebClient;

use PhpCfdi\SatWsDescargaMasiva\WebClient\Exceptions\WebClientException;

class CurlWebClient implements WebClientInterface
{
    /** @var \Closure|null */
    private $onFireRequest = null;

    /** @var \Closure|null */
    private $onFireResponse = null;

    public function __construct(
        ?\Closure $onFireRequest = null,
        ?\Closure $onFireResponse = null,
    ) {
        $this->onFireRequest = $onFireRequest;
        $this->onFireResponse = $onFireResponse;
    }

    public function fireRequest(Request $request): void
    {
        if ($this->onFireRequest !== null) {
            ($this->onFireRequest)($request);
        }
    }

    public function fireResponse(Response $response): void
    {
        if ($this->onFireResponse !== null) {
            ($this->onFireResponse)($response);
        }
    }

    public function call(Request $request): Response
    {
        $ch = curl_init($request->getUri());

        $headers = [];
        foreach ($request->getHeaders() as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $request->getBody(),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            $response = new Response(500, '', []);
            throw new WebClientException(
                'Curl error: ' . ($error ?: 'Unknown error'),
                $request,
                $response,
                new \RuntimeException($error)
            );
        }

        return new Response($statusCode, $body);
    }
}
