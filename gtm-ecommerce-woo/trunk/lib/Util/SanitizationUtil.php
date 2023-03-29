<?php

namespace GtmEcommerceWoo\Lib\Util;

class SanitizationUtil {

	const WP_KSES_ALLOWED_HTML = [
		'a' => [
			'id' => [],
			'href' => [],
			'target' => [],
			'class' => [],
			'style' => [],
		],
		'br' => [],
		'div' => [
			'id' => [],
			'style' => [],
			'class' => [],
		],
		'p' => [
			'id' => [],
			'style' => [],
			'class' => [],
		],
		'span' => [
			'id' => [],
			'style' => [],
			'class' => [],
		],
		'b' => [],
		'h3' => [
			'id' => [],
			'style' => [
				'display' => ['none']
			],
			'class' => [],
		],
	];

	const WP_KSES_ALLOWED_PROTOCOLS = [
		'http', 'https'
	];
}
