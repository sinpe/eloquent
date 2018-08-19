<?php

namespace Sinpe\Eloquent;

use Anomaly\Streams\Platform\Ui\Grid\Contract\GridRepositoryInterface;
use Anomaly\Streams\Platform\Ui\Grid\Event\GridIsQuerying;
use Anomaly\Streams\Platform\Ui\Grid\GridBuilder;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Collection;

/**
 * Class GridRepositoryInterface.
 */
class GridRepository implements GridRepositoryInterface
{
    use DispatchesJobs;

    /**
     * The repository model.
     *
     * @var Model
     */
    protected $model;

    /**
     * Create a new Model instance.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get the grid entries.
     *
     * @param GridBuilder $builder
     *
     * @return Collection
     */
    public function get(GridBuilder $builder)
    {
        // Start a new query.
        $query = $this->model->newQuery();

        /*
         * Prevent joins from overriding intended columns
         * by prefixing with the model's grid name.
         */
        $query = $query->select($this->model->getTable().'.*');

        /*
         * Eager load any relations to
         * save resources and queries.
         */
        $query = $query->with($builder->getGridOption('eager', []));

        /*
         * Raise and fire an event here to allow
         * other things (including filters / views)
         * to modify the query before proceeding.
         */
        $builder->fire('querying', compact('builder', 'query'));
        app('events')->fire(new GridIsQuerying($builder, $query));

        /*
         * Before we actually adjust the baseline query
         * set the total amount of entries possible back
         * on the grid so it can be used later.
         */
        $total = $query->count();

        $builder->setGridOption('total_results', $total);

        /*
         * Order the query results.
         */
        foreach ($builder->getGridOption('order_by', ['sort_order' => 'asc']) as $column => $direction) {
            $query = $query->orderBy($column, $direction);
        }

        return $query->get();
    }

    /**
     * Save the grid.
     *
     * @param GridBuilder $builder
     * @param array       $items
     */
    public function save(GridBuilder $builder, array $items = [])
    {
        $model = $builder->getGridModel();

        $items = $items ?: $builder->getRequestValue('items');

        foreach ($items as $index => $item) {
            /* @var Model $entry */
            $entry = $model->find($item['id']);

            $entry->{$builder->getGridOption('sort_column', 'sort_order')} = $index + 1;

            $entry->save();
        }
    }
}
