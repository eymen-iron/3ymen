<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;
use Eymen\Database\QueryBuilder;
use Eymen\Database\Connection;

final class Unique implements RuleInterface
{
    private static ?Connection $connection = null;

    public static function setConnection(Connection $connection): void
    {
        self::$connection = $connection;
    }

    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (self::$connection === null) {
            throw new \RuntimeException('Database connection not set for Unique rule');
        }

        $table = $parameters[0] ?? '';
        $column = $parameters[1] ?? $attribute;
        $exceptId = $parameters[2] ?? null;
        $idColumn = $parameters[3] ?? 'id';

        if ($table === '') {
            return false;
        }

        $query = (new QueryBuilder(self::$connection, $table))
            ->where($column, '=', $value);

        if ($exceptId !== null) {
            $query->where($idColumn, '!=', $exceptId);
        }

        return !$query->exists();
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return "The {$attribute} has already been taken.";
    }
}
