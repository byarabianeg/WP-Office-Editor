<?php
class WP_Office_Editor_Loader {
    
    private $actions;
    private $filters;
    private $shortcodes;
    
    public function __construct() {
        $this->actions = [];
        $this->filters = [];
        $this->shortcodes = [];
    }
    
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    public function add_shortcode($tag, $component, $callback) {
        $this->shortcodes[] = [
            'tag' => $tag,
            'component' => $component,
            'callback' => $callback
        ];
    }
    
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = [
            'hook' => $hook,
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args
        ];
        
        return $hooks;
    }
    
    public function run() {
        // تسجيل الأفعال
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                [$hook['component'], $hook['callback']],
                $hook['priority'],
                $hook['accepted_args']
            );
        }
        
        // تسجيل المرشحات
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                [$hook['component'], $hook['callback']],
                $hook['priority'],
                $hook['accepted_args']
            );
        }
        
        // تسجيل الشورت كودات
        foreach ($this->shortcodes as $shortcode) {
            add_shortcode($shortcode['tag'], [$shortcode['component'], $shortcode['callback']]);
        }
    }
}