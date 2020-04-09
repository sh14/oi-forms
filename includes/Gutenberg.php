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
				'name' => 'block_type[]',
				'data' => [
					'name' => 'block_type',
				],
				'class'=>bem('form.control._select js.block-type'),
			],
			'content'    => [],
		];

		// post content field template
		$templateBlockContent = [
			'type'       => 'textarea',
			'attributes' => [
				'name' => 'block_content[]',
				'data' => [
					'name' => 'block_content',
				],
				'class'=>bem('form.control'),
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
		$text = stripslashes( trim( strip_tags( $text, $allowabletags ) ) );

		return $text;
	}

	public static function get( $values, $allowedContentTags ) {

		$plus     = [
			'type'       => 'div',
			'attributes' => [
				'class' => bem( 'plus' ),
			],
			'content'    => [
				[
					'type'       => 'div',
					'attributes' => [
						'class' => bem( 'plus.button js.plus-wp-block' ),
					],
					'content'    => '+',
				],
			],
		];
		$blocks   = [];
		$blocks[] = [
			'type'    => 'legend',
			'attributes' => [
				'class' => bem( 'form.legend' ),
			],
			'content' => 'Content',
		];
		$blocks[] = $plus;
		$matches  = [];
		if ( ! empty( $values['post_content'] ) ) {
			preg_match_all( '/<!-- wp:(.*?) -->(.*?)<!-- \/wp:(.*?) -->/si', $values['post_content'], $matches );
		}


		// get templates
		$templates = self::getTemplates( $matches );

		// wp-block JS template
		$blocks[] = [
			'type'       => 'script',
			'attributes' => [
				'id'   => bem('template.wp-block',false,false),
				'type' => 'text/ejs',
				'class'=>bem('js.template-wp-block'),
			],
			'content'    => [
				[
					'type'       => 'div',
					'attributes' => [
						'class' => bem('form.group js.wp-block'),
					],
					'content'    => array_values( $templates ),
				],
			],
		];

		// let's add so many blocks as values we have
		if ( ! empty( $matches[2] ) ) {
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

				// in case of block is...
				if ( ! empty( $block[1] ) ) {

					// set block options
					$blockOptions['attributes']['value'] = esc_attr( $block[1] );

					$block[1] = (array) json_decode( $block[1] );
					if ( ! empty( $block[1]['level'] ) ) {
						$actualTag .= ' ' . $block[1]['level'];
					}
				}
				$blockOptions['attributes']['name'] = 'block_options[' . $i . ']';

				$blockType['attributes']['name'] = 'block_type[' . $i . ']';

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
				// block output as normal HTML(image HTML for example)
				$blockContent['content'] = $value;

				// add block to set of blocks
				$blocks[] = [
					'type'       => 'div',
					'attributes' => [
						'class' => bem('form.group js.wp-block'),
					],
					'content'    => [
						$blockOptions,
						$blockType,
						$blockContent,
						$plus,
					],
				];
			}
		}
		else {
			$blockOptions['attributes']['name'] = 'block_options[0]';
			$blockType['attributes']['name']    = 'block_type[0]';
			$blockContent['attributes']['name'] = 'block_content[0]';
			$blocks[]                           = [
				'type'       => 'div',
				'attributes' => [
					'class' => bem('form.group'),
				],
				'content'    => [
					$blockOptions,
					$blockType,
					$blockContent,
				],
			];
		}

		$fieldsSet = [
			'type'       => 'fieldset',
			'attributes' => [
				'class' => bem('form.fieldset'),
			],
			'content'    => $blocks,
			'html'       => '%%',
		];

		return $fieldsSet;
	}
}

// eof
