<?php
/**
 * Date: 2019-02-24
 * @author Alex Isaenko <info@sh14.ru>
 */

namespace forms;

use Elements\Element;

abstract class forms {
	// name of function that process AJAX requests
	private $action = '';
	// request method
	private $method = 'post';
	// form id
	private $id = '';
	// formed array of form with values
	protected $form = [];
	// rendered form
	protected $data = [];
	// form values
	protected $values = [];

	// errors list
	public $errors = [];

	public function __construct( $request ) {

		// define form id by class
		$this->id = str_replace( '\\', '-', get_called_class() );

		// define name of function which process AJAX request
		$this->action = __NAMESPACE__ . '_ajax';

		// if the INIT method exists
		if ( method_exists( $this, 'init' ) ) {

			$this->init( $request );
		}

		// if we have errors
		if ( ! empty( $this->errors ) ) {
			$this->addError();
		}
		else {
			// if request key has been sent and same method exists
			if ( ! empty( $request['request'] ) && method_exists( $this, $request['request'] ) ) {

				if ( 'update' == $request['request'] ) {

					// reset response data
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
		$this->form['type']                 = 'form';
		$this->form['attributes']['id']     = $this->id;
		$this->form['attributes']['method'] = ! empty( $this->form['method'] ) ? $this->form['method'] : $this->method;

		// add "action" field to make wp-ajax works
		$this->form['content'][] = [
			'type'       => 'hidden',
			'attributes' => [
				'name'  => 'action',
				'value' => $this->action,
			],
		];


		// add "request" field for updating form on submit
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
			/*			if ( ! empty( $field_id = $element['attributes']['id'] ) ) {
							if ( ! empty( $element['attributes']['class'] ) ) {
								$data[ $i ]['attributes']['class']   = explode( ' ', $data[ $i ]['attributes']['class'] );
								$data[ $i ]['attributes']['class'][] = 'js-form-control-' . $field_id;
								$data[ $i ]['attributes']['class'][] = $field_id;
								$data[ $i ]['attributes']['class']   = join( ' ', $data[ $i ]['attributes']['class'] );
							}
							else {
								$data[ $i ]['attributes']['class'] = 'js-form-control-' . $field_id;
							}
						}*/

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
								if ( is_array( $this->values[ $element['attributes']['name'] ] ) ) {
									// if option value in values list
									if ( in_array( $item['attributes']['value'], $this->values[ $element['attributes']['name'] ] ) ) {
										$data[ $i ]['content'][ $j ]['attributes']['selected'] = true;
									}
								}
								else {
									if ( $item['attributes']['value'] == $this->values[ $element['attributes']['name'] ] ) {
										$data[ $i ]['content'][ $j ]['attributes']['selected'] = true;
									}
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
			if ( Element::isErrors() ) {

				$this->addError( Element::getErrors() );
			}

			// adding special classes
			$this->form = $this->addSpecialClasses( $this->form );

			// if form have method for getting values
			if ( method_exists( $this, 'get_values' ) ) {

				// getting values from DB
				$this->values = $this->get_values( $request );

				// setting values to each element
				$this->form = $this->setValues( $this->form );
			}
		}
	}

	/**
	 * Adding an error to the main errors list and to returnable Class properties.
	 *
	 * @param string|array $error
	 */
	protected function addError( $error = '' ) {
		if ( ! empty( $error ) ) {
			if ( is_string( $error ) ) {
				$this->errors[] = $error;
			}
			else if ( is_array( $error ) ) {
				$this->errors = array_merge( $this->errors, $error );
			}
		}

		// get names of returnable properties
		$props = [ 'data', 'form', 'values' ];

		// loop for properties
		foreach ( $props as $prop ) {

			// set property value
			$this->$prop = [
				'errors' => $this->errors,
			];
		}
	}

	/**
	 * Возвращение заполненных полей формы при запросе из vue.js
	 *
	 * @return array
	 */
	private function get_form_vue() {

		$form = $this->form;
		if ( empty( $form['content'] ) ) {
			$form['content'] = [];
		}
		$fields = array_values( $form['content'] );

		foreach ( $fields as $i => $field ) {

			if ( ! empty( $fields[ $i ]['type'] ) ) {

				// set type to lower case
				$fields[ $i ]['type'] = strtolower( $fields[ $i ]['type'] );

				// if field type is
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
		if ( ! empty( $this->data['errors'] ) ) {
			return $this->data;
		}
		// build the form with necessary fields and values
		$this->build( $request );

		// return data if it has errors
		if ( ! empty( $this->data['errors'] ) ) {
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
