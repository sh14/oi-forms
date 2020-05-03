<?php
/**
 * Plugin Name: Oi Forms
 * Plugin URI: https://sh14.ru/plugin/oi-forms
 * Description: Core for form creation plugins. Generates forms from arrays.
 * Author: Alexei Isaenko
 * Version: 1.0.0
 * Author URI: https://sh14.ru/user/1
 * Text Domain: oi-forms
 * Domain Path: /language
 * Date: 2019-02-25
 */

namespace forms;

require 'Init.php';
// init
Init::init();
require 'includes/forms.php';
require 'modules/Element/Element.php';
require 'includes/Gutenberg.php';
require 'includes/shortcode.php';
require 'includes/ajax.php';
require 'includes/rest-api.php';


// require forms from active theme
require_all_in( Init::$data['theme_path'] . Init::$data['slug'] );

/**
 * Getting and processing form data.
 *
 * @param $data
 *
 * @return array
 */
function get_forms( $data ) {
	$data = wp_parse_args( $data, [
		// name of function which result we gonna get: update, get
		'request' => 'get',
	] );

	// convert form id to class name with namespace
	$class = str_replace( '/', '\\', $data['form_id'] );
	$class = str_replace( '-', '\\', $class );

	// pointed class doesn't exists
	if ( class_exists( $class ) ) {

		// create class object
		$form = new $class( $data );

		// if requested method is allowed and exists
		if ( in_array( $data['request'], [ 'get', 'update', ] ) && method_exists( $form, $data['request'] ) ) {
//			return $data;

			// result of pointed method
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
 * Check if user can do something.
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
 * Require all files from given directory.
 *
 * @param string $dir
 *
 * @return bool
 */
function require_all_in( $dir = '' ) {
	if ( empty( $dir ) ) {
		return false;
	}
	$dir = '/' . trim( $dir, '/' ) . '/';

	if ( is_dir( $dir ) ) {
		$files = scandir( $dir );

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
			return true;
		}
	}
}

// eof
