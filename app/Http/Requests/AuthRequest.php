<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class AuthRequest extends FormRequest
{
    /** @return array<string, mixed> */
    abstract public function rules(): array;

    public function authorize(): bool
    {
        return true;
    }

    public function withValidator(Validator $validator): void
    {
        $allowed = collect(array_keys($this->rules()))
            ->map(static fn (string $key): string => str($key)->before('.')->toString())
            ->push(...$this->additionalAllowedKeys())
            ->unique();

        $validator->after(function (Validator $validator) use ($allowed): void {
            foreach (array_keys($this->all()) as $key) {
                if (! $allowed->contains($key)) {
                    $validator->errors()->add($key, 'This field is not allowed.');
                }
            }
        });
    }

    /** @return list<string> */
    protected function additionalAllowedKeys(): array
    {
        return [];
    }
}
