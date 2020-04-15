<?php
/**
 * Date: 2019-02-24
 * @author Isaenko Alexey <info@oiplug.com>
 */

namespace forms;

/**
 * Шорткод форм для вставки в контент
 *
 * @param $atts
 *
 * @return array|string
 */
function shortcode( $atts ) {

	// указание на то, что в ответе должен быть возвращен html формы
	$atts['response'] = 'html';

	// получение данных
	$data = get_forms( $atts );

	// если ответ содержит ошибки
	if ( ! empty( $data['errors'] ) ) {

		// каждая строка обрамляется тегами
		$data = array_map( function ( $value ) {

			return "<p>{$value}</p>";
		}, $data['errors'] );

		// массив переводится в строку
		$data = implode( '', $data );

		// весь список помещается в блок
		$data = '<div class="info info-eror info-danger">' . $data . '</div>';
	}

	if ( empty( $data ) ) {
		$data = '';
	}

	return $data;
}

add_shortcode( 'form', __NAMESPACE__ . '\shortcode' );

/**
 * Updating form via classic send data mode with page reload
 *
 * @return bool
 */
function update_forms() {
	$data = [];

	// if admin-ajax.php was requested, kill that function to allow wp ajax to work
	if ( ! empty( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'admin-ajax.php' ) > 0 ) {
		return false;
	}
	if ( empty( $_POST['action'] ) && empty( $_GET['action'] ) ) {
		return false;
	}
	if ( ! empty( $_POST['action'] ) && 'forms_ajax' == $_POST['action'] ) {
		$data = $_POST;
	} else if ( ! empty( $_GET['action'] ) && 'forms_ajax' == $_GET['action'] ) {
		$data = $_GET;
	}

	if ( ! empty( $data ) ) {

		// преобразование id формы в имя класса с пространством имен
		$class = str_replace( '/', '\\', $data['form_id'] );
		$class = str_replace( '-', '\\', $class );

		// если указанный класс существует
		if ( class_exists( $class ) ) {

			// создается эксемпляр класса
			$form = new $class( $data );

			// если запрошен один из разрешенных методов и он определен
			if ( in_array( $data['request'], [ 'update', ] ) && method_exists( $form, $data['request'] ) ) {

				// возвращается рузултат выполнения
				$form->{$data['request']}( $data );
			}
		}
	}
	return false;
}

add_action( 'init', __NAMESPACE__ . '\update_forms' );

// eof
