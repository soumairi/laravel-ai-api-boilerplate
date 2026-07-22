<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Throwable;

final class ProblemDetailsResponseExtension extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        foreach ($operation->responses ?? [] as $response) {
            $status = $this->status($response);
            if ($status === null || $status < 400) {
                continue;
            }

            $problem = (new ObjectType)
                ->addProperty('type', new StringType)
                ->addProperty('title', new StringType)
                ->addProperty('status', new IntegerType)
                ->addProperty('detail', new StringType)
                ->addProperty('instance', new StringType)
                ->addProperty('request_id', new StringType);
            $required = ['type', 'title', 'status', 'detail', 'instance', 'request_id'];

            if ($status === 422) {
                $problem->addProperty(
                    'errors',
                    (new ObjectType)->additionalProperties(
                        (new ArrayType)->setItems(new StringType),
                    ),
                );
                $required[] = 'errors';
            }

            $operation->addResponse(
                Response::make($status)
                    ->setDescription($this->description($status))
                    ->setContent(
                        'application/problem+json',
                        Schema::fromType($problem->setRequired($required)),
                    ),
            );
        }
    }

    private function status(Response|Reference $response): ?int
    {
        try {
            $resolved = $response instanceof Reference ? $response->resolve() : $response;

            return is_numeric($resolved->code) ? (int) $resolved->code : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function description(int $status): string
    {
        return match ($status) {
            400 => 'Bad request',
            401 => 'Unauthenticated',
            403 => 'Forbidden',
            404 => 'Not found',
            409 => 'Conflict',
            413 => 'Payload too large',
            415 => 'Unsupported media type',
            422 => 'Validation error',
            429 => 'Too many requests',
            default => 'Request failed',
        };
    }
}
