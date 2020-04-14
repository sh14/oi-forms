<?php
/**
 * Date: 2020-04-07
 * @author Isaenko Alexey <info@oiplug.com>
 */


namespace forms;

class Gutenberg {
	public static $options = [
		'paragraph' => '',
		'heading'   => '',
		'heading 3' => '{"level":3}',
		'heading 4' => '{"level":4}',
//			'image'     => '',
	];

	public static function getTemplates( $matches ) {
		// post content field template
		$templateBlockType = [
			'type'       => 'select',
			'attributes' => [
				'name'  => 'block_type[]',
				'data'  => [
					'name' => 'block_type',
				],
				'class' => bem( 'dynamic.control._select js.block-type' ),
			],
			'content'    => [],
		];

		// post content field template
		$templateBlockContent = [
			'type'       => 'textarea',
			'attributes' => [
				'name'  => 'block_content[]',
				'data'  => [
					'name' => 'block_content',
				],
				'class' => bem( 'dynamic.control._textarea js.block-content' ),
			],
		];

		// post content field template
		$templateBlockOptions = [
			'type'       => 'hidden',
			'attributes' => [
				'name' => 'block_options[]',
				'data' => [
					'name' => 'block_options',
				],
				'class' => bem( 'js.block-options' ),
			],
		];

		// default block types order
		$options = self::$options;

		if ( ! empty( $matches[0] ) ) {

			// adding Gutenberg block options from content to $options and correct them values
			foreach ( $matches[0] as $i => $value ) {
				// block options
				$block = explode( ' ', $matches[1][ $i ], 2 );
				$key   = $block[0];

				if ( ! empty( $block[1] ) ) {

					$block[1] = (array) json_decode( $block[1] );
					if ( ! empty( $block[1]['level'] ) ) {
						$key .= ' ' . $block[1]['level'];
					}
					if ( ! empty( $block[1] ) ) {
						$options[ $key ] = $block[1];
					}
				}
				else {
					$options[ $key ] = '';
				}
			}
		}

		// loop for Gutenberg options
		foreach ( $options as $key => $value ) {
			$templateBlockType['content'][] = [
				'type'       => 'option',
				'attributes' => [
					'value' => $key,
					'data'  => [
						'options' => ! empty( $value ) ? esc_attr( json_encode( $value ) ) : '',
					],
				],
				'content'    => $key,
			];
		}

		$templateBlockType['content'][] = [
			'type'       => 'option',
			'attributes' => [
				'value' => 'remove',
				'class' => bem( 'js.remove-wp-block' ),
			],
			'content'    => __( 'Remove block', __NAMESPACE__ ),
		];

		return [
			'options' => $templateBlockOptions,
			'type'    => $templateBlockType,
			'content' => $templateBlockContent,
		];
	}

	/**
	 * Удаляет из текста лишнее, кроме разрешенных тегов
	 *
	 * @param string $text
	 * @param string $allowabletags
	 *
	 * @return string
	 */
	private static function simplify( $text, $allowabletags = '' ) {
		if ( empty( $text ) ) {
			return '';
		}
		$text = stripslashes( trim( strip_tags( $text, $allowabletags ) ) );

		return $text;
	}

	public static function get( $values, $allowedContentTags ) {

		$addButton = [
			'type'       => 'div',
			'attributes' => [
				'class' => bem( 'dynamic.add' ),
			],
			'content'    => [
				[
					'type'       => 'button',
					'attributes' => [
						'type'  => 'button',
						'class' => bem( 'dynamic.add-button js.add-wp-block' ),
					],
					'content'    => '+',
				],
			],
		];

		// set type of $blocks
		$blocks   = [];
		$blocks[] = [
			'type'       => 'legend',
			'attributes' => [
				'class' => bem( 'dynamic.legend' ),
			],
			'content'    => 'Content',
		];
		$blocks[] = $addButton;
		$matches  = [];
		if ( ! empty( $values['post_content'] ) ) {
			preg_match_all( '/<!-- wp:(.*?) -->(.*?)<!-- \/wp:(.*?) -->/si', $values['post_content'], $matches );
		}

		// get templates
		$templates = self::getTemplates( $matches );

		// block, contains all templates
		$templateBlock = [
			'type'       => 'div',
			'attributes' => [
				'class' => bem( 'dynamic.group js.wp-block' ),
			],
			'content'    => array_merge( array_values( $templates ), [ $addButton ] ),
		];

		// wp-block JS template
		$blocks[] = [
			'type'       => 'script',
			'attributes' => [
				'id'    => bem( 'template.wp-block', false, false ),
				'type'  => 'text/ejs',
				'class' => bem( 'js.template-wp-block' ),
			],
			'content'    => [ $templateBlock ],
		];

		// if we don't have any blocks
		if ( empty( $matches[2] ) ) {
			// let's create one paragraph with empty value
			$matches = [
				[ '' ],
				[ 'paragraph' ],
				[ '' ],
			];
		}

		// loop for Gutenberg blocks
		foreach ( $matches[2] as $i => $value ) {

			$value = self::simplify( $value, $allowedContentTags );

			$blockOptions = $templates['options'];
			$blockType    = $templates['type'];
			$blockContent = $templates['content'];

			// tag options
			$block = explode( ' ', $matches[1][ $i ], 2 );

			// actual tag of current block
			$actualTag = $block[0];

			// if block has options
			if ( ! empty( $block[1] ) ) {

				// set block options to special field
				$blockOptions['attributes']['value'] = esc_attr( $block[1] );

				// convert options to an array
				$block[1] = (array) json_decode( $block[1] );
				if ( ! empty( $block[1]['level'] ) ) {
					// add to tag name option part
					$actualTag .= ' ' . $block[1]['level'];
				}
			}
			$blockOptions['attributes']['name'] = 'block_options[' . $i . ']';
			$blockType['attributes']['name']    = 'block_type[' . $i . ']';

			// set selected tag in select
			foreach ( $blockType['content'] as $j => $option ) {

				// set "selected" or not
				if ( $actualTag == $option['attributes']['value'] ) {
					$blockType['content'][ $j ]['attributes']['selected'] = true;
				}
			}

			if ( 'image' == $actualTag ) {
				$blockContent['type']                = 'hidden';
				$blockContent['attributes']['value'] = esc_attr( $value );
			}
			$blockContent['attributes']['name'] = 'block_content[' . $i . ']';
			$blockContent['content']            = $value;
			$templateBlock['content']           = [
				$blockOptions,
				$blockType,
				$blockContent,
				$addButton,
			];
			$blocks[]                           = $templateBlock;
		}

		$fieldsSet = [
			'type'       => 'fieldset',
			'attributes' => [
				'class' => bem( 'dynamic' ),
			],
			'content'    => $blocks,
			'html'       => '%%',
		];

		return $fieldsSet;
	}

	public static function set($post,$allowedContentTags){
		foreach ( $post['block_content'] as $i => $value ) {
			if ( ! empty( $post['block_content'][ $i ] ) ) {
				$post['block_content'][ $i ] = self::simplify( $post['block_content'][ $i ], $allowedContentTags );
				$options                     = ! empty( $post['block_options'][ $i ] ) ? ' ' . stripslashes( $post['block_options'][ $i ] ) : '';
				$optionsArray                = ! empty( $options ) ? (array) json_decode( trim( $options ) ) : [];
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
			}
		}

		return join( '', $post['block_content'] );
	}
}

// eof
