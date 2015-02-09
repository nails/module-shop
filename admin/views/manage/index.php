<div class="group-shop manage index">
	<p class="<?=$isFancybox ? 'system-alert' : ''?>">
		Choose which Manager you'd like to utilise.
	</p>
	<?=$isFancybox ? '' : '<hr />'?>
	<ul>
	<?php

		//	Gather manager options available to this user
		$_option = array();

		if ( userHasPermission( 'admin.shop:0.attribute_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/attribute' . $isFancybox, 'Attributes' );

		endif;

		if ( userHasPermission( 'admin.shop:0.brand_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/brand' . $isFancybox, 'Brands' );

		endif;

		if ( userHasPermission( 'admin.shop:0.category_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/category' . $isFancybox, 'Categories' );

		endif;

		if ( userHasPermission( 'admin.shop:0.collection_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/collection' . $isFancybox, 'Collections' );

		endif;

		if ( userHasPermission( 'admin.shop:0.range_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/range' . $isFancybox, 'Ranges' );

		endif;

		if ( userHasPermission( 'admin.shop:0.tag_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/tag' . $isFancybox, 'Tags' );

		endif;

		if ( userHasPermission( 'admin.shop:0.tax_rate_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/tax_rate' . $isFancybox, 'Tax Rates' );

		endif;

		if ( userHasPermission( 'admin.shop:0.product_type_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/product_type' . $isFancybox, 'Product Types' );

		endif;

		if ( userHasPermission( 'admin.shop:0.product_type_meta_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/product_type_meta' . $isFancybox, 'Product Type Meta' );

		endif;

		// --------------------------------------------------------------------------

		if ( ! empty( $_options ) ) :

			echo '<ul>';
				echo '<li>' . implode( '</li><li>', $_options ) . '</li>';
			echo '</ul>';

		else :

			echo '<p class="system-alert message">';
				echo 'It looks as if there are no manager options available for you to use. If you were expecting to see options here then please contact the shop manager.';
			echo '</p>';

		endif;

	?>
	</ul>
</div>