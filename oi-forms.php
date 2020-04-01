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


function pr($d){
	echo '<pre>';
	print_r($d);
	echo '</pre>';
}
function is_json( $data ) {
	json_decode( $data, true );

	return json_last_error() == JSON_ERROR_NONE;
}

require 'init.php';
require 'includes/templating.php';
//require 'includes/FormBuilder.php';
require 'includes/forms.php';
require 'includes/Element.php';
require 'shortcode.php';
require 'ajax.php';
require 'rest-api.php';
//echo get_stylesheet().'/'.get_plugin_name();die;
// require forms from active theme
require_all_in( WP_CONTENT_DIR . '/themes/' . get_stylesheet() . '/' . get_plugin_name() );

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
 * Определение даты, которая должна быть указана у публикации
 * дата перестает меняться после того, как статья публикуется, если при этом не устанавливается дата позже текущей
 *
 * @param $post_id
 * @param $status
 *
 * @return int|string
 */
function get_post_publication_date( $post_id, $status ) {
	$post         = get_post( $post_id, ARRAY_A );
	$current_time = current_time( 'mysql' );

	// если пост публикуется и при этом дата меньше или равна текущей
	if ( 'publish' != $post['post_status'] && 'publish' == $status && strtotime( $post['post_date'] ) <= strtotime( $current_time ) ) {
		$date = $current_time;
	}
	else {

		// дата отложенной публикации
		$date = $post['post_date'];
	}


	return $date;

}

/**
 * Функция определения роли текущего пользователя
 *
 * @param $role
 *
 * @return bool
 */
function isRole( $role ) {
	switch ( $role ) {

		// администратор всего мультисайта
		case 'superadmin':
			if ( current_user_can( 'create_sites' ) ) {
				return true;
			}
			break;

		// администратор на обычном сайте
		case 'admin':
			if ( current_user_can( 'activate_plugins' ) ) {
				return true;
			}
			break;
		case 'editor':
			if ( current_user_can( 'edit_private_posts' ) ) {
				return true;
			}
			break;
		case 'author':
			if ( current_user_can( 'edit_published_posts' ) ) {
				return true;
			}
			break;
		case 'contributor':
			if ( current_user_can( 'edit_posts' ) ) {
				return true;
			}
			break;
		case 'subscriber':
			if ( current_user_can( 'read' ) ) {
				return true;
			}
			break;
	}

	return false;
}

/**
 * Определение - может ли текущий пользователь редактировать указанный пост
 *
 * @param $post_id
 *
 * @return bool
 */
function current_user_can_edit( $post_id ) {
	if ( isRole( 'admin' ) ) {
		return true;
	}

	// получение данных поста из бд
	$post = get_post( $post_id, ARRAY_A );

	// если текущий пользователь является автором поста или редактором, и при этом пост не опубликован
	if ( ( get_current_user_id() == $post['post_author'] || isRole( 'editor' ) ) && 'publish' != $post['post_status'] ) {

		return true;
	}

	return false;
}


/**
 * Подключение всех файлов из указанной папки
 *
 * @param $dir
 */
function require_all_in( $dir ) {

	$dir = '/' . trim( $dir, '/' ) . '/';

	if ( is_dir( $dir ) ) {
		$files = scandir( $dir );
//		print '<pre class="hidden">';
//		print_r( $dir );
//		print_r( $files );
//		print '</pre>';

		if ( ! empty( $files ) && is_array( $files ) ) {

			// перебор файлов в папке
			foreach ( $files as $filename ) {
				$path = $dir . $filename;

				if ( file_exists( $path ) && is_file( $path ) ) {

					// опреление расширения файла
					$ext = explode( '.', $filename );
					$ext = end( $ext );

					// если это php файл
					if ( 'php' == $ext ) {

						// файл подключается
						require $path;
					}
				}
			}
		}
	}
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
/*
function forms_endpoints( $endpoints ) {
	// эндпоинт для работы с
	return array_merge( $endpoints, [
		// Форма публикации
		'post'             => [
			'methods' => [ WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ],
		],
	] );
}

add_filter( 'forms_endpoints_filter', __NAMESPACE__ . '\\' . 'forms_endpoints', 10, 1 );
*/

function register_scripts() {

	wp_enqueue_script(
		'oijq',
		get_site_url().'/oijq/oijq.js',
		[],
		Init::$data['version'],
		true
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\register_scripts' );


// eof
