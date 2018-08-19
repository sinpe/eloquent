<?php

namespace Sinpe\Eloquent;

use ArrayAccess;
use IteratorAggregate;

/**
 * Class Decorator.
 */
class Decorator extends \Robbo\Presenter\Decorator
{
    /**
     * Undecorate a value.
     *
     * @param $value
     *
     * @return mixed
     */
    public function undecorate($value)
    {
        if ($value instanceof Presenter) {
            return $value->getObject();
        }

        if (is_array($value) || ($value instanceof IteratorAggregate && $value instanceof ArrayAccess)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->undecorate($v);
            }
        }

        return $value;
    }
}
