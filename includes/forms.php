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
	// сформированный массив формы со значениями
	protected $form = [];
	// отрендеренная форма в том виде, в котором запросил пользователь
	protected $data = [];
	// значения полей полученные с сервера
	protected $values = [];

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
	 * Добавление специальных полей, если это необходимо
	 */
	private function addSpecialFields() {
//		pr( $this->form );
		// form fields adding
		$this->form['type']                  = 'Form';
		$this->form['attributes']['id']      = $this->id;
		$this->form['attributes']['class'][] = 'form';
		$this->form['attributes']['class'][] = 'js-oi-forms';
		$this->form['attributes']['method']  = ! empty( $this->form['method'] ) ? $this->form['method'] : $this->method;

		// добавление поля action, чтобы по нему дергать wp-ajax
		$this->form['content'][] = [
			'type'       => 'hidden',
			'attributes' => [
				'name'  => 'action',
				'value' => $this->action,
			],
		];


		// добавление поля request, чтобы при отправке формы происходило ее сохранение
		$this->form['content'][] = [
			'type'       => 'hidden',
			'attributes' => [
				'name'  => 'request',
				'value' => 'update',
			],
		];

		// adding form class name equal form id
		$this->form['content'][] = [
			'type'       => 'hidden',
			'attributes' => [
				'name'  => 'form_id',
				'value' => $this->id,
			],
		];
	}

	protected function addSpecialClasses( $data ) {
		foreach ( $data as $i => $element ) {
			if ( ! empty( $field_id = $element['attributes']['id'] ) ) {
				if ( ! empty( $element['attributes']['class'] ) ) {
					$data[ $i ]['attributes']['class']   = explode( ' ', $data[ $i ]['attributes']['class'] );
					$data[ $i ]['attributes']['class'][] = 'js-form-control-' . $field_id;
					$data[ $i ]['attributes']['class'][] = $field_id;
					$data[ $i ]['attributes']['class']   = join( ' ', $data[ $i ]['attributes']['class'] );
				}
				else {
					$data[ $i ]['attributes']['class'] = 'js-form-control-' . $field_id;
				}
			}

			// if element content is not empty and it's an array
			if ( ! empty( $data[ $i ]['content'] ) && is_array( $data[ $i ]['content'] ) ) {
				// go deeper to element content
				$data[ $i ]['content'] = self::addSpecialClasses( $data[ $i ]['content'] );
			}
		}

		return $data;
	}


	/**
	 * Insert values to elements
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function setValues( array $data ) {
		if ( ! empty( $this->values ) ) {

			// loop elements
			foreach ( $data as $i => $element ) {

				// if element value doesn't set and element has "name" attribute and value for that name are set
				if ( ! isset( $element['attributes']['value'] ) && ! empty( $element['attributes']['name'] ) && isset( $this->values[ $element['attributes']['name'] ] ) ) {

					// if element type is
					switch ( $element['type'] ) {
						case 'checkbox':
							// set true or false
							$data[ $i ]['attributes']['checked'] = ! empty( $this->values[ $element['attributes']['name'] ] );
							break;
						case 'textarea':
							// set the content to the element
							$data[ $i ]['content'] = $this->values[ $element['attributes']['name'] ];
							break;
						case 'select':
							// loop for content(select options)
							foreach ( $data[ $i ]['content'] as $j => $item ) {
								// if option value in values list
								if ( in_array( $item['attributes']['value'], $this->values[ $element['attributes']['name'] ] ) ) {
									$data[ $i ]['content'][ $j ]['attributes']['selected'] = true;
								}
							}
							break;
						default:
							// set the value to the element
							$data[ $i ]['attributes']['value'] = $this->values[ $element['attributes']['name'] ];
					}
				}

				// if element content is not empty and it's an array
				if ( ! empty( $data[ $i ]['content'] ) && is_array( $data[ $i ]['content'] ) ) {
					// go deeper to element content
					$data[ $i ]['content'] = self::setValues( $data[ $i ]['content'] );
				}
			}
		}

		return $data;
	}


	/**
	 * Сборка формы: значения добавляются в массив данных полей
	 *
	 * @param $request
	 */
	protected function build( $request ) {

		if ( method_exists( $this, 'set_form' ) ) {

			// getting form data array
			$this->form = $this->set_form();

			// adding special fields
			$this->addSpecialFields();

			// preparing the data array, with the insertion of the form element into the array, because it must be part of the set
			$this->form = Element::prepare( [ $this->form ] );

			// adding special classes
			$this->form = $this->addSpecialClasses( $this->form );

			// if form have method for getting values
			if ( method_exists( $this, 'get_values' ) ) {

				// getting values from DB
				$this->values = $this->get_values( $request );

				// setting values to each element
				$this->form = $this->setValues( $this->form );
			}
//			pr($this->form );
		}
	}

	/**
	 * Возвращение заполненных полей формы при запросе из vue.js
	 *
	 * @return array
	 */
	private function get_form_vue() {

		$form   = $this->form;
		$fields = array_values( $form['content'] );

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

		$form['content'] = $fields;

		return $form;
	}


	/**
	 * Возвращение заполненных полей формы том виде, в котором их запросил пользователь
	 *
	 * @param $request
	 *
	 * @return array|false|string
	 */
	public function get( $request ) {

		// return data if it has errors
		if(!empty($this->data['errors'])){
			return $this->data;
		}
		// построение формы с обязательными полями и значениями
		$this->build( $request );

		// return data if it has errors
		if(!empty($this->data['errors'])){
			return $this->data;
		}


		switch ( $request['response'] ) {
			case 'json':
				$this->data = wp_json_encode( $this->form, JSON_UNESCAPED_UNICODE );
				break;
			case 'vue':
				$this->data = $this->get_form_vue();

				break;
			case 'html':
				$this->data = Element::get( $this->form );

				break;
			default:
				$this->data = $this->form;
		}

		return $this->data;
	}
}


// eof
