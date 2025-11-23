<?php

if (!defined('BASEPATH')) {
    die();
}

class Tango_Hooks
{
    private $actions = array();
    private $filters = array();

    /**
     * Add a new action hook.
     *
     * @param string $hook
     * @param callable $callback
     * @param int $priority
     */
    public function add_action($hook, $callback, $priority = 10)
    {
        $this->actions[$hook][$priority][] = $callback;
    }

    /**
     * Execute an action hook.
     *
     * @param string $hook
     * @param array $args
     */
    public function do_action($hook, $args = array())
    {
        if (isset($this->actions[$hook])) {
            ksort($this->actions[$hook]);
            foreach ($this->actions[$hook] as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    if (is_callable($callback)) {
                        call_user_func_array($callback, $args);
                    }
                }
            }
        }
    }

    /**
     * Add a new filter hook.
     *
     * @param string $hook
     * @param callable $callback
     * @param int $priority
     */
    public function add_filter($hook, $callback, $priority = 10)
    {
        $this->filters[$hook][$priority][] = $callback;
    }

    /**
     * Apply filters to a value.
     *
     * @param string $hook
     * @param mixed $value
     * @param array $args
     * @return mixed
     */
    public function apply_filters($hook, $value, $args = array())
    {
        if (!is_array($args)) {
            $args = array($args);
        }
        if (isset($this->filters[$hook])) {
            ksort($this->filters[$hook]);
            foreach ($this->filters[$hook] as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    if (is_callable($callback)) {
                        // Prepend value to args
                        $call_args = array_merge(array($value), $args);
                        $value = call_user_func_array($callback, $call_args);
                    }
                }
            }
        }
        return $value;
    }
}
