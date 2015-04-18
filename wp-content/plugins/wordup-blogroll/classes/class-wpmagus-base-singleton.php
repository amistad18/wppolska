<?php

if ( !class_exists('WPmagus_Base_Singleton') ):

abstract class WPmagus_Base_Singleton {

    /**
     * Parsed options for module
     *
     * @var array
     */
    protected $options;

    /**
     * List of default options for module
     *
     * @var array
     */
    static protected $default_options = array();

    /**
     * Collection of modules objects
     *
     * @var array
     */
    static private $instances;

    static public function get_instance($options =array()) {
        $class_name = get_called_class();

        if ( !isset(self::$instances[$class_name]) ) {

            // For PHP >= 5.3 change next line to: if ( !is_array($options) || !is_array($class_name::$default_options) ) {
            //if ( !is_array($options) || !is_array($class_name::$default_options) ) {
            if( !is_array($options) || !is_array( self::get_default_options($class_name) ) ) {
                throw new InvalidArgumentException('Module options have to be an Array.');
            }
            self::$instances[$class_name] = new $class_name($options);
        }
        return self::$instances[$class_name];
    }

    /**
     * Protected constructor
     * @param   array
     */
    protected function __construct($options =array()) {
        $this->options = $this->parse_options($options);
    }

    /**
     * Parses options array for module
     * @param   array
     * @return  array
     */
    protected function parse_options($options) {
        return wp_parse_args($options, self::$default_options );
    }

    /**
     * Returns list of default options of current module
     * Fix for PHP < 5.3
     * @return  array
     */
    static private function get_default_options($class_name, $property_name ='default_options') {
        if ( !class_exists($class_name) )
            return null;

        $vars = get_class_vars($class_name);
        if ( array_key_exists($property_name, $vars) )
            return $vars[$property_name];

        return null;
    }


    final private function __clone() { }

}

endif;


/* For PHP < 5.3 */
if ( !function_exists('get_called_class') ) :

function get_called_class() {
    $bt = debug_backtrace();
    $lines = file($bt[1]['file']);
    preg_match(
        '/([a-zA-Z0-9\_]+)::'.$bt[1]['function'] . '/',
        $lines[$bt[1]['line']-1],
        $matches
    );
    return $matches[1];
}

endif;