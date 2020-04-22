# Oi Forms

This plugin is intended for web developers who need to create many forms and keep them under full control.

With that plugin you can create any form for your Wordpress website.

### How to

You can create a class of your form, including the following methods:

* init - method which will always be called if exists;
* set_form - method for creating form's array;
* get_values - method for getting values from DB;
* update - method which updates data sent in the request.

You can put your class in the "oi-forms" folder, previously created in the active theme or create standalone plugin that will call your class.

**Code of example form class:**
```$php
namespace MyNameSpace;

class MyForm extends forms {

	private $user_id = 0;
	private $post_id = 0;

	/**
	 * Initialization
	 *
	 * @param $request
	 */
	protected function init( $request ) {

		// if user is not authorized or has not enough privileges
		if ( ! is_user_logged_in() || ! isRole( 'contributor' ) ) {
			$this->error = __( 'You have to be authorized.', __NAMESPACE__ );
		}
		else {

			$this->user_id = ! empty( $_GET['user_id'] ) ? $_GET['user_id'] : 0;

			if ( ! empty( $request['ID'] ) ) {
				$this->post_id = $request['ID'];
			}
			else {
				$this->post_id = ! empty( $_GET['post_id'] ) ? $_GET['post_id'] : 0;
			}

			// getting values from DB
			$this->values = $this->get_values( $request );

			// convert list of tags to PHP 5 style
			$this->allowedContentTags = join( '', array_map( function ( $tag ) {
				return "<{$tag}>";
			}, $this->allowedContentTags ) );

			$handle = ( str_replace( '\\', '', __CLASS__ ) );
			wp_enqueue_style( $handle, Init::$data['url'] . 'assets/css/style.css', [], Init::$data['version'] );
			wp_enqueue_script( $handle, Init::$data['url'] . 'assets/js/functions.js', [ 'oijq' ], Init::$data['version'], true );
			wp_localize_script(
				$handle,
				$handle,
				[
					'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
					'ajaxNonce' => wp_create_nonce( 'mynonce' ),
				]
			);
		}
	}
	
	public function set_form() {
	
	    $fields = [];
	    
		// post id field
		$fields[] = [
			'type'       => 'hidden',
			'attributes' => [
				'name' => 'ID',
			],
		];
	    
		// post title field
		$fields[] = [
			'type'       => 'text',
			'attributes' => [
				'name'  => 'post_title',
				'class' => bem( 'form.control._text' ),
			],
			'vars'       => [
				'label' => __( 'Title', __NAMESPACE__ ),
			],
		];
	    
		// submit button
		$fields[] = [
			'type'       => 'button',
			'content'    => __( 'Submit', __NAMESPACE__ ),
			'attributes' => [
				'type'  => 'submit',
				'class' => bem( 'form.submit js.submit' ),
			],
		];

		// loop for fields
		foreach ( $fields as $i => $field ) {
			// if it is not a button
			if ( ! in_array( $fields[ $i ]['type'], [ 'button', 'html' ] ) && ! isset( $fields[ $i ]['html'] ) ) {
				// if we have some data in field
				$label = ! empty( $fields[ $i ]['vars']['label'] ) ? '<label' . bem( 'form.label', true ) . 'for="%id%">%label%</label>' : '';
				$hint  = ! empty( $fields[ $i ]['vars']['hint'] ) ? '<div' . bem( 'form.hint', true ) . '>%hint%</div>' : '';
				// add it to HTML pattern
				$fields[ $i ]['html'] = '<div' . bem( 'form.group', true ) . '>'
				                        . $label
				                        . '<div' . bem( 'form.field', true ) . '>'
				                        . '%%'
				                        . '</div>'
				                        . $hint
				                        . '</div>';
			}
		}
		$form     = [
			'type'       => 'form',
			'attributes' => [
				'method' => 'post',
				'class'  => bem( 'product.one.two.three._first._second js.form' ),
			],
			'content'    => $fields,
		];

		return $form;
    }	
    
	/**
	 * Getting of previously saved values
	 *
	 * @param $request
	 *
	 * @return array
	 */
	public function get_values( $request ) {
		$post = [];

		// if post ID not empty
		if ( ! empty( $this->post_id ) ) {
			// get post data
			$post = get_post( $this->post_id, ARRAY_A );
		}
		$this->values = $post;

		return $post;
    }

	/**
	 * Updating of pointed post.
	 *
	 * @return array
	 */
	public function update( $request ) {

		// user cant update if has not enough privileges
		if ( ! isRole( 'contributor' ) ) {

			// set error message
			return [
				'errors' => [
					401 => __( 'Aothorization failed.', __NAMESPACE__ ),
				]
			];
		}

		$post = $request;

		// if pointed post is editing
		if ( ! empty( $this->post_id ) ) {
			$post['ID'] = $this->post_id;
		}

		$this->post_id = wp_insert_post( $post, true );

		if ( ! is_wp_error( $this->post_id ) ) {

			$post['ID']         = $this->post_id;
			$post['post_title'] = 'no-name' != $post['post_title'] ? $post['post_title'] : '';
		}
		else {
			$post['errors'] = $this->post_id->get_error_message();
		}

		// if transfer method doesn't set, it mean that form has been sent via usual method with page reload
		if ( empty( $post['transfer_method'] ) ) {
			// redirect to edit created/edited post
			if ( wp_redirect( '?post_id=' . $this->post_id ) ) {
				exit;
			}
		}

		return $post;
	}
}
```

To call your form on any page or in a template you have to write a shortcode.

Form id in a shortcode should be separated with a - symbol instead of slash.

**Shortcode example:**
```$php
[form form_id="MyNameSpace-MyForm"]
```

If you want to use standalone plugin for your form you can do it like that:

**Plugin main file example**
```$php
/**
 * Plugin Name: My Form Plugin
 * Plugin URI: https://sh14.ru/
 * Description: Post submit form.
 * Author: John Dou
 * Version: 1.0
 */

Namespace MyNameSpace;

function isFormsExists() {
	if ( class_exists( 'forms\forms' ) ) {
		require 'PostForm.php';
	}
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\isFormsExists' );

``` 


**Just form array example**
```$php
$form = [
	[
		'type'    => 'Form',
		'content' => [
			[
				'type'       => 'Select',
				'attributes' => [
					'super'      => '',
					'user-attr' => 'aaa',
					'required'  => '',
					'name'      => 'categories[]',
					'data'      => [
						'post'   => 15,
						'check'  => true,
						'name'   => 'small caption',
						'inside' => [
							'one' => 1,
							'two' => 2,
						],
					],
					'asd'     => 'zxc vbn mkj',
					'class'     => [
						'form-control',
						'light',
					],
					'style'     => [
						'color'  => 'red',
						'border' => [
							'width' => [
								'top'    => '1px',
								'right'  => '2px',
								'bottom' => '3px',
								'left'   => '4px',
							],
							'style' => 'solid',
							'color' => 'black',
						],
					],
				],
				'content'    => [
					[
						'type'       => 'Option',
						'content'    => 'News',
						'attributes' => [
							'value' => 3,
							'selected' => true,
						],
					],
					[
						'type'       => 'Option',
						'content'    => 'Interview',
						'attributes' => [
							'value' => 7,
						],
					],
				],
				'vars'       => [
					'label' => 'Select category',
				],
				'html'       => '<div class="form-group">'
				                . '<label for="%id%" class="form-group__label">%label%</label>'
				                . '<div class="form-group__input">%%</div>'
				                . '</div>',
			],
			[
				'type'       => 'input',
				'attributes'=>[
					'name'      => 'categories[]',
					'value'      => 'text 4',
				],
				'html'    => '<div class="form__control">%%</div>',
			],
		],
		'html'    => '<div class="form">%%</div>',
	],
];

print_r( Element::get( $form ) );

```


### Redirect

You can check for transfer method and if it not an `ajax` or `api` the the data has been sent via usual method with page reloading
```$php
if(empty($_POST['transfer_method'])){
    wp_redirect('?post_id' . $data['ID'] );
}else{
    return $data;
}
```
