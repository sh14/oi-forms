<?php
/**
 * Plugin Name: Oi Forms
 * Plugin URI: https://oiplug.com/plugin/
 * Description: --
 * Author: Alexei Isaenko
 * Version: 1.0.0
 * Author URI: https://oiplug.com/members/isaenkoalexei
 * Text Domain: oi-forms
 * Domain Path: /language
 * Date: 2019-02-25
 * @author Isaenko Alexey <info@oiplug.com>
 */

namespace forms;

use WP_REST_Server;

function is_json( $data ) {
	json_decode( $data, true );

	return json_last_error() == JSON_ERROR_NONE;
}

require 'forms.php';
require 'shortcode.php';
require 'ajax.php';
require 'rest-api.php';


/**
 * Функция обработки запроса на получение данных формы
 *
 * @param $data
 *
 * @return array
 */
function get_forms( $data ) {
	$data = wp_parse_args( $data, [
		// значение опеределяет результат какого метода необходимо вернуть: update, get
		'request' => 'get',
	] );
	// преобразование id формы в имя класса с пространством имен
	$class = str_replace( '/', '\\', $data['form_id'] );


	// если указанный класс существует
	if ( class_exists( $class ) ) {

		// создается эксемпляр класса
		$form = new $class( $data );

		// если запрошен один из разрешенных методов и он определен
		if ( in_array( $data['request'], [ 'update', 'get' ] ) && method_exists( $form, $data['request'] ) ) {
//			return $data;

			// возвращается рузултат выполнения
			return $form->{$data['request']}( $data );
		}

		return [
			'errors' => [
				__( 'Свойство', __NAMESPACE__ )
				. ' "' . $data['request'] . '" '
				. __( 'не определено.', __NAMESPACE__ ),
			],
		];
	}

	return [
		'errors' => [
			__( 'Форма "' . $class . '" не найдена.', __NAMESPACE__ ),
		],
	];
}



/**
 * ПРИМЕР !!!
 *
 * Фильтр эндпоинтов форм
 * Получение данных формы:
 * GET: /wp-json/forms/---
 * params:
 *   'request' => 'fields', - определяет какое свойство или метод должен быть возвращен, получение полей - fields
 *
 * Сохранение формы:
 * POST: /wp-json/forms/post
 * params:
 *   'request' => 'update', - определяет какое свойство или метод должен быть возвращен, сохранение - update
 *
 * @param $endpoints
 *
 * @return array
 */
/*function forms_endpoints( $endpoints ) {
	// эндпоинт для работы с
	return array_merge( $endpoints, [
		// Форма публикации
		'post'             => [
			'methods' => [ WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ],
		],
	] );
}

add_filter( 'forms_endpoints_filter', __NAMESPACE__ . '\\' . 'forms_endpoints', 10, 1 );*/


// eof
