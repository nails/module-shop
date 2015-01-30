<div class="group-shop manage index">
	<p class="<?=$is_fancybox ? 'system-alert' : ''?>">
		Choose which Manager you'd like to utilise.
	</p>
	<?=$is_fancybox ? '' : '<hr />'?>
	<ul>
	<?php

		//	Gather manager options available to this user
		$_option = array();

		if ( user_has_permission( 'admin.shop:0.attribute_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/attribute' . $is_fancybox, 'Attributes' );

		endif;

		if ( user_has_permission( 'admin.shop:0.brand_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/brand' . $is_fancybox, 'Brands' );

		endif;

		if ( user_has_permission( 'admin.shop:0.category_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/category' . $is_fancybox, 'Categories' );

		endif;

		if ( user_has_permission( 'admin.shop:0.collection_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/collection' . $is_fancybox, 'Collections' );

		endif;

		if ( user_has_permission( 'admin.shop:0.range_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/range' . $is_fancybox, 'Ranges' );

		endif;

		if ( user_has_permission( 'admin.shop:0.tag_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/tag' . $is_fancybox, 'Tags' );

		endif;

		if ( user_has_permission( 'admin.shop:0.tax_rate_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/tax_rate' . $is_fancybox, 'Tax Rates' );

		endif;

		if ( user_has_permission( 'admin.shop:0.product_type_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/product_type' . $is_fancybox, 'Product Types' );

		endif;

		if ( user_has_permission( 'admin.shop:0.product_type_meta_manage' ) ) :

			$_options[] = anchor( 'admin/shop/manage/product_type_meta' . $is_fancybox, 'Product Type Meta' );

		endif;

		// --------------------------------------------------------------------------

		if ( ! empty( $_options ) ) :

			echo '<ul>';
				echo '<li>' . implode( '</li><li>', $_options ) . '</li>';
			echo '</ul>';

		else :

			echo '<p class="system-alert message">';
				echo '<strong>Sorry,</strong> it looks as if there are no manager options available for you to use. If you were expecting to see options here then please contact the shop manager.';
			echo '</p>';

		endif;

	?>
	</ul>
</div>