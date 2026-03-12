<?php

declare(strict_types=1);

namespace App\Validators;

use Eymen\Validation\Validator;

final class UserValidator
{
    public static function registration(array $data): Validator
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);
    }

    public static function login(array $data): Validator
    {
        return Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
    }

    public static function update(array $data, int $userId): Validator
    {
        return Validator::make($data, [
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|max:255|unique:users,email,{$userId}",
        ]);
    }
}
