<?php
/**
 * Date: 10/02/2019
 * @author Isaenko Alexey <info@oiplug.com>
 */

Namespace forms;

/**
 * Class contains plugin information.
 *
 * Class Init
 * @package forms
 */
class Init {
	public static $data = array();

	private static function get_table_prefix() {
		global $wpdb;

		return apply_filters('plugin_custom_tables_prefix',$wpdb->prefix . __NAMESPACE__ . '_');
	}

	public static function init() {

		// current plugin directory
		self::$data['table_prefix'] = self::get_table_prefix();

		// current plugin directory
		self::$data['path_dir'] = plugin_dir_path( __FILE__ );

		// current plugin slug
		self::$data['slug'] = plugin_basename( self::$data['path_dir'] );

		// full path to current plugin
		self::$data['path'] = self::$data['path_dir'] . self::$data['slug'] . '.php';

		// current plugin url directory
		self::$data['url'] = plugin_dir_url( __FILE__ );

		// current plugin 8kiB data
		$file_data  = get_file_data( self::$data['path'], [
			'version'     => 'Version',
			'name'        => 'Plugin Name',
			'link'        => 'Plugin URI',
			'description' => 'Description',
			'author'      => 'Author',
			'author_uri'  => 'Author URI',
			'domain'      => 'Text Domain',
			'domain_path' => 'Domain Path',
			'github_uri'  => 'GitHub Plugin URI',
		] );
		$data = self::$data;
		self::$data = array_merge( $data, $file_data );

		// current plugin url directory
		self::$data['theme_path'] = WP_CONTENT_DIR . '/themes/' . get_stylesheet() . '/' . $data['name'];
	}
}



// eof
