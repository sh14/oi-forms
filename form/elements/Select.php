<?php
/**
 * Date: 2019-05-05
 * @author Isaenko Alexey <info@oiplug.com>
 */

namespace forms;


class Select extends abstractField {

	protected static function optionsPropertie( $data ) {
		$options = [];
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {

			}
		}

		return 'zzzzzzzzzz';
	}

	public static function element( $data ) {
		self::init();

		// определяется список сформированных html элементов
		$elementsList = [];

		// перебор элементов
		foreach ( $data as $i => $element ) {

			// в список добавляется сформированный элемент
			$elementsList[] = self::html( $element );
		}

		// список переводится в строку, разделенную по строкам
		$elementsList = implode( "\n", $elementsList );

		return $elementsList;
	}

}
// eof
