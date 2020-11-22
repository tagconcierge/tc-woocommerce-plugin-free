<?php

namespace GtmEcommerceWoo\Utils;

function obWrap( callable $wrap ) {
	$cur_level = ob_get_level();

	try {
		ob_start();
		$wrap();
		return ob_get_clean();
	} catch ( Exception $e ) {
	} catch ( Throwable $e ) {
	}

	// clean the ob stack
	while ( ob_get_level() > $cur_level ) {
		ob_end_clean();
	}

	throw $e;
}
