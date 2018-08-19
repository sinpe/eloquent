<?php

namespace Sinpe\Eloquent;

//use Illuminate\Foundation\Bus\DispatchesJobs;

use Sinpe\Support\Traits\MacroAware as MacroTrait;

/**
 * Class Builder.
 */
class Builder extends \Illuminate\Database\Eloquent\Builder
{
    use MacroTrait;
    //use DispatchesJobs;

    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns 字段
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function get($columns = ['*'])
    {
        // TODO 临时注释 $this->orderByDefault();

        try {
            return $this->model->cache(
                $this->getCacheKey(),
                function () use ($columns) {
                    return parent::get($columns);
                }
            );
        } catch (\Exception $e) {
            return parent::get($columns);
        }
    }

    /**
     * Return if a table has been joined or not.
     *
     * @param $table
     *
     * @return bool
     */
    public function hasJoin($table)
    {
        if (!$this->query->joins) {
            return false;
        }

        /* @var JoinClause $join */
        foreach ($this->query->joins as $join) {
            if ($join->table === $table) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the unique cache key for the query.
     *
     * @return string
     */
    public function getCacheKey()
    {
        $name = $this->model->getConnection()->getName();

        return md5($name.$this->toSql().serialize($this->getBindings()));
    }

    /**
     * Get fresh cache.
     *
     * @return object
     */
    public function fresh()
    {
        $cacheManager = $this->model->getCacheManager();

        if ($cacheManager) {
            $cacheManager->delete($this->getCacheKey());
        }

        return $this;
    }

    /**
     * Update a record in the database.
     *
     * @param array $values 数据集
     *
     * @return int
     */
    public function update(array $values)
    {
        $this->model->fireModelEvent('updatingMultiple');

        $return = parent::update($values);

        $this->model->fireModelEvent('updatedMultiple');

        return $return;
    }

    /**
     * Delete a record from the database.
     *
     * @return mixed
     */
    public function delete()
    {
        $this->model->fireModelEvent('deletingMultiple');

        $return = parent::delete();

        $this->model->fireModelEvent('deletedMultiple');

        return $return;
    }

    /**
     * Order by sort_order if null.
     */
    protected function orderByDefault()
    {
        $model = $this->getModel();
        $query = $this->getQuery();

        if ($query->orders === null) {
            if ($model->titleColumnIsTranslatable()) {
                /*
                 * Postgres makes it damn near impossible
                 * to order by a foreign column and retain
                 * distinct results so let's avoid it entirely.
                 *
                 * Sorry!
                 */
                if (env('DB_CONNECTION', 'mysql') == 'pgsql') {
                    return;
                }

                if (!$this->hasJoin($model->getTranslationsTableName())) {
                    $this->query->leftJoin(
                        $model->getTranslationsTableName(),
                        $model->getTableName().'.id',
                        '=',
                        $model->getTranslationsTableName().'.entry_id'
                    );
                }

                $this
                    ->groupBy($model->getTableName().'.id')
                    ->select($model->getTableName().'.*')
                    ->where(
                        function (Builder $query) use ($model) {
                            $query->where($model->getTranslationsTableName().'.locale', config('app.locale'));
                            $query->orWhere(
                                $model->getTranslationsTableName().'.locale',
                                config('app.fallback_locale')
                            );
                            $query->orWhereNull($model->getTranslationsTableName().'.locale');
                        }
                    )
                    ->orderBy($model->getTranslationsTableName().'.'.$model->getTitleName(), 'ASC');
            } elseif ($model->getTitleName() && $model->getTitleName() !== 'id') {
                $query->orderBy($model->getTitleName(), 'ASC');
            }
        }
    }

    /**
     * Select the default columns.
     *
     * This is helpful when using addSelect
     * elsewhere like in a hook/criteria and
     * that select ends up being all you select.
     *
     * @return $this
     */
    public function selectDefault()
    {
        if (!$this->query->columns && $this->query->from) {
            $this->query->select($this->query->from.'.*');
        }

        return $this;
    }

    /**
     * Add macro catch to the query builder system.
     *
     * @param string $method     方法
     * @param array  $parameters 参数
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($macro = snake_case($method))) {
            return $this->runMacro($macro, $parameters);
        }

        return parent::__call($method, $parameters);
    }
}
