<?php

namespace Sinpe\Eloquent;

use Illuminate\Database\Eloquent\Collection as CollectionBase;

/**
 * Class Collection.
 */
class Collection extends CollectionBase
{
    /**
     * Return the item IDs.
     *
     * @return array
     */
    public function ids()
    {
        return $this->pluck('id')->all();
    }

    /**
     * Find a model by key.
     *
     * @param string $key   过滤项
     * @param mixed  $value 参考值
     *
     * @return static
     */
    public function filterBy($key, $value)
    {
        $decorator = new Decorator();

        return $this->filter(
            function ($entry) use ($key, $value, $decorator) {
                return $decorator->undecorate($entry)->{$key} === $value;
            }
        );
    }

    /**
     * Return decorated items.
     *
     * @return static|$this
     */
    public function decorate()
    {
        return new static((new Decorator())->decorate($this->items));
    }

    /**
     * Return undecorated items.
     *
     * @return static|$this
     */
    public function undecorate()
    {
        return new static((new Decorator())->undecorate($this->items));
    }
}
