<?php
/**
 * Date: 2019-02-24
 * @author Isaenko Alexey <info@oiplug.com>
 */

namespace forms;

/**
 * Функция обработки запросов к формам по средствам WP-AJAX
 */
function forms_ajax() {
	$data = [];

	// если данные вообще были переданы
	if ( !(empty( $_POST['action'] ) && empty( $_GET['action'] ) )) {

		// определяется метод передачи
		if ( ! empty( $_POST['action'] ) && 'forms_ajax' == $_POST['action'] ) {
			$data = $_POST;
		} else if ( ! empty( $_GET['action'] ) && 'forms_ajax' == $_GET['action'] ) {
			$data = $_GET;
		}

		// если данные получены
		if ( ! empty( $data ) ) {

			// set flag of transfer method
			$data['transfer_method'] = 'ajax';

			// выполняется обработка данных
			$result = get_forms( $data );

			if ( ! empty( $result ) ) {

				// возвращается результат обработки
				wp_send_json_success( $result );
			}
		}
	}
	wp_send_json_error();
}

add_action( 'wp_ajax_' . __NAMESPACE__ . '_ajax', __NAMESPACE__ . '\\forms_ajax' );
add_action( 'wp_ajax_nopriv_' . __NAMESPACE__ . '_ajax', __NAMESPACE__ . '\\forms_ajax' );

// eof
