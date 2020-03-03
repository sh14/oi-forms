<?php
/**
 * Date: 2019-05-05
 * @author Isaenko Alexey <info@oiplug.com>
 */

namespace elements;

/*
 * 1 тип эелемента
 * 2 значение
 * 3 значение по умолчанию
 * 4 варианты значений
 * 5 атрибуты элемента
 * 6 атрибуты вариантов
 */

class elements {

	public static function build( $array ) {

		return $array;
	}
}


print_r( elements::build( [
	'type'         => 'select',
	'name'         => 'categories',
	'readonly'     => false,
	'disabled'     => false,
	'required'     => false,
	// off/on
	'autocomplete' => 'on',
	// для type="checkbox" или type="radio"
	'checked'      => false,
	// название формы или форм, к которым относится селект
	'form'         => '',
	'options'      => [
		// optgroup - группа опций
		[
			'label'    => '',
			'disabled' => false,
			'options'  => [
				'label'    => '',
				'value'    => '',
				'selected' => false,
				'disabled' => false,
			],
		],
	],
	'atts'         => [
		'autofocus' => '',
		// дает возможность выбрать несколько файлов для type="file" или несколько пунктов для select
		'multiple'  => false,
		// определяет число видимых опций
		'size'      => '',
		/* input */
		// определяет тип файлов, которые может выбрать пользователь, только для type="file"
		'accept'    => '',
		// отправит направление текста, например ltr
		'dirname'   => '',
		// определяет id datalist, в котором содержатся значения для текущего input'а
		'list'      => '',
		'min'       => false,
		'max'       => false,
		// шаг прирощения
		'step'      => false,
		// определяет максимальную длинну строки
		'maxlength' => false,
		// regex для вводимого текста
		'pattern'   => '',
		''          => '',
	],
] ) );
// eof
