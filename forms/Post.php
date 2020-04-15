<?php
/**
 * Date: 2020-03-05
 * @author Isaenko Alexey <info@oiplug.com>
 */

namespace myTheme;

use function forms\bem;
use forms\forms;
use forms\Init;
use forms\Gutenberg;
use function \oifrontend\image_uploader\uploadable_image;
use function forms\isRole;
use function forms\current_user_can_edit;
use function forms\pr;
use function forms\get_plugin_url;

class Post extends forms {

	private $categories;
	private $user_id = 0;
	private $post_id = 0;
	// tags that will not be removed in the values
	private $allowedContentTags = [ 'strong', 'b', 'i', 'quote', 'figure', 'img' ];

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
			wp_enqueue_script( $handle, get_plugin_url() . 'assets/js/Post.js', [ 'oijq' ], Init::$data['version'], true );
			wp_localize_script(
				$handle,
				$handle,
				[
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
//			'ajax_nonce' => wp_create_nonce( 'oiproaccount' ),
				]
			);
		}
	}

	/**
	 * Determining of all form fields without values
	 */
	public function set_form() {

		// get all categories even empty
		$this->categories = get_categories(
			[ 'hide_empty' => false, ]
		);

		$fields = [];

		$categoryOptions = [];
		// making of options field of each category
		foreach ( $this->categories as $category ) {
			$categoryOptions[] = [
				'type'       => 'option',
				'attributes' => [
					'value' => $category->term_id,
				],
				'content'    => $category->name,
			];
		}

		// post title field
		$fields[] = [
			'type'       => 'hidden',
			'attributes' => [
				'name' => 'ID',
			],
		];

		// post thumbnail field
		if ( function_exists( '\oifrontend\image_uploader\uploadable_image' ) ) {
			$fields[] = [
				'type' => 'html',
				'html' => '<div class="form__group thumbnail js-thumbnail">'
				          . uploadable_image( [
						'post_id'     => $this->post_id,
						// block styles
						'styles'      => 'padding-top:100%;',
						// width
						'width'       => 1000 * 4 / 5,
						'height'      => 1000,
						// max width
						'maxwidth'    => 2160,
						'destination' => 'post_thumbnail',
						'size'        => 'contain',
						// image upload template form name
						'slug'        => 'form',
						// image upload template form name variation
						'name'        => 'crop-upload',
					] )
				          . '</div>',
			];
		}

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

		// post title field
		$fields[] = [
			'type'       => 'select',
			'attributes' => [
				'name'  => 'post_category',
				'class' => bem( 'form.control._select' ),
			],
			'vars'       => [
				'label' => __( 'Category', __NAMESPACE__ ),
				'hint'  => __( '', __NAMESPACE__ ),
			],
			'content'    => $categoryOptions,
		];

		// add Gutenberg compatible fields for post content
		$fields[] = Gutenberg::get( $this->values, $this->allowedContentTags );

		// post title field
		$fields[] = [
			'type'       => 'text',
			'attributes' => [
				'name'  => 'tags_input',
				'class' => bem( 'form.control._text' ),
			],
			'vars'       => [
				'label' => __( 'Tags', __NAMESPACE__ ),
				'hint'  => __( 'Tags should be separated by comma', __NAMESPACE__ ),
			],
		];

		// if user has an author role or higher
		if ( isRole( 'author' ) ) {

			// post date field will be added
			$fields[] = [
				'type'       => 'datetime-local',
				'attributes' => [
					'name'  => 'post_date',
					'class' => bem( 'form.control._text._date' ),
				],
				'vars'       => [
					'label' => __( 'Publication date', __NAMESPACE__ ),
				],
			];
		}

		// post status
		$fields[] = [
			'type'       => 'select',
			'attributes' => [
				'name'  => 'post_status',
				'class' => bem( 'form.control._select._status' ),
			],
			'vars'       => [
				'label' => __( 'Status', __NAMESPACE__ ),
			],
			'content'    => [
				[
					'type'       => 'option',
					'attributes' => [
						'value' => 'draft',
					],
					'content'    => __( 'Draft', __NAMESPACE__ ),
				],
				[
					'type'       => 'option',
					'attributes' => [
						'value' => 'publish',
					],
					'content'    => __( 'Publish', __NAMESPACE__ ),
				],
			],
		];

		// submit button
		$fields[] = [
			'type'       => 'button',
			'content'    => __( 'Submit', __NAMESPACE__ ),
			'attributes' => [
				'type'  => 'submit',
				'class' => bem( 'form.submit' ),
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

		$form = [
			'type'       => 'form',
			'attributes' => [
				'method' => 'post',
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

			// get post tags
			$post_tags = get_the_tags( $this->post_id );
			$tags      = [];
			if ( ! empty( $post_tags ) ) {
				foreach ( $post_tags as $tag ) {
					$tags[] = $tag->name;
				}
			}
			$tags = join( ', ', $tags );
			// add list of tags to post data
			$post['tags_input'] = $tags;

			// convert date for using in datetime-local input
			$post['post_date'] = str_replace( ' ', 'T', $post['post_date'] );
		}
		$this->values = $post;

		return $post;
	}

	/**
	 * Parsing of given tags with ability to save them for pointed post.
	 *
	 * @param     $tags
	 *
	 * @return array
	 */
	private function parseTags( $tags = [] ) {
		if ( empty( $tags ) ) {
			return [];
		}

		if ( ! is_array( $tags ) ) {
			$tags = mb_strtolower( $tags );
			preg_match_all( '/([\p{Cyrillic}a-z0-9_]+)/u', $tags, $tags_new );
			$tags = $tags_new[1];
		}

		if ( ! empty( $tags ) ) {
			$tags = array_unique( $tags );
		}

		return $tags;
	}

	/**
	 * Removing unnecessary tags from text besides of allowed.
	 *
	 * @param string $text
	 * @param string $allowabletags
	 *
	 * @return string
	 */
	private function simplify( $text, $allowabletags = '' ) {
		$text = stripslashes( trim( strip_tags( $text, $allowabletags ) ) );
		$text = str_replace( '--', '-', $text );

		return $text;
	}

	/**
	 * Updating of pointed post.
	 *
	 * @return array
	 */
	public function update() {

		// user cant update if has not enough privileges
		if ( ! isRole( 'contributor' ) ) {

			// set error message
			return [
				'errors' => [
					401 => __( 'Если вы автор статьи, вам необходимо авторизоваться в своем аккаунте.', __NAMESPACE__ ),
				]
			];
		}

		$post = $_POST;

		// if pointed post is editing
		if ( ! empty( $this->post_id ) ) {

			// if user cant edit that post
			if ( ! current_user_can_edit( $this->post_id ) ) {

				// post is already published
				if ( 'publish' == $post['post_status'] ) {
					return [
						'errors' => [
							401 => __( 'The post is already published it cannot be edited.', __NAMESPACE__ ),
						]
					];
				}
				else {

					// just not enough privileges
					return [
						'errors' => [
							401 => __( 'You have to be authorized for editing the post. ', __NAMESPACE__ ),
						]
					];
				}
			}

			$post['ID'] = $this->post_id;
		}

		// if the user doesn't have necessary role but tries to publish
		if ( 'publish' == $post['post_status'] && ! isRole( 'author' ) ) {

			// set pending status
			$post['post_status'] = 'pending';
			// don't use new date
			unset( $post['post_date'] );
		}
		else {
			// convert date from datetime-local input to sql format
			$post['post_date'] = str_replace( 'T', ' ', $post['post_date'] );

			// if post date erlier then now
			if ( strtotime( $post['post_date'] ) < strtotime( current_time( 'mysql' ) ) ) {
				// don't use new date
				unset( $post['post_date'] );
			}
		}

		$post['post_title']     = ! empty( $post['post_title'] ) ? $this->simplify( $post['post_title'] ) : 'no-name';
		$content                = Gutenberg::set( $post, $this->allowedContentTags );
		$post['block_content']  = $content['block_content'];
		$post['post_content']   = $content['post_content'];
		$post['post_type']      = 'post';
		$post['comment_status'] = 'open';
		$post['post_category']  = ! empty( $post['post_category'] ) ? array_map( 'absint', explode( ',', $post['post_category'] ) ) : [];
		$post['tags_input']     = ! empty( $post['tags_input'] ) ? $this->parseTags( $post['tags_input'] ) : '';

		$this->post_id = wp_insert_post( $post, true );

		// если при сохранении публикации не произошло ошибок
		if ( ! is_wp_error( $this->post_id ) ) {

			$post['post_id']    = $this->post_id;
			$post['post_title'] = 'no-name' != $post['post_title'] ? $post['post_title'] : '';

			/*			// сохранение "обложки" записи
						update_post_meta( $this->post_id, 'post_thumb', array(
							$post['post_thumb'],
							$post['post_thumb_index'],
						) );

						// сохранение всего массива
						update_post_meta( $this->post_id, 'interview', $post );*/

			// если пост публикуется
			if ( 'publish' == $post['post_status'] ) {
				do_action( __CLASS__ . '_after_post_publish', $post['post_author'], $this->post_id, $post['publication_type'], $post );
			}
		}
		else {

			// добавление информации об ошибке
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


// eof
