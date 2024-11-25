<?php

namespace GtmEcommerceWoo\Lib\Util;

use Automattic\WooCommerce\Utilities\OrderUtil;

class WooCommerceFeaturesUtil {

	public static function isHposEnabled() {
		if (class_exists(OrderUtil::class)) {
			return OrderUtil::custom_orders_table_usage_is_enabled();
		}
		return false;
	}
}
