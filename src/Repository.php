<?php

namespace Skore\LaravelRepositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Response;
use Skore\LaravelRepositories\Contracts\Repository as Contract;
use Skore\LaravelRepositories\Traits\InteractsWithController;
use Skore\LaravelRepositories\Traits\Makeable;

class Repository implements Contract
{
    use Makeable,
        InteractsWithController;

    /**
     * @var string
     */
    protected $model;

    /**
     * @var \SkoreLabs\JsonApi\Http\Resources\JsonApiResource
     */
    protected $resource;

    /**
     * Instantiate this class.
     *
     * @param string $model
     * @return void
     */
    public function __construct(string $model, string $context = null, $resource = null)
    {
        $this->model = $model;
        $this->context = $context ?: $this->resolveContext();
        $this->resource = $context === self::WEB_CONTEXT
            ? Response::class
            : ($resource ?: JsonApiResource::class);
    }

    /**
     * List resources from a query.
     *
     * @param \Illuminate\Database\Query\Builder|null $query
     * @param bool|null $authorise
     * @return \SkoreLabs\JsonApi\Http\Resources\JsonApiCollection
     */
    public function list($query = null, $authorise = null)
    {
        return $this->paginateList($this->getQueryBuilder($query), $authorise);
    }

    /**
     * Get one resource.
     *
     * @param int|\Illuminate\Database\Eloquent\Model $id
     * @param bool|null $authorise
     * @return \SkoreLabs\JsonApi\Http\Resources\JsonApiResource
     */
    public function get($id, $authorise = null)
    {
        return $this->resource::make(
            $this->setModelIncludes(
                $id instanceof Model
                    ? $id
                    : $this->getQueryBuilder()->find($id)
            ),
            $authorise
        );
    }

    /**
     * Get all resources by column/value condition.
     *
     * @param mixed $column
     * @param mixed $value
     * @param bool|null $authorise
     * @return \SkoreLabs\JsonApi\Http\Resources\JsonApiCollection
     */
    public function getBy($column, $value, $authorise = null)
    {
        return $this->paginateList(
            $this->getQueryBuilder()->where($column, $value),
            $authorise
        );
    }

    /**
     * Get first resource by column/value condition.
     *
     * @param mixed $column
     * @param mixed $value
     * @param bool|null $authorise
     * @return \SkoreLabs\JsonApi\Http\Resources\Json\ResourceCollection
     */
    public function getFirstBy($column, $value, $authorise = null)
    {
        return $this->resource::make(
            $this->getQueryBuilder()
                ->where($column, $value)
                ->firstOrFail(),
            $authorise
        );
    }

    /**
     * Set includes to model, also prevents double queries.
     *
     * @param mixed $model
     * @return \Illuminate\Database\Eloquent\Model|false
     */
    protected function setModelIncludes($model)
    {
        $includesArr = array_intersect($this->includes, static::getRequestIncludes());
        $appendsArr = array_intersect($this->appends, static::getRequestAppends());

        if ($includesArr) {
            $model->load($includesArr);
        }

        if ($appendsArr) {
            $model->append($appendsArr);
        }

        return $model;
    }

    /**
     * Add pagination to the QueryBuilder as ResourceCollection.
     *
     * @param \Spatie\QueryBuilder\QueryBuilder $queryBuilder
     * @param bool|null $authorise
     * @return \SkoreLabs\JsonApi\Http\Resources\JsonApiCollection
     */
    protected function paginateList($queryBuilder, $authorise)
    {
        return call_measure('JSON:API', fn () => JsonApiCollection::make(
            $queryBuilder->jsonPaginate(),
            $authorise,
            $this->resource
        )->withQuery(request()->except('page.number')));
    }

    /**
     * Set the resource wrapper for the responses.
     *
     * @param mixed $resource
     * @return $this
     */
    public function setResourceWrapper($resource)
    {
        $this->resource = $resource;
        return $this;
    }
}
