<?php

namespace Sinpe\Eloquent\Event;

use Sinpe\Eloquent\Model;

/**
 * Class ModelsWereUpdated.
 */
class ModelsWereUpdated
{
    /**
     * The model object.
     *
     * @var Model
     */
    protected $model;

    /**
     * Create a new ModelsWereUpdated instance.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get the model object.
     *
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }
}
