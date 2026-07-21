<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi;

use App\Http\Controllers\Api\V1\Admin\PermissionController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;

final class QueryBuilderParameterExtension extends OperationExtension
{
    /** @var array<class-string, array{filters: list<string>, sorts: list<string>, includes: list<string>, fields: list<string>}> */
    private const ALLOWLISTS = [
        UserController::class => [
            'filters' => ['name', 'email', 'status'],
            'sorts' => ['name', 'email', 'status', 'created_at'],
            'includes' => ['roles'],
            'fields' => ['id', 'name', 'email', 'status', 'email_verified_at', 'created_at', 'updated_at'],
        ],
        RoleController::class => [
            'filters' => ['name'],
            'sorts' => ['name', 'created_at'],
            'includes' => ['permissions'],
            'fields' => ['id', 'name', 'guard_name', 'created_at', 'updated_at'],
        ],
        PermissionController::class => [
            'filters' => ['name'],
            'sorts' => ['name', 'created_at'],
            'includes' => [],
            'fields' => ['id', 'name', 'guard_name', 'created_at', 'updated_at'],
        ],
    ];

    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $controller = $routeInfo->className();
        if ($routeInfo->methodName() !== 'index' || ! $controller || ! isset(self::ALLOWLISTS[$controller])) {
            return;
        }

        $allowlist = self::ALLOWLISTS[$controller];
        foreach ($allowlist['filters'] as $filter) {
            $this->add($operation, "filter[$filter]", (new StringType)->setDescription("Allowed filter: $filter."));
        }

        $this->addEnum($operation, 'sort', $this->withDescending($allowlist['sorts']), 'Comma-separated allowed sorts; prefix with - for descending.');
        $this->addEnum($operation, 'include', $allowlist['includes'], 'Comma-separated allowed relationships.');
        $this->addEnum($operation, 'fields', $allowlist['fields'], 'Comma-separated sparse fieldset.');
        $this->add($operation, 'page', (new IntegerType)->setMin(1)->setDescription('Page number.'));
        $this->add($operation, 'per_page', (new IntegerType)->setMin(1)->setMax(100)->setDescription('Items per page (maximum 100).'));
    }

    /** @param list<string> $values */
    private function addEnum(Operation $operation, string $name, array $values, string $description): void
    {
        if ($values === []) {
            return;
        }

        $this->add(
            $operation,
            $name,
            (new StringType)->enum($values)->setDescription($description),
        );
    }

    private function add(Operation $operation, string $name, StringType|IntegerType $type): void
    {
        $alreadyDocumented = collect($operation->parameters)->contains(
            fn ($parameter): bool => $parameter instanceof Parameter && $parameter->name === $name && $parameter->in === 'query',
        );
        if (! $alreadyDocumented) {
            $operation->addParameters([
                Parameter::make($name, 'query')->setSchema(Schema::fromType($type)),
            ]);
        }
    }

    /** @param list<string> $sorts
     * @return list<string>
     */
    private function withDescending(array $sorts): array
    {
        return array_values(array_merge($sorts, array_map(static fn (string $sort): string => "-$sort", $sorts)));
    }
}
