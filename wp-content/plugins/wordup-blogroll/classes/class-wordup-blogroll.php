<?php

require_once 'class-wpmagus-base-singleton.php';
require_once 'class-wordup-blogroll-widget.php';


if ( ! class_exists('WordUp_Blogroll') ) :

class WordUp_Blogroll extends WPmagus_Base_Singleton {

    const TEXTDOMAIN = 'wordup-blogroll';

    protected function __construct($options =array()) {
        parent::__construct($options);

        add_action( 'widgets_init', array($this, 'register_widgets') );
        add_filter( 'wordup_blogroll_get_blogs_info', array($this, 'get_blogs_info') );
    }


    public function register_widgets() {
        register_widget('WordUp_Blogroll_Widget');
    }

    public function get_blogs_info($blogs_info =array()) {
        $blogs_info = get_transient( 'wordup_blogroll_data' );

        if ( false === ( $blogs_info ) ) {

            $request = wp_remote_get( $this->options['update_info_url'] );

            if ( !is_wp_error($request) || 200 === wp_remote_retrieve_response_code($request) ) {
                $blogs_info = json_decode($request['body'], false);

                if ( $blogs_info ) {
                    set_transient( 'wordup_blogroll_data', $blogs_info, 48 * HOUR_IN_SECONDS );
                }
            }
        }

        return $blogs_info;
    }

}

endif;