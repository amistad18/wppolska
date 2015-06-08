<?php

if ( !class_exists('Plugin_Custom_Free_Update') ) :

class Plugin_Custom_Free_Update {

    protected static $default_options = array(
        'update_info_url' => '',
        'current_version' => '0.0.0',
        'slug' => '',
        'plugin' => ''
    );


    public function __construct($options =array()) {
        $this->options = $this->parse_options($options);

        add_filter( 'pre_set_site_transient_update_plugins', array(&$this, 'check_update') );
        add_filter( 'plugins_api', array(&$this, 'check_info'), 10, 3 );
    }


    protected function parse_options($options) {
        return wp_parse_args($options, self::$default_options );
    }


    public function check_update($transient) {
        if ( empty($transient->checked) ) {
            return $transient;
        }

        $remote_update_info = $this->get_remote_update_info();

        if ( $remote_update_info && version_compare($this->options['current_version'], $remote_update_info->new_version, '<') ) {
            $transient->response[$this->options['plugin']] = $remote_update_info;
        }

        return $transient;
    }

    public function check_info($false, $action, $arg) {
        if ( $arg->slug === $this->options['slug'] ) {
            $information = $this->get_remote_information();
            return $information;
        }

        return false;
    }


    public function get_remote_update_info() {
        $request = wp_remote_post(
            $this->options['update_info_url'],
            array(
                'body' => array(
                    'action' => 'version',
                    'site_url' => urlencode(site_url()),
                    'plugin' => $this->options['slug']
                )
            )
        );

        if ( !is_wp_error($request) || 200 === wp_remote_retrieve_response_code($request) ) {
            return unserialize($request['body']);
        }

        return false;
    }

    public function get_remote_information() {
        $request = wp_remote_post(
            $this->options['update_info_url'],
            array(
                'body' => array(
                    'action' => 'info',
                    'site_url' => urlencode(site_url()),
                    'plugin' => $this->options['slug']
                )
            )
        );

        if ( !is_wp_error($request) || 200 === wp_remote_retrieve_response_code($request) ) {
            return unserialize($request['body']);
        }

        return false;
    }

    public function get_remote_license() {
        $request = wp_remote_post(
            $this->options['update_info_url'],
            array(
                'body' => array(
                    'action' => 'license',
                    'site_url' => urlencode(site_url()),
                    'plugin' => $this->options['slug']
                )
            )
        );

        if ( !is_wp_error($request) || 200 === wp_remote_retrieve_response_code($request) ) {
            return $request['body'];
        }

        return false;
    }
}

endif;