<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

abstract class DataTableRequest extends FormRequest
{
    protected string $tableNamespace = '';

    public static function forTable(Request $request, string $namespace): static
    {
        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $namespace)) {
            throw new InvalidArgumentException('Use a simple, unique table namespace.');
        }

        $table = static::createFrom($request);
        $table->tableNamespace = $namespace;
        $container = Container::getInstance();
        $table->setContainer($container);
        $table->setRedirector($container->make(Redirector::class));
        $table->validateResolved();

        return $table;
    }

    public function queryNamespace(): string
    {
        return $this->tableNamespace;
    }

    public function parameter(string $key): string
    {
        return $this->tableNamespace === '' ? $key : $this->tableNamespace.'.'.$key;
    }

    /** @return array<string, array<int, mixed>> */
    public function filterRules(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function filterDefaults(): array
    {
        return [];
    }

    /**
     * @param Builder<*> $query
     * @param  array<string, mixed>  $filters
     */
    public function applyFilters(Builder $query, array $filters): void {}

    /** @return array<string, string|Closure(Builder<*>, string): void> */
    abstract public function searchableColumns(): array;

    /** @return array<string, string|list<string>> */
    abstract public function sortableColumns(): array;

    /** @return list<int> */
    public function perPageOptions(): array
    {
        return [10, 25, 50, 100];
    }

    public function defaultPerPage(): int
    {
        return 10;
    }

    public function defaultSort(): string
    {
        return 'created_at';
    }

    public function defaultDirection(): string
    {
        return 'desc';
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $rules = [
            'page' => ['sometimes', 'integer', 'min:1', 'max:2147483647'],
            'per_page' => ['sometimes', 'integer', Rule::in($this->perPageOptions())],
            'search' => ['nullable', 'string', 'max:255'],
            'search_column' => ['nullable', 'string', Rule::in(array_keys($this->searchableColumns()))],
            'sort' => ['sometimes', 'string', Rule::in(array_keys($this->sortableColumns()))],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];

        $filterKeys = array_unique(array_map(fn (string $key) => explode('.', $key)[0], array_keys($this->filterRules())));
        $rules['filters'] = ['sometimes', $filterKeys === [] ? 'array' : 'array:'.implode(',', $filterKeys)];

        foreach ($this->filterRules() as $key => $filterRules) {
            $rules['filters.'.$key] = $filterRules;
        }

        $rules = collect($rules)->mapWithKeys(fn (array $rules, string $key) => [$this->parameter($key) => $rules])->all();

        if ($this->tableNamespace !== '') {
            $rules[$this->tableNamespace] = ['sometimes', 'array'];
        }

        return $rules;
    }

    /** @return array{search: string, search_column: string, sort: string, direction: 'asc'|'desc', per_page: int, filters: array<string, mixed>} */
    public function tableState(): array
    {
        return [
            'search' => trim($this->validated($this->parameter('search')) ?? ''),
            'search_column' => $this->validated($this->parameter('search_column')) ?? '',
            'sort' => $this->validated($this->parameter('sort'), $this->defaultSort()),
            'direction' => $this->validated($this->parameter('direction'), $this->defaultDirection()) === 'asc' ? 'asc' : 'desc',
            'per_page' => (int) $this->validated($this->parameter('per_page'), $this->defaultPerPage()),
            'filters' => array_replace($this->filterDefaults(), $this->filterRules() === [] ? [] : $this->validated($this->parameter('filters'), [])),
        ];
    }
}
