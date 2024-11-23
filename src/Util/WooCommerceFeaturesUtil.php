<?php

namespace GtmEcommerceWoo\Lib\Util;

class WooCommerceFeaturesUtil {

	public static function isHposEnabled() {
		if (class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class)) {
			return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		}
		return false;
	}
}
