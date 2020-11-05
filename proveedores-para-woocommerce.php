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
 * Version: 0.8.2
 * License: GPL2
 */
if (!class_exists('Providers_For_WooCommerce')) {
	/**
	 * Class Providers_For_WooCommerce.
	 *
	 * Extends WooCommerce existing plugin.
	 *
	 * @version	0.8.2
	 * @author	Leandro Ibarra
	 */
	class Providers_For_WooCommerce {
		/**
		 * @var string
		 */
		public $version = '0.8.2';

		/**
		 * @var string
		 */
		public $text_domain = 'providers-for-woocommerce';

		/**
		 * @var Providers_For_WooCommerce
		 */
		private static $instance;

		/**
		 * @var integer
		 */
		private $last_days = 30;

		/**
		 * @var string
		 */
		private $products_sales_in_last_days = 'view_products_sales_in_last_days';

		/**
		 * @var string
		 */
		private $products_sales_in_last_sixty_days = 'view_products_sales_in_last_sixty_days';

		/**
		 * @var string
		 */
		private $products_sales_meta_values = 'view_products_sales_meta_values';

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
			// db queries
			register_activation_hook(__FILE__, array($this, 'db_add'));
			register_deactivation_hook(__FILE__, array($this, 'db_remove'));

			// post type
			add_action('init', array($this, 'register_post_type'));

			// admin columns
			add_filter('manage_provider_posts_columns', array($this, 'set_custom_provider_columns'));
			add_action('manage_provider_posts_custom_column', array($this, 'set_custom_provider_content_column'), 10, 2);
			add_filter('manage_edit-product_columns', array($this, 'set_product_provider_column'));
			add_action('manage_posts_custom_column', array($this, 'set_product_provider_content_column'), 10, 1);

			// admin tab
			add_filter('woocommerce_product_data_tabs', array($this, 'add_provider_tab_to_woocommerce_product'), 99, 1);
			add_action('woocommerce_product_data_panels', array($this, 'add_provider_fields_to_tab'));
			add_action('woocommerce_process_product_meta', array($this, 'save_provider_fields'));

			// admin bulk edit
			add_action('woocommerce_product_bulk_edit_start', array($this, 'provider_field_in_product_bulk_edit'));
			add_action('woocommerce_product_bulk_edit_save', array($this, 'save_provider_field_in_product_bulk_edit'), 10, 1);

			// admin quick edit
			add_action('woocommerce_product_quick_edit_start', array($this, 'provider_field_in_product_quick_edit'));
			add_action('woocommerce_product_quick_edit_save', array($this, 'save_provider_field_in_product_quick_edit'), 10, 1);

			// menu
			add_action('admin_menu', array($this, 'add_admin_menu'), 26);

			// highlight
			add_action('admin_head', array($this, 'menu_highlight'));

			// ajax
			add_action('wp_ajax_save_product', array($this, 'save_product_ajax'));
			add_action('wp_ajax_generate_purchase_order_xls', array($this, 'generate_purchase_order_xls_ajax'));
			add_action('wp_ajax_generate_sales_report_xls', array($this, 'generate_sales_report_xls_ajax'));

			// checkout
			add_action('woocommerce_checkout_create_order_line_item', array($this, 'action_woocommerce_checkout_create_order_line_item'), 10, 4 );

			// admin scripts
			add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
			add_action('admin_enqueue_scripts', array($this, 'provider_enqueue_quick_edit_js'), 10, 1);
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
			$_columns['order'] = ''; //__( 'Órden de Compra', 'providers-for-woocommerce' );
			$_columns['report'] = ''; //__( 'Reporte de Productos', 'providers-for-woocommerce' );
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
					case 'order':
						echo '<a href="/wp-admin/edit.php?post_type=provider&page=provider_orders&id='.$post_id.'">'.__( 'Órden de Compra', 'providers-for-woocommerce' ).'</a>';
						break;
					case 'report':
						echo '<a href="/wp-admin/edit.php?post_type=provider&page=provider_report&id='.$post_id.'">'.__( 'Reporte de Productos', 'providers-for-woocommerce' ).'</a>';
						break;
				}
			}
		}

		/**
		 * Set custom provider column in WooCommerce product list.
		 */
		public function set_product_provider_column( $columns_array ) {
			return array_slice( $columns_array, 0, count($columns_array) - 3 )
				+ array( 'provider' => __( 'Proveedor', 'providers-for-woocommerce' ) )
				+ array_slice( $columns_array, -2 );
		}

		/**
		 * Set value for custom provider column in WooCommerce product list.
		 */
		public function set_product_provider_content_column( $column_name ) {
			if ( $column_name  == 'provider' ) {
				$post_id = get_the_ID();
				$provider_id = get_post_meta($post_id, 'provider')[0];

				echo '<input type="hidden" name="hidden-provider['.$post_id.']" id="hidden-provider-'.$post_id.'" value="'.$provider_id.'" />';

				if ($provider_id) {
					echo get_the_title($provider_id);
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

			$current_options[0] = '';

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
		 * Add provider field to product bulk edition.
		 */
		public function provider_field_in_product_bulk_edit() {
			$this->provider_field_in_product_bulk_and_quick_edit('bulk');
		}

		/**
		 * Add provider field to product quick edition.
		 */
		public function provider_field_in_product_quick_edit() {
			$this->provider_field_in_product_bulk_and_quick_edit('quick');
		}

		/**
		 * Build provider field to product bulk and quick edition.
		 */
		private function provider_field_in_product_bulk_and_quick_edit($target) {
			$id = get_the_ID();
			echo '<div class="inline-edit-group" data-id="'.$id.'">';

			$posts = get_posts( array(
				'post_type'     =>   'provider',
				'orderby'       =>   'title',
				'order'         =>   'ASC',
				'numberposts'   =>   -1
			) );

			if ($target === 'bulk') {
				$options = array(
					''  => __( '— Sin cambios —', 'providers-for-woocommerce' ),
					'0' => __( '— Ninguno —', 'providers-for-woocommerce' )
				);
			} else if ($target === 'quick') {
				$options = array('' => '');
			}

			foreach ($posts as $key => $post) {
				$options[$post->ID] = $post->post_title;
			}

			$content = '
				<span class="title">' . __( 'Proveedor', 'providers-for-woocommerce' ) . '</span>
				<span class="input-text-wrap">
					<select class="change_provider change_to" name="change_provider">
			';

			foreach ( $options as $key => $value ) {
				$content .= '<option value="' . esc_attr( $key ) . '">' . $value . '</option>';
			}

			$content .= '
					</select>
				</span>
			';

			if ($target === 'bulk') {
				$content = '<label class="alignleft">' . $content . '</label>';
			}

			echo $content;

			echo '</div>';
		}

		/**
		 * Save provider field from product bulk edition.
		 */
		public function save_provider_field_in_product_bulk_edit( $product ) {
			$this->save_provider_field_in_product_build_and_quick_edit($product, 'bulk');
		}

		/**
		 * Save provider field from product quick edition.
		 */
		public function save_provider_field_in_product_quick_edit( $product ) {
			$this->save_provider_field_in_product_build_and_quick_edit($product, 'quick');
		}

		/**
		 * Save provider field from product in bulk and quick edition.
		 */
		private function save_provider_field_in_product_build_and_quick_edit($product, $from) {
			$post_id = $product->get_id();

			if ( isset( $_REQUEST['change_provider'] ) && ( ( $from === 'bulk' && $_REQUEST['change_provider'] !== '' ) || $from === 'quick' ) ) {
				$provider = $_REQUEST['change_provider'];
				update_post_meta( $post_id, 'provider', wc_clean( $provider ) );
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

			$provider_orders = add_submenu_page(
				null,
				__( 'Órdenes de Compra', 'providers-for-woocommerce' ),
				__( 'Órden de Compra', 'providers-for-woocommerce' ),
				'manage_options',
				'provider_orders',
				array($this, 'create_admin_provider_orders')
			);

			add_action( 'admin_print_styles-' . $provider_orders, array( $this, 'provider_orders_enqueue_styles' ) );
			add_action( 'admin_print_scripts-' . $provider_orders, array( $this, 'provider_orders_enqueue_scripts' ) );

			$provider_report = add_submenu_page(
				null,
				__( 'Reportes de Productos', 'providers-for-woocommerce' ),
				__( 'Reporte de Productos', 'providers-for-woocommerce' ),
				'manage_options',
				'provider_report',
				array($this, 'create_admin_provider_report')
			);

			add_action( 'admin_print_styles-' . $provider_report, array( $this, 'provider_report_enqueue_styles' ) );
			add_action( 'admin_print_scripts-' . $provider_report, array( $this, 'provider_report_enqueue_scripts' ) );

			$products_report = add_submenu_page(
				'edit.php?post_type=provider',
				__( 'Reportes de Productos', 'providers-for-woocommerce' ),
				__( 'Reporte de Productos', 'providers-for-woocommerce' ),
				'manage_options',
				'products_report',
				array($this, 'create_admin_provider_report')
			);

			add_action( 'admin_print_styles-' . $products_report, array( $this, 'provider_report_enqueue_styles' ) );
			add_action( 'admin_print_scripts-' . $products_report, array( $this, 'provider_report_enqueue_scripts' ) );
		}

		/**
		 * Highlights the correct top level admin menu item for post type add screens.
		 */
		public function menu_highlight() {
			global $typenow, $parent_file, $submenu_file;;

			$screen = get_current_screen();

			if (is_object($screen)) {
				if ($typenow == 'provider' && ($screen->parent_base == 'edit' || in_array($_GET['page'], array('provider_products', 'provider_orders', 'provider_report')))) {
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
		public function create_admin_provider_orders() {
			require_once $this->plugin_path() . '/admin/partials/provider-orders.php';
		}
		public function create_admin_provider_report() {
			require_once $this->plugin_path() . '/admin/partials/provider-report.php';
		}

		/**
		 * Add css styles.
		 */
		public function provider_products_enqueue_styles() {
			wp_enqueue_style( 'providers-for-woocommerce-datatables-css', $this->plugin_url() . '/assets/css/datatables.css', array(), '', 'all' );
			wp_enqueue_style( 'providers-for-woocommerce-products-css', $this->plugin_url() . '/assets/css/provider-products.css', array(), '', 'all' );
		}
		public function provider_orders_enqueue_styles() {
			wp_enqueue_style( 'providers-for-woocommerce-datatables-css', $this->plugin_url() . '/assets/css/datatables.css', array(), '', 'all' );
			wp_enqueue_style( 'providers-for-woocommerce-orders-css', $this->plugin_url() . '/assets/css/provider-orders.css', array(), '', 'all' );
		}
		public function provider_report_enqueue_styles() {
			wp_enqueue_style( 'providers-for-woocommerce-daterangepicker-css', $this->plugin_url() . '/assets/css/daterangepicker.css', array(), '', 'all' );
			wp_enqueue_style( 'providers-for-woocommerce-report-css', $this->plugin_url() . '/assets/css/provider-report.css', array(), '', 'all' );
		}

		/**
		 * Add js scripts.
		 */
		public function provider_products_enqueue_scripts() {
			wp_enqueue_script( 'providers-for-woocommerce-datatables-js', $this->plugin_url() . '/assets/js/datatables.js', array( 'jquery' ), '', true );
			wp_enqueue_script( 'providers-for-woocommerce-products-js', $this->plugin_url() . '/assets/js/provider-products.js', array( 'jquery' ), '', true );
		}
		public function provider_orders_enqueue_scripts() {
			wp_enqueue_script( 'providers-for-woocommerce-datatables-js', $this->plugin_url() . '/assets/js/datatables.js', array( 'jquery' ), '', true );
			wp_enqueue_script( 'providers-for-woocommerce-orders-js', $this->plugin_url() . '/assets/js/provider-orders.js', array( 'jquery' ), '', true );
		}
		public function provider_report_enqueue_scripts() {
			wp_enqueue_script( 'providers-for-woocommerce-moment-js', $this->plugin_url() . '/assets/js/moment.js', array( 'jquery' ), '', true );
			wp_enqueue_script( 'providers-for-woocommerce-daterangepicker-js', $this->plugin_url() . '/assets/js/daterangepicker.js', array( 'jquery' ), '', true );
			wp_enqueue_script( 'providers-for-woocommerce-report-js', $this->plugin_url() . '/assets/js/provider-report.js', array( 'jquery' ), '', true );
		}
		public function admin_scripts() {
			wp_enqueue_script( 'providers-for-woocommerce-scripts-js', $this->plugin_url() . '/assets/js/scripts.js', array( 'jquery' ), '', true );
		}

		/**
		 * Populate quick edit custom field.
		 */
		public function provider_enqueue_quick_edit_js( $pageHook ) {
			if ( 'edit.php' != $pageHook ) {
				return;
			}

			wp_enqueue_script( 'providers-for-woocommerce-quick-edit-js', $this->plugin_url() . '/assets/js/quick-edit.js', array( 'jquery' ) );
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

				$_stock = $_POST['_stock'];
				if (isset($_stock)) {
					update_post_meta($_POST['post_id'], '_stock', floatval($_stock));
				}

				update_post_meta($_POST['post_id'], 'profit_margin', (!empty($_POST['profit_margin'])) ? (filter_var($_POST['profit_margin'], FILTER_SANITIZE_NUMBER_INT) <= 200 ? filter_var($_POST['profit_margin'], FILTER_SANITIZE_NUMBER_INT) : 200) : 0);

				wp_die('success');
			} else {
				wp_die('error');
			}
		}

		/**
		 * Generate purchase order.
		 */
		public function generate_purchase_order_xls_ajax() {
			require_once $this->plugin_path() . '/includes/php-excel.class.php';

			$aReport = array();

			global $wpdb;

			$products = $wpdb->get_results(
				"
				SELECT
					P.ID,
					P.post_title,
					COALESCE(SSD.quantity, 0) AS quantity,
					COALESCE(SSD.average_for_a_day, 0) AS average_for_a_day,
					COALESCE(SKU.meta_value, '') AS sku
				FROM {$wpdb->prefix}posts P
					LEFT JOIN {$wpdb->prefix}{$this->products_sales_in_last_sixty_days} AS SSD
						ON SSD.product_id = P.ID
					LEFT JOIN {$wpdb->prefix}postmeta AS SKU
						ON SKU.post_id = P.ID AND SKU.meta_key = '_sku'
				WHERE P.ID IN (".implode(', ', $_POST['product_id']).")
				",
				'ARRAY_A'
			);

			foreach ($products as $key => $product) {
				$aReport[$key][__('SKU', 'providers-for-woocommerce')] = $product['sku'];
				$aReport[$key][__('Nombre', 'providers-for-woocommerce')] = $product['post_title'];
				$aReport[$key][__('Cantidad Compra', 'providers-for-woocommerce')] = intval($product['average_for_a_day']) * intval($_POST['days']);
			}

			$aReport = array_merge(array(array_combine(array_keys($aReport[0]), array_keys($aReport[0]))), $aReport);

			$xls = new Excel_XML;
			$xls->addWorksheet(__('Hoja 1', 'providers-for-woocommerce'), $aReport);
			$xls->sendWorkbook(mktime() . '_' . __('orden_de_compra', 'providers-for-woocommerce').'.xls');

			exit();
		}

		/**
		 * Generate sales report.
		 */
		public function generate_sales_report_xls_ajax() {
			require_once $this->plugin_path() . '/includes/php-excel.class.php';

			$aReport = array();

			global $wpdb;

			$sProviderCondition = "PM.meta_key='provider' ";

			if ($_POST['provider_id']) {
				$sProviderCondition .= "AND PM.meta_value={$_POST['provider_id']} ";
			}

			$products = $wpdb->get_results(
				"
				SELECT
					P.ID AS ID,
					P.post_title AS post_title,
					PROVIDER.post_title AS provider,
					SKU.meta_value AS sku,
					PURCHASE.meta_value AS purchase_price,
					MARGIN.meta_value AS profit_margin,
					COALESCE(sales.quantity, 0) AS quantity,
					COALESCE(sales.total, 0) AS total,
					COALESCE(sales.utility, 0) AS utility
				FROM {$wpdb->prefix}postmeta PM
					INNER JOIN {$wpdb->prefix}posts P
						ON P.ID = PM.post_id
					INNER JOIN {$wpdb->prefix}posts PROVIDER
						ON PROVIDER.ID = PM.meta_value
					INNER JOIN {$wpdb->prefix}term_relationships AS rel
						ON rel.object_id = P.ID
					INNER JOIN {$wpdb->prefix}terms AS term
						ON term.term_id = rel.term_taxonomy_id
					INNER JOIN {$wpdb->prefix}term_taxonomy AS tax
						ON tax.term_taxonomy_id = rel.term_taxonomy_id
					LEFT JOIN {$wpdb->prefix}postmeta SKU
						ON SKU.post_id = P.ID
					LEFT JOIN {$wpdb->prefix}postmeta PURCHASE
						ON PURCHASE.post_id = P.ID
					LEFT JOIN {$wpdb->prefix}postmeta MARGIN
						ON MARGIN.post_id = P.ID
					LEFT JOIN (
						SELECT
							SUM(quantity) AS quantity,
							FORMAT(SUM(total), 2) AS total,
							FORMAT(SUM(utility), 2) AS utility,
							product_id
						FROM {$wpdb->prefix}{$this->products_sales_meta_values}
						WHERE order_date BETWEEN '{$_POST['start_date']}' AND '{$_POST['end_date']}'
						GROUP BY product_id
					) AS sales ON sales.product_id = P.ID
				WHERE
					{$sProviderCondition} AND
					tax.term_id IN (".implode(', ', (array) $_POST['category_id']).") AND
					SKU.meta_key = '_sku' AND
					PURCHASE.meta_key = 'purchase_price' AND
					MARGIN.meta_key = 'profit_margin'
				GROUP BY P.ID
				ORDER BY P.post_title ASC
				",
				'ARRAY_A'
			);

			foreach ($products as $key => $product) {
				$aReport[$key][__('SKU', 'providers-for-woocommerce')] = $product['sku'];
				$aReport[$key][__('Nombre', 'providers-for-woocommerce')] = $product['post_title'];
				$aReport[$key][__('Proveedor', 'providers-for-woocommerce')] = $product['provider'];

				$aReport[$key][__('Categoría/s', 'providers-for-woocommerce')] = implode(', ', array_map(
					function($category) {
						return $category->name;
					},
					wp_get_post_terms($product['ID'], 'product_cat'))
				);

				$aReport[$key][__('Cantidad de Unidades Vendidas', 'providers-for-woocommerce')] = $product['quantity'];
				$aReport[$key][__('Monto ventas', 'providers-for-woocommerce')] = $product['total'];
				$aReport[$key][__('Margen de Ganancia', 'providers-for-woocommerce')] = $product['profit_margin'];
				$aReport[$key][__('Utilidad', 'providers-for-woocommerce')] = $product['utility'];
			}

			$aReport = array_merge(array(array_combine(array_keys($aReport[0]), array_keys($aReport[0]))), $aReport);

			$sStartDate = date('d-m-Y', strtotime($_POST['start_date']));
			$sEndDate = date('d-m-Y', strtotime($_POST['end_date']));

			$xls = new Excel_XML;
			$xls->addWorksheet(implode(' - ', [$sStartDate, $sEndDate]), $aReport);
			$xls->sendWorkbook(implode('_', [$sStartDate, $sEndDate, __('reporte_de_productos', 'providers-for-woocommerce')]).'.xls');

			exit();
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

		/**
		 * Add database necessary view on plugin activation.
		 */
		public function db_add() {
			global $wpdb;

			$sql = "
				CREATE OR REPLACE VIEW {$wpdb->prefix}{$this->products_sales_in_last_days} AS
					SELECT
						SUM(OI_QUANTITY.meta_value) AS quantity,
						CEIL(SUM(OI_QUANTITY.meta_value) / IF(DATEDIFF(CURRENT_DATE, PRODUCT.post_date)<{$this->last_days}, DATEDIFF(CURRENT_DATE, PRODUCT.post_date), {$this->last_days})) AS average_for_a_day,
						OI_PRODUCT_ID.meta_value AS product_id
					FROM {$wpdb->prefix}posts AS P
						INNER JOIN {$wpdb->prefix}woocommerce_order_items AS OI
							ON P.ID = OI.order_id
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS OI_QUANTITY
							ON OI.order_item_id = OI_QUANTITY.order_item_id AND OI_QUANTITY.meta_key = '_qty'
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS OI_PRODUCT_ID
							ON OI.order_item_id = OI_PRODUCT_ID.order_item_id AND OI_PRODUCT_ID.meta_key = '_product_id'
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS OI_PRODUCT_VARIATION
							ON OI.order_item_id = OI_PRODUCT_VARIATION.order_item_id
						INNER JOIN {$wpdb->prefix}posts PRODUCT
							ON PRODUCT.ID = OI_PRODUCT_VARIATION.meta_value
					WHERE
						P.post_type IN ('shop_order', 'shop_order_refund') AND
						P.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold',' wc-refunded') AND
						P.post_date >= CONCAT(CURRENT_DATE - INTERVAL IF(DATEDIFF(CURRENT_DATE, PRODUCT.post_date)<{$this->last_days}, DATEDIFF(CURRENT_DATE, PRODUCT.post_date), {$this->last_days}) DAY, ' 00:00:00') AND
						P.post_date < CONCAT(CURRENT_DATE, ' 23:59:59') AND
						OI_PRODUCT_VARIATION.meta_key IN ('_product_id', '_variation_id') AND
						PRODUCT.post_type='product' AND
						PRODUCT.post_status='publish'
					GROUP BY product_id
					ORDER BY quantity DESC;
			";

			$result = $wpdb->query($sql);

			$sql = "
				CREATE OR REPLACE VIEW {$wpdb->prefix}{$this->products_sales_meta_values} AS
					SELECT
						P.ID AS order_id,
						P.post_date AS order_date,
						OI_PRODUCT_ID.meta_value AS product_id,
						-- Cantidad de unidades vendidas en la orden
						SUM(OI_QUANTITY.meta_value) AS quantity,
						-- Monto total del producto en la orden (cantidad * precio de venta)
						SUM(OI_TOTAL.meta_value) AS total,
						-- Precio unitario del producto en la orden (calculado)
						(SUM(OI_TOTAL.meta_value) / SUM(OI_QUANTITY.meta_value)) AS unit_price,
						-- Margen de Ganancia (guardado al momento de agregar el producto en la orden)
						COALESCE(OI_MARGIN.meta_value, 0) AS profit_margin,
						-- Precio de compra (guardado al momento de agregar el producto en la orden)
						COALESCE(OI_PURCHASE.meta_value, 0) AS purchase_price,
						-- Utilidad del producto en la orden
						-- (monto de venta: productos vendidos * precio venta) - (productos vendidos * precio compra)
						FORMAT(SUM(OI_TOTAL.meta_value) - (SUM(OI_QUANTITY.meta_value) * COALESCE(OI_PURCHASE.meta_value, 0)), 2) AS utility
					FROM {$wpdb->prefix}posts AS P
						INNER JOIN {$wpdb->prefix}woocommerce_order_items AS OI
							ON P.ID = OI.order_id
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS OI_PRODUCT_VARIATION
							ON OI.order_item_id = OI_PRODUCT_VARIATION.order_item_id
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS OI_QUANTITY
							ON OI.order_item_id = OI_QUANTITY.order_item_id AND OI_QUANTITY.meta_key = '_qty'
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS OI_TOTAL
							ON OI.order_item_id = OI_TOTAL.order_item_id AND OI_TOTAL.meta_key = '_line_total'
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS OI_MARGIN
							ON OI.order_item_id = OI_MARGIN.order_item_id AND OI_MARGIN.meta_key = 'profit_margin'
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS OI_PURCHASE
							ON OI.order_item_id = OI_PURCHASE.order_item_id AND OI_PURCHASE.meta_key = 'purchase_price'
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS OI_PRODUCT_ID
							ON OI.order_item_id = OI_PRODUCT_ID.order_item_id AND OI_PRODUCT_ID.meta_key = '_product_id'
					WHERE
						P.post_type IN ('shop_order', 'shop_order_refund') AND
						P.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold',' wc-refunded') AND
						OI_PRODUCT_VARIATION.meta_key IN ('_product_id', '_variation_id')
					GROUP BY P.ID, product_id
					ORDER BY order_date DESC;
			";

			$result = $wpdb->query($sql);
		}

		/**
		 * Remove database necessary view on plugin deactivation.
		 */
		public function db_remove() {
			global $wpdb;

			$result = $wpdb->query("DROP VIEW IF EXISTS {$wpdb->prefix}{$this->products_sales_in_last_days};");

			$result = $wpdb->query("DROP VIEW IF EXISTS {$wpdb->prefix}{$this->products_sales_in_last_sixty_days};");

			$result = $wpdb->query("DROP VIEW IF EXISTS {$wpdb->prefix}{$this->products_sales_meta_values};");
		}
	}

	Providers_For_WooCommerce::instance();
}