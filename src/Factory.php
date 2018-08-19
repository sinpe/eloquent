<?php

namespace Sinpe\Eloquent;

use Sinpe\Support\Traits\FireAware as FireTrait;
use Anomaly\Streams\Platform\Support\Hydrator;
use Sinpe\Support\ContainerInterface;

//use Illuminate\Foundation\Bus\DispatchesJobs;

/**
 * Class Factory.
 */
class Factory
{
    //use DispatchesJobs;
    use FireTrait;

    /**
     * The hydrator utility.
     *
     * @var Hydrator
     */
    protected $hydrator;

    /**
     * The service container.
     *
     * @var Container
     */
    protected $container;

    /**
     * Create a new Factory instance.
     *
     * @param Hydrator  $hydrator
     * @param Container $container
     */
    public function __construct(
        Hydrator $hydrator,
        ContainerInterface $container
    ) {
        $this->hydrator = $hydrator;
        $this->container = $container;
    }

    /**
     * Make a new EntryBuilder instance.
     *
     * @param        $model
     * @param string $method
     *
     * @return Criteria|null
     */
    public function make($model, $method = 'get')
    {
        if (!$model) {
            $model = Model::class;
        }

        /* @var Model $model */
        $model = $this->container->make($model);

        $criteria = $model->getCriteriaName();
        $query = $model->newQuery();

        return $this->container->make(
            $criteria,
            [
                'query' => $query,
                'method' => $method,
            ]
        );
    }
}
