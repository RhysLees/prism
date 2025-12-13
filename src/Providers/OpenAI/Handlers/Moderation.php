<?php

declare(strict_types=1);

namespace Prism\Prism\Providers\OpenAI\Handlers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Prism\Prism\Moderation\Request;
use Prism\Prism\Moderation\Response as ModerationResponse;
use Prism\Prism\Providers\OpenAI\Concerns\ProcessRateLimits;
use Prism\Prism\Providers\OpenAI\Concerns\ValidatesResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\ModerationResult;

class Moderation
{
    use ProcessRateLimits;
    use ValidatesResponse;

    public function __construct(protected PendingRequest $client) {}

    public function handle(Request $request): ModerationResponse
    {
        $response = $this->sendRequest($request);

        $this->validateResponse($response);

        $data = $response->json();

        return new ModerationResponse(
            results: array_map(
                ModerationResult::fromArray(...),
                data_get($data, 'results', [])
            ),
            meta: new Meta(
                id: data_get($data, 'id', ''),
                model: data_get($data, 'model', $request->model()),
                rateLimits: $this->processRateLimits($response),
            ),
        );
    }

    protected function sendRequest(Request $request): Response
    {
        /** @var Response $response */
        $response = $this->client->post(
            'moderations',
            array_merge([
                'input' => count($request->inputs()) === 1 ? $request->inputs()[0] : $request->inputs(),
            ], array_filter([
                'model' => $request->model() ?: null,
                ...($request->providerOptions() ?? []),
            ]))
        );

        return $response;
    }
}
