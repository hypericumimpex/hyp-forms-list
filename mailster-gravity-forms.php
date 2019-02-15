<?php
/*
Plugin Name: Mailster Gravity Forms
Plugin URI: https://github.com/hypericumimpex/hyp-forms-list/
Version: 1.0.4
License: GPLv2
Author: Romeo C.
Author URI: https://github.com/hypericumimpex/
Text Domain: mailster-gravityforms
Description: Integrates Mailster Newsletter Plugin with Gravity Forms to subscribe users with a Gravity Form.
Requires the Mailster Newsletter Plugin and the Gravity Forms plugin
*/

class MailsterGravitiyForm {

	private $plugin_path;
	private $plugin_url;

	public function __construct() {

		$this->plugin_path = plugin_dir_path( __FILE__ );
		$this->plugin_url = plugin_dir_url( __FILE__ );

		register_activation_hook( __FILE__, array( &$this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

		load_plugin_textdomain( 'mailster-gravityforms' );

		add_action( 'init', array( &$this, 'init' ) );

	}

	public function activate( $network_wide ) {}

	public function deactivate( $network_wide ) {}

	public function init() {

		add_filter( 'gform_after_submission', array( &$this, 'after_submission' ), 10, 2 );

		if ( is_admin() ) {

			add_filter( 'gform_form_settings_menu', array( &$this, 'settings_menu' ), 10, 2 );
			add_action( 'gform_form_settings_page_mailster', array( &$this, 'settings_page' ) );

			if ( isset( $_POST['gform_save_settings'] ) ) {
				$this->save();
			}
		}
	}

	public function after_submission( $entry, $form ) {

		// Mailster doesn't exists.
		if ( ! function_exists( 'mailster' ) ) { return; }

		// Mailster options are not defined.
		if ( ! isset( $form['mailster'] ) ) { return; }

		// form not active.
		if ( ! isset( $form['mailster']['active'] ) ) { return; }

		// condition check matches.
		if ( isset( $form['mailster']['conditional'] ) ) {

			// radio button.
			if ( isset( $form['mailster']['conditional_id'] ) ) {

				if ( isset( $entry[ $form['mailster']['conditional_id'] ] ) && ($entry[ $form['mailster']['conditional_id'] ] != $form['mailster']['conditional_field']) ) { return; }
				if ( ! isset( $entry[ $form['mailster']['conditional_id'] ] ) ) { return; }

				// checkbox.
			} else {

				if ( isset( $entry[ $form['mailster']['conditional_field'] ] ) && empty( $entry[ $form['mailster']['conditional_field'] ] ) ) { return; }
				if ( ! isset( $entry[ $form['mailster']['conditional_field'] ] ) ) { return; }
			}
		}

		$userdata = array();
		foreach ( $form['mailster']['map'] as $field_id => $key ) {
			if ( $key != -1 ) {
				$userdata[ $key ] = $entry[ $field_id ];
			}
		}

		if ( ! isset( $userdata['email'] ) ) { return; }

		$list_ids = $form['mailster']['lists'];

		if ( $subscriber = mailster( 'subscribers' )->get_by_mail( $userdata['email'] ) ) {
		} else {
			$userdata['status'] = isset( $form['mailster']['double-opt-in'] ) ? 0 : 1;
		}

		$overwrite = true;
		$merge = true;
		$subscriber_id = mailster( 'subscribers' )->add( $userdata, $overwrite, $merge );

		if ( ! is_wp_error( $subscriber_id ) ) {
			mailster( 'subscribers' )->assign_lists( $subscriber_id, $list_ids, false, $userdata['status'] ? true : false );
		}

	}


	public function page() {

		include $this->plugin_path . '/views/page.php';

	}

	public function settings_page() {

		GFFormSettings::page_header();

		include $this->plugin_path . '/views/page.php';

		GFFormSettings::page_footer();

	}

	public function settings_menu( $settings_tabs, $form_id ) {

		$settings_tabs[] = array(
			'name' => 'mailster',
			'label' => 'Mailster',
		);
		return $settings_tabs;
	}

	public function save() {

		if ( ! current_user_can( 'manage_options' ) ) { return; }

		if ( ! isset( $_POST['gform_save_form_settings'] ) || ! wp_verify_nonce( $_POST['gform_save_form_settings'], 'mailster_gf_save_form' ) ) { return; }

		$form_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : null;
		if ( ! $form_id ) { return; }

		$form = RGFormsModel::get_form_meta( $form_id );
		if ( ! $form ) { return; }

		$form['mailster'] = $_POST['mailster'];
		$conditional = explode( '|', $form['mailster']['conditional_field'] );

		if ( count( $conditional ) > 1 ) {
			$form['mailster']['conditional_id'] = array_shift( $conditional );
		}

		$form['mailster']['conditional_field'] = implode( '|',$conditional );

		RGFormsModel::update_form_meta( $form_id, $form );

	}
}

new MailsterGravitiyForm();
