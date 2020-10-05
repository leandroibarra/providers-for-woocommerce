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
 * Version: 0.0.2
 * License: GPL2
 */
if (!class_exists('Providers_For_WooCommerce')) {
	/**
	 * Class Providers_For_WooCommerce.
	 *
	 * Extends WooCommerce existing plugin.
	 *
	 * @version	0.0.2
	 * @author	Leandro Ibarra
	 */
	class Providers_For_WooCommerce {
		/**
		 * @var string
		 */
		public $version = '0.0.2';

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

			// admin columns
			add_filter('manage_provider_posts_columns', array($this, 'set_custom_provider_columns'));
			add_action('manage_provider_posts_custom_column', array($this, 'set_custom_provider_content_column'), 10, 2);

			// admin tab
			add_filter('woocommerce_product_data_tabs', array($this, 'add_provider_tab_to_woocommerce_product'), 99, 1);
			add_action('woocommerce_product_data_panels', array($this, 'add_provider_fields_to_tab'));
			add_action('woocommerce_process_product_meta', array($this, 'save_provider_fields'));

			// menu
			add_action('admin_menu', array($this, 'add_admin_menu'), 26);

			// highlight
			add_action('admin_head', array($this, 'menu_highlight'));

			// ajax
			add_action( 'wp_ajax_save_product', array( $this, 'save_product_ajax' ) );

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
		 * Set custom products column to provider post type list.
		 */
		public function set_custom_provider_columns($columns) {
			$_columns['title'] = $columns['title'];
			$_columns['products'] = ''; //__( 'Productos', 'providers-for-woocommerce' );
			$_columns['date'] = $columns['date'];

			return $_columns;
		}

		/**
		 * Set value for custom products column in provider post type list. 
		 */
		public function set_custom_provider_content_column($column_key, $post_id) {
			if (get_post_type($post_id) === 'provider') {
				switch ($column_key) {
					case 'products':
						echo '<a href="/wp-admin/edit.php?post_type=provider&page=provider_products&id='.$post_id.'">'.__( 'Productos', 'providers-for-woocommerce' ).'</a>';
						break;
				}
			}
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
			update_post_meta($post_id, 'purchase_price', floatval(isset($_POST['purchase_price']) ? (($_POST['purchase_price'] > 0) ? $_POST['purchase_price'] : 0) : 0));

			update_post_meta($post_id, 'purchase_discount', (!empty($_POST['purchase_discount'])) ? (filter_var($_POST['purchase_discount'], FILTER_SANITIZE_NUMBER_INT) <= 100 ? filter_var($_POST['purchase_discount'], FILTER_SANITIZE_NUMBER_INT) : 100) : 0);

			update_post_meta($post_id, 'profit_margin', (!empty($_POST['profit_margin'])) ? (filter_var($_POST['profit_margin'], FILTER_SANITIZE_NUMBER_INT) <= 200 ? filter_var($_POST['profit_margin'], FILTER_SANITIZE_NUMBER_INT) : 200) : 0);

			$provider = $_POST['provider'];
			if (!empty($provider)) {
				update_post_meta($post_id, 'provider', filter_var($provider, FILTER_SANITIZE_NUMBER_INT));
			}
		}

		/**
		 * Define sub-menu page (hidden from sidebar navigation).
		 */
		public function add_admin_menu() {
			$provider_products = add_submenu_page(
				null,
				__( 'Productos', 'providers-for-woocommerce' ),
				__( 'Producto', 'providers-for-woocommerce' ),
				'manage_options',
				'provider_products',
				array($this, 'create_admin_provider_products')
			);

			add_action( 'admin_print_styles-' . $provider_products, array( $this, 'provider_products_enqueue_styles' ) );
			add_action( 'admin_print_scripts-' . $provider_products, array( $this, 'provider_products_enqueue_scripts' ) );
		}

		/**
		 * Highlights the correct top level admin menu item for post type add screens.
		 */
		public function menu_highlight() {
			global $typenow, $parent_file, $submenu_file;;

			$screen = get_current_screen();

			if (is_object($screen)) {
				if ($typenow == 'provider') {
					$parent_file = null;
					$submenu_file = 'edit.php?post_type=provider';
				}
			}
		}

		/**
		 * Callback function for the admin submenu page.
		 */
		public function create_admin_provider_products() {
			require_once $this->plugin_path() . '/admin/partials/provider-products.php';
		}

		/**
		 * Add css styles.
		 */
		public function provider_products_enqueue_styles() {
			wp_enqueue_style( 'providers-for-woocommerce-datatables-css', $this->plugin_url() . '/assets/css/datatables.css', array(), '', 'all' );
			wp_enqueue_style( 'providers-for-woocommerce-products-css', $this->plugin_url() . '/assets/css/provider-products.css', array(), '', 'all' );
		}

		/**
		 * Add js scripts.
		 */
		public function provider_products_enqueue_scripts() {
			wp_enqueue_script( 'providers-for-woocommerce-datatables-js', $this->plugin_url() . '/assets/js/datatables.js', array( 'jquery' ), '', true );
			wp_enqueue_script( 'providers-for-woocommerce-products-js', $this->plugin_url() . '/assets/js/provider-products.js', array( 'jquery' ), '', true );
		}
		public function admin_scripts() {
			wp_enqueue_script( 'providers-for-woocommerce-scripts-js', $this->plugin_url() . '/assets/js/scripts.js', array( 'jquery' ), '', true );
		}

		/**
		 * Save product data from provider products list.
		 */
		public function save_product_ajax() {
			if ($_POST['action'] === 'save_product' && !empty($_POST['post_id'])) {
				$purchase_price = $_POST['purchase_price'];
				if (isset($purchase_price)) {
					update_post_meta($_POST['post_id'], 'purchase_price', floatval(($purchase_price > 0) ? $purchase_price : 0));
				}

				$_price = $_POST['_price'];
				if (isset($_price)) {
					update_post_meta($_POST['post_id'], '_price', floatval($_price));
					update_post_meta($_POST['post_id'], '_regular_price', floatval($_price));
				}

				update_post_meta($_POST['post_id'], 'profit_margin', (!empty($_POST['profit_margin'])) ? (filter_var($_POST['profit_margin'], FILTER_SANITIZE_NUMBER_INT) <= 200 ? filter_var($_POST['profit_margin'], FILTER_SANITIZE_NUMBER_INT) : 200) : 0);

				wp_die('success');
			} else {
				wp_die('error');
			}
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