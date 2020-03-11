<?php
/**
 * Date: 2019-02-24
 * @author Isaenko Alexey <info@oiplug.com>
 */

namespace forms;

use Elements\Element;

abstract class forms {
	// имя функции, которая обрабатывает ajax запросы
	private $action = '';
	// метод отправки данных
	private $method = 'post';
	// id формы
	private $id = '';
	// cписок id элементов с значением равным кол-ву равноназванных элементов
	private $ids = [];
	// сформированный массив формы со значениями
	private $form = [];
	// отрендеренная форма в том виде, в котором запросил пользователь
	private $data = [];

	// список ошибок
	public $error = [];

	public function __construct( $request ) {

		// определение id формы по вызываемому классу в пространстве имен
		$this->id = str_replace( '\\', '-', get_called_class() );

		// определение имени функции, которая обрабатывает ajax запросы
		$this->action = __NAMESPACE__ . '_ajax';

		if ( method_exists( $this, 'init' ) ) {

			// если есть инициализация
			$this->init( $request );
		}

		// если есть ошибка
		if ( ! empty( $this->error ) ) {

			// получение имен всех свойств класса
			$props = array_keys( get_object_vars( $this ) );

			// определение текста ошибки
			$error = $this->error;

			// перебор каждого свойства
			foreach ( $props as $prop ) {

				// установка значения свойства
				$this->$prop = [
					'errors' => [
						$error,
					],
				];
			}

		}
		else {
			// если передан ключ request и при этом существует такой метод
			if ( ! empty( $request['request'] ) && method_exists( $this, $request['request'] ) ) {

				// определение имени метода
				$method = $request['request'];

				// если метод update
				if ( 'update' == $method ) {

					// обнуление выдачи
					$this->data = [];
				}
			}
		}
	}

	/**
	 * определение атрибута формы
	 *
	 * @param string $key
	 * @param string $value
	 */
	private function set_form_attribute( $key = '', $value = '' ) {

		// предотвращение случайного удаления всех полей формы
		if ( 'fields' != $key && ! empty( $key ) ) {

			// если значение атрибута является массивом
			if ( is_array( $value ) ) {

				// массив преобразуется в строку со значениями, разделенными пробелами
				$value = implode( ' ', $value );
			}

			// определение атрибута формы
			$this->form[ $key ] = $value;
		}
	}

	/**
	 * Установка значения указанного поля
	 *
	 * @param $key
	 * @param $value
	 */
	private function set_form_field( $key, $value ) {
		$this->form['fields'][ $key ] = $value;
	}

	/**
	 * Добавление специальных полей, если это необходимо
	 */
	private function add_special_fields() {

		// определение id формы
		$this->set_form_attribute( 'id', $this->id );

		// добавление поля action, чтобы по нему дергать wp-ajax
		$this->set_form_field( 'action', [
			'type'  => 'hidden',
			'value' => $this->action,
		] );

		// добавление поля request, чтобы при отправке формы происходило ее сохранение
		$this->set_form_field( 'request', [
			'type'  => 'hidden',
			'value' => 'update',
		] );
		$this->set_form_field( 'form_id', [
			'type'  => 'hidden',
			'value' => str_replace( '-', '/', $this->id ),
		] );
	}

	/**
	 * Сборка формы: значения добавляются в массив данных полей
	 *
	 * @param $request
	 */
	protected function build( $request ) {

		$values = [];
		if ( method_exists( $this, 'set_form' ) ) {

			// получение определенных пользователем полей
			$this->form = $this->set_form();

			// добавление специальных полей
			$this->add_special_fields();

			if ( method_exists( $this, 'get_values' ) ) {

				// получение значений из бд
				$values = $this->get_values( $request );
			}
		}

		$form = $this->form;

		// если поля определены
		if ( ! empty( $form['fields'] ) ) {

			// осущетваляется перебор полей
			foreach ( $form['fields'] as $key => $field ) {

				// если ключ не является индесом
				if ( ! is_numeric( $key ) ) {

					// имя поля определяется в соответствии с ключом
					$form['fields'][ $key ]['name'] = $key;

					// удаление квадратных скобок из имени поля
					$field_id = str_replace( [ '[', ']' ], [ '-', '' ], $key );

					// если получившееся имя уже присутствует в списке id'ов
					if ( in_array( $field_id, array_keys( $this->ids ) ) ) {

						// количество одинаковых id'ов увеличивается на 1
						$this->ids[ $field_id ] ++;

						// к id дописывается порядковый номер
						$field_id .= '-' . $this->ids[ $field_id ];
					}
					else {

						// определяется кол-во элементов с указанным id
						$this->ids[ $field_id ] = 1;
					}

					// полю присваеватся уникальный id
					$form['fields'][ $key ]['id'] = $field_id;

					// если классы определены
					if ( ! empty( $form['fields'][ $key ]['class'] ) ) {

						// к существующим классам дописвается дополнителный - селектор для JS
						$form['fields'][ $key ]['class'] .= ' js-form-control-' . $field_id . ' ' . $field_id;
					}
					else {

						// определяется класс - селектор для JS
						$form['fields'][ $key ]['class'] = ' js-form-control-' . $field_id . ' ' . $field_id;
					}

					// если значение не пусто
					if ( ! empty( $values[ $key ] ) ) {

						// если значение является массивом
						if ( is_array( $values[ $key ] ) ) {

							// если значение содержит данные gallery
							if ( ! empty( $values[ $key ]['gallery'] ) ) {

								// если поле не содержит ключ gallery
								if ( empty( $form['fields'][ $key ]['gallery'] ) ) {

									// в поле устанавливается пустое значение для ключа gallery
									$form['fields'][ $key ]['gallery'] = [];
								}

								// производится слияние поля и значения по ключу gallery
								$form['fields'][ $key ]['gallery'] = array_merge( $form['fields'][ $key ]['gallery'], $values[ $key ]['gallery'] );

							}

						}
						else {

							// устанавливается значение поля
							$form['fields'][ $key ] = $values[ $key ];
						}
					}

					// если существует массив галереи
					if ( ! empty( $form['fields'][ $key ]['gallery'] ) ) {

						// установка имени поля для gallery
						$form['fields'][ $key ]['gallery']['name'] = str_replace( [ '[', ']' ], [
							'__',
							''
						], $key );
					}

					// если при определении полей формы значение не было задано
					if ( empty( $form['fields'][ $key ]['value'] ) ) {
						if ( ! empty( $values[ $key ]['value'] ) ) {
							$value = $values[ $key ]['value'];
						}
						else {

							// если поле числовое
							if ( 'number' == $form['fields'][ $key ]['type'] ) {

								// если для поля установлено минимальное значение
								if ( isset( $form['fields'][ $key ]['attributes']['min'] ) ) {

									// значение примет минимальное
									$value = $form['fields'][ $key ]['attributes']['min'];
								}
								else {

									// значение будет равно нулю
									$value = 0;
								}
							}
							else {
								$value = '';
							}
						}
					}
					else {

						// применяется определенное пользоваетлем значение
						$value = $form['fields'][ $key ]['value'];
					}

					// значение поля определяется стандартным способом
					$form['fields'][ $key ]['value'] = $value;

					// если поле является селектбоксом
					if ( ! empty( $form['fields'][ $key ]['type'] ) && 'select' == $form['fields'][ $key ]['type'] ) {

						// значение поля определяется для селектбокса
						$form['fields'][ $key ]['option_value'] = $value;
					}

					// установка значения поля
					$this->set_form_field( $key, $form['fields'][ $key ] );
				}
			}
		}
	}

	/**
	 * Возвращение заполненных полей формы при запросе из vue.js
	 *
	 * @return array
	 */
	private function get_form_vue() {

		$form   = $this->form;
		$fields = array_values( $form['fields'] );

		foreach ( $fields as $i => $field ) {

			if ( ! empty( $fields[ $i ]['type'] ) ) {

				// приведение типа к нижнему регистру
				$fields[ $i ]['type'] = strtolower( $fields[ $i ]['type'] );

				// выполнение действий, если тип равен указанному
				switch ( $fields[ $i ]['type'] ) {
					case 'submit':
					case 'multiselect':

						// тип поля для сборщика формы указывается равным типу
						$fields[ $i ]['inputType'] = $fields[ $i ]['type'];
						break;
					case 'textarea':

						// определяется тип
						$fields[ $i ]['type'] = 'textArea';

						// тип для сборщика формы удаляется, так как в textarea он не используется
						unset( $fields[ $i ]['inputType'] );
						break;
					case 'select':

						// определяется тип
//						$fields[ $i ]['type'] = 'textArea';

						// тип для сборщика формы удаляется, так как в textarea он не используется
						unset( $fields[ $i ]['inputType'] );
						break;
					case 'gallery':
						// тип поля для сборщика формы указывается равным типу для приема файлов
						$fields[ $i ]['inputType'] = 'file';
						break;
					case 'html':
						// никак не обрабатывать type html
						break;
					default:
						// тип поля для сборщика формы указывается равным типу
						$fields[ $i ]['inputType'] = $fields[ $i ]['type'];

						// тип определяется как инпут
						$fields[ $i ]['type'] = 'input';
				}
			}

			// если тип поля - кнопка
			if ( in_array( $fields[ $i ]['type'], [ 'submit', 'button', ] ) && ! empty( $fields[ $i ]['value'] ) ) {
				$fields[ $i ]['buttonText'] = $fields[ $i ]['value'];
				unset( $fields[ $i ]['value'] );
			}

			if ( ! empty( $fields[ $i ]['name'] ) ) {
				// определение имени поля
				$fields[ $i ]['inputName'] = $fields[ $i ]['name'];
			}

			if ( ! empty( $fields[ $i ]['id'] ) ) {
				// определение ключа model для использования во vue
				$fields[ $i ]['model'] = $fields[ $i ]['id'];
			}
		}

		$form['fields'] = $fields;

		$this->data = $form;
	}

	/**
	 * Сборка html всей формы
	 */
	private function get_html() {

		$form                          = $this->form;
		$form['attributes']['class'][] = 'form';
		$form['attributes']['class'][] = 'js-oi-forms';
		$form['attributes']['method']  = ! empty( $this->form['method'] ) ? $this->form['method'] : $this->method;
		$form['attributes']['id']      = $this->id;

		pr( $form['fields'] );
		$this->data = Element::get( $form['fields'] );
	}

	/**
	 * Возвращение заполненных полей формы том виде, в котором их запросил пользователь
	 *
	 * @return array
	 */
	public function get( $request ) {

		// построение формы с обязательными полями и значениями
		$this->build( $request );

		switch ( $request['response'] ) {
			case 'vue':
				$this->get_form_vue();

				return $this->data;
				break;
			case 'html':
				$this->get_html();

				return $this->data;
				break;
		}

		return $this->form;
	}
}


// eof
