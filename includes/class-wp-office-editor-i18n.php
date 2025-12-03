<?php
class WP_Office_Editor_i18n {
    
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wp-office-editor',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}