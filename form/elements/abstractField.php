<?php
/**
 * Date: 2019-05-05
 * @author Isaenko Alexey <info@oiplug.com>
 */


namespace forms;


abstract class abstractField {
	public static $attributes = [];

	protected static function init() {
		self::$attributes = self::attributes();
	}

	/**
	 * указание вида элемента - парный(с закрывающим тегом) или одиночный
	 *
	 * @return array
	 */
	protected static function elements() {
		return [
			'select' => [
				'single' => false,
			],
		];
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
	 * обработка атрибутов тега
	 *
	 * @param        $elementAttributes
	 * @param array  $attributesList
	 * @param string $prefix
	 *
	 * @return array
	 */
	private static function processAttributes( $elementAttributes, $attributesList = [], $prefix = '' ) {

		$keyPrefix = '';

		// получение определенного списка атрибутов по умолчанию
		$attributes = self::$attributes;

		// перебор пользовательских атрибутов
		foreach ( $elementAttributes as $key => $value ) {

			// определение имени вероятно существующего метода
			$method = $key . 'Attribute';

			// определение имени класса из которого вызывается абстракция
			$called_class_name = get_called_class();

			// если в вызванном классе(дочернем) существует метод для обработки атрибута
			if ( method_exists( $called_class_name, $method ) ) {

				// вызывается метод для обработки указанного атрибута
				$attributesList[] = $called_class_name::$method( $value );
			} // атрибут обрабатывается обычным способом
			else {
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
						$value = implode( $delimiter, self::processAttributeValues( $value, $delimiters ) );
					} else {

						// метод перебирает вложенный список
						$attributesList = self::processAttributes( $value, $attributesList, $key );

						// осуществляется переход к следующей итерации
						continue;
					}
				}

				// если элемент не определен или указан префикс, значит идет перебор пользовательских атрибутов
				if ( empty( $attributes[ $key ] ) || ! empty( $prefix ) ) {

					// тип атрибута переопределяется на строку
					$attributes[ $key ]['type'] = 'String';

					// атрибут не скрывается при пустом значении
					$attributes[ $key ]['hideEmpty'] = false;

					// атрибут не являются обязательными
					$attributes[ $key ]['required'] = false;
				}

				// если указан префикс, атрибут составной
				if ( ! empty( $prefix ) ) {

					$keyPrefix = $prefix . '-';
				}

				// если атрибут обязателен и он пуст
				if ( true == $attributes[ $key ]['required'] && empty( $value ) ) {

					// возвращается информация об ошибке для вставкив sprintf
					return [ 'error' => [ 'Key "%s" is required.', $key ], ];
				}

				// если значение определено как пустое или не указано
				if ( empty( $value ) ) {

					// если при отсутствии значения атрибут должен скрываться
					if ( true == $attributes[ $key ]['hideEmpty'] ) {

						// осуществляется переход к следующей итерации
						continue;
					} else // если указано значение по умолчанию
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
							$attributesList[] = $key;
						}
						break;
					case 'String':

						// добавляется название атрибута с текстовым значением
						$attributesList[] = $keyPrefix . $key . '="' . htmlspecialchars( $value ) . '"';
						break;
					case 'Number':

						// добавляется название атрибута с числовым значением
						$attributesList[] = $key . '="' . floatval( $value ) . '"';
						break;
					case 'Array':

						// добавляется название атрибута с текстовым значением
						$attributesList[] = $key . '="' . htmlspecialchars( $value ) . '"';
						break;
				}
			}
		}

		return $attributesList;
	}


	/**
	 * Обработка списков значений атрибутов, например классов или стилей
	 *
	 * @param        $attributeValues
	 * @param string $delimiter
	 * @param array  $values
	 * @param string $prefix
	 *
	 * @return array
	 */
	private static function processAttributeValues( $attributeValues, $delimiter = ' ', $values = [], $prefix = '' ) {

		$keyPrefix = '';

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
			} else {

				// берется второй разделитель - для ключа со значением(color:red)
				$separator = $delimiter[1];
			}

			if ( ! empty( $prefix ) ) {
				// если массив нумерованный
				if ( is_numeric( $key ) ) {

					// определяется часть имени без индекса
					$keyPrefix = $prefix . $separator;
				} else {

					// определяется часть имени с ключом
					$keyPrefix = $prefix . $key . $separator;
				}
			} else {
				// если массив нумерованный
				if ( is_numeric( $key ) ) {

					// определяется часть имени без индекса
					$keyPrefix = '';
				} else {

					// определяется часть имени с ключом
					$keyPrefix = $key . $separator;
				}
			}

			// если значение является массивом
			if ( is_array( $value ) ) {

				// вызывается функция с параметрами
				$values = self::processAttributeValues( $value, $delimiter, $values, $keyPrefix );

				// осуществляется переход к следующей итерации
				continue;
			}

			// к списку значений добавляется имя со своим значением
			$values[] = $keyPrefix . $value;

		}

		return $values;
	}

	/**
	 * функция формирования html элемента из массива данных
	 *
	 * @param $element
	 *
	 * @return mixed|string
	 */
	protected static function html( $element ) {

//		if(!empty($element['content'])){
//
//			if(is_array($element['content'])){
//				$content = self::element($element['content']);
//			}else{
//				$content = $element['content'];
//			}
//		}else{
//			$content = '';
//		}
		print_r($element);
		print "\n";
		$content = '';

		// получение стандартных свойств элементов
		$elements = self::elements();

		// определение атрибутов
		$attributes = self::processAttributes( $element['attributes'] ) ;

		print_r($attributes);
		print "\n";


		$attributes = implode( ' ', $attributes );

		// перевод списка атрибутов со значениями в строку через пробел
		$html = implode( ' ', [ $element['type'], $attributes ] );

		// если элемент парный
		if ( empty( $elements[ $element['type'] ]['single'] ) ) {

			// формирование парного элемента
			$html = "<{$html}>{$content}</{$element['type']}>";
		} else {

			// формирование не парного элемента
			$html = "<{$html}/>{$content}";
		}

		// если ключ html содержит данные
		if ( ! empty( $element['html'] ) ) {

			// формируется html обертка для элемента
			$html = self::htmlProcess( $element, $html );
		}

		return $html;
	}

	/**
	 * Формирование html обертки для элемента
	 *
	 * @param $element
	 * @param $elementHtml
	 *
	 * @return mixed
	 */
	protected static function htmlProcess( $element, $elementHtml ) {

		// определение html с псевдопеременными
		$html = $element['html'];

		// перебор ключей с определением приоритета замены данных(сперва замена происходит из vars, затем из attributes)
		foreach ( [ 'vars', 'attributes' ] as $type ) {

			// перебор данных из указанного источника
			foreach ( $element[ $type ] as $key => $value ) {

				// если значение не является массивом
				if ( is_string( $value ) ) {

					// в html заменяются все вхождения указанного ключа
					$html = str_replace( "%{$key}%", $value, $html );
				}
			}
		}

		// в html заменяется псевдоключ элемента на его html эквивалент
		$html = str_replace( "%%", $elementHtml, $html );

		return $html;
	}


}
// eof
