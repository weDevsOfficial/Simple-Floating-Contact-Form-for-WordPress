<?php

namespace WeDevs\WpFeather;

/**
 * The frontend class
 */
class Ajax {

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_ajax_wpfeather_' . WPFEATHER_AJAX_KEY, [ $this, 'handle_frontend_form' ] );
		add_action( 'wp_ajax_wpfeather_get_settings', [ $this, 'get_settings' ] );
		add_action( 'wp_ajax_wpfeather_settings', [ $this, 'handle_settings' ] );
		add_action( 'wp_ajax_nopriv_wpfeather_' . WPFEATHER_AJAX_KEY, [ $this, 'handle_frontend_form' ] );
	}

	/**
	 * Get the wpfeather settings from options table
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function get_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
                'type'    => 'error',
                'message' => __( 'Unauthorized', 'wpfeather' ),
            ] );
		}

		$result = get_option( 'wpfeather_settings' );

		if ( false === $result ) {
			wp_send_json_success( [
                'type'    => 'no_data',
                'message' => __( 'No data found', 'wpfeather' ),
            ] );
		} else {
			wp_send_json_success( [
				'type'     => 'success',
				'settings' => $result,
          ] );
		}
	}

	/**
	 * Save the wpfeather settings page
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
	            'type'    => 'error',
	            'message' => __( 'Unauthorized', 'wpfeather' ),
	        ] );
		}
		$recipient = ! empty( $_POST['recipient'] ) ? sanitize_email( $_POST['recipient'] ) : '';
		$sitekey   = ! empty( $_POST['sitekey'] ) ? sanitize_key( $_POST['sitekey'] ) : '';

		if ( empty( $recipient ) ) {
			wp_send_json_error( [
                'type'    => 'error',
                'message' => __( 'Recipient e-mail is required', 'wpfeather' ),
            ] );
		}

		$updated = wpfeather_update_option( 'wpfeather_settings', [
			'recipient' => $recipient,
			'sitekey'   => $sitekey,
		] );

		if ( $updated ) {
			wp_send_json_success( [
                'type'    => 'success',
                'message' => __( 'Saved successfully', 'wpfeather' ),
            ] );
		} else {
			wp_send_json_error( [
                'type'    => 'error',
                'message' => __( 'Error saving data', 'wpfeather' ),
            ] );
		}


	}

	/**
	 * Handle the frontend form submission. Sanitize form inputs and submits the formdata to a mail
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_frontend_form() {
		$nonce = ! empty( $_POST['nonce'] ) ? $_POST['nonce'] : '';

		// checking the nonce
		if ( ! wp_verify_nonce( $nonce, 'wpfeather_form' ) ) {
			wp_send_json_error( [
				'type'    => 'error',
				'message' => __( 'Not authorized', 'wpfeather' ),
			] );
		}
		$name    = ! empty( $_POST['fullName'] ) ? sanitize_text_field( $_POST['fullName'] ) : '';
		$email   = ! empty( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$message = ! empty( $_POST['message'] ) ? wp_kses_post( $_POST['message'] ) : '';

		if ( empty( $name ) || empty( $email ) || empty( $message ) ) {
			wp_send_json_error( [
                'type'    => 'error',
                'message' => __( 'Name, email and message is required', 'wpfeather' ),
            ] );
		}

		// define hook name beforehand
		$mailing_hook = 'wpfeather_mail_frontend_form_submission';

		if ( false === as_next_scheduled_action( $mailing_hook ) ) {
			// now schedule to send an email containing the frontend result
			as_enqueue_async_action(
				$mailing_hook,
				[
					'name'    => $name,
					'email'   => $email,
					'message' => $message,
				]
			);
		}

		// validation success
		wp_send_json_success( [
			'message' => __( 'Form submitted successfully', 'wpfeather' ),
		] );
	}
}
