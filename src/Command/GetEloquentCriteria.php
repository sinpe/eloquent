<?php

namespace Sinpe\Eloquent\Command;

use Sinpe\Eloquent\Factory;

/**
 * Class GetEloquentCriteria.
 */
class GetEloquentCriteria
{
    /**
     * The model string.
     *
     * @var string
     */
    protected $model;

    /**
     * The getter method.
     *
     * @var string
     */
    protected $method;

    /**
     * Create a new GetEloquentCriteria instance.
     *
     * @param        $model
     * @param string $method
     */
    public function __construct($model, $method = 'get')
    {
        $this->model = $model;
        $this->method = $method;
    }

    /**
     * Handle the command.
     *
     * @param Factory $factory
     *
     * @return \Sinpe\Eloquent\Criteria|null
     */
    public function handle(Factory $factory)
    {
        return $factory->make($this->model, $this->method);
    }
}
