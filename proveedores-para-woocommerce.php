<?php
/**
 * Plugin Name: Proveedores para WooCommerce
 * Plugin URI: https://wordpress.org/plugins/providers-for-woocommerce
 * Description: Agrega campos para configuraciones de proveedores en productos de WooCommerce y secciones de reportes.
 * Author: Leandro Ibarra
 * Author URI:
 *
 * Text Domain: providers-for-woocommerce
 *
 * Version: 0.0.1
 * License: GPL2
 */
if (!class_exists('Providers_For_WooCommerce')) {
	/**
	 * Class Providers_For_WooCommerce.
	 *
	 * Extends WooCommerce existing plugin.
	 *
	 * @version	0.0.1
	 * @author	Leandro Ibarra
	 */
	class Providers_For_WooCommerce {
		/**
		 * @var string
		 */
		public $version = '0.0.1';

		/**
		 * @var string
		 */
		public $text_domain = 'providers-for-woocommerce';

		/**
		 * @var Providers_For_WooCommerce
		 */
		private static $instance;

		/**
		 * Retrieve an instance of this class.
		 *
		 * @return Providers_For_WooCommerce
		 */
		public static function instance() {
			if (!isset(self::$instance)) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Providers_For_WooCommerce constructor.
		 */
		public function __construct() {
			// post type
			add_action('init', array($this, 'register_post_type'));

			// admin tab
			add_filter('woocommerce_product_data_tabs', array($this, 'add_provider_tab_to_woocommerce_product'), 99, 1);
			add_action('woocommerce_product_data_panels', array($this, 'add_provider_fields_to_tab'));
			add_action('woocommerce_process_product_meta', array($this, 'save_provider_fields'));

			// checkout
			add_action('woocommerce_checkout_create_order_line_item', array($this, 'action_woocommerce_checkout_create_order_line_item'), 10, 4 );

			// admin scripts
			add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
		}

		/**
		 * Register provider post type.
		 */
		public static function register_post_type() {
			register_post_type(
				'provider',
				array(
					'labels' => array(
						'name'			=> __( 'Proveedores', 'providers-for-woocommerce' ),
						'singular_name' => __( 'Proveedor', 'providers-for-woocommerce' ),
						'all_items'		=> __( 'Todos los proveedores', 'providers-for-woocommerce' ),
						'add_new'       => __( 'Agregar nuevo', 'providers-for-woocommerce' ),
					),
					'public'                => true,
					'show_ui'               => true,
					'show_in_menu'          => true,
					'show_in_nav_menus'     => false,
					'show_in_admin_bar'     => true,
					'exclude_from_search'   => true,
					'has_archive'           => true,
					'publicly_queryable'    => false,
					'rewrite'               => array('slug' => 'provider'),
					'show_in_rest'          => false,
				)
			);

			// Flush rules after install
			flush_rewrite_rules();
		}

		/**
		 * Add provider tab for WooCommerce product.
		 */
		public function add_provider_tab_to_woocommerce_product($product_data_tabs) {
			$product_data_tabs['provider-woocommerce-product-tab'] = array(
				'label' => __( 'Proveedor', 'providers-for-woocommerce' ),
				'target' => 'provider_woocommerce_product_tab',
			);

			return $product_data_tabs;
		}

		/**
		 * Add fields to provider tab for WooCommerce product.
		 */
		public function add_provider_fields_to_tab() {
			global $woocommerce, $post;

			echo '<div id="provider_woocommerce_product_tab" class="panel woocommerce_options_panel">';

			woocommerce_wp_text_input(
				array(
					'id' => 'purchase_price',
					'placeholder' => '',
					'label' => __('Precio de Compra ('.get_woocommerce_currency_symbol().')', 'providers-for-woocommerce'),
					'class' => 'wc_input_price short',
					'type' => 'number',
					'custom_attributes' => array(
						'min' => '0'
					)
				)
			);

			woocommerce_wp_text_input(
				array(
					'id' => 'purchase_discount',
					'placeholder' => '',
					'label' => __('Compra con Descuento', 'providers-for-woocommerce'),
					'type' => 'number',
					'custom_attributes' => array(
						'min' => '0',
						'max' => '100'
					)
				)
			);

			woocommerce_wp_text_input(
				array(
					'id' => 'profit_margin',
					'placeholder' => '',
					'label' => __('Margen de Ganancia', 'providers-for-woocommerce'),
					'type' => 'number',
					'custom_attributes' => array(
						'min' => '0',
						'max' => '200'
					)
				)
			);

			$posts = get_posts( array(
				'post_type'     =>   'provider',
				'orderby'       =>   'title',
				'order'         =>   'ASC',
				'numberposts'   =>   -1
			) );

			foreach ($posts as $key => $post) {
				$current_options[$post->ID] = $post->post_title;
			}

			woocommerce_wp_select( array(
				'id' => 'provider',
				'label' => __( 'Proveedor', 'providers-for-woocommerce' ),
				'options' => $current_options
			) );

			echo '</div>';
		}

		/**
		 * Save provider fields from WooCommerce product.
		 */
		public function save_provider_fields($post_id) {
			update_post_meta($post_id, 'purchase_price', floatval(isset($_POST['purchase_price']) ? $_POST['purchase_price'] : 0));

			update_post_meta($post_id, 'purchase_discount', (!empty($_POST['purchase_discount'])) ? (filter_var($_POST['purchase_discount'], FILTER_SANITIZE_NUMBER_INT) <= 100 ? filter_var($_POST['purchase_discount'], FILTER_SANITIZE_NUMBER_INT) : 100) : 0);

			update_post_meta($post_id, 'profit_margin', (!empty($_POST['profit_margin'])) ? (filter_var($_POST['profit_margin'], FILTER_SANITIZE_NUMBER_INT) <= 200 ? filter_var($_POST['profit_margin'], FILTER_SANITIZE_NUMBER_INT) : 200) : 0);

			$provider = $_POST['provider'];
			if (!empty($provider)) {
				update_post_meta($post_id, 'provider', filter_var($provider, FILTER_SANITIZE_NUMBER_INT));
			}
		}

		/**
		 * Add js scripts.
		 */
		public function admin_scripts() {
			wp_enqueue_script( 'providers-for-woocommerce-scripts-js', $this->plugin_url() . '/assets/js/scripts.js', array( 'jquery' ), '', true );
		}

		/**
		 * Add custom postmeta to each item from the order in checkout.
		 */
		public function action_woocommerce_checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) {
			// The corresponding Product Id for the item
			$product_id = $values[ 'product_id' ];

			if (isset($product_id)) {
				$purchase_price = get_post_meta($product_id, 'purchase_price');
				$purchase_discount = get_post_meta($product_id, 'purchase_discount');
				$profit_margin = get_post_meta($product_id, 'profit_margin');

				$item->update_meta_data('purchase_price', $purchase_price[0]);
				$item->update_meta_data('purchase_discount', $purchase_discount[0]);
				$item->update_meta_data('profit_margin', $profit_margin[0]);
			}
		}

		/**
		 * Get the plugin url.
		 *
		 * @return	string
		 */
		private function plugin_url() {
			return untrailingslashit(plugins_url('/', __FILE__));
		}

		/**
		 * Get the plugin path.
		 *
		 * @return	string
		 */
		public function plugin_path() {
			return untrailingslashit(plugin_dir_path(__FILE__));
		}
	}

	Providers_For_WooCommerce::instance();
}