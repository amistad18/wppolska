<?php
/*
Plugin Name: WPPolska Facebook Event Importer
Description: Imports provided Facebook event to CPT. Needs WPPolska Main plugin activated.
Plugin URI: http://wppolska.pl/plugins/fb-importer/
Author: Kuba Mikita
Author URI: http://www.wpart.pl
Version: 1.0
License: GPL2
Text Domain: wppolska
*/

/*
    Copyright (C) 2015  Kuba Mikita  hello@underdev.it

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;

if ( ! defined( 'WPPOLSKA_TEXT_DOMAIN' ) ) {
	define( 'WPPOLSKA_TEXT_DOMAIN', 'wppolska' );
}

define( 'WPPFBI_DIR', plugin_dir_path( __FILE__ ) );

/**
 * WPPolska_FB_Importer class
 */
class WPPolska_FB_Importer {

	/**
	 * Class constructor
	 */
	public function __construct() {

		load_plugin_textdomain( WPPOLSKA_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 

		$this->load();

		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );

		add_action( 'admin_post_wppfbi_add_event', array( $this, 'add_event' ) );

		add_action( 'admin_notices', array( $this, 'notices' ) );

		register_activation_hook( __FILE__, array( 'WPPolska_FB_Importer', 'install' ) );

	}

	/**
	 * Loads libraries
	 * @return void
	 */
	public function load() {

		// Facebook PHP SDK
		if ( ! defined( 'FACEBOOK_SDK_V4_SRC_DIR' ) ) {
			define( 'FACEBOOK_SDK_V4_SRC_DIR', WPPFBI_DIR . '/fb-sdk/src/Facebook/' );
			require_once( WPPFBI_DIR . '/fb-sdk/autoload.php' );
		}

	}

	/**
	 * Checks for dependencies
	 *
	 * If there are no required resources, plugin will be deacivated.
	 * 
	 * @return void
	 */
	public static function install() {

		if ( ! is_plugin_active( 'wppolska-main/wppolska-main.php' ) ) {

            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die( __( 'Please install WPPolska Main plugin before activating WPPolska Facebook Importer!', WPPOLSKA_TEXT_DOMAIN ) );

        }

		if ( version_compare( PHP_VERSION, '5.4.0', '<' ) ) {

            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die( __( 'WPPolska Facebook Importer uses Facebook PHP SDK which requires PHP 5.4 or greater', 'pagebox' ) );

        }

	}

	/**
	 * Adds menu page
	 */
	public function add_menu_page() {

		$this->page_hook = add_management_page( __( 'Facebook Event Importer', WPPOLSKA_TEXT_DOMAIN ), __( 'FB Importer', WPPOLSKA_TEXT_DOMAIN ), 'edit_posts', 'fb-event-importer', array( $this, 'display_menu_page' ) );

	}

	/**
	 * Displays menu page
	 * @return void
	 */
	public function display_menu_page() {

		include( 'templates/importer.php' );

	}

	/**
	 * Adds an event
	 *
	 * Before adding it checks if event wasn't added already.
	 * Also handles Event type taxonomy
	 *
	 * @uses Facebook PHP SDK
	 * @return void
	 */
	public function add_event() {

		check_admin_referer( 'wppfbi_add_event', 'nonce' );

		if ( filter_var( $_POST['event'], FILTER_VALIDATE_URL ) ) {

			if ( preg_match( "/\/(\d+)$/", rtrim( $_POST['event'], '/' ), $matches ) ) {
				$event_id = $matches[1];
			} else {
				wp_redirect( add_query_arg( 'result', 'event-error', $_POST['_wp_http_referer'] ) );
				die();
			}

		} else {

			$event_id = filter_var( $_POST['event'], FILTER_SANITIZE_NUMBER_INT );

		}

		// Connect with Facebook

		FacebookSession::setDefaultApplication( '1555029401426940', 'f457c33807a2c2d6b160c7cd2f114cae' );

		$session = new FacebookSession( '1555029401426940|ku2PCcJBWFF-FN33aoR7dd1S114' );

		try {

			$request = new FacebookRequest(
				$session,
				'GET',
				'/' . $event_id
			);

			$event = $request->execute()->getGraphObject()->asArray();

		} catch (FacebookRequestException $e) {

			wp_redirect( add_query_arg( 'result', 'error', $_POST['_wp_http_referer'] ) );
			die();

		} catch (\Exception $e) {

			wp_redirect( add_query_arg( 'result', 'error', $_POST['_wp_http_referer'] ) );
			die();

		}

		// no starting date so it's propably not event
		if (  ! isset( $event['is_date_only'] ) && ! isset( $event['start_time'] ) ) {
			wp_redirect( add_query_arg( 'result', 'event-error', $_POST['_wp_http_referer'] ) );
			die();
		}

		// check if it was imported
		
		if ( get_page_by_title( $event['name'], 'OBJECT', 'wppolska_event' ) !== null ) {
			wp_redirect( add_query_arg( 'result', 'event-exists', $_POST['_wp_http_referer'] ) );
			die();
		}

		// handle custom taxonomy
		
		$term = term_exists( $_POST['event_type'], 'wppolska_event_type' );

		if ( empty( $term ) ) {
			$term = wp_insert_term( $_POST['event_type'], 'wppolska_event_type' );
		}

		$term_id = $term['term_id'];

		// prepare post

		$postarr = array(
			'post_title' => $event['name'],
			'post_content' => $event['description'],
			'post_status' => 'pending',
			'post_type' => 'wppolska_event',
		);

		$post_id = wp_insert_post( $postarr );

		wp_set_object_terms( $post_id, array( $term_id ), 'wppolska_event_type' );

		add_post_meta( $post_id, 'start_time', strtotime( $event['start_time'] ) );

		if ( isset( $event['ticket_uri'] ) ) {
			add_post_meta( $post_id, 'ticket_uri', strtotime( $event['ticket_uri'] ) );
		}

		/**
		 * @todo  Localization handle - didn't saw this property at the time
		 */
		
		wp_redirect( add_query_arg( 'result', 'success', $_POST['_wp_http_referer'] ) );

	}

	/**
	 * Adds notices
	 * @return void
	 */
	public function notices() {

		$screen = get_current_screen();

		if ( $screen->id != $this->page_hook ) {
			return;
		}

		if ( ! isset( $_GET['result'] ) ) {
			return;
		}

		if ( $_GET['result'] == 'success' ) {
			$this->add_notice( 'updated', sprintf( __( 'Event imported successfuly. Go to <a href="%s">events</a>', WPPOLSKA_TEXT_DOMAIN ), admin_url( 'edit.php?post_type=wppolska_event' ) ) );
		} else if ( $_GET['result'] == 'wrong-url' ) {
			$this->add_notice( 'error', __( 'Provided URL is wrong. Please make sure it\'s pointing to FB event', WPPOLSKA_TEXT_DOMAIN ) );
		} else if ( $_GET['result'] == 'event-error' ) {
			$this->add_notice( 'error', __( 'Did you provided valid event? Doesn\'t look like', WPPOLSKA_TEXT_DOMAIN ) );
		} else if ( $_GET['result'] == 'event-exists' ) {
			$this->add_notice( 'error', sprintf( __( 'This event seems to be added already. <a href="%s">Please check</a>', WPPOLSKA_TEXT_DOMAIN ), admin_url( 'edit.php?post_type=wppolska_event' ) ) );
		} else if ( $_GET['result'] == 'error' ) {
			$this->add_notice( 'error', __( 'There is something wrong with Facebook conection or you have provided wrong Event URL or ID. Please try again', WPPOLSKA_TEXT_DOMAIN ) );
		}  

	}

	/**
	 * Displays notice HTML
	 * @param string $type   notice type. error or updated
	 * @param string $notice notice body
	 */
	public function add_notice( $type, $notice ) {

		echo '<div class="' . $type . '"><p>' . $notice . '</p></div>';

	}

}

new \WPPolska_FB_Importer();