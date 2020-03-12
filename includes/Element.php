<?php
/**
 * Date: 2019-05-05
 * @author Isaenko Alexey <info@oiplug.com>
 */


namespace Elements;


class Element {
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
				// тип
				'type'      => 'Boolean',
				// значение по умолчанию
				'default'   => false,
				// скрывать, если значение пусто
				'hideEmpty' => true,
				// значение атрибут обязательно должно быть указано
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
				// первый разделяет сами элементы(например классы или свойста стилей),
				// второй для ключа со значением(color:red), третий - составляющие имени
				'delimiter' => [ ';', ':', '-', ],
			],
		];
	}

	/**
	 * Обработка атрибутов тега
	 *
	 * @param array  $elementAttributes
	 * @param array  $attributesList
	 * @param string $prefix
	 *
	 * @return array - список строк вида 'ключ="значение"'
	 */
	private static function prepareAttributes( array $elementAttributes, $attributesList = [], $prefix = '' ) {

		// если указан префикс, атрибут составной
		$keyPrefix = ! empty( $prefix ) ? $prefix . '-' : '';

		// get attributes options
		$attributes = self::attributes();

		// перебор пользовательских атрибутов
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

			// если значение атрибута является массивом, значит это составной атрибут, типа data
			if ( is_array( $value ) ) {

				// если тип атрибута определен и он - массив, например class может быть массивом
				if ( ! empty( $attributes[ $key ]['type'] ) && 'Array' == $attributes[ $key ]['type'] ) {

					// определяется разделитель элементов
					$delimiters = ! empty( $attributes[ $key ]['delimiter'] ) ? $attributes[ $key ]['delimiter'] : [ ' ' ];

					// если разделитель не массив
					if ( ! is_array( $delimiters ) ) {

						// разделитель преобразуется в массив с одним элементом
						$delimiters = [ $delimiters ];
					}

					// если разделителей больше одного, определяется первый элемент и убирается из общего списка
					$delimiter = sizeof( $delimiters ) > 1 ? array_shift( $delimiters ) : $delimiters[0];

					// массив преобразуется в строку, разделенную указанным разделителем
					$value = implode( $delimiter, self::prepareAttributeValues( $value, $delimiters ) );
				}
				else {

					// метод перебирает вложенный список
					$attributesList = self::prepareAttributes( $value, $attributesList, $keyPrefix . $key );

					// осуществляется переход к следующей итерации
					continue;
				}
			}

			// если элемент не определен по умолчанию или указан префикс, значит идет перебор пользовательских атрибутов
			if ( empty( $attributes[ $key ] ) || ! empty( $prefix ) ) {

				// тип атрибута переопределяется на строку
				$attributes[ $key ]['type'] = 'String';

				// атрибут не скрывается при пустом значении
				$attributes[ $key ]['hideEmpty'] = false;

				// атрибут не являются обязательными
				$attributes[ $key ]['required'] = false;
			}


			// если атрибут обязателен и он пуст
			if ( true == $attributes[ $key ]['required'] && false === $attributes[ $key ]['hideEmpty'] && empty( $value ) ) {

				// add an error
				self::$errors[] = __( sprintf( 'Key "%s" is required.', $key ), __NAMESPACE__ );

				// return empty value
				return [];
			}

			// если значение определено как пустое или не указано
			if ( empty( $value ) ) {

				// если при отсутствии значения атрибут должен скрываться
				if ( true == $attributes[ $key ]['hideEmpty'] ) {

					// осуществляется переход к следующей итерации
					continue;
				}
				else // если указано значение по умолчанию
					if ( ! empty( $attributes[ $key ]['default'] ) ) {

						// значение по умолчанию устанавливается в качестве значения
						$value = $attributes[ $key ]['default'];
					}
			}

			// если тип атрибута
			switch ( $attributes[ $key ]['type'] ) {
				case 'Boolean':

					// по умолчанию включен или пользователь указал не пустое значение
					if ( true == $attributes[ $key ]['type'] || ! empty( $value ) ) {

						// добавляется название атрибута
						$attributesList[ $key ] = $key;
					}
					break;
				case 'String':

					// добавляется название атрибута с текстовым значением
					$attributesList[ $keyPrefix . $key ] = htmlspecialchars( $value );
					break;
				case 'Number':

					// добавляется название атрибута с числовым значением
					$attributesList[ $key ] = floatval( $value );
					break;
				case 'Array':

					// добавляется название атрибута с текстовым значением
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
	 * Обработка списков значений атрибутов, например классов или стилей
	 *
	 * @param array  $attributeValues
	 * @param string $delimiter
	 * @param array  $values
	 * @param string $prefix
	 *
	 * @return array - список строк значений, сформированных для указанного атрибута
	 */
	private static function prepareAttributeValues( array $attributeValues, $delimiter = ' ', $values = [], $prefix = '' ) {

//		$keyPrefix = '';

		// если разделитель не массив
		if ( ! is_array( $delimiter ) ) {

			// разделитель преобразуется в массив с одним элементом
			$delimiter = [ $delimiter ];
		}

		// перебор значений атрибута
		foreach ( $attributeValues as $key => $value ) {

			// если значением является не массив
			if ( ! is_array( $value ) ) {

				// берется первый разделитель - для составляющей имени значения(border-width-top)
				$separator = $delimiter[0];
			}
			else {

				// берется второй разделитель - для ключа со значением(color:red)
				$separator = $delimiter[1];
			}

			if ( ! empty( $prefix ) ) {
				// если массив нумерованный
				if ( is_numeric( $key ) ) {

					// определяется часть имени без индекса
					$keyPrefix = $prefix . $separator;
				}
				else {

					// определяется часть имени с ключом
					$keyPrefix = $prefix . $key . $separator;
				}
			}
			else {
				// если массив нумерованный
				if ( is_numeric( $key ) ) {

					// определяется часть имени без индекса
					$keyPrefix = '';
				}
				else {

					// определяется часть имени с ключом
					$keyPrefix = $key . $separator;
				}
			}

			// если значение является массивом
			if ( is_array( $value ) ) {

				// вызывается функция с параметрами
				$values = self::prepareAttributeValues( $value, $delimiter, $values, $keyPrefix );

				// осуществляется переход к следующей итерации
				continue;
			}

			// к списку значений добавляется имя со своим значением
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
		$content = ! empty( $element['content'] ) ? self::get( $element['content'] ) : '';

		// если элемент парный
		if ( ! in_array( $element['type'], ['input',] ) ) {

			// формирование парного элемента
			$html = "<{$html}>{$content}</{$element['type']}>";
		}
		else {
			// формирование непарного элемента
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

		// определение html с псевдопеременными
		$html = $element['html'];

		// перебор ключей с определением приоритета замены данных(сперва замена происходит из vars, затем из attributes)
		foreach ( [ 'vars', 'attributes' ] as $type ) {

			if ( ! empty( $element[ $type ] ) ) {
				// перебор данных из указанного источника
				foreach ( $element[ $type ] as $key => $value ) {

					// если значение не является массивом
					if ( is_string( $value ) ) {

						// в html заменяются все вхождения указанного ключа
						$html = str_replace( "%{$key}%", $value, $html );
					}
				}
			}
		}

		// в html заменяется псевдоключ элемента на его html эквивалент
		$html = str_replace( "%%", $elementHtml, $html );

		return $html;
	}

	/**
	 * Prepare element array
	 *
	 * @param array $element
	 *
	 * @return array
	 */
	protected static function prepareElement( array $element ) {
		// return empty string if type not set
		if ( empty( $element['type'] ) ) {
			self::$errors[] = __( 'The element type was not specified.', __NAMESPACE__ );

			return [];
		}

		$element['type'] = strtolower( $element['type'] );

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


		// if the HTML pattern exists
		if ( ! empty( $element['html'] ) ) {
			if ( ! empty( self::$id ) ) {
				$element['vars']['id'] = self::$id;
			}
		}

		return $element;
	}

	/**
	 * Prepare all elements of given array
	 *
	 * @param $data
	 *
	 * @return array|bool|string
	 */
	public static function prepare( $data ) {
		static::init();

		// if data is not an array, it means that $data is a content like a string, label, for example
		if ( ! is_array( $data ) ) {
			return $data;
		}
		// определяется список сформированных html элементов
		$elementsList = [];

		// перебор элементов
		foreach ( $data as $i => $element ) {
//if(is_string($element)){
//	echo '!!! '.$element.' ???';die;
//}
			// в список добавляется сформированный элемент
			$elementsList[] = self::prepareElement( $element );
		}

		if ( ! empty( $errors = self::isErrors() ) ) {

			return $errors;
		}

		return $elementsList;
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
		if ( ! is_array( $data ) ) {
			return $data;
		}

		// prepare data for converting to HTML
		$data = self::prepare( $data );

		// определяется список сформированных html элементов
		$elementsList = [];

		// перебор элементов
		foreach ( $data as $i => $element ) {

			// в список добавляется сформированный элемент
			$elementsList[] = self::convertToHtml( $element );
		}

		if ( ! empty( $errors = self::isErrors() ) ) {

			return $errors;
		}

		// список переводится в строку, разделенную по строкам
		$elementsList = implode( PHP_EOL, $elementsList );

		return $elementsList;
	}

	protected static function isErrors() {
		if ( ! empty( self::$errors ) ) {
			return join( PHP_EOL, array_map( function ( $item ) {
				return '<p class="error">' . $item . '</p>';
			}, self::$errors ) );
		}

		return false;
	}
}
// eof
