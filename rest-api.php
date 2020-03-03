<?php
/**
 * Date: 2019-02-25
 * @author Isaenko Alexey <info@oiplug.com>
 */

namespace forms;

use WP_REST_Request;

/**
 * Регистрация эндпоинта для работы с формами
 */
function rest_api_endpoints() {

	// список эндпоинтов
	$endpoints = apply_filters( 'forms_endpoints_filter', [] );

	// перебор эндпоинтов
	foreach ( $endpoints as $endpoint => $data ) {

		// поверяется существование указанного класса, который должен лежать в томже пространстве имен
		if ( class_exists( '\\' . __NAMESPACE__ . '\\' . $endpoint ) ) {

			// определение функции обработки rest api запроса
			$data['callback'] = '\\' . __NAMESPACE__ . '\\' . 'forms_rest_api';

			// регистрация rest api эндпроинта
			register_rest_route( __NAMESPACE__, $endpoint, $data );
		}
	}
}

add_action( 'rest_api_init', __NAMESPACE__ . '\rest_api_endpoints' );

/**
 * Функция обработки REST-API запроса
 *
 * @param WP_REST_Request $request
 *
 * @return array
 */
function forms_rest_api( WP_REST_Request $request ) {

	return get_forms( $request->get_params() );
}

// eof
