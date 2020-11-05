<?php
require_once ABSPATH . 'wp-admin/admin-header.php';

global $wpdb;

if ($_GET['id']) {
	$meta_key = 'provider';

	$provider_id = $_GET['id'];

	$provider = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE ID={$provider_id}" );

	$categories = $wpdb->get_results(
		"
		SELECT
			term.term_id AS term_id,
			term.name AS term_name,
			COUNT(p.ID) AS quantity
		FROM {$wpdb->prefix}term_taxonomy AS tax
			LEFT JOIN {$wpdb->prefix}term_relationships AS rel ON rel.term_taxonomy_id = tax.term_taxonomy_id
			LEFT JOIN {$wpdb->prefix}terms AS term ON term.term_id = tax.term_id
			LEFT JOIN (
				SELECT
					P.ID,
					P.post_title
				FROM {$wpdb->prefix}postmeta PM
					LEFT JOIN {$wpdb->prefix}posts P ON P.ID = PM.post_id
				WHERE PM.meta_key='{$meta_key}' AND PM.meta_value={$provider_id}
				ORDER BY P.post_title ASC
			) AS p ON p.ID = rel.object_id
		WHERE tax.taxonomy='product_cat'
		GROUP BY tax.term_taxonomy_id
		ORDER BY term.name ASC
		",
		'ARRAY_A'
	);

	$page_title = __( 'Reporte de Productos del proveedor', 'providers-for-woocommerce' ) . ' ' . esc_html( $provider[0]->post_title );
} else {
	$categories = $wpdb->get_results(
		"
		SELECT
			term.term_id AS term_id,
			term.name AS term_name,
			COUNT(P.ID) AS quantity
		FROM {$wpdb->prefix}term_taxonomy AS tax
			LEFT JOIN {$wpdb->prefix}term_relationships AS rel ON rel.term_taxonomy_id = tax.term_taxonomy_id
			LEFT JOIN {$wpdb->prefix}terms AS term ON term.term_id = tax.term_id
			LEFT JOIN {$wpdb->prefix}posts P ON P.ID = rel.object_id
		WHERE tax.taxonomy='product_cat'
		GROUP BY tax.term_taxonomy_id
		ORDER BY term.name ASC
		",
		'ARRAY_A'
	);

	$page_title = __( 'Reporte de Productos de Todos los Proveedores', 'providers-for-woocommerce' );
}
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo $page_title; ?></h1>

	<hr class="wp-header-end">

	<div class="report-wrapper">
		<form method="POST" action="/wp-admin/admin-ajax.php?action=generate_sales_report_xls" class="export-form">
			<?php if ($_GET['id']) { ?>
			<input type="hidden" name="provider_id" id="provider_id" value="<?php echo $provider_id; ?>" />
			<?php } ?>

			<input type="hidden" name="start_date" id="start_date" value="" />

			<input type="hidden" name="end_date" id="end_date" value="" />

			<span class="report-label"><?php echo __( 'Fecha', 'providers-for-woocommerce' ); ?></span>

			<input id="date-range" name="date-range" />

			<span class="report-label"><?php echo __( 'Categorias', 'providers-for-woocommerce' ); ?></span>

			<div class="select-wrapper">
				<span><?php echo __( 'Seleccionar', 'providers-for-woocommerce' ); ?></span>
				<a href="javascript:void(0);" class="category-select-all"><?php echo __( 'Todas', 'providers-for-woocommerce' ); ?></a>
				<span>/<span>
				<a href="javascript:void(0);" class="category-select-none"><?php echo __( 'Ninguna', 'providers-for-woocommerce' ); ?></a>
			</div>

			<div class="category-wrapper">
				<div class="category-row">
					<?php foreach (array_chunk($categories, ceil(count($categories) / 3)) as $categories_column) { ?>
					<div class="category-column">
						<?php
						foreach ( (array) $categories_column as $term ) {
						?>
						<label class="selectit">
							<input id="<?php echo "category-id-{$term['term_id']}"; ?>" name="category_id[<?php echo (int) $term['term_id']; ?>]" class="category-id" type="checkbox" value="<?php echo (int) $term['term_id']; ?>" />
							<?php echo $term['term_name']; ?>
						</label>
						<?php } ?>
					</div>
					<?php } ?>
				</div>
			</div>

			<div class="export-wrapper">
				<input type="submit" name="export" id="export-button" class="export-button add-new-h2 disabled" value="<?php echo __('Exportar Reporte', 'providers-for-woocommerce'); ?>" disabled="disabled" />
			</div>
		</form>
	</div>
</div>