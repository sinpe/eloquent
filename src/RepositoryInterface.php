<?php

namespace Sinpe\Eloquent\Contract;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Sinpe\Eloquent\Collection;
use Sinpe\Eloquent\Model;

/**
 * Interface RepositoryInterface.
 */
interface RepositoryInterface
{
    /**
     * Return all records.
     *
     * @return Collection
     */
    public function all();

    /**
     * Find a record by it's ID.
     *
     * @param $id
     *
     * @return null|Model
     */
    public function find($id);

    /**
     * Find a record by it's column value.
     *
     * @param $column
     * @param $value
     *
     * @return Model|null
     */
    public function findBy($column, $value);

    /**
     * Find all records by IDs.
     *
     * @param array $ids
     *
     * @return Collection
     */
    public function findAll(array $ids);

    /**
     * Find a trashed record by it's ID.
     *
     * @param $id
     *
     * @return null|Model
     */
    public function findTrashed($id);

    /**
     * Create a new record.
     *
     * @param array $attributes
     *
     * @return Model
     */
    public function create(array $attributes);

    /**
     * Return a new query builder.
     *
     * @return Builder
     */
    public function newQuery();

    /**
     * Return a new instance.
     *
     * @param array $attributes
     *
     * @return Model
     */
    public function newInstance(array $attributes = []);

    /**
     * Count all records.
     *
     * @return int
     */
    public function count();

    /**
     * Return a paginated collection.
     *
     * @param array $parameters
     *
     * @return LengthAwarePaginator
     */
    public function paginate(array $parameters = []);

    /**
     * Save a record.
     *
     * @param Model $entry
     *
     * @return bool
     */
    public function save(Model $entry);

    /**
     * Update multiple records.
     *
     * @param array $attributes
     *
     * @return bool
     */
    public function update(array $attributes = []);

    /**
     * Delete a record.
     *
     * @param Model $entry
     *
     * @return bool
     */
    public function delete(Model $entry);

    /**
     * Force delete a record.
     *
     * @param Model $entry
     *
     * @return bool
     */
    public function forceDelete(Model $entry);

    /**
     * Restore a trashed record.
     *
     * @param Model $entry
     *
     * @return bool
     */
    public function restore(Model $entry);

    /**
     * Truncate the entries.
     *
     * @return $this
     */
    public function truncate();

    /**
     * Cache a value in the
     * model's cache collection.
     *
     * @param $key
     * @param $ttl
     * @param $value
     *
     * @return mixed
     */
    public function cache($key, $ttl, $value);

    /**
     * Flush the cache.
     *
     * @return $this
     */
    public function flushCache();

    /**
     * Guard the model.
     *
     * @return $this
     */
    public function guard();

    /**
     * Unguard the model.
     *
     * @return $this
     */
    public function unguard();

    /**
     * Set the repository model.
     *
     * @param Model $model
     *
     * @return $this
     */
    public function setModel(Model $model);

    /**
     * Get the model.
     *
     * @return Model
     */
    public function getModel();
}
