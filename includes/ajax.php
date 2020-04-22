<?php
/**
 * Date: 2019-02-24
 * @author Isaenko Alexey <info@oiplug.com>
 */

namespace forms;

/**
 * Form processing via WP-AJAX
 */
function forms_ajax() {
	$data = [];

	// if data has been sent
	if ( !(empty( $_POST['action'] ) && empty( $_GET['action'] ) )) {

		// determine the method
		if ( ! empty( $_POST['action'] ) && 'forms_ajax' == $_POST['action'] ) {
			$data = $_POST;
		} else if ( ! empty( $_GET['action'] ) && 'forms_ajax' == $_GET['action'] ) {
			$data = $_GET;
		}

		// if we have the data
		if ( ! empty( $data ) ) {

			// set flag of transfer method
			$data['transfer_method'] = 'ajax';

			// process
			$result = get_forms( $data );

			if ( ! empty( $result ) ) {

				// send the result
				wp_send_json_success( $result );
			}
		}
	}
	wp_send_json_error();
}

add_action( 'wp_ajax_' . __NAMESPACE__ . '_ajax', __NAMESPACE__ . '\forms_ajax' );
add_action( 'wp_ajax_nopriv_' . __NAMESPACE__ . '_ajax', __NAMESPACE__ . '\forms_ajax' );

// eof
