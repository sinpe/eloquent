<?php

namespace Sinpe\Eloquent;

use LogicException;
use Sinpe\Cache\CacheableTrait;
use Robbo\Presenter\PresentableInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as ModelBase;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Class Model.
 */
class Model extends ModelBase implements PresentableInterface
{
    use CacheableTrait;

    /**
     * 注入容器.
     *
     * @var
     */
    //static protected $container;

    /**
     * The attributes that are
     * not mass assignable. Let upper
     * models handle this themselves.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Observable model events.
     *
     * @var array
     */
    protected $observables = [
        'updatingMultiple',
        'updatedMultiple',
        'deletingMultiple',
        'deletedMultiple',
    ];

    /**
     * The cascading delete-able relations.
     *
     * @var array
     */
    //protected $cascades = [];

    /**
     * 注入容器，使用前必须要调用初始化.
     *
     * @return string
     */
    /*
    static public function setContainer($container)
    {
        static::$container = $container;
    }
    */
	
	/**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // TODO 缓存

        self::observe(Observer::class);
    }

    /**
     * Return the object's ETag fingerprint.
     *
     * @return string
     */
    public function etag()
    {
        return md5(get_class($this).json_encode($this->toArray()));
    }

    /**
     * Get the criteria class.
     *
     * @return string
     */
    public function getCriteriaName()
    {
        $criteria = get_class($this).'Criteria';

        return class_exists($criteria) ? $criteria : Criteria::class;
    }

    /**
     * Return the entry presenter.
     *
     * This is against standards but required
     * by the presentable interface.
     *
     * @return Presenter
     */
    public function getPresenter()
    {
        $presenter = get_class($this).'Presenter';

        if (class_exists($presenter)) {
            return new $presenter($this);
        }

        return new Presenter($this);
    }

    /**
     * Return a new collection class with our models.
     *
     * 方法重写
     *
     * @param array $items
     *
     * @return Collection
     */
    public function newCollection(array $items = [])
    {
        $collection = get_class($this).'Collection';

        if (class_exists($collection)) {
            return new $collection($items);
        }

        return new Collection($items);
    }

    /**
     * Return if a row is deletable or not.
     *
     * @return bool
     */
    /*
    public function isDeletable()
    {
        return true;
    }
    */

    /**
     * Return if the model is restorable or not.
     *
     * @return bool
     */
    /*
    public function isRestorable()
    {
        return true;
    }
    */

    /**
     * Return whether the model is being
     * force deleted or not.
     *
     * @return bool
     */
    public function isForceDeleting()
    {
        return isset($this->forceDeleting) && $this->forceDeleting === true;
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * 方法重写
     *
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newEloquentBuilder($query)
    {
        $builder = get_class($this).'Builder';

        if (class_exists($builder)) {
            return new $builder($query);
        }

        return new Builder($query);
    }

    /**
     * Return unguarded attributes.
     *
     * @return array
     */
    public function getUnguardedAttributes()
    {
        foreach ($attributes = $this->getAttributes() as $attribute => $value) {
            $attributes[$attribute] = $value;
        }

        return array_diff_key($attributes, array_flip($this->getGuarded()));
    }

    /**
     * Set the specific relationship in the model.
     *
     * 重写方法，添加格式化
     *
     * @param string $relation
     * @param mixed  $value
     *
     * @return $this
     */
    public function setRelation($relation, $value)
    {
        $relation = studly_case($relation);

        $this->relations[$relation] = $value;

        return $this;
    }

    /**
     * Get a relationship.
     *
     * 重写方法，格式化关系的命名：get<Name>Relation的写法
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getRelationValue($key)
    {
        $key = studly_case($key);

        // If the key already exists in the relationships array, it just means the
        // relationship has already been loaded, so we'll just return it out of
        // here because there is no need to query within the relations twice.
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        // If the "attribute" exists as a method on the model, we will just assume
        // it is a relationship and will load and return results from the query
        // and hydrate the relationship's value on the "relationships" array.
        if (method_exists($this, 'get'.$key.'Relation')) {
            return $this->getRelationshipFromMethod($key);
        }
    }

    /**
     * Get a relationship value from a method.
     *
     * 重写方法，格式化关系的命名：get<Name>Relation的写法
     *
     * @param string $method
     *
     * @return mixed
     *
     * @throws \LogicException
     */
    protected function getRelationshipFromMethod($method)
    {
        $methodNormalized = 'get'.$method.'Relation';

        $relation = $this->$methodNormalized();

        if (!$relation instanceof Relation) {
            throw new LogicException(
                sprintf(
                    '%s::%s must return a relationship instance.',
                    static::class,
                    $methodNormalized
                )
            );
        }

        return tap($relation->getResults(), function ($results) use ($method) {
            $this->setRelation($method, $results);
        });
    }

    /**
     * Get the cascading actions.
     *
     * @return array
     */
    /*
    public function getCascades()
    {
        return $this->cascades;
    }
    */

    /**
     * Return the string form of the model.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }
}
