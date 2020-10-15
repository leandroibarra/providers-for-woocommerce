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
	<h1 class="wp-heading-inline"><?php echo __( 'Órden de Compra del proveedor', 'providers-for-woocommerce' ) . ' ' . esc_html( $provider[0]->post_title ); ?></h1>

	<hr class="wp-header-end">

	<div class="table-wrapper">
		<div id="overlay">
			<div class="spinner-border"></div>
		</div>

		<table id="table-provider-orders" class="display">
			<thead>
				<tr>
					<th>&nbsp;</th>
					<?php foreach ($columns as $key => $column) { ?>
					<th align="<?php echo (($key >= 2) ? 'right' : 'center'); ?>"><?php echo $column; ?></th>
					<?php } ?>
				</tr>
			</thead>

			<tbody>
				<?php foreach ($products as $index => $product) { ?>
				<tr data-index="<?php echo $index; ?>" data-post-id="<?php echo $product['ID']; ?>">
					<td>
						<input type="checkbox" name="checkbox_product_<?php echo $index; ?>" id="checkbox_product_<?php echo $index; ?>" class="select-product" value="<?php echo $product['ID']; ?>" />
					</td>
					<td><?php echo $product['_sku']; ?></td>
					<td><?php echo $product['post_title']; ?></td>
					<td align="right"><?php echo $product['purchase_price']; ?></td>
					<td align="right"><?php echo $product['profit_margin']; ?></td>
					<td align="right"><?php echo $product['_price']; ?></td>
					<td align="right"><?php echo $product['_stock']; ?></td>
				</tr>
				<?php } ?>
			</tbody>

			<tfoot>
				<tr>
					<th>&nbsp;</th>
					<?php foreach ($columns as $key => $column) { ?>
					<th align="<?php echo (($key >= 2) ? 'right' : 'center'); ?>"><?php echo $column; ?></th>
					<?php } ?>
				</tr>
			</tfoot>
		</table>

		<?php if (count($products) > 0) { ?>
		<div class="export-wrapper">
			<form method="POST" action="/wp-admin/admin-ajax.php?action=generate_purchase_order_xls" class="export-form">
				<?php echo __('Exportar Órden de Compra para', 'providers-for-woocommerce'); ?>
				<input type="number" name="days" id="export-input" class="export-input disabled" min="1" disabled="disabled" />
				<?php echo __('días', 'providers-for-woocommerce'); ?>
				<input type="submit" name="export" id="export-button" class="export-button add-new-h2 disabled" value="⇩" disabled="disabled" />
			</form>
		</div>
		<?php } ?>
	</div>
</div>
