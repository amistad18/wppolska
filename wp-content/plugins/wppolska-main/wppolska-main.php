<?php
/*
Plugin Name: WPPolska Main
Description: Main functionality for WPPolska.pl, like custom post types, custom taxonomies etc
Author: Maciej Stróżyński
Version: 1.0
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'WPPOLSKA_TEXT_DOMAIN', 'wppolska' );

if ( !class_exists( 'WPPolskaMainClass' ) ) {

	class WPPolskaMainClass
	{

		public function __construct()
		{
			add_action( 'init', array( $this, 'wppolska_register_cpt' ), 0 );
			add_action( 'init', array( $this, 'wppolska_register_ctax' ), 0 );
		}

		public function wppolska_register_cpt()
		{
			$labelsEvent = array(
				'name'               => __( 'Events', WPPOLSKA_TEXT_DOMAIN ),
				'singular_name'      => __( 'Event', WPPOLSKA_TEXT_DOMAIN ),
				'menu_name'          => __( 'Events', WPPOLSKA_TEXT_DOMAIN ),
				'name_admin_bar'     => __( 'Events', WPPOLSKA_TEXT_DOMAIN ),
			);

			$labelsSpeaker = array(
				'name'               => __( 'Speakers', WPPOLSKA_TEXT_DOMAIN ),
				'singular_name'      => __( 'Speaker', WPPOLSKA_TEXT_DOMAIN ),
				'menu_name'          => __( 'Speakers', WPPOLSKA_TEXT_DOMAIN ),
				'name_admin_bar'     => __( 'Speakers', WPPOLSKA_TEXT_DOMAIN ),
			);

			$labelsTalk = array(
				'name'               => __( 'Talks', WPPOLSKA_TEXT_DOMAIN ),
				'singular_name'      => __( 'Talk', WPPOLSKA_TEXT_DOMAIN ),
				'menu_name'          => __( 'Talks', WPPOLSKA_TEXT_DOMAIN ),
				'name_admin_bar'     => __( 'Talks', WPPOLSKA_TEXT_DOMAIN ),
			);

			$argsEvent = array(
				'labels'				=> $labelsEvent,
				'public'				=> true,
				'exclude_from_search'	=> false,
				'publicly_queryable'	=> true,
				'show_ui'				=> true,
				'show_in_menu'			=> true,
				'show_in_nav_menus'		=> true,
				'show_in_admin_bar'		=> true,
				'query_var'				=> true,
				'rewrite'				=> array( 'slug' => __( 'event', WPPOLSKA_TEXT_DOMAIN ) ),
				'capability_type'		=> 'post',
				'has_archive'			=> true,
				'menu_position'			=> 30,
				'taxonomies'			=> array( 'wppolska_event_city', 'wppolska_event_type' ),
				'supports'				=> array( 'title', 'editor', 'author', 'excerpt', 'comments', 'revisions', 'thumbnail', 'post-formats', 'custom-fields' )
			);

			$argsSpeaker = array(
				'labels'				=> $labelsSpeaker,
				'public'				=> true,
				'exclude_from_search'	=> false,
				'publicly_queryable'	=> true,
				'show_ui'				=> true,
				'show_in_menu'			=> true,
				'show_in_nav_menus'		=> true,
				'show_in_admin_bar'		=> true,
				'query_var'				=> true,
				'rewrite'				=> array( 'slug' => __( 'speaker', WPPOLSKA_TEXT_DOMAIN ) ),
				'capability_type'		=> 'post',
				'has_archive'			=> true,
				'menu_position'			=> 31,
				'supports'				=> array( 'title', 'editor', 'thumbnail', 'custom-fields' )
			);

			$argsTalk = array(
				'labels'				=> $labelsTalk,
				'public'				=> true,
				'exclude_from_search'	=> false,
				'publicly_queryable'	=> true,
				'show_ui'				=> true,
				'show_in_menu'			=> true,
				'show_in_nav_menus'		=> true,
				'show_in_admin_bar'		=> true,
				'query_var'				=> true,
				'rewrite'				=> array( 'slug' => __( 'talk', WPPOLSKA_TEXT_DOMAIN ) ),
				'capability_type'		=> 'post',
				'has_archive'			=> true,
				'menu_position'			=> 30,
				'taxonomies'			=> array( 'wppolska_talk_tag' ),
				'supports'				=> array( 'title', 'editor', 'author', 'excerpt', 'comments', 'revisions', 'thumbnail', 'post-formats', 'custom-fields' )
			);

			register_post_type( 'wppolska_event', $argsEvent );
			register_post_type( 'wppolska_speaker', $argsSpeaker );
			register_post_type( 'wppolska_talk', $argsTalk );
		}

		public function wppolska_register_ctax()
		{
			$labelsEventCity = array(
				'name'              => 'Event Cities',
				'singular_name'     => 'Event City',
				'menu_name'         => 'Event Cities',
			);

			$labelsEventType = array(
				'name'              => 'Event Types',
				'singular_name'     => 'Event Type',
				'menu_name'         => 'Event Types',
			);

			$labelsTalkTag = array(
				'name'              => 'Talk Tags',
				'singular_name'     => 'Talk Tag',
				'menu_name'         => 'Talk Tags',
			);

			$argsEventCity = array(
				'labels'			=> $labelsEventCity,
				'public'			=> true,
				'show_ui'			=> true,
				'show_in_nav_menus' => true,
				'show_tagcloud'		=> true,
				'show_admin_column'	=> true,
				'query_var'			=> true,
				'rewrite'			=> array( 'slug' => __( 'event_city', WPPOLSKA_TEXT_DOMAIN ) ),
			);

			$argsEventType = array(
				'labels'			=> $labelsEventType,
				'public'			=> true,
				'show_ui'			=> true,
				'show_in_nav_menus' => true,
				'show_tagcloud'		=> true,
				'show_admin_column'	=> true,
				'query_var'			=> true,
				'rewrite'			=> array( 'slug' => __( 'event_type', WPPOLSKA_TEXT_DOMAIN ) ),
			);

			$argsTalkTag = array(
				'labels'			=> $labelsTalkTag,
				'public'			=> true,
				'show_ui'			=> true,
				'show_in_nav_menus' => true,
				'show_tagcloud'		=> true,
				'show_admin_column'	=> true,
				'query_var'			=> true,
				'rewrite'			=> array( 'slug' => __( 'talk_tag', WPPOLSKA_TEXT_DOMAIN ) ),
			);

			register_taxonomy( 'wppolska_event_city', 'wppolska_event', $argsEventCity );
			register_taxonomy( 'wppolska_event_type', 'wppolska_event', $argsEventType );
			register_taxonomy( 'wppolska_talk_tag', 'wppolska_talk', $argsTalkTag );
		}

	}

}

$WPPolskaMainClass = new WPPolskaMainClass();

?>
