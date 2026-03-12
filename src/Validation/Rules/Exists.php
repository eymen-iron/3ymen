<?php

declare(strict_types=1);

namespace Eymen\Validation\Rules;

use Eymen\Validation\RuleInterface;
use Eymen\Database\QueryBuilder;
use Eymen\Database\Connection;

final class Exists implements RuleInterface
{
    private static ?Connection $connection = null;

    public static function setConnection(Connection $connection): void
    {
        self::$connection = $connection;
    }

    public function passes(string $attribute, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (self::$connection === null) {
            throw new \RuntimeException('Database connection not set for Exists rule');
        }

        $table = $parameters[0] ?? '';
        $column = $parameters[1] ?? $attribute;

        if ($table === '') {
            return false;
        }

        return (new QueryBuilder(self::$connection, $table))
            ->where($column, '=', $value)
            ->exists();
    }

    public function message(string $attribute, array $parameters = []): string
    {
        return "The selected {$attribute} is invalid.";
    }
}
