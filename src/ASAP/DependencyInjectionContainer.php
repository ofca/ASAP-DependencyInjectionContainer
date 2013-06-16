<?php

namespace ASAP;

/**
 * Lightweight dependency injection container.
 *
 * @author  ofca <ofca@emve.org>
 */
class DependencyInjectionContainer
{
    protected $params = array();
    protected $classes = array();
    protected $cache = array();

    /**
     * Instantiate the container.
     * Objects and parameters can be passed as argument to the constructor.
     *
     * @param array $values The objects.
     * @param array $params The parameters.
     */
    public function __construct($classes = array(), $params = array())
    {
        $this->classes = $classes;
        $this->register('__this__', $this);
    }

    /**
     * Gets object.
     * 
     * @param  string $id The unique identifier for object.
     * @return mixed The object.
     * @throws InvalidArgumentException if the identifier is not defined.
     */
    public function get($id)
    {
        if ( ! array_key_exists($id, $this->classes)) {
            throw new \InvalidArgumentException(
                sprintf('Identifier "%s" is not defined.', $id));
        }

        if (array_key_exists($id, $this->cache)) {
            return $this->cache[$id];
        }

        return $this->instantiate($id);
    }

    /**
     * Return value of a parameter.
     * 
     * @param  string $id     Unique identifier of parameter.
     * @param  mixed $default  Default value returned if parameter not exists.
     * @return mixed
     */
    public function getParam($id, $default = null)
    {
        return array_key_exists($id, $this->params) ? $this->params[$id] : $default;
    }

    /**
     * Sets value of parameter.
     *
     * Note: Parameter can be overwrited!
     * 
     * @param string $id   Unique key of parameter.
     * @param mixed $value Valud of parameter.
     * @return $this
     */
    public function setParam($id, $value)
    {
        $this->params[$id] = $value;

        return $this;
    }

    /**
     * Instantiate object.
     * 
     * @param  string $id Unique object identifier.
     * @return object Instance of class.
     */
    protected function instantiate($id)
    {
        $opt = $this->classes[$id];

        $className = $opt['class'];

        $args = array();

        foreach ($opt['arguments'] as $arg) {
            if (is_callable($arg)) {
                $arg = $arg();
            }

            $last = strlen($arg) - 1;

            // Param
            if ($arg[0] == '@' and $arg[$last] == '@') {
                $arg = $this->getParam(substr($arg, 1, $last-1));
            } else if ($arg[0] == '%' and $arg[$last] == '%') {
                $arg = $this->get(substr($arg, 1, $last-1));
            }

            $args[] = $arg;
        }

        if ($opt['constructor']) {
            $instance = call_user_func_array($opt['constructor'], $args);     
        } else {
            $class = new \ReflectionClass($className);
            $instance = $class->newInstanceArgs($args);
        }

        foreach ($opt['methods'] as $value) {
            $method = $value[0];
            $args = isset($value[1]) ? $value[1] : array();

            if (is_callable($method)) {
                call_user_func_array($method, $args);
            } else {
                call_user_func_array(array($instance, $method), $args);
            }
        }

        if (isset($opt['shared']) and $opt['shared']) {
            $this->cache[$id] = $instance;
        }

        return $instance;
    }

    public function register($id, $opt)
    {
        if (isset($this->classes[$id])) {
            throw new \InvalidArgumentException(
                sprintf('Identifier "%s" is already defined.', $id));
        }

        if (is_object($opt)) {
            $this->cache[$id] = $opt;
            $opt = array(
                'class'     => get_class($opt),
                'shared'    => true
            );
        }

        $defaults = array(
            'class'         => false,
            'constructor'   => false,
            'methods'       => array(),
            'arguments'     => array(),
            'shared'        => array()
        );
        $intersect = array_intersect_key($opt, $defaults);
        $diff = array_diff_key($defaults, $opt);
        $opt = $diff + $intersect;

        if ( ! is_callable($opt['constructor']) and $opt['class'] == false) {
            throw new \InvalidArgumentException('Class name not defined.');
        }

        $this->classes[$id] = $opt;

        return $this;
    }
}