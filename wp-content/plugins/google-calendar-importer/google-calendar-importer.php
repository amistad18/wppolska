<?php
/*
Plugin Name: WPPolska Google Calendar Importer
Description: Imports events from Google Calendar to CPT. Needs WPPolska Main plugin activated.
Author: Maciej Stróżyński
Version: 1.0
License: GPL2
Text Domain: wppolska
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if ( ! defined( 'WPPOLSKA_TEXT_DOMAIN' ) ) {
	define( 'WPPOLSKA_TEXT_DOMAIN', 'wppolska' );
}

if ( !class_exists( 'WPPolska_GoogleCal_Importer' ) ) {

	class WPPolska_GoogleCal_Importer {

		protected static $instance = null;
		protected static $version = '1.0';

		private $client_id = null;
		private $service_account_name = null;
		private $key_file_location = null;

		private $nextSyncToken = null;

		private function __construct() {
			$this->get_secrets();
			$this->get_sync_token();

			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

			add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

			add_action( 'wp_ajax_wppolska_save_event', array( $this, 'ajax_save_event' ));
		}

		public static function instance() {

			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		public function load_plugin_textdomain() {

			$domain = WPPOLSKA_TEXT_DOMAIN;
			$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

			load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
		}

		public function enqueue_admin_styles() {

			$screen = get_current_screen();

			if ( ! isset( $screen->id ) || ( $screen->parent_base != 'tools' && $screen->base != 'tools_page_google-callendar-event-importer' ) ) {
				return;
			}

			wp_enqueue_style( 'wppolska-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), $this->version );
			wp_enqueue_style( 'sweet-alert', plugins_url( 'css/sweetalert.css', __FILE__ ), array(), $this->version );
		}

		public function enqueue_admin_scripts() {

			$screen = get_current_screen();

			if ( ! isset( $screen->id ) || ( $screen->parent_base != 'tools' && $screen->base != 'tools_page_google-callendar-event-importer' ) ) {
				return;
			}

			wp_register_script( 'sweet-alert', plugins_url( 'js/sweetalert.min.js', __FILE__ ), array(), $this->version );
			wp_register_script( 'wppolska-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), $this->version );
			wp_enqueue_script( 'wppolska-admin-script' );
			wp_enqueue_script( 'sweet-alert' );

			wp_localize_script( 'wppolska-admin-script', 'ajax_wppolska_var', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce_save_event' => wp_create_nonce( 'wppolska-ajax-save-event' )
			));
		}

		public function add_menu_page() {
			$this->page_hook = add_management_page(
				__( 'Google Calendar Event Importer', WPPOLSKA_TEXT_DOMAIN ),
				__( 'GoogleCal Importer', WPPOLSKA_TEXT_DOMAIN ),
				'edit_posts',
				'google-callendar-event-importer',
				array( $this, 'display_menu_page' )
			);
		}

		private function get_secrets() {
			$this->set_secrets();
		}

		private function set_secrets() {
			$this->client_id = esc_url( get_option( 'wppolska_googleCal_client_id', null ));
			$this->service_account_name = get_option( 'wppolska_googleCal_service_account_name', null );
			$this->key_file_location = get_option( 'wppolska_googleCal_key_file_location', null );
		}

		private function save_secrets( $secrets = null ) {

			if( !is_array( $secrets ) || empty( $secrets )){
				return;
			}

			$this->get_secrets();

			if( isset( $secrets['client_id'] ) && !empty( $secrets['client_id'] ) && $secrets['client_id'] != $this->client_id ){
				update_option( 'wppolska_googleCal_client_id', sanitize_text_field( $secrets['client_id'] ));
				$this->client_id = esc_url( sanitize_text_field( $secrets['client_id'] ));
			}

			if( isset( $secrets['service_account_name'] ) && !empty( $secrets['service_account_name'] ) && $secrets['service_account_name'] != $this->service_account_name ){
				update_option( 'wppolska_googleCal_service_account_name', sanitize_email( $secrets['service_account_name'] ));
				$this->service_account_name = sanitize_email( $secrets['service_account_name'] );
			}

			if( isset( $secrets['key_file_location'] ) && !empty( $secrets['key_file_location'] ) && $secrets['key_file_location'] != $this->key_file_location ){
				update_option( 'wppolska_googleCal_key_file_location', sanitize_text_field( $secrets['key_file_location'] ));
				$this->key_file_location = sanitize_text_field( $secrets['key_file_location'] );
			}

		}

		private function get_sync_token() {
			$this->set_sync_token();
		}

		private function set_sync_token() {
			$this->nextSyncToken = get_option( 'wppolska_googleCal_sync_token', null );
		}

		private function save_sync_token( $token = null ) {

			if( !is_numeric( $token ) || empty( $token )){
				return;
			}

			$this->get_sync_token();

			if( isset( $token ) && !empty( $token ) && $token != $this->nextSyncToken ){
				update_option( 'wppolska_googleCal_sync_token', sanitize_text_field( $token ));
				$this->nextSyncToken = sanitize_text_field( $token );
			}

		}

		public function display_menu_page() {
			?>
			<div id="googleCal_page" class="wrap">

				<h2><?php _e( 'Google Calendar Event Importer', WPPOLSKA_TEXT_DOMAIN ); ?></h2>

				<form action="" method="post">

					<?php wp_nonce_field( 'wppolska_google_cal_import_events', 'wppolska_google_cal_import_events_nonce' ); ?>

					<input type="hidden" name="action" value="wppolska_google_cal_add_event">

					<table class="form-table">

					<?php if( $this->nextSyncToken === null ){ ?>
						<tr>
							<th scope="row"><label for="client_id"><?php _e( 'Google Callendar client ID', WPPOLSKA_TEXT_DOMAIN ); ?></label></th>
							<td><input type="text" name="client_id" id="client_id" class="regular-text" placeholder="827133893147-7nari5r0iblntdhj1bt76bskidu7ulal.apps.googleusercontent.com" required="required" value="<?php echo $this->client_id; ?>"></td>
						</tr>

						<tr>
							<th scope="row"><label for="service_account_name"><?php _e( 'Google Callendar service account name', WPPOLSKA_TEXT_DOMAIN ); ?></label></th>
							<td><input type="text" name="service_account_name" id="service_account_name" class="regular-text" placeholder="827133893147-7nari5r0iblntdhj1bt76bskidu7ulal@developer.gserviceaccount.com" required="required" value="<?php echo $this->service_account_name; ?>"></td>
						</tr>
	
						<tr>
							<th scope="row"><label for="key_file_location"><?php _e( 'Google Callendar secret key file location', WPPOLSKA_TEXT_DOMAIN ); ?></label></th>
							<td><input type="text" name="key_file_location" id="key_file_location" class="regular-text" placeholder="keys/ab8075473ec0.p12" required="required" value="<?php echo $this->key_file_location; ?>"></td>
						</tr>
					<?php } ?>

						<tr>
							<th scope="row"><label for="calendar"><?php _e( 'Google Callendar ID or URL', WPPOLSKA_TEXT_DOMAIN ); ?></label></th>
							<td><input type="text" name="calendar" id="calendar" class="regular-text" placeholder="https://www.google.com/calendar/embed?src=g9taks392e8mc5letq9pmtfqq8@group.calendar.google.com" required="required" value="https://www.google.com/calendar/embed?src=g9taks392e8mc5letq9pmtfqq8@group.calendar.google.com"></td>
						</tr>

						<tr>
							<th scope="row"><label for="token"><?php _e( 'Google Callendar nextSyncToken', WPPOLSKA_TEXT_DOMAIN ); ?></label></th>
							<td><input type="text" name="token" id="token" class="regular-text" placeholder="00001429704014469000" value="<?php echo $this->nextSyncToken; ?>"></td>
						</tr>

						<tr>
							<th scope="row"><?php submit_button( __( 'Get Events', WPPOLSKA_TEXT_DOMAIN ), 'primary', null, false ); ?></th>
						</tr>

					</table>

				</form>

			</div>
			<?php

			if( $_POST['action'] === 'wppolska_google_cal_add_event' ){

				if( $this->nextSyncToken === null ){

					$googleCal_api_secrets = array();

					if( isset( $_POST['client_id'] ) && !empty( $_POST['client_id'] ) ){
						$googleCal_api_secrets['client_id'] = $_POST['client_id'];
					}
					if( isset( $_POST['service_account_name'] ) && !empty( $_POST['service_account_name'] )){
						$googleCal_api_secrets['service_account_name'] = $_POST['service_account_name'];
					}
					if( isset( $_POST['key_file_location'] ) && !empty( $_POST['key_file_location'] )){
						$googleCal_api_secrets['key_file_location'] = $_POST['key_file_location'];
					}

					$this->save_secrets( $googleCal_api_secrets );

				}

				if( isset( $_POST['calendar'] ) && !empty( $_POST['calendar'] )){

					if( !isset( $_POST['wppolska_google_cal_import_events_nonce'] ) || !wp_verify_nonce( $_POST['wppolska_google_cal_import_events_nonce'], 'wppolska_google_cal_import_events' )){
						// TODO: error handling
						echo 'nonce error';
						exit;
					}

					if( strpos( 'https://www.google.com/calendar/', $_POST['calendar'] ) === false ){
						parse_str( $_POST['calendar'], $exploded );
						parse_str( explode("?", $_POST['calendar'] )[1], $exploded );
						$callendarID = $exploded['src'];
					} else {
						$callendarID = $_POST['calendar'];
					}

					$this->get_events_list( $callendarID, $_POST['token'] );

				}

			}

		}

		public function get_events_list( $callendarID, $token ) {

				require_once 'google-api-php-client/autoload.php';
				require_once "google-api-php-client/src/Google/Client.php";
				require_once "google-api-php-client/src/Google/Service/Calendar.php";

				// Service Account info
				// TODO: move those data to database before public repo

				// TODO: not used for now
				// $this->client_id

				$client = new Google_Client();
				$client->setApplicationName("WPPolska Google Calendar Import");

				$service = new Google_Service_Calendar( $client );
				$key = file_get_contents( $this->key_file_location, true );

				$cred = new Google_Auth_AssertionCredentials( $this->service_account_name, array( 'https://www.googleapis.com/auth/calendar.readonly' ), $key	);
				$client->setAssertionCredentials( $cred );
				$cals = $service->calendarList->listCalendarList();
				//print_r($cals);

				if( isset( $cals->nextSyncToken ) && !empty( $cals->nextSyncToken ) && is_numeric( $cals->nextSyncToken )){
					$this->save_sync_token( $cals->nextSyncToken );
				}

				if( isset( $token ) && !empty( $token )){
					$params = array( 'syncToken' => $token );
				} else {
					$params = array( 'orderBy' => 'startTime', 'singleEvents' => true );
				}

				$events = $service->events->listEvents( $callendarID, $params );

				if( count( $events->getItems() ) > 0 ){

					?>
					<table id="googleCal_page_table" class="wp-list-table widefat fixed events plugins">
						<thead>
							<tr>
								<th style="" class="manage-column column-title desc" id="title" scope="col">
									<span>Tytuł</span>
								</th>
								<th style="" class="manage-column column-author" id="author" scope="col">Autor</th>
								<th style="" class="manage-column column-date asc" id="date" scope="col">
									<span>Data</span>
								</th>
								<th style="" class="manage-column column-comments num desc" id="comments" scope="col">
									<span>Dodaj</span>
								</th>
							</tr>
						</thead>

						<tfoot>
							<tr>
								<th style="" class="manage-column column-title desc" scope="col">
									<span>Tytuł</span>
								</th>
								<th style="" class="manage-column column-author" scope="col">Autor</th>
								<th style="" class="manage-column column-date asc" scope="col">
									<span>Data</span><span class="sorting-indicator"></span>
								</th>
								<th style="" class="manage-column column-comments num desc" id="comments" scope="col">
									<span>Dodaj</span>
								</th>
							</tr>
						</tfoot>

						<tbody id="the-list">
					<?php
					// sorting
					$eventsArray = $events->getItems();
					krsort($eventsArray, SORT_NUMERIC);

					foreach ($eventsArray as $event) {
						// todo: .active na tr tylko jeśli jest już event w bazie
						$is_event_exist = $this->check_event_ID( $event->getId() );
						?>
							<tr class="type-post type-event status-publish format-standard hentry alternate iedit author-self level-0 <?php if( $is_event_exist ) echo 'active'; ?>">
								<td class="post-title page-title column-title">
									<strong>
										<span class="event-summary"><?php echo $event->getSummary(); ?></span>
									</strong>
									<span class="event-description hidden"><?php echo $event->getDescription(); ?></span>
									<span class="event-terms">
									<?php
									
				$eventTypes = array(
					'WordUp',
					'WordCamp',
					'Warsztaty',
					'Laboratoria',
					'Party'
				);

				$eventCities = array(
					'Bydgoszcz',
					'Gdańsk',
					'Kraków',
					'Lublin',
					'Łódź',
					'Silesia',
					'Szczecin',
					'Toruń',
					'Trójmiasto',
					'Warszawa',
					'Wrocław'
				);

				// spróbujemy na podstawie tytułu od razu dodać odpowiednią taksnonomie dla typu wydarzenia
				foreach( $eventTypes as $eventType ){
					// substr dla różnych odmian - warsztat/laboratorium etc
					if( stripos( $event->getSummary(), substr( $eventType, 0, 8 )) !== false ){
						echo $eventType . ' - ';
						$term = term_exists( $eventType, 'wppolska_event_type' );

						$termID = $term['term_id'];
						echo $termID . ', ';
					}
				}

				// i tak samo spróbujemy wyciągnąć miasto z tytułu
				foreach( $eventCities as $eventCity ){
					// substr dla różnych odmian - warsztat/laboratorium etc
					if( stripos( $event->getSummary(), $eventCity ) !== false ){
						echo $eventCity . ' - ';
						$term = term_exists( $eventCity, 'wppolska_event_city' );

						$termID = $term['term_id'];
						echo $termID;

					}
				}
				?>
									</span>
									<span class="event-id hidden"><?php echo $event->getId(); ?></span>
								</td>
								<td class="author column-author">
									<span class="event-creator-name"><?php echo $event->getCreator()->displayName; ?></span><br />
									<span class="event-creator-email"><?php echo $event->getCreator()->email; ?></span>
								</td>
								<td class="date column-date">
									<abbr title="<?php echo $this->format_google_date( $event->getStart() ); ?>"><?php echo $this->format_google_date( $event->getStart() ); ?></abbr><br>
									<abbr title="<?php echo $this->format_google_date( $event->getEnd() ); ?>"><?php echo $this->format_google_date( $event->getEnd() ); ?></abbr><br>
									<span class="event-start-date hidden"><?php echo $this->format_google_date( $event->getStart(), 'date' ); ?></span>
									<span class="event-start-time hidden"><?php echo $this->format_google_date( $event->getStart(), 'time' ); ?></span>
									<span class="event-end-date hidden"><?php echo $this->format_google_date( $event->getEnd(), 'date' ); ?></span>
									<span class="event-end-time hidden"><?php echo $this->format_google_date( $event->getEnd(), 'time' ); ?></span>
									<span class="event-status hidden"><?php echo $event->getStatus(); ?></span>
								</td>
								<td class="column-comments comments">
									<div class="add_event <?php if( $is_event_exist ) echo 'hidden'; ?>"><span class="dashicons dashicons-plus add_event_action"></span></div>
									<div class="success <?php if( !$is_event_exist ) echo 'hidden'; ?>"><span class="dashicons dashicons-yes"></span></div>
									<div class="update hidden"><span class="dashicons dashicons-update"></span></div>
									<div class="dismiss hidden"><span class="dashicons dashicons-dismiss"></span></div>
									<div class="loading hidden"><img src="<?php echo get_admin_url(); ?>/images/loading.gif" /></div>
								</td>
							</tr>
						<?php
					}
					?>
						</tbody>
					</table>
					<?php
				} else {

					if( isset( $token ) && !empty( $token )){
						_e( 'None events changes to display. <br /> If you want to display all events, remove nextSyncToken.', WPPOLSKA_TEXT_DOMAIN );
					} else {
						_e( 'None events to display.', WPPOLSKA_TEXT_DOMAIN );
					}

				}
		}

		public function format_google_date( $gdate, $arg = null ) {
			if( $arg === 'date' ){
				return (new DateTime( $gdate->getDate() ))->format( 'd-m-Y' );
			} else if( $arg === 'time' ){
				if ( $val = $gdate->getDateTime() ){
					return (new DateTime($val))->format( 'H:i' );
				} else {
					return null;
				}
			} else {
				if ( $val = $gdate->getDateTime() ){
					return (new DateTime($val))->format( 'd-m-Y H:i' );
				} else if ( $val = $gdate->getDate() ){
					return (new DateTime($val))->format( 'd-m-Y' ) . ' (all day)';
				}
			} 
		}

		public function display_single_event() {
			return null;
		}

		private function sortEvents( $event1, $event2 ) {
			if ( $date1 = $event1->getStart()->getDateTime() ){
				$event1date = (new DateTime($date1))->format( 'd-m-Y H:i' );
			} else if ( $date1 = $event1->getStart()->getDate() ){
				$event1date = (new DateTime($date1))->format( 'd-m-Y' );
			}

			if ( $date2 = $event2->getStart()->getDateTime() ){
				$event2date = (new DateTime($date2))->format( 'd-m-Y H:i' );
			} else if ( $date2 = $event2->getStart()->getDate() ){
				$event2date = (new DateTime($date2))->format( 'd-m-Y' );
			}

			var_dump ( strtotime( $event1date ) - strtotime( $event2date ) );
			return strtotime( $event1date ) - strtotime( $event2date );
		}

		public function ajax_save_event() {
			try {
				if( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'wppolska-ajax-save-event' )){
					if( isset( $_POST['event_id'] ) && !empty( $_POST['event_id'] )){
						if( isset( $_POST['event_id'] ) && !$this->check_event_ID( $_POST['event_id'] )){
							if( isset( $_POST['event_summary'] ) && !empty( $_POST['event_summary'] )){
								// save event
								return $this->save_event($_POST);
							} else {
								throw new Exception('This event title (sumary) is empty');
							}
						} else {
							throw new Exception('This event ID already exists in the database');
						}
					} else {
						throw new Exception('This event don\'t have unique ID');
					}
				} else {
					throw new Exception('You don\'t have access to this action');
				}
			} catch (Exception $e){
				$this->return_json_msg($e->getMessage(), 500);
			}

			wp_die();
		}

		private function check_event_ID( $ID ) {
			// TODO: true jeśli nie ma, false jeśli sie powtarza
			$the_query = new WP_Query(
				array(
					'post_type' => 'wppolska_event',
					'meta_query' => array(
						array(
							'key'     => 'google_calendar_ID',
							'value'   => $ID,
						),
					),
				)
			);

			if ( $the_query->have_posts() ) {
				wp_reset_postdata();
				return true;
			} else {
				wp_reset_postdata();
				return false;
			}

		}

		private function save_event( $data ) {

			// TODO: może po event_creator_email lub event_creator_name dodawać taksonomie miasta? Na zasadzie że Arek dodaje eventy z Wawy, Szymon z Lublina a Kasia z Wro?
			$newEvent = array(
				'post_title' => $data['event_summary'],
				'post_content' => $data['event_description'],
				'post_status' => 'pending',
				'post_type' => 'wppolska_event',
			);

			$newEventID = wp_insert_post( $newEvent );

			if( $newEventID && !is_wp_error( $newEventID )){

				// TODO: for public repo - move that to options
				$eventTypes = array(
					'WordUp',
					'WordCamp',
					'Warsztaty',
					'Laboratoria',
					'Party'
				);

				$eventCities = array(
					'Bydgoszcz',
					'Gdańsk',
					'Kraków',
					'Lublin',
					'Łódź',
					'Silesia',
					'Szczecin',
					'Toruń',
					'Trójmiasto',
					'Warszawa',
					'Wrocław'
				);

				// spróbujemy na podstawie tytułu od razu dodać odpowiednią taksnonomie dla typu wydarzenia
				foreach( $eventTypes as $eventType ){
					// substr dla różnych odmian - warsztat/laboratorium etc
					if( stripos( $data['event_summary'], substr( $eventType, 0, 8 )) !== false ){

						$term = term_exists( $eventType, 'wppolska_event_type' );

						if ( empty( $term ) ) {
							$term = wp_insert_term( $eventType, 'wppolska_event_type' );
						}

						$termID = $term['term_id'];

						wp_set_object_terms( $newEventID, (int)$termID, 'wppolska_event_type' );
					}
				}

				// i tak samo spróbujemy wyciągnąć miasto z tytułu
				foreach( $eventCities as $eventCity ){
					// substr dla różnych odmian - warsztat/laboratorium etc
					if( stripos( $data['event_summary'], $eventCity ) !== false ){

						$term = term_exists( $eventCity, 'wppolska_event_city' );

						if ( empty( $term ) ) {
							$term = wp_insert_term( $eventCity, 'wppolska_event_city' );
						}

						$termID = $term['term_id'];

						wp_set_object_terms( $newEventID, (int)$termID, 'wppolska_event_city' );
					}
				}

				add_post_meta( $newEventID, 'start_time', strtotime( $data['event_start_date'] . $data['event_start_time'] ), true );
				add_post_meta( $newEventID, 'end_time', strtotime( $data['event_end_date'] . $data['event_end_time'] ), true );

				add_post_meta( $newEventID, 'google_calendar_ID', filter_var( $data['event_id'], FILTER_SANITIZE_STRING ), true );
				add_post_meta( $newEventID, 'google_calendar_creator_email', filter_var( $data['event_creator_email'], FILTER_SANITIZE_EMAIL ), true );
				add_post_meta( $newEventID, 'google_calendar_creator_name', filter_var( $data['event_creator_name'], FILTER_SANITIZE_STRING ), true );

				$this->return_json_msg( $newEventID, 200 );
			} else {
				$this->return_json_msg( 'Error while adding new event: ' . $newEventID, 500 );
			}

		}

		public function return_json_msg( $message, $status = 200 ) {
			header('Content-Type: application/json');
			$response = new stdClass();
			$response -> status = $status;
			$response -> msg = $message;
			echo json_encode($response);
			wp_die();
		}

	}

}

add_action( 'plugins_loaded', array( 'WPPolska_GoogleCal_Importer', 'instance' ));

