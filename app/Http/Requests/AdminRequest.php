<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Identity\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class AdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $routeName = (string) $this->route()->getName();
        $entity = $this->route('user') ?? $this->route('role') ?? $this->route('permission');
        $table = str_starts_with($routeName, 'users.') ? 'users'
            : (str_starts_with($routeName, 'roles.') ? 'roles' : 'permissions');

        if ($table === 'users') {
            if ($routeName === 'users.status') {
                return ['status' => ['required', Rule::enum(UserStatus::class)]];
            }
            if ($routeName === 'users.roles') {
                return [
                    'roles' => ['required', 'array', 'max:20'],
                    'roles.*' => ['string', 'exists:roles,name'],
                ];
            }

            return [
                'name' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'max:120'],
                'email' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'email:rfc', 'max:254', Rule::unique('users')->ignore($entity)],
                'password' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
                'status' => [$this->isMethod('POST') ? 'sometimes' : 'prohibited', Rule::enum(UserStatus::class)],
                'roles' => [$this->isMethod('POST') ? 'sometimes' : 'prohibited', 'array', 'max:20'],
                'roles.*' => ['string', 'exists:roles,name'],
            ];
        }

        return [
            'name' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'max:100', Rule::unique($table)->ignore($entity)],
            'permissions' => [$routeName === 'roles.permissions' ? 'required' : 'sometimes', 'array', 'max:100'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
