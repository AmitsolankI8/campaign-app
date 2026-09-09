<?php

namespace App\Http\Requests\UserManagement;

use App\Concerns\PreferenceValidationRules;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    use PreferenceValidationRules;

    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->route('user');

        return $this->user()?->can('users.edit') === true
            && (! $user->hasRole('admin') || $user->is($this->user()));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique((new User)->getTable(), 'email')->ignore($user),
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::when(! $user->hasRole('admin'), [Rule::notIn(['admin'])]), Rule::exists((new Role)->getTable(), 'name')],
            ...$this->preferenceRules(),
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var User $user */
            $user = $this->route('user');

            if ($user->hasRole('admin') && $this->exists('roles')
                && (! is_array($this->input('roles')) || collect($this->input('roles'))->sort()->values()->all() !== $user->roles->pluck('name')->sort()->values()->all())) {
                $validator->errors()->add('roles', __('The administrator user’s roles cannot be changed.'));
            }
        }];
    }
}
