<?php
/**
 * Date: 2019-05-06
 * @author Isaenko Alexey <info@oiplug.com>
 */


function processAttributeValues( $attributeValues, $values = [], $delimiter = ' ', $prefix = '', $depth = 0 ) {
	if ( ! is_array( $delimiter ) ) {
		$delimiter = [ $delimiter ];
	}
//	if($depth>0)return $depth;
	foreach ( $attributeValues as $key => $value ) {
//return $delimiter[ $depth ];

		if ( is_array( $value ) ) {
			$separator = $delimiter[ 1 ];
		} else  {
			$separator = $delimiter[ 2 ];
		}

		if ( is_numeric( $key ) ) {
			$keyPrefix = $prefix . $separator;
		} else {
			$keyPrefix = $prefix . $key . $separator;
		}

		if ( is_array( $value ) ) {
			$values = processAttributeValues( $value, $values, $delimiter, $keyPrefix, ++ $depth );
			continue;
		}

		$values[] = $keyPrefix . $value;

	}

	return $values;
}

$delimiter = [';','-',':',    ];
$s         = [
	'style' => [
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
];


print_r( processAttributeValues( $s['style'], [], $delimiter ) );
// eof
