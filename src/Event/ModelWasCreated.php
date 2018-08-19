<?php

namespace Sinpe\Eloquent\Event;

use Sinpe\Eloquent\Model;

/**
 * Class ModelWasCreated.
 */
class ModelWasCreated
{
    /**
     * The model object.
     *
     * @var Model
     */
    protected $model;

    /**
     * Create a new ModelWasCreated instance.
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
