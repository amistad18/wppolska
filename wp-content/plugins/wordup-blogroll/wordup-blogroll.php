<?php
/*
Plugin Name: Blogroll polskich WordUpów
Version: 0.9.1
Plugin URI: http://wppolska.pl/plugins/wordup-blogroll/
Description: Wtyczka dodaje nowy typ widgetu, który wyświetla listę stron polskich WordUpów.
Author: Krzysiek Dróżdż
Author URI: http://wpmagus.pl
*/

require_once 'classes/class-plugin-custom-free-update.php';
require_once 'classes/class-wordup-blogroll.php';


WordUp_Blogroll::get_instance( array(
    '__FILE__' => __FILE__,
    'update_info_url' => 'http://wppolska.pl/plugins/wordup-blogroll/data.json'
) );


function wordup_blogroll_update() {
    new Plugin_Custom_Free_Update( array(
        'update_info_url' => 'http://wppolska.pl/plugins/',
        'current_version' => '0.9.1',
        'slug' => 'wordup-blogroll',
        'plugin' => plugin_basename(__FILE__)
    ) );
}
add_action('init', 'wordup_blogroll_update');