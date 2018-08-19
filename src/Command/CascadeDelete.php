<?php

namespace Sinpe\Eloquent\Command;

use Illuminate\Database\Eloquent\Relations\Relation;
use Sinpe\Eloquent\Collection;
use Sinpe\Eloquent\Model;

/**
 * Class CascadeDelete.
 */
class CascadeDelete
{
    /**
     * The eloquent model.
     *
     * @var Model
     */
    protected $model;

    /**
     * Create a new CascadeDelete instance.
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
        $action = $this->model->isForceDeleting() ? 'forceDelete' : 'delete';

        /*
         * If the model itself can not be trashed
         * then we have no reason to keep any
         * relations that cascade.
         */
        if (!method_exists($this->model, 'restore')) {
            $action = 'forceDelete';
        }

        foreach ($this->model->getCascades() as $relation) {
            /* @var Relation $relation */
            $relation = $this->model->{$relation}();

            if ($action == 'forceDelete' && method_exists($relation, 'withTrashed')) {
                $relation = $relation->withTrashed();
            }

            $relation = $relation->getResults();

            if ($relation instanceof Model) {
                $relation->{$action}();
            }

            if ($relation instanceof Collection) {
                $relation->each(
                    function (Model $item) use ($action) {
                        $item->{$action}();
                    }
                );
            }
        }
    }
}
