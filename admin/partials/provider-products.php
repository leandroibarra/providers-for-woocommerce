<?php
require_once ABSPATH . 'wp-admin/admin-header.php';

$provider_id = $_GET['id'];

$meta_key = 'provider';

global $wpdb;

$provider = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE ID={$provider_id}" );

$products = $wpdb->get_results(
	"
	SELECT
		P.ID,
		P.post_title
	FROM {$wpdb->prefix}postmeta PM
		LEFT JOIN {$wpdb->prefix}posts P ON P.ID = PM.post_id
	WHERE PM.meta_key='{$meta_key}' AND PM.meta_value={$provider_id}
	ORDER BY P.post_title ASC
	",
	'ARRAY_A'
);

foreach ($products as $key => $product) {
	$post_meta = get_post_meta($product['ID']);

	foreach ($post_meta as $k => $v) {
		if (in_array($k, array('_sku', 'purchase_price', 'profit_margin', '_price', '_stock'))) {
			$products[$key][$k] = $v[0];
		}
	}
}

$columns = array(
	__( 'SKU', 'providers-for-woocommerce' ),
	__( 'Nombre', 'providers-for-woocommerce' ),
	__( 'Precio de Compra ('.get_woocommerce_currency_symbol().')', 'providers-for-woocommerce' ),
	__( 'Margen de Ganancia', 'providers-for-woocommerce' ),
	__( 'Precio regular', 'providers-for-woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')',
	__( 'Stock', 'providers-for-woocommerce' )
);
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo __( 'Productos del proveedor', 'providers-for-woocommerce' ) . ' ' . esc_html( $provider[0]->post_title ); ?></h1>

	<hr class="wp-header-end">

	<div class="table-wrapper">
		<div id="overlay">
			<div class="spinner-border"></div>
		</div>
	
		<table id="table-provider-products" class="display">
			<thead>
				<tr>
					<?php foreach ($columns as $column) { ?>
					<th><?php echo $column; ?></th>
					<?php } ?>
					<th>&nbsp;</th>
				</tr>
			</thead>

			<tbody>
				<?php foreach ($products as $index => $product) { ?>
				<tr data-index="<?php echo $index; ?>" data-post-id="<?php echo $product['ID']; ?>">
					<td><?php echo $product['_sku']; ?></td>
					<td><?php echo $product['post_title']; ?></td>
					<td>
					<?php
					woocommerce_wp_text_input(
						array(
							'id' => 'purchase_price_' . $index,
							'class' => 'purchase_price wc_input_price short',
							'value' => $product['purchase_price'],
							'type' => 'number',
							'custom_attributes' => array(
								'min' => '0'
							)
						)
					);
					?>
					</td>
					<td>
					<?php
					woocommerce_wp_text_input(
						array(
							'id' => 'profit_margin_' . $index,
							'class' => 'profit_margin',
							'type' => 'number',
							'custom_attributes' => array(
								'min' => '0',
								'max' => '200'
							),
							'value' => $product['profit_margin']
						)
					);
					?>
					</td>
					<td>
					<?php
					woocommerce_wp_text_input(
						array(
							'id' => '_price_' . $index,
							'class' => 'price',
							'value' => $product['_price'],
							'type' => 'number',
							'custom_attributes' => array(
								'min' => '0',
							)
						)
					);
					?>
					</td>
					<td><?php echo $product['_stock']; ?></td>
					<td>
						<a href="javascript:void(0);" class="save-product add-new-h2"><?php echo __('Guardar', 'providers-for-woocommerce'); ?></a>
					</td>
				</tr>
				<?php } ?>
			</tbody>

			<tfoot>
				<tr>
					<?php foreach ($columns as $column) { ?>
					<th><?php echo $column; ?></th>
					<?php } ?>
					<th>&nbsp;</th>
				</tr>
			</tfoot>
		</table>

		<?php if (count($products) > 0) { ?>
		<a href="javascript:void(0);" class="save-all add-new-h2"><?php echo __('Guardar Todos', 'providers-for-woocommerce'); ?></a>
		<?php } ?>
	</div>
</div>
