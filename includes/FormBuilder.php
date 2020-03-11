<?php
/**
 * Date: 2020-03-05
 * @author Isaenko Alexey <info@oiplug.com>
 */


if ( ! function_exists( 'esc_attr' ) ) {
	function esc_html( $text ) {
		if ( get_magic_quotes_gpc() ) {
			$text = stripslashes( $text );
		}

		return $text;
	}

	function esc_attr( $text ) {
		$text = esc_html( $text );
		$text = htmlentities( $text );

		return $text;
	}

	function esc_url( $url ) {
		return $url;
	}
}
if ( ! function_exists( 'shortcode_atts' ) ) {
	function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
		$atts = (array) $atts;
		$out  = array();
		foreach ( $pairs as $name => $default ) {
			if ( array_key_exists( $name, $atts ) ) {
				$out[ $name ] = $atts[ $name ];
			} else {
				$out[ $name ] = $default;
			}
		}
		/**
		 * Filter a shortcode's default attributes.
		 *
		 * If the third parameter of the shortcode_atts() function is present then this filter is available.
		 * The third parameter, $shortcode, is the name of the shortcode.
		 *
		 * @since 3.6.0
		 * @since 4.4.0 Added the `$shortcode` parameter.
		 *
		 * @param array  $out       The output array of shortcode attributes.
		 * @param array  $pairs     The supported attributes and their defaults.
		 * @param array  $atts      The user defined shortcode attributes.
		 * @param string $shortcode The shortcode name.
		 */
		if ( $shortcode ) {
			$out = apply_filters( "shortcode_atts_{$shortcode}", $out, $pairs, $atts, $shortcode );
		}

		return $out;
	}
}


/**
 * Функция перевода аттрибутов в виде массива в строку
 *
 * @param        $array
 * @param string $prefix
 *
 * @return string
 */
function attributes_to_string( $array, $prefix = '' ) {

	// начальное определение переменной
	$data = array();

	// проход по всем атрибутам
	foreach ( $array as $key => $value ) {

		if ( ! is_array( $key ) ) {
			$key = trim( $key );
		}

		// если $value не является массивом
		if ( ! is_array( $value ) ) {

			// строка эскейпится
			$value = esc_attr( trim( $value ) );

			// если есть префикс
			if ( ! empty( $prefix ) ) {
				// формирование и добавление атрибута в массив
				$data[] =$prefix . '-'. $key . '="' . $value . '"';
			}else{
				// формирование и добавление атрибутав массив
				$data[] = $key . '="' . $value . '"';
			}
		} else {
			$data[] = attributes_to_string( $value, $key );
		}
	}

	// перевод массива в строку
	$data = ' ' . implode( ' ', $data );

	return $data;
}

/**
 *  Функция замены псевдопеременных в html на их реальные значения
 *
 * @param $html string - html строка, в которой будет производиться замена
 * @param $atts array - список атрибтов, которые необходимо заменить
 * @param $out  string - сформированный элемент
 *
 * @return string
 */
function oireplace_vars( $html, $atts, $out ) {
	if ( empty( $html ) ) {
		return $out;
	}

	// поддержка старого формата
	$html = str_replace( '%1$s', '%%', $html );
	$html = str_replace( '%2$s', '%label%', $html );
	$html = str_replace( '%3$s', '%hint%', $html );

	// проход по всем атрибутам
	foreach ( $atts as $key => $value ) {
		if ( is_numeric( $value ) || is_bool( $value ) ) {
			$value .= '';
		}
		if ( is_string( $value ) ) {
			// замена псевдопременной ее значением
			$html = str_replace( '%' . $key . '%', $value, $html );
		}
	}

	// замена псевдопеременной значением поля
	$html = str_replace( '%%', $out, $html );

	return $html;
}

if ( ! function_exists( 'oinput' ) ) {

	/*
	 * Clone of oinput() in WP style
	 */
	function get_oinput( $atts ) {
		return oinput( $atts );
	}

	/*
	 * Echo of oinput() in WP style
	 */
	function the_oinput( $atts ) {
		echo oinput( $atts );
	}

	/**
	 * Формирование атрибута value для поля
	 *
	 * @param $value
	 *
	 * @return mixed
	 */
	function oinput_make_value_attribute( $value ) {
		if ( ! is_array( $value ) ) {

			// преобразование значения в строку
			$value = $value . '';

			// если значение не пустое и оно не массив
			if ( isset( $value ) && ! is_array( $value ) && '' !== $value ) {

				// делается эскейп для вывода как атрибута
				$value = esc_attr( $value );

				// формируется атрибут поля
				$value = ' value="' . $value . '"';
			}
		}

		return $value;
	}

	/**
	 * Функция возврата сформированного html, содержащего соответствующие входным параметрам данные
	 *
	 * @param $atts
	 *
	 * @return bool|string
	 */
	function oinput( $atts ) {
		$atts = array_merge( $atts, shortcode_atts( array(
			'key'              => '',        // name of element ID and NAME
			'name'             => '',        // same as key
			'id'               => '',        // element ID
			'type'             => 'text',    // field type: text, hidden, password, select, option, textarea
			'no_select'        => array(),        // value that shouln't be selected
			'value'            => '',        // field value
			'before'           => '',        // label befor field
			'after'            => '',        // label after field
			'label'            => '',        // label field
			'placeholder'      => '',        // text example in a field
			'hint'             => '',        // hint text after field
			'class'            => '',        // element class
			'label_class'      => '',        // label class
			'style'            => '',        // element style
			'data'             => '',        // data attribute as array
			'attributes'       => '',        // you able to write what ever you want to see inside a field
			'delimiter'        => '',        // you able to separate label and input with some tag
			'html'             => '',        // html template: %1$s - field, %2$s - label, %3$s - hint
			'checked'          => false,    // checked flag
			'multiple'         => false,    // multiple flag
			'readonly'         => false,    // readonly flag
			'disabled'         => false,    // disabled flag
			'required'         => false,    // required flag
			'autofocus'        => false,    // autofocus flag
			'autofocus_at_end' => false,    // set cursor to the end of focused string via JS
			'published'        => true,    // if is false - don't print
		), $atts ) );

		// trim all none boolean data
		foreach ( $atts as $key => $value ) {
			if ( is_string( $value ) ) {
				$atts[ $key ] = trim( $value );
			}
		}

		// exit if don't need to publish that field
		if ( false === $atts['published'] ) {
			return false;
		}

		// if $atts['name'] is empty and $key is not(old versions support)
		if ( empty( $atts['name'] ) && ! empty( $atts['key'] ) ) {
			$atts['key']  = esc_attr( $atts['key'] );
			$atts['name'] = $atts['key'];
		}

		// определение переменной $field_type для использования в качестве флага типа поля
		$field_type = esc_attr( $atts['type'] );

		// переменная $atts['type'] будет использована в качестве атрибута поля
		$atts['type'] = $field_type;

		// если тип поля не select и переменная является строкой
		if ( 'select' !== $field_type && is_string( $atts['value'] ) ) {
			// определение новой переменной с эскейпом
			$field_value = esc_attr( $atts['value'] );

			// переопределение переменной
			$atts['value'] = $field_value;
		} else {
			// определение новой переменной
			$field_value = $atts['value'];
		}

		if ( $atts['name'] || 'submit' === $field_type || 'button' === $field_type || 'html' === $field_type ) {
			if ( empty( $atts['id'] ) ) {
				$atts['id'] = $atts['name'];

				// удаление квадратных скобок для формирования нормального id
				$atts['id'] = str_replace( '][', '-', $atts['id'] );
				$atts['id'] = str_replace( ']', '', $atts['id'] );
				$atts['id'] = str_replace( '[', '-', $atts['id'] );
			}

			// set class for label
			if ( ! empty( $atts['label_class'] ) ) {
				$atts['label_class'] = 'class="' . esc_attr( $atts['label_class'] ) . '"';
			}

			// list of labels
			$attributes = array(
				'before',
				'after',
				'label',
			);

			foreach ( $attributes as $key ) {
				if ( ! empty( $atts[ $key ] ) && ! in_array( $atts['type'], array( 'checkbox', 'radio' ) ) ) {
					$atts[ $key ] = '<label ' . $atts['label_class'] . ' for="' . esc_attr( $atts['id'] ) . '">' . esc_html( $atts[ $key ] ) . '</label>';
				}
			}

			// list of boolean attributes
			$attributes = array(
				'checked',
				'multiple',
				'readonly',
				'disabled',
				'required',
			);

			foreach ( $attributes as $key ) {
				if ( true === $atts[ $key ] ) {
					$atts[ $key ] = ' ' . esc_attr( $key ) . '="' . esc_attr( $key ) . '"';
				} else {
					$atts[ $key ] = '';
				}
			}

			// list of boolean non pair attributes
			$attributes = array(
				'autofocus',
			);

			foreach ( $attributes as $key ) {
				if ( true === $atts[ $key ] ) {
					$atts[ $key ] = ' ' . esc_attr( $key );
				} else {
					$atts[ $key ] = '';
				}
			}

			if ( true === $atts['autofocus_at_end'] ) {
				$atts['autofocus_at_end'] = ' onfocus="this.value = this.value;"';
			} else {
				$atts['autofocus_at_end'] = '';
			}

			if ( 'option' !== $field_type ) {
				// list of attributes
				$attributes = array(
					'id',
					'name',
					'type',
					'value',
					'class',
					'style',
					'data',
					'placeholder',
					'attributes',
				);
				foreach ( $attributes as $key ) {

					// если элемент не пуст
					if ( ! empty( $atts[ $key ] ) ) {

						// если элемент является массивом
						if ( is_array( $atts[ $key ] ) ) {

							if ( 'class' === $key ) {

								// формирование строки, содержащей классы
								$atts[ $key ] = ' ' . $key . '="' . esc_attr( implode( ' ', array_map( 'trim', $atts[ $key ] ) ) ) . '"';
							} else {

								// если проверяется стиль
								if ( 'style' === $key ) {

									// если атрибут что-то содержит
									if ( ! empty( $atts[ $key ] ) ) {

										// начальное определение переменной
										$data = array();

										// проход по всем стилям
										foreach ( $atts[ $key ] as $name => $value ) {

											// формирование и добавление в массив одного стиля
											$data[] = esc_attr( trim( $name ) . ':' . trim( $value ) );
										}

										// формирование атрибута со стилями
										$atts[ $key ] = ' style="' . implode( ';', $data ) . '"';
									}
									// если атрибут data
								} elseif ( 'data' === $key ) {

									// если содержимое что-то содержит
									if ( ! empty( $atts[ $key ] ) ) {

										// преобразование всех data в строку
										$atts[ $key ] = attributes_to_string( $atts[ $key ], $key );
									}
								} elseif ( 'attributes' === $key ) {

									// если содержимое что-то содержит
									if ( ! empty( $atts[ $key ] ) ) {

										// преобразование всех data в строку
										$atts[ $key ] = attributes_to_string( $atts[ $key ] );
									}
								}
							}
						} else {
							if ( 'data' === $key ) {
								$atts[ $key ] = ' ' . implode( ' ', $atts[ $key ] );
							} else {

								// если значение не пусто
								if ( ! empty( $atts[ $key ] ) ) {

									// формируется строка со значением
									$atts[ $key ] = ' ' . $key . '="' . esc_attr( $atts[ $key ] ) . '"';
								}
							}
						}
					} else {
						// если атрибут является пустым массивом, он преобразуется в пустую строку
						$atts[ $key ] = '';
					}
				}

				/*				if ( ! empty( $atts['hint'] ) ) {
									$atts['hint'] = '<span class="help-block description">' . esc_html( $atts['hint'] ) . '</span>';
								}*/

				if ( 'option' !== $field_type ) {

					// формирование элемента
					$atts['attributes'] = $atts['placeholder']
					                      . $atts['style']
					                      . $atts['checked']
					                      . $atts['multiple']
					                      . $atts['readonly']
					                      . $atts['disabled']
					                      . $atts['required']
					                      . $atts['autofocus']
					                      . $atts['autofocus_at_end']
					                      . $atts['data']
					                      . $atts['attributes'];
				}
			}

			switch ( $field_type ) {
				case 'select':
					$tag = $field_type;
					break;
				case 'option':
					$tag = $field_type;
					break;
				case 'textarea':
					$tag = $field_type;
					break;
				case 'submit':
					$tag = 'button';
					break;
				case 'button':
					$tag = 'button';
					break;
				default:
					$tag = 'input';
					break;
			}

			switch ( $field_type ) {
				case 'html':
					// замена псевдопеременных вида %var% на их значения
					$out = oireplace_vars( $atts['html'], $atts, '' );

					return $out;
					break;
				case 'select':
					$out =
						'<' . $tag . $atts['class'] . $atts['type'] . $atts['id'] . $atts['name'] . $atts['attributes'] . '>' .
						$field_value .
						'</' . $tag . '>';
					break;
				case 'option':
					$data_key = '';

					// если data указано
					if ( ! empty( $atts['data'] ) ) {

						// производится определение $data_key и массива значений
						foreach ( $atts['data'] as $data_key => $data_value ) {

							// присваивается значение из data
							$atts['data'] = $data_value;
						}
					}

					$out = '';
					if ( ! empty( $atts['name'] ) && is_array( $atts['name'] ) ) {
						foreach ( (array) $atts['name'] as $k => $v ) {

							// указание, что элемент пока не является выбранным
							$selected = '';

							// если $field_value является массивом
							if ( is_array( $field_value ) ) {

								// если значение текущего option принадлежит массиву значений, но не присутствует в no_select
								if ( in_array( $k, $field_value ) && ! in_array( $k, $atts['no_select'] ) ) {
									$selected = 'selected="selected"';
								}
							} else {

								// делается эскейп для вывода как атрибута
								$field_value = esc_attr( $field_value );

								// определение - выбран ли элемент
								$selected = selected( $field_value, $k, false );
							}

							// если $k-й элемент не пуст
							if ( ! empty( $atts['data'][ $k ] ) ) {

								// формируется строка data-<ключ>="<значение>"
								$data = ' data-' . $data_key . '="' . $atts['data'][ $k ] . '"';
							} else {
								$data = '';
							}

							$out .= '<' . $tag . $data . ' value="' . $k . '" ' . $selected . '>' . esc_html( $v ) . '</' . $tag . '>';
						}
					}
					break;
				case 'hidden':
					$out = '<' . $tag . $atts['class'] . $atts['type'] . $atts['id'] . $atts['name'] . $atts['attributes'] . $atts['value'] . ' />';
					break;
				case 'radio':
					// if we have a value
					if ( ! empty( $field_value ) ) {
						// it means that that radio was checked
						$atts['attributes'] .= ' checked="checked"';
					}
					$out = '<' . $tag . $atts['class'] . $atts['type'] . $atts['id'] . $atts['name'] . $atts['attributes'] . $atts['value'] . ' />';
					break;
				case 'checkbox':
					// if we have a value
					if ( ! empty( $field_value ) ) {
						$atts['attributes'] .= ' checked="checked"'; // it means that that checkbox was checked
					}
					$out = '<' . $tag . $atts['class'] . $atts['type'] . $atts['id'] . $atts['name'] . $atts['attributes'] . ' value="1" />';
					break;
				case 'submit':
					$out = '<' . $tag . $atts['class'] . $atts['type'] . $atts['id'] . $atts['attributes'] . '>' . esc_html( $field_value ) . '</' . $tag . '>';
					break;
				case 'button':
					$out = '<' . $tag . $atts['class'] . $atts['type'] . $atts['id'] . $atts['attributes'] . '>' . esc_html( $field_value ) . '</' . $tag . '>';
					break;
				case 'textarea':
					$out = '<' . $tag . $atts['class'] . $atts['type'] . $atts['id'] . $atts['name'] . $atts['attributes'] . '>' . esc_html( $field_value ) . '</' . $tag . '>';
					break;
				default:
					$atts['value'] = oinput_make_value_attribute( $field_value );
					$out           = '<' . $tag . $atts['class'] . $atts['type'] . $atts['id'] . $atts['name'] . $atts['attributes'] . $atts['value'] . ' />';
					break;
			}

			// make html output
			if ( ! empty( $atts['html'] ) ) {
				$out = $atts['before'] . $out . $atts['after'];
				//$out = preg_replace('/\{([a-zA-Z0-9]+)\}/e', "$$1", $atts['html']);
				//$out = sprintf( $atts['html'], $atts['before'] . $out . $atts['after'], $label, $atts['hint'] );
			} else {
				$open_tag  = '';
				$close_tag = '';
				if ( ! empty( $delimiter ) ) {
					$open_tag  = '<' . esc_attr( $delimiter ) . '>';
					$close_tag = '</' . esc_attr( $delimiter ) . '>';
				}
				$out = $open_tag . $atts['before'] . $close_tag . $open_tag . $out . $atts['after'] . $atts['hint'] . $close_tag;
			}
			$out = oireplace_vars( $atts['html'], $atts, $out );

			return $out;
		}

		return true;
	}

	if ( function_exists( 'add_shortcode' ) ) {
		add_shortcode( 'oinput', 'oinput' );
	}
}

function oinput_form( $fields, $atts = null ) {
	$atts = shortcode_atts( array(
		'id'         => '',
		'name'       => '',
		'method'     => 'post',
		'action'     => '',
		'attributes' => '',
		'echo'       => true,
		'form'       => true, // true - добавлять тег form как контейнер
	), $atts );

	// определение начального значения переменной, содержащей выходные данные
	$out = '';

	// проход по добавляемым полям
	foreach ( $fields as $key => $field ) {
		// добавление сформированного поля к выводу
		$out .= oinput( $field );
	}

	if ( ! empty( $atts['attributes'] ) ) {

		// преобразование всех attributes в строку
		$atts['attributes'] = attributes_to_string( $atts['attributes'] );
	}

	// если не указано, что метод передачи POST
	if ( 'post' !== $atts['method'] ) {
		// устанавливается метод передачи GET
		$atts['method'] = 'get';
	}

	// trim all none boolean data
	if ( ! empty( $atts ) ) {
		foreach ( $atts as $key => $value ) {
			if ( ! is_bool( $value ) ) {
				$atts[ $key ] = trim( $value );
			}
		}
	}
	$atts['action'] = esc_url( $atts['action'] );

	// список атрибутов, которые надо сформировать в правиьном виде: атрибут="значение"
	$attributes = array(
		'id',
		'name',
		'method',
		'action',
	);

	// проход по списку атрибутов
	foreach ( $attributes as $key ) {
		// если значение указанного атрибута указано
		if ( ! empty( $atts[ $key ] ) ) {
			// формируется конструкция трибут=значение
			$atts[ $key ] = ' ' . $key . '="' . esc_attr( $atts[ $key ] ) . '"';
		}

		// конструкция добавляется к выводу
		$atts['attributes'] .= $atts[ $key ];
	}

	if ( true === $atts['form'] ) {
		$out = '<form ' . $atts['attributes'] . '>' . $out . '</form>';
	}
	if ( true === $atts['echo'] ) {
		echo $out;
	}

	return $out;
}

function get_oitemp( $template, $atts, $include = array() ) {

	foreach ( $include as $i => $item ) {
		$include[ $item ] = '';
		unset( $include[ $i ] );
	}
	$atts = array_merge( $include, $atts );

	foreach ( $atts as $key => $value ) {
		if ( ! is_array( $value ) ) {
			if ( empty( $value ) ) {
				$value = '';
			}
			$template = str_replace( '%' . $key . '%', $value, $template );
		}
	}

	return $template;
}

function get_oitemplate( $template, $atts ) {

	ob_start();

	// Извлекаем переменные, если они переданы.
	if ( ! empty( $atts ) ) {
		if ( ! empty( $atts ) ) {
			extract( $atts, EXTR_SKIP );
		}
	}
	echo $template;

	// Вернем отрендеренный шаблон.
	return ob_get_clean();
}

/**
 * Making HTML from array with classes by BEM
 *
 * // bem array
 * $user_links = get_html( [
 * 'tag'     => 'div',
 * 'atts'    => [
 * 'class' => '&__contacts',
 * ],
 * 'content' => [
 * [
 * 'tag'     => 'h3',
 * 'atts'    => ['class' => '&__contacts-title',],
 * 'content' => __( 'Bio' ),
 * ],
 * [
 * 'tag'     => 'ul',
 * 'atts'    => ['class' => '&__contacts-list',],
 * 'content' => implode( "\n", $user_links ),
 * ],
 * ],
 * ], 'profile' );
 *
 * @param        $atts
 * @param string $base_class
 *
 * @return string
 */
function get_html( $atts, $base_class = '' ) {
	$atts = shortcode_atts( array(
		'tag'     => 'div',
		'atts'    => array(
			'class' => '&',
		),
		'content' => '',
	), $atts );

	$mono   = array(
		'br',
		'hr',
		'input',
		'meta',
		'link',
		'img',
	);
	$out    = '';
	$object = array();

	// перебор содержимого массива
	foreach ( $atts as $key => $value ) {

		// если ключи массива не ассоциативные
		if ( is_numeric( $key ) ) {

			// если элемент является массивом
			if ( is_array( $value ) ) {

				// необходимо сделать рекурсию
				$out .= get_html( $value, $base_class );
			} else {

				// необходимо добавить значение в строку
				$out .= $value;
			}
		} else {
			// массив ассоциативный, идет разбор массива, как тега

			switch ( $key ) {
				case 'tag':
					$object[ $key ][] = '<' . $value;
					break;
				case 'atts':
					$object[ $key ][] = ' ';

					// если значение является массивом
					if ( is_array( $value ) ) {
						$attributes = array();

						// атрибуты выстраиваются в строку
						foreach ( $value as $name => $val ) {

							// преобразование массива со значениями в строку
							if ( is_array( $val ) ) {
								$val = implode( ' ', $val );
							}

							// змена символа & на базовый класс
							$val = str_replace( '&', $base_class, $val );

							$attributes[] = $name . '="' . $val . '"';
						}

						$object[ $key ][] = implode( ' ', $attributes );
					} else {
						$object[ $key ][] = $value;
					}
					break;
				case 'content':
					// если элемент является массивом, вероятно это описание вложенного элемента или набора вложений
					if ( is_array( $value ) ) {

						foreach ( $value as $index => $val ) {
							//pr( $val );
							$object[ $key ][] = get_html( $value, $base_class );
						}
					} else {
						$object[ $key ][] = $value;
					}
					break;
			}
		}
	}

	// если $out что-то содержит, следует вернуть данные
	if ( ! empty( $out ) ) {
		return $out;
	}

	if ( empty( $atts['atts'] ) ) {
		$atts['atts'] = array();
	}
	// перебор элементов и дописывание закрывающих частей
	foreach ( $atts as $key => $value ) {
		switch ( $key ) {
			case 'atts':
				// закрытие открывающего тега, если
				if ( ! in_array( $atts['tag'], $mono ) ) {
					$object[ $key ][] = '>';
				}
				break;
			case 'tag':
				if ( in_array( $value, $mono ) ) {

					$object[ 'end_' . $key ][] = ' />';
				} else {
					$object[ 'end_' . $key ][] = '</' . $value . '>';
				}

				break;
		}
	}

	// порядок составления элементов
	$order = array(
		'tag',
		'atts',
		'content',
		'end_tag',
	);
	foreach ( $order as $key ) {
		if ( ! empty( $object[ $key ] ) ) {
			$out .= implode( '', $object[ $key ] );
		}
	}

	return $out;
}

// eof
