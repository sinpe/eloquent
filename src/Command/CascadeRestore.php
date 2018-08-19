<?php

namespace Sinpe\Eloquent\Command;

use Sinpe\Eloquent\Collection;
use Sinpe\Eloquent\Model;

/**
 * Class CascadeRestore.
 */
class CascadeRestore
{
    /**
     * The eloquent model.
     *
     * @var Model
     */
    protected $model;

    /**
     * Create a new CascadeRestore instance.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Handle the command.
     */
    public function handle()
    {
        foreach ($this->model->getCascades() as $relation) {
            $relation = $this->model
                ->{camel_case($relation)}()
                ->onlyTrashed()
                ->getResults();

            if ($relation instanceof Model) {
                $relation->restore();
            }

            if ($relation instanceof Collection) {
                $relation->each(
                    function (Model $item) {
                        $item->restore();
                    }
                );
            }
        }
    }
}
