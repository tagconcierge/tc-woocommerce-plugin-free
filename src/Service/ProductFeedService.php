<?php

namespace GtmEcommerceWoo\Lib\Service;

use GtmEcommerceWoo\Lib\Util\WpSettingsUtil;

/**
 * Service to inject dataLayer eCommerce events inspector which is a box
 * fixed to the bottom part of the browser.
 *
 * To enable special demo mode: wp option set gtm_ecommerce_woo_event_inspector_demo_mode 1
 */
class ProductFeedService {
	protected $snakeCaseNamespace;
	protected $wpSettingsUtil;
	protected $types = ['google'];
	protected $headers = [
		'google' => [
			'id',
			'title',
			'description',
			'price',
			'condition',
			'link',
			'availability',
			'image_link',
			'item_group_id'
		]
	];
	protected $defaultSchedule = [
		'google' => '00:30:00'
	];


	public function __construct( string $snakeCaseNamespace, WpSettingsUtil $wpSettingsUtil ) {
		$this->snakeCaseNamespace = $snakeCaseNamespace;
		$this->wpSettingsUtil = $wpSettingsUtil;
	}

	public function initialize() {
		add_filter( 'cron_schedules', [$this, 'schedules']);

		$cronName = $this->snakeCaseNamespace . '_product_feed';


		// TODO: support multiple types
		if ('1' !== $this->wpSettingsUtil->getOption('product_feed_google_enabled')) {
			$timestamp = wp_next_scheduled( $cronName );
			wp_unschedule_event( $timestamp, $cronName );
			return;
		}

		add_action( $cronName, [$this, 'cronJob'] );
		if ( ! wp_next_scheduled( $cronName ) ) {
			wp_schedule_event( time(), 'minutely', $cronName );
		}
	}

	public function schedules( $schedules ) {
		$schedules['minutely'] = array(
			'interval' => 60,
			'display' => __('Once a minute')
		);
		return $schedules;
	}

	public function generateRandomString() {
		return bin2hex(random_bytes(16));
	}

	public function getProductFeedFile( $type) {
		$fileName = $this->wpSettingsUtil->getOption('product_feed_' . $type . '_file_name');
		if (false === $fileName) {
			$fileName = $this->generateRandomString() . '_product_feed_' . $type . '.tsv';
			$this->wpSettingsUtil->updateOption('product_feed_' . $type . '_file_name', $fileName);
		}
		$upload_dir = wp_upload_dir();
		$this->wpSettingsUtil->updateOption('product_feed_' . $type . '_file_url', $upload_dir['baseurl'] . '/' . $fileName);
		return $upload_dir['basedir'] . '/' . $fileName;
	}

	public function getProductFeedTempFile( $type) {
		return $this->getProductFeedFile($type) . '.tmp';
	}

	protected function shouldGenerateNewFeed( $type) {
		$enabled = $this->wpSettingsUtil->getOption('product_feed_' . $type . '_enabled');

		if ('1' !== $enabled) {
			return false;
		}

		$lastStarted = get_transient($this->snakeCaseNamespace . '_product_feed_' . $type . '_started');


		// Check if we already generated today
		if (false !== $lastStarted && gmdate('Ymd') === gmdate('Ymd', $lastStarted)) {
			// Check if it's scheduled time
			$scheduleTime = $this->wpSettingsUtil->getOption('product_feed_' . $type . '_schedule')
				?? $this->defaultSchedule[$type];

			$scheduledDateTime = strtotime(gmdate('Y-m-d') . ' ' . $scheduleTime);
			if (time() >= $scheduledDateTime && $lastStarted < $scheduledDateTime) {
				return true;
			}
			return false;
		}

		return true;
	}

	public function cronJob() {
		$timeout = 30; // seconds
		$startTime = time();

		foreach ($this->types as $type) {

			while ( time() - $startTime <= $timeout ) {
				// Check if there's an ongoing generation
				$currentPage = get_transient($this->snakeCaseNamespace . '_product_feed_' . $type . '_current_page');

				if (false === $currentPage) {
					// No ongoing generation, check if we should start new one
					if (!$this->shouldGenerateNewFeed($type)) {
						break;
					}

					// Start new generation
					$currentPage = 1;
					$tempFile = $this->getProductFeedTempFile($type);

					// Write headers
					$handle = fopen($tempFile, 'w');
					fputcsv($handle, $this->headers[$type], "\t");
					fclose($handle);

					set_transient($this->snakeCaseNamespace . '_product_feed_' . $type . '_started', time());
				}

				$nextPage = $this->generateProductFeed($type, $currentPage);

				if (false === $nextPage) {
					// Generation completed
					$tempFile = $this->getProductFeedTempFile($type);
					$finalFile = $this->getProductFeedFile($type);
					rename($tempFile, $finalFile);

					delete_transient($this->snakeCaseNamespace . '_product_feed_' . $type . '_current_page');
					set_transient($this->snakeCaseNamespace . '_product_feed_' . $type . '_generated', time());
					break;
				}

				$currentPage = $nextPage;
				set_transient($this->snakeCaseNamespace . '_product_feed_' . $type . '_current_page', $currentPage);
			}
		}
	}

	/**
	 * Returns another page number 2, 3, 4 if there is more products to process
	 * or false if it finished
	 */
	public function generateProductFeed( $type, $page = 1) {
		$query = new \WC_Product_Query( array(
			'status' => 'publish',
			'page'  => 1,
			'paginate' => true,
			'limit' => 100,
			'orderby' => 'ID',
			'order' => 'ASC',
			'visibility' => 'visible',
		) );

		$results = $query->get_products();
		if (empty($results->products)) {
			return false;
		}

		$tempFile = $this->getProductFeedTempFile($type);
		$handle = fopen($tempFile, 'a');

		foreach ($results->products as $product) {

			if ($product->is_type('variable')) {
				foreach ($product->get_available_variations('objects') as $variant) {
					$data = $this->formatProductData($variant, $type);
					fputcsv($handle, $data, "\t");
				}
			} else {
				$data = $this->formatProductData($product, $type);
				fputcsv($handle, $data, "\t");
			}
		}

		fclose($handle);

		if ($results->max_num_pages > $page) {
			return $page + 1;
		}

		return false;
	}

	protected function formatProductData( $product, $type ) {

		$idPattern = $this->wpSettingsUtil->getOption('product_feed_' . $type . '_id_pattern', '{{sku}}');
		$data = [];
		foreach ($this->headers[$type] as $header) {
			switch ($header) {
				case 'id':
					$data[] = $this->getProductId($product, $idPattern);
					break;
				case 'title':
					$data[] = $product->get_name();
					break;
				case 'description':
					$data[] = substr(wp_strip_all_tags($product->get_description()), 0, 5000);
					break;
				case 'price':
					$data[] = $product->get_price();
					break;
				case 'condition':
					$data[] = 'new';
					break;
				case 'link':
					$data[] = get_permalink($product->get_id());
					break;
				case 'availability':
					$data[] = $product->is_in_stock() ? 'in stock' : 'out of stock';
					break;
				case 'image_link':
					$data[] = wp_get_attachment_url($product->get_image_id());
					break;
				case 'item_group_id':
					$parentProduct = wc_get_product($product->get_parent_id());
					$data[] = $parentProduct ? $this->getProductId($parentProduct, $idPattern) : '';
					break;
				default:
					$data[] = '';
			}
		}
		return $data;
	}

	protected function getProductId( $product, $pattern ) {
		$id = $pattern;
		if (strstr($id, '{{id}}')) {
			$id = str_replace('{{id}}', $product->get_id(), $id);
		}

		if (strstr($id, '{{sku}}')) {
			$id = str_replace('{{sku}}', $product->get_sku(), $id);
		}

		return $id;
	}


}
