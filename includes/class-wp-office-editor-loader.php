<?php
/**
 * Simple Loader class to manage WordPress actions & filters.
 *
 * @package WP_Office_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Office_Editor_Loader {

    /**
     * Actions storage
     *
     * @var array
     */
    protected $actions = array();

    /**
     * Filters storage
     *
     * @var array
     */
    protected $filters = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Add a WordPress action hook
     *
     * @param string   $hook
     * @param object   $component
     * @param string   $callback
     * @param int      $priority
     * @param int      $accepted_args
     */
    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {

        $this->actions[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
    }

    /**
     * Add a WordPress filter hook
     *
     * @param string   $hook
     * @param object   $component
     * @param string   $callback
     * @param int      $priority
     * @param int      $accepted_args
     */
    public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {

        $this->filters[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
    }

    /**
     * Register all stored actions & filters with WordPress
     */
    public function run() {

        // Register filters
        foreach ( $this->filters as $hook ) {
            add_filter(
                $hook['hook'],
                array( $hook['component'], $hook['callback'] ),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        // Register actions
        foreach ( $this->actions as $hook ) {
            add_action(
                $hook['hook'],
                array( $hook['component'], $hook['callback'] ),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}
