<?php
/**
 * Date: 2019-05-05
 * @author Isaenko Alexey <info@oiplug.com>
 */

namespace forms;

require 'elements/abstractField.php';
require 'elements/Select.php';
$form = [
	'elements' => [
		[
			'type'       => 'select',
			'options'    => [
				'one',
				'two',
				'three',
			],
			'value'      => 3,
			'attributes' => [
				'options' => 'aaa',
				'required' => '',
				'name'     => 'categories',
				'data'     => [
					'post'  => 15,
					'check' => true,
					'name'  => 'small caption',
				],
				'class'    => [
					'form-control',
					'light',
				],
				'style'    => [
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
					'type'       => 'option',
					'content'    => 'News',
					'attributes' => [
						'value' => 3,
					],
				],
				[
					'type'       => 'option',
					'content'    => 'Interview',
					'attributes' => [
						'value' => 7,
					],
				],
			],
			'vars'       => [
				'label' => 'Select category',
			],
			'html'       => '
				<div class="form-group">
					<label for="%name%" class="form-group__label">%label%</label>
					<div class="form-group__input">
						%%
					</div>
				</div>
				',
		],
	],
];
print_r( Select::element( $form['elements'] ) );

// eof
