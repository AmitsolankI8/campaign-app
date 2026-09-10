<?php

namespace App\Support;

use App\Http\Requests\DataTableRequest;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

class DataTable
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  class-string<JsonResource>  $resource
     * @return array<string, mixed>
     */
    public static function make(Builder $query, DataTableRequest $request, string $resource): array
    {
        $state = $request->tableState();
        $searchable = $request->searchableColumns();
        $request->applyFilters($query, $state['filters']);

        if ($state['search'] !== '' && $searchable !== []) {
            $columns = $state['search_column'] !== ''
                ? [$searchable[$state['search_column']]]
                : array_values($searchable);

            $query->where(function (Builder $searchQuery) use ($columns, $state): void {
                foreach ($columns as $column) {
                    $searchQuery->orWhere(function (Builder $columnQuery) use ($column, $state): void {
                        if ($column instanceof Closure) {
                            $column($columnQuery, $state['search']);
                        } else {
                            $columnQuery->whereLike($column, '%'.$state['search'].'%');
                        }
                    });
                }
            });
        }

        $query->reorder();

        foreach ((array) $request->sortableColumns()[$state['sort']] as $column) {
            $query->orderBy($column, $state['direction']);
        }

        // A unique tie-breaker keeps equal values stable across pages.
        $query->orderBy($query->getModel()->getQualifiedKeyName(), $state['direction']);

        $page = (int) $request->validated($request->parameter('page'), 1);
        $paginator = $query->paginate($state['per_page'], page: $page);

        // Recover after deletions or a bookmarked page beyond the filtered result set.
        if ($page > $paginator->lastPage()) {
            $paginator = $query->paginate($state['per_page'], page: $paginator->lastPage(), total: $paginator->total());
        }

        return [
            'data' => $resource::collection($paginator->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'state' => [...$state, 'filters' => (object) $state['filters']],
            'options' => [
                'defaults' => [
                    'page' => 1,
                    'per_page' => $request->defaultPerPage(),
                    'search' => '',
                    'search_column' => '',
                    'sort' => $request->defaultSort(),
                    'direction' => $request->defaultDirection(),
                ],
                'query_namespace' => $request->queryNamespace(),
                'filter_defaults' => (object) $request->filterDefaults(),
                'per_page_options' => $request->perPageOptions(),
                'searchable_columns' => array_keys($searchable),
                'sortable_columns' => array_keys($request->sortableColumns()),
            ],
        ];
    }
}
