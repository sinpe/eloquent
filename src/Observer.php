<?php

namespace Sinpe\Eloquent;

use Sinpe\Support\Traits\FireAware as FireTrait;
use Sinpe\Eloquent\Command\CascadeDelete;
use Sinpe\Eloquent\Command\CascadeRestore;
use Sinpe\Eloquent\Event\ModelsWereDeleted;
use Sinpe\Eloquent\Event\ModelsWereUpdated;
use Sinpe\Eloquent\Event\ModelWasCreated;
use Sinpe\Eloquent\Event\ModelWasDeleted;
use Sinpe\Eloquent\Event\ModelWasRestored;
use Sinpe\Eloquent\Event\ModelWasSaved;
use Sinpe\Eloquent\Event\ModelWasUpdated;

/**
 * Class Observer.
 */
class Observer
{
    use FireTrait;
    //use DispatchesJobs;

    /**
     * The event dispatcher.
     *
     * @var EventDispatcher
     */
    private $events;

    /**
     * Create a new EloquentObserver instance.
     */
    public function __construct()
    {
        $this->events = Model::getEventDispatcher();
    }

    /**
     * Run after a record is created.
     *
     * @param Model $model
     */
    public function creating(Model $model)
    {
    }

    /**
     * Run after a record is created.
     *
     * @param Model $model
     */
    public function created(Model $model)
    {
        $model->flushCache();

        $this->events->fire(new ModelWasCreated($model));
    }

    /**
     * Run after saving a record.
     *
     * @param Model $model
     */
    public function saved(Model $model)
    {
        $model->flushCache();

        $this->events->fire(new ModelWasSaved($model));
    }

    /**
     * Run after a record has been updated.
     *
     * @param Model $model
     */
    public function updated(Model $model)
    {
        $model->flushCache();

        $this->events->fire(new ModelWasUpdated($model));
    }

    /**
     * Run after multiple records have been updated.
     *
     * @param Model $model
     */
    public function updatedMultiple(Model $model)
    {
        $model->flushCache();

        $this->events->fire(new ModelsWereUpdated($model));
    }

    /**
     * Run before a record is deleted.
     *
     * @param Model $entry
     */
    public function deleting(Model $entry)
    {
        $this->dispatch(new CascadeDelete($entry));
    }

    /**
     * Run after a record has been deleted.
     *
     * @param Model $model
     */
    public function deleted(Model $model)
    {
        $model->flushCache();

        /* @var Model $translation */
        if ($model->isTranslatable()) {
            foreach ($model->translations as $translation) {
                $translation->delete();
            }
        }

        $this->events->fire(new ModelWasDeleted($model));
    }

    /**
     * Run after multiple records have been deleted.
     *
     * @param Model $model
     */
    public function deletedMultiple(Model $model)
    {
        $model->flushCache();

        $this->events->fire(new ModelsWereDeleted($model));
    }

    /**
     * Fired just before restoring.
     *
     * @param Model $model
     */
    public function restoring(Model $model)
    {
    }

    /**
     * Run after a record has been restored.
     *
     * @param Model $model
     */
    public function restored(Model $model)
    {
        $model->flushCache();

        $this->dispatch(new CascadeRestore($model));

        $this->events->fire(new ModelWasRestored($model));
    }
}
