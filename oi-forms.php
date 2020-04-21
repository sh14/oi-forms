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

//use WP_REST_Server;


function pr( $line, $d = [], $specialchars = false ) {


	ob_start();

	print_r( $d );
	$out = ob_get_contents();
	ob_clean();
	if ( ! empty( $specialchars ) ) {
		$out = htmlspecialchars( $out );
	}
	echo '<pre>';
	echo 'Line: ' . $line . '<br>';
	echo $out;
	echo '</pre><hr>';
}

function is_json( $data ) {
	json_decode( $data, true );

	return json_last_error() == JSON_ERROR_NONE;
}

require 'Init.php';
// init
Init::init();
require 'includes/forms.php';
require 'includes/Element.php';
require 'includes/Gutenberg.php';
require 'includes/shortcode.php';
require 'includes/ajax.php';
//require 'includes/rest-api.php';


// require forms from active theme
require_all_in( Init::$data['theme_path'] . Init::$data['slug'] );

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
	$class = str_replace( '-', '\\', $class );

	// если указанный класс существует
	if ( class_exists( $class ) ) {

		// создается эксемпляр класса
		$form = new $class( $data );

		// если запрошен один из разрешенных методов и он определен
		if ( in_array( $data['request'], [ 'get', 'update', ] ) && method_exists( $form, $data['request'] ) ) {
//			return $data;

			// возвращается рузултат выполнения
			return $form->{$data['request']}( $data );
		}

		return [
			'errors' => [
				sprintf( __( '"%s" property does not allowed or does not set.', Init::$data['domain'] ), $data['request'] )
			],
		];
	}

	return [
		'errors' => [
			sprintf( __( 'The form "%s" is not found or does not exist.', Init::$data['domain'] ), $class )
		],
	];
}

/**
 * Function that creates the classes chain in BEM style
 *
 * @param string $classTrail
 * @param bool   $toAttribute
 * @param bool   $isArray
 *
 * @return array|string
 */
function bem( $classTrail = '', $toAttribute = false, $isArray = true ) {
	if ( empty( $classTrail ) ) {
		return '';
	}

	$trails = [];

	$classTrails = array_values( array_filter( explode( ' ', $classTrail ) ) );

	foreach ( $classTrails as $classTrail ) {

		$classTrail = array_values( array_filter( explode( '.', $classTrail ) ) );

		$mixins  = [];
		$classes = [];
		$block   = '';
		$count   = sizeof( $classTrail );
		foreach ( $classTrail as $i => $item ) {
			if ( 0 === $i ) {
				$block = $classTrail[ $i ];

				if ( 1 == $count ) {
					$classes[] = $block;
				}
			}
			else {

				if ( 0 === strpos( $classTrail[ $i ], '_' ) ) {
					$mixins[] = $classTrail[ $i ];
					if ( 2 == $count ) {
						$classes[] = $block;
					}
				}
				else {
					$classes[] = $block . '__' . $classTrail[ $i ];
				}
			}
		}

		foreach ( $classes as $i => $class ) {
			foreach ( $mixins as $j => $mixin ) {
				$classes[] = $classes[ $i ] . $mixin;
			}
		}

		$trails = array_merge( $trails, $classes );
	}

	if ( ! empty( $toAttribute ) ) {
		return ' class="' . join( ' ', $trails ) . '" ';
	}

	// if $isArray is false
	if ( empty( $isArray ) ) {
		$trails = join( ' ', $trails );
	}

	// return classes as array
	return $trails;
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
	wp_register_script(
		'oijq',
		get_site_url() . '/oijq/js/oijq.js',
		[],
		Init::$data['version'],
		true
	);
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\register_scripts' );


// eof
