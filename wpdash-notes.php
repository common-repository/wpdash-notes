<?php
/*
Plugin Name: WPDash Notes
Description: WPDash Notes est un plugin qui vous permet de créer des notes sur votre tableau de bord WordPress et sur ceux des autres utilisateurs.
Version: 1.3.5
Author: WPFormation, NicolasKulka, WPServeur
Author URI: https://wpformation.com/
Domain Path: languages
Text Domain: wpdash-notes
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Plugin constants
define( 'WPDASH_NOTES_VERSION', '1.3.5' );
define( 'WPDASH_NOTES_FOLDER', 'wpdash-notes' );
define( 'WPDASH_NOTES_BASENAME', plugin_basename( __FILE__ ) );

define( 'WPDASH_NOTES_URL', plugin_dir_url( __FILE__ ) );
define( 'WPDASH_NOTES_DIR', plugin_dir_path( __FILE__ ) );

require_once WPDASH_NOTES_DIR . 'autoload.php';

register_activation_hook( __FILE__, array( '\WPF\WPDash_Notes\Plugin', 'install' ) );

add_action( 'plugins_loaded', 'plugins_loaded_wpdash_notes' );
function plugins_loaded_wpdash_notes() {
	\WPF\WPDash_Notes\Plugin::get_instance();
	\WPF\WPDash_Notes\Pub::get_instance();

	load_plugin_textdomain( 'wpdash-notes', false, basename( rtrim( dirname( __FILE__ ), '/' ) ) . '/languages' );

	$message = __( 'Do you like plugin WPDash Notes ?<br> Thank you for taking a few seconds to note us on', 'wpdash-notes' );
	if ( 'fr_FR' === get_locale() ) {
		$message = 'Vous aimez l\'extension WPDash Notes ?<br>Merci de prendre quelques secondes pour nous noter sur';
	}

	new \WP_Review_Me(
		array(
			'days_after' => 10,
			'type'       => 'plugin',
			'slug'       => 'wpdash-notes',
			'message'    => $message,
			'link_label' => 'WordPress.org'
		)
	);

	if ( ! class_exists( 'WPF_Dashboard_News' ) ) {
		include_once( WPDASH_NOTES_DIR . 'classes/dashboard-news.php' );
	}

	if ( class_exists( 'WPF_Dashboard_News' ) && get_user_locale() === 'fr_FR' ) {
		$news_translations = array(
			'product_title'    => 'WPFormation',
			'item_prefix'      => __( 'WPFormation', 'wpdash-notes' ),
			'item_description' => __( 'WPFormation News', 'wpdash-notes' ),
			'dismiss_tooltip'  => __( 'Dismiss all WPFormation news', 'wpdash-notes' ),
			'dismiss_confirm'  => __( 'Are you sure you want to dismiss all WPFormation news forever?', 'wpdash-notes' ),
		);

		new WPF_Dashboard_News( 'https://wpformation.com/feed/', 'https://wpformation.com/', $news_translations );
	}
}

function checklistinpost_load_fa() {
	wp_enqueue_style( 'wpb-fa', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css' );
}

add_action( 'admin_enqueue_scripts', 'checklistinpost_load_fa' );
function checklistinpost_load_frontend_style() {
	wp_enqueue_style( 'checklist_in_post_css_frontend', WPDASH_NOTES_URL . 'assets/css/checklist_in_post_frontend.css' );
}

function checklistinpost_load_frontend_script() {

	wp_register_script(
		'checklist_in_post_js_frontend',
		WPDASH_NOTES_URL . 'assets/js/checklist_in_post_frontend.js',
		array( 'jquery' ),
		'',
		'true'
	);
	$options = array(
		'cookies' => ( get_option( 'cookies' ) ) ? true : false,
	);
	wp_localize_script( 'checklist_in_post_js_frontend', 'options', $options );

	wp_localize_script( 'checklist_in_post_js_frontend', 'postitnonce', array(
		'nonce' => wp_create_nonce( 'postit-ajax-nonce' ),
	) );

	wp_enqueue_script( 'checklist_in_post_js_frontend' );
}

function checklistinpost_enqueue_plugin_scripts( $plugin_array ) {
	//enqueue TinyMCE plugin script with its ID.
	$plugin_array["green_button_plugin"] = WPDASH_NOTES_URL . "assets/js/checklist_in_post.js";

	return $plugin_array;
}

//add_filter( "mce_external_plugins", "checklistinpost_enqueue_plugin_scripts" );

function checklistinpost_register_buttons_editor( $buttons ) {
	//register buttons with their id.
	array_push( $buttons, "green" );

	return $buttons;
}

//add_filter( "mce_buttons", "checklistinpost_register_buttons_editor" );

function checklistinpost_load_plugin_css() {
	wp_enqueue_style( 'checklist_in_post_css', WPDASH_NOTES_URL . 'assets/css/checklist_in_post.css' );
}

add_action( 'admin_enqueue_scripts', 'checklistinpost_load_plugin_css' );

function checklistinpost_start_shortcode( $attributes, $content = null ) {
	$content = "<div class='checklist_in_post'>" . $content . "</div>";

	return $content;
}

add_shortcode( 'checklist_in_post', 'checklistinpost_start_shortcode' );
add_action( 'admin_enqueue_scripts', 'checklistinpost_load_frontend_script' );
add_action( 'admin_enqueue_scripts', 'checklistinpost_load_frontend_style' );
add_action( 'admin_enqueue_scripts', 'checklistinpost_load_fa' );

// Soucis si note sans titre -> rendre titre obligatoire ?
// Soucis à l'installation / titre + content en EN