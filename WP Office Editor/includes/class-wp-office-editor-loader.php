<?php
class WP_Office_Editor_Loader {

    private $actions = [];

    public function add_action($hook, $component, $callback) {
        $this->actions[] = [ $hook, $component, $callback ];
    }

    public function run() {
        foreach ($this->actions as $hook) {
            add_action($hook[0], [$hook[1], $hook[2]]);
        }
    }
}
