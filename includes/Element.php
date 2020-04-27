<?php
/**
 * Date: 2019-05-05
 * @author Isaenko Alexey <info@oiplug.com>
 */


namespace Elements;

class Element {
	public static $domain = __NAMESPACE__;
	public static $attributes = [];
	public static $ids = [];
	public static $id = '';
	public static $errors = [];
	// input types - https://www.w3schools.com/html/html_form_input_types.asp
	public static $inputTypes = [
//		'button', // except button, because there is the same tag
		'checkbox',
		'color',
		'date',
		'datetime-local',
		'email',
		'file',
		'hidden',
		'image',
		'month',
		'number',
		'password',
		'radio',
		'range',
		'reset',
		'search',
		'submit',
		'tel',
		'text',
		'time',
		'url',
		'week',
	];

	protected static function init() {
		self::$attributes = self::attributes();
	}

	protected static function attributes() {
		return [
			'autofocus' => [
				// type
				'type'      => 'Boolean',
				// default value
				'default'   => false,
				// hide if value is empty
				'hideEmpty' => true,
				// points that field should not be empty
				'required'  => false,
			],
			'disabled'  => [
				'type'      => 'Boolean',
				'default'   => false,
				'hideEmpty' => true,
				'required'  => false,
			],
			'selected'  => [
				'type'      => 'Boolean',
				'default'   => false,
				'hideEmpty' => true,
				'required'  => false,
			],
			'checked'   => [
				'type'      => 'Boolean',
				'default'   => false,
				'hideEmpty' => true,
				'required'  => false,
			],
			'multiple'  => [
				'type'      => 'Boolean',
				'default'   => false,
				'hideEmpty' => true,
				'required'  => false,
			],
			'required'  => [
				'type'      => 'Boolean',
				'default'   => false,
				'hideEmpty' => true,
				'required'  => false,
			],
			'form'      => [
				'type'      => 'String',
				'default'   => '',
				'hideEmpty' => true,
				'required'  => false,
			],
			'size'      => [
				'type'      => 'Number',
				'default'   => 1,
				'hideEmpty' => true,
				'required'  => false,
			],
			'id'        => [
				'type'      => 'String',
				'default'   => '',
				'hideEmpty' => true,
				'required'  => true,
			],
			'name'      => [
				'type'      => 'String',
				'default'   => '',
				'hideEmpty' => true,
				'required'  => true,
			],
			'class'     => [
				'type'      => 'Array',
				'default'   => '',
				'hideEmpty' => true,
				'required'  => false,
			],
			'style'     => [
				'type'      => 'Array',
				'default'   => '',
				'hideEmpty' => true,
				'required'  => false,
				// first for elements(example: classes or style properties),
				// second for key with value(example: color:red), third - name part(example: data-some-one)
				'delimiter' => [ ';', ':', '-', ],
			],
		];
	}

	/**
	 * Processing of tag's attributes
	 *
	 * @param array  $elementAttributes
	 * @param array  $attributesList
	 * @param string $prefix
	 *
	 * @return array - list of lines should looks like 'key="value"'
	 */
	private static function prepareAttributes( array $elementAttributes, $attributesList = [], $prefix = '' ) {

		// if there is a prefix, then the attribute is a composite
		$keyPrefix = ! empty( $prefix ) ? $prefix . '-' : '';

		// get attributes options
		$attributes = self::attributes();

		// loop for user attributes
		foreach ( $elementAttributes as $key => $value ) {
			$key = strtolower( $key );

			// if element has default attributes
			if ( ! empty( $attributes[ $key ] ) ) {
				// if attribute should be an array and default attribute has delimiter and given attribute is not an array
				if ( 'array' == $attributes[ $key ]['type'] && ! empty( $attributes[ $key ]['delimiter'] ) && ! is_array( $value ) ) {
					// convert value to array with given delimiter
					$value = explode( $attributes[ $key ]['delimiter'], $value );
				}
			}

			// if attribute value is an array then it is a composite attribute like a "data"
			if ( is_array( $value ) ) {

				// if the attribute type is defined, and it is an array, then it can be a "class"
				if ( ! empty( $attributes[ $key ]['type'] ) && 'Array' == $attributes[ $key ]['type'] ) {

					// element delimiter definition
					$delimiters = ! empty( $attributes[ $key ]['delimiter'] ) ? $attributes[ $key ]['delimiter'] : [ ' ' ];

					// if the delimiter is not an array
					if ( ! is_array( $delimiters ) ) {

						// delimiter is converted to an array with one element
						$delimiters = [ $delimiters ];
					}

					//if there are more then one delimiters then the first element is determined and removed from the list
					$delimiter = sizeof( $delimiters ) > 1 ? array_shift( $delimiters ) : $delimiters[0];

					// the array is converted to a string with specific delimiters
					$value = implode( $delimiter, self::prepareAttributeValues( $value, $delimiters ) );
				}
				else {

					// loop for inner list in the method
					$attributesList = self::prepareAttributes( $value, $attributesList, $keyPrefix . $key );

					// go to the next iteration
					continue;
				}
			}

			// if an element is undefined by default or a prefix is set then loop for user attributes
			if ( empty( $attributes[ $key ] ) || ! empty( $prefix ) ) {

				// attribute type overrides to string
				$attributes[ $key ]['type'] = 'String';

				// if the value is empty, then the attribute is not hidden
				$attributes[ $key ]['hideEmpty'] = false;

				// attribute is not required
				$attributes[ $key ]['required'] = false;
			}


			// if the attribute is required and it is empty
			if ( true == $attributes[ $key ]['required'] && false === $attributes[ $key ]['hideEmpty'] && empty( $value ) ) {

				// add an error
				self::addError( __( sprintf( 'Key "%s" is required.', $key ), self::$domain ) );

				// return empty value
				return [];
			}

			// if the value is empty
			if ( empty( $value ) ) {

				// if the attribute should be hidden
				if ( true == $attributes[ $key ]['hideEmpty'] ) {

					// go to the next iteration
					continue;
				}
				else // if default value is set
					if ( ! empty( $attributes[ $key ]['default'] ) ) {

						// set default value as $value
						$value = $attributes[ $key ]['default'];
					}
			}

			// if attribute type is
			switch ( $attributes[ $key ]['type'] ) {
				case 'Boolean':

					// if it is true by default or user set something
					if ( true == $attributes[ $key ]['type'] || ! empty( $value ) ) {

						// add the attribute name
						$attributesList[ $key ] = $key;
					}
					break;
				case 'String':

					// text value without converting htmlspecialchars to avoid converting errors
					$attributesList[ $keyPrefix . $key ] = ( $value );
					break;
				case 'Number':

					// add the attribute name with number as value
					$attributesList[ $key ] = floatval( $value );
					break;
				case 'Array':

					// add the attribute name with text as value
					$attributesList[ $key ] = htmlspecialchars( $value );
					break;
			}

			// if it's not included attribute and element has "name" attribute and donn't has "id"
			if ( empty( $prefix ) && 'name' == $key && empty( $elementAttributes['id'] ) ) {
				$id = $elementAttributes['name'];
				$id = str_replace( '[]', '!', $id );
				if ( ! isset( self::$ids[ $id ] ) ) {
					self::$ids[ $id ] = 0;
					// if ! at the end, then don't put -
					self::$id = strpos( $id, '!' ) == strlen( $id ) - 1 ? str_replace( '!', '', $id ) : str_replace( '!', '-', $id );
				}
				else {
					self::$ids[ $id ] ++;
					// if ! at the end, then don't put -
					self::$id = strpos( $id, '!' ) == strlen( $id ) - 1 ? str_replace( '!', '', $id ) : str_replace( '!', '-', $id );
					self::$id .= '-' . self::$ids[ $id ];
				}

				$attributesList['id'] = self::$id;
			}

		}


		return $attributesList;
	}

	/**
	 * Convert delimiter to an array
	 *
	 * @param $delimiter
	 *
	 * @return array
	 */
	private static function getDelimiter( $delimiter ) {
		// if the delimiter is not an array
		if ( ! is_array( $delimiter ) ) {

			// delimiter is converted to an array with one element
			$delimiter = [ $delimiter ];
		}

		return $delimiter;
	}

	/**
	 * Processing of attributes values lists. Classes or styles for example.
	 *
	 * @param array        $attributeValues
	 * @param string|array $delimiter
	 * @param array        $values
	 * @param string       $prefix
	 *
	 * @return array - list of string values, generated for the specified attribute
	 */
	private static function prepareAttributeValues( array $attributeValues, $delimiter = ' ', $values = [], $prefix = '' ) {

		$delimiter = self::getDelimiter( $delimiter );

		// loop for attribute values
		foreach ( $attributeValues as $key => $value ) {

			// if value is not an array
			if ( ! is_array( $value ) ) {

				// getting the first delimiter for value name component(border-width-top)
				$separator = $delimiter[0];
			}
			else {

				// getting the second delimiter for key with a value(color:red)
				$separator = $delimiter[1];
			}

			if ( ! empty( $prefix ) ) {
				// if it is a numeric array
				if ( is_numeric( $key ) ) {

					// define part of a name without the index
					$keyPrefix = $prefix . $separator;
				}
				else {

					// define part of a name with a key
					$keyPrefix = $prefix . $key . $separator;
				}
			}
			else {
				// if it is a numeric array
				if ( is_numeric( $key ) ) {

					// define part of a name without the index
					$keyPrefix = '';
				}
				else {

					// define part of a name with a key
					$keyPrefix = $key . $separator;
				}
			}

			// if the value is an array
			if ( is_array( $value ) ) {

				// call the function with parameters
				$values = self::prepareAttributeValues( $value, $delimiter, $values, $keyPrefix );

				// go to the next iteration
				continue;
			}

			// add name with value to list
			$values[] = $keyPrefix . $value;

		}

		return $values;
	}

	/**
	 * Combining attributes to string for adding into a tag
	 *
	 * @param $attributes
	 *
	 * @return string
	 */
	public static function stringifyAttributes( $attributes ) {
		foreach ( $attributes as $key => $value ) {
			$attributes[ $key ] = $key . '=' . '"' . $value . '"';
		}

		return join( ' ', $attributes );
	}

	/**
	 * Converts an array describing an element to HTML code
	 *
	 * @param array $element - an array describing an element
	 *
	 * @return string
	 */
	protected static function convertToHtml( array $element ) {

		// if attributes has been set
		$attributes = ! empty( $element['attributes'] ) ? self::stringifyAttributes( $element['attributes'] ) : '';

		// join strings to body of an element
		$html = join( ' ', [ $element['type'], $attributes ] );

		// get element content
		$content = isset( $element['content'] ) ? self::get( $element['content'] ) : '';

		// if the element is paired
		if ( ! in_array( $element['type'], [ 'input', ] ) ) {

			// pair element formation
			$html = "<{$html}>{$content}</{$element['type']}>";
		}
		else {
			// formation of unpaired elements
			$html = empty( $element['before'] ) ? "<{$html}/>{$content}" : "{$content}<{$html}/>";
		}

		// if the HTML pattern exists
		if ( ! empty( $element['html'] ) ) {

			// insert element to pattern
			$html = self::useHtmlPattern( $element, $html );
		}

		return $html;
	}

	/**
	 * Inserting element string to given HTML pattern
	 *
	 * @param array  $element
	 * @param string $elementHtml
	 *
	 * @return string - string of HTML code
	 */
	protected static function useHtmlPattern( array $element, $elementHtml = '' ) {

		// defining of HTML with pseudo elements
		$html = $element['html'];

		// loop for keys with a certain priority
		foreach ( [ 'vars', 'attributes' ] as $type ) {

			if ( ! empty( $element[ $type ] ) ) {
				// loop for data from defined source
				foreach ( $element[ $type ] as $key => $value ) {

					if ( is_string( $key ) ) {

						// replace pseudo values with actual values
						$html = str_replace( "%{$key}%", $value, $html );
					}
				}
			}
		}

		// replace pseudo element with actual HTML element
		$html = str_replace( "%%", $elementHtml, $html );

		return $html;
	}

	/**
	 * Prepare element array
	 *
	 * @param array      $element
	 * @param int|string $index
	 *
	 * @return array
	 */
	protected static function prepareElement( array $element, $index ) {

		$element = self::setElementProps( $element, $index );

		// if attributes is not an array
		if ( ! empty( $element['attributes'] ) && ! is_array( $element['attributes'] ) ) {
			// add an error
			self::addError( __( sprintf( 'Attributes must have an array type. Check the "%s" in "%s" element.', $element['attributes'], $element['type'] ), self::$domain ) );

			return [];
		}

		// if user set input type as element type
		if ( in_array( $element['type'], self::$inputTypes ) ) {
			// set type as attribute
			$element['attributes']['type'] = $element['type'];
			// set correct element type
			$element['type'] = 'input';
		}

		// if user doesn't set type for input
		if ( 'input' == $element['type'] && empty( $element['attributes']['type'] ) ) {
			// set default input type
			$element['attributes']['type'] = 'text';
		}

		// if attributes has been set
		if ( ! empty( $element['attributes'] ) ) {

			// prepare attributes
			$element['attributes'] = self::prepareAttributes( $element['attributes'] );
		}

		if ( ! empty( $element['content'] ) ) {
			$element['content'] = self::prepare( $element['content'] );
		}

		return $element;
	}

	/**
	 * Prepare all elements of given array
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public static function prepare( $data ) {
		static::init();

		// if data is not an array, it means that $data is a content like a string, label, for example
		if ( ! is_array( $data ) ) {
			return $data;
		}
		// list of generated HTML elements
		$elementsList = [];

		// loop for elements
		foreach ( $data as $i => $element ) {

			// add generated element to the list
			$elementsList[] = self::prepareElement( $element, $i );
		}

		return $elementsList;
	}

	/**
	 * Set element name from a shorthands.
	 *
	 * @param array      $element
	 * @param int|string $index
	 *
	 * @return array
	 */
	private static function setElementName( array $element, $index ) {
		// if element has a key instead of index
		if ( ! is_numeric( $index ) ) {
			if ( empty( $element['attributes'] ) ) {
				$element['attributes'] = [];
			}
			// set element type equal to element key
			$element['attributes']['name'] = $index;
		}

		return $element;
	}

	/**
	 * Set element name from a shorthands.
	 *
	 * @param array $element
	 *
	 * @return array
	 */
	private static function setElementType( array $element ) {
		// loop for element items
		foreach ( $element as $key => $value ) {
			// if we have a string value with index instead of key
			if ( is_numeric( $key ) && is_string( $value ) ) {
				// set the value as an element type
				$element['type'] = $value;
				unset( $element[ $key ] );
			}
		}

		// if element type is empty
		if ( empty( $element['type'] ) ) {
			// set div as a type
			$element['type'] = 'div';
		}

		$element['type'] = strtolower( $element['type'] );

		return $element;
	}

	/**
	 * If element attributes has not be set in a right way then get them from a shorthand.
	 *
	 * @param array $element
	 *
	 * @return array
	 */
	private static function setElementAttributes( array $element ) {
		// loop for element items
		foreach ( $element as $key => $value ) {
			// if we have a string value with index instead of key
			if ( is_numeric( $key ) && is_array( $value ) ) {
				// set the value as an element type
				$element['attributes'] = $value;
				unset( $element[ $key ] );
			}
		}

		if ( empty( $element['attributes'] ) ) {
			$element['attributes'] = [];
		}

		return $element;
	}

	/**
	 * Set element properties.
	 *
	 * @param array      $element
	 * @param int|string $index
	 *
	 * @return array
	 */
	private static function setElementProps( array $element, $index ) {

		$element = self::setElementType( $element );
		$element = self::setElementAttributes( $element );
		$element = self::setElementName( $element, $index );

		return $element;
	}

	/**
	 * Converting elements set to list of html elements strings
	 *
	 * @param string|array $data - elements set
	 *
	 * @return string
	 */
	public static function get( $data ) {
		static::init();

		// if data is not an array, it means that $data is a content like a string, label, for example
		if ( ! is_array( $data ) || empty( $data ) ) {
			return $data;
		}

		// prepare data for converting to HTML
		$data = self::prepare( $data );

		if ( self::isErrors() ) {

			return self::getErrors();
		}

		// list of generated HTML elements
		$elementsList = [];

		if ( is_array( $data ) ) {
			// loop for elements
			foreach ( $data as $i => $element ) {

				// add generated element to the list
				$elementsList[] = self::convertToHtml( $element );
			}
		}
		else {
			$elementsList[] = $data;
		}

		if ( self::isErrors() ) {

			return self::getErrors();
		}

		// convert to an array
		$elementsList = implode( PHP_EOL, $elementsList );

		return $elementsList;
	}

	/**
	 * Adding an error to the list.
	 *
	 * @param string $error
	 */
	protected static function addError( string $error ) {
		if ( ! empty( $error ) ) {
			self::$errors[] = $error;
		}
	}

	/**
	 * Check if there errors.
	 *
	 * @return bool
	 */
	public static function isErrors() {
		return ! empty( self::$errors );
	}

	/**
	 * Get errors list.
	 *
	 * @param bool $asArray
	 *
	 * @return array|bool|string
	 */
	public static function getErrors( $asArray = false ) {
		if ( ! empty( self::$errors ) ) {
			if ( empty( $asArray ) ) {
				return join( PHP_EOL, array_map( function ( $item ) {
					return '<p class="error">' . $item . '</p>';
				}, self::$errors ) );
			}
			else {
				return self::$errors;
			}
		}

		return false;
	}


}
// eof
