<?php
/**
 * Date: 26.08.18
 * @author Isaenko Alexey <info@oiplug.com>
 */

namespace forms;

/**
 * Return current plugin name.
 *
 * @return string
 */
function get_plugin_name() {
	return array_slice( explode( '/', plugin_basename( __FILE__ ) ), 0, 1 )[0];
}

/**
 * Return current plugin path.
 *
 * @return string
 */
function get_plugin_path() {
	$plugin_name = get_plugin_name();
	$plugin_path = explode( $plugin_name, plugin_dir_path( __FILE__ ) )[0] . '/' . $plugin_name . '/';

	return $plugin_path;
}

/**
 * Return current plugin url.
 *
 * @return string
 */
function get_plugin_url() {
	return plugins_url() . '/' . get_plugin_name() . '/';
}

/**
 * Return template as a string.
 *
 * @param  $file string - name of file
 * @param  $atts array - attributes used in the file
 *
 * @return string
 */
function get_template_part( $file, $atts = array() ) {

	ob_start();
	$pathes = apply_filters( __NAMESPACE__ . '_template_path', array(
		// путь к файлу в папке с темой
		trailingslashit( get_stylesheet_directory() ) . $file . '.php',
		// путь к файлу в папке плагина в теме
		trailingslashit( get_stylesheet_directory() ) . get_plugin_name() . '/' . $file . '.php',
		// папка в самом плагине в спец.папке
		get_plugin_path() . 'templates/' . $file . '.php',
		// путь к файлу в корне плагина
		get_plugin_path() . $file . '.php',
	), get_plugin_name(), $file );
	foreach ( $pathes as $path ) {
		//print $path.'<br>';
		if ( file_exists( $path ) ) {
			include $path;

			return ob_get_clean();
		}
	}

	return '';
}

/**
 * Загрузка шаблонов для JS
 *
 * @param $file
 * @param $atts
 *
 * @return string
 */
function add_templates( $file, $atts ) {

	$out = '';

	// todo: проверить целесообразность данной проверки
	if ( are_you( 'contributor' ) ) {

		foreach ( $atts as $key => $value ) {
			if ( is_numeric( $key ) ) {
				$value = '<%=' . $key . '%>';
			}
			$atts[ $key ] = $value;
		}

		$out = '<script id="' . $file . '" type="text/ejs">' . get_template_part( $file, $atts ) . '</script>';

	}

	return $out;
}

//add_action( 'wp_footer', __NAMESPACE__ . '\add_templates' );


/**
 * Функция определения роли текущего пользователя
 *
 * @param $role
 *
 * @return bool
 */
function are_you( $role ) {
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

// eof
