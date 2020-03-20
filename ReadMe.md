### Произвольная обработка атрибутов
Для того, чтобы обработать отдельный атрибут так, как вам нужно, необходимо в классе элемента написать функцию, формат имени которой будет слудующим: `<имя атрибута>Attribute`

Если нужно обработать, таким образом, атрибут `align`, необходимо написать функцию `alignAttribute`, которая должна прнимать значение атрибута в качестве параметра.

**Пример:**
```php
private static function alignAttribute(){

    // code is here

}
```

## Example

```php
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
```php
if(empty($_POST['transfer_method'])){
    wp_redirect('?post_id' . $data['ID'] );
}else{
    return $data;
}
```
