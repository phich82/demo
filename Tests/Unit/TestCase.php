<?php

class TestCase
{
    /**
     * Undocumented function
     * use $this->get(...) instead $this->call('GET', ...);
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
            return $this->call($method, $args[0]);
        }
    
        throw new BadMethodCallException;
    }

    public function mock($class)
    {
        $mock = Mockery::mock($class);
        //$mock->shouldReceive('method')->with('args')->andReturn('result');
    
        $this->app->instance($class, $mock);
    
        return $mock;
    }
}
