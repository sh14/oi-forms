<?php
/**
 * Date: 2020-03-05
 * @author Isaenko Alexey <info@oiplug.com>
 */

namespace myTheme;

use forms\forms;
use forms\Init;
use forms\Gutenberg;
use function \oifrontend\image_uploader\uploadable_image;
use function forms\get_post_publication_date;
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
	 * Инициализация
	 *
	 * @param $request
	 */
	protected function init( $request ) {

		// если пользователь не авторизован или не имеет достаточно прав
		if ( ! is_user_logged_in() || ! isRole( 'contributor' ) ) {
			$this->error = __( 'Необходимо аторизоваться.', __NAMESPACE__ );
		}
		else {

			$this->user_id = ! empty( $_GET['user_id'] ) ? $_GET['user_id'] : 0;
			$this->post_id = ! empty( $_GET['post_id'] ) ? $_GET['post_id'] : 0;

			// getting values from DB
			$this->values = $this->get_values( $request );

			// convert list of tags to PHP 5 style
			$this->allowedContentTags = join( '', array_map( function ( $tag ) {
				return "<{$tag}>";
			}, $this->allowedContentTags ) );

			wp_enqueue_script( 'oijq' );
			wp_enqueue_script( 'oi-form-post', get_plugin_url() . '/js/Post.js', [ 'oijq' ], Init::$data['version'], true );
		}
	}

	/**
	 * Определение всех полей формы без значений
	 */
	public function set_form() {


		// get all categories even empty
		$this->categories = get_categories(
			[ 'hide_empty' => false, ]
		);

		$fields = [];

		$categoryOptions = [];
		// формирование option для каждой рубрики
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

		if ( function_exists( '\oifrontend\image_uploader\uploadable_image' ) ) {
			$fields[] = [
				'type' => 'html',
				'html' => '<div class="form__group thumbnail js-thumbnail">'
				          . uploadable_image( [
						'post_id'     => $this->post_id,
//						'user_id'     => $atts['user_id'],
//						// условие - можно ли изменять выводимое изображение
//						'can_edit'    => true,
//						// путь к изображению
//						'image'       => '',
						// стили блока с изображением
						'styles'      => 'padding-top:100%;',
						// ширина
						'width'       => 1000 * 4 / 5,
						'height'      => 1000,
						// максимальный размер по бóльшей стороне
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
				'name' => 'post_title',
			],
			'vars'       => [
				'label' => __( 'Title', __NAMESPACE__ ),
			],
		];

		// post title field
		$fields[] = [
			'type'       => 'select',
			'attributes' => [
				'name' => 'post_category',
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
				'name' => 'tags_input',
			],
			'vars'       => [
				'label' => __( 'Tags', __NAMESPACE__ ),
				'hint'  => __( 'Tags should be separated by comma', __NAMESPACE__ ),
			],
		];

		// добавление кнопки отправки формы
		$fields[] = [
			'type'       => 'button',
			'content'    => __( 'сохранить', __NAMESPACE__ ),
			'attributes' => [
				'type'  => 'submit',
				'class' => 'form__submit pull-right button',
			],
		];

		// loop for fields
		foreach ( $fields as $i => $field ) {
			// if it is not a button
			if ( ! in_array( $fields[ $i ]['type'], [ 'button', 'html' ] ) && ! isset( $fields[ $i ]['html'] ) ) {
				// if we have some data in field
				$label = ! empty( $fields[ $i ]['vars']['label'] ) ? '<label class="form__label" for="%id%">%label%</label>' : '';
				$hint  = ! empty( $fields[ $i ]['vars']['hint'] ) ? '<div class="form__hint">%hint%</div>' : '';
				// add it to HTML pattern
				$fields[ $i ]['html'] = '<div class="form__group">'
				                        . $label
				                        . '<div class="form__input">'
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
	 * получение сохраненных ранее значений
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
		}
		$this->values = $post;

//		pr( $post );

		return $post;
	}

	/**
	 * Обработка списка тегов, с возможностью сохранения для указанного поста
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
	 * Удаляет из текста лишнее, кроме разрешенных тегов
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

	public function update() {

		// пользователь не может редактировать публикацию
		if ( ! isRole( 'contributor' ) ) {

			// у пользователя не достаточно прав для редактирования
			return array(
				'errors' => array(
					401 => __( 'Если вы автор статьи, вам необходимо авторизоваться в своем аккаунте.', __NAMESPACE__ ),
				)
			);
		}

		$post = $_POST;

		// если происходит редактирование указанного поста
		if ( ! empty( $this->post_id ) ) {

			// если у пользователя нет прав
			if ( ! current_user_can_edit( $this->post_id ) ) {

				// статья уже опубликована
				if ( 'publish' == $post['post_status'] ) {
					return array(
						'errors' => array(
							401 => __( 'Данная статья уже опубликована, ее нельзя изменить.', __NAMESPACE__ ),
						)
					);
				}
				else {

					// просто не достаточно прав
					return array(
						'errors' => array(
							401 => __( 'Для внесения изменений в публикацию необходимо аторизоваться.', __NAMESPACE__ ),
						)
					);
				}
			}

			$post['ID'] = $this->post_id;
			// дата публикации меняется на текущую, если пост публикуется, не сохраняется как черновик
			$post['post_date'] = get_post_publication_date( $this->post_id, $post['post_status'] );
		}

		// если пользователь не имеет необходимой роли
		if ( 'publish' == $post['post_status'] && ! isRole( 'author' ) ) {

			// статус устанавливается в режим ожидания
			$post['post_status'] = 'pending';
		}

		$post['post_title'] = ! empty( $post['post_title'] ) ? $this->simplify( $post['post_title'] ) : 'no-name';
		foreach ( $post['block_content'] as $i => $value ) {
			if ( ! empty( $post['block_content'][ $i ] ) ) {
				$post['block_content'][ $i ] = $this->simplify( $post['block_content'][ $i ], $this->allowedContentTags );
				$options                     = ! empty( $post['block_options'][ $i ] ) ? ' ' . stripslashes( $post['block_options'][ $i ] ) : '';
				$optionsArray                = ! empty( $options ) ? (array) json_decode( trim( $options ) ) : [];
//				pr( __LINE__, $optionsArray );
				list( $tagType ) = explode( ' ', $post['block_type'][ $i ] );
				switch ( $tagType ) {
					case 'paragraph':
						$tag = 'p';
						break;
					case 'heading':
						$tag = 'h';

						if ( ! empty( $optionsArray['level'] ) ) {
							$tag .= $optionsArray['level'];
						}
						else {
							$tag .= '2';
						}
						break;
					case 'image':
						$tag = '';
						break;
				}
				$tagStart                    = ! empty( $tag ) ? "<{$tag}>" : '';
				$tagEnd                      = ! empty( $tag ) ? "</{$tag}>" : '';
				$post['block_content'][ $i ] = '<!-- wp:' . $tagType . $options . ' -->' . PHP_EOL
				                               . $tagStart . $post['block_content'][ $i ] . $tagEnd . PHP_EOL
				                               . '<!-- /wp:' . $tagType . ' -->' . PHP_EOL
				                               . PHP_EOL;
//				pr( __LINE__, $post['block_content'][ $i ], true );
			}
		}

//		die;
		$post['post_content']   = join( '', $post['block_content'] );
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
