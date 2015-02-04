<div class="group-shop manage product-type-meta edit">
	<?php

		if ( $isFancybox ) :

			echo '<h1>' . $page->title . '</h1>';
			$_class = 'system-alert';

		else :

			$_class = '';

		endif;

		echo form_open( uri_string() . $isFancybox );

	?>
	<p class="<?=$_class?>">
		Product Type Meta fields allow the shop to store additional information for variants. The store
		also uses this data to provide a user friendly filtering system which responds to the products
		available in the current view.
	</p>
	<?=$isFancybox ? '' : '<hr />'?>
	<ul class="tabs disabled">
		<li class="tab">
			<?=anchor( 'admin/shop/manage/product_type_meta' . $isFancybox, 'Overview', 'class="confirm" data-title="Are you sure?" data-body="Any unsaved changes will be lost."' )?>
		</li>
		<li class="tab active">
			<?=anchor( 'admin/shop/manage/product_type_meta/create' . $isFancybox, 'Create Product Type' )?>
		</li>
	</ul>
	<section class="tabs pages">
		<div class="tab page active">
			<fieldset>
				<legend>Basic Details</legend>
				<?php

					// --------------------------------------------------------------------------

					$_field					= array();
					$_field['key']			= 'label';
					$_field['label']		= 'Label';
					$_field['required']		= TRUE;
					$_field['default']		= isset( $meta_field->label ) ? $meta_field->label : '';
					$_field['placeholder']	= 'The label to give this field, e.g., Colour';

					echo form_field( $_field );

					// --------------------------------------------------------------------------

					$_field					= array();
					$_field['key']			= 'admin_form_sub_label';
					$_field['label']		= 'Admin: Sub Label';
					$_field['placeholder']	= 'The sub label, shown beneath the text on the left using a smaller font size on the Inventory Create and Edit pages';
					$_field['default']		= isset( $meta_field->admin_form_sub_label ) ? $meta_field->admin_form_sub_label : '';

					echo form_field( $_field );

					// --------------------------------------------------------------------------

					$_field					= array();
					$_field['key']			= 'admin_form_placeholder';
					$_field['label']		= 'Admin: Placeholder';
					$_field['default']		= isset( $meta_field->admin_form_placeholder ) ? $meta_field->admin_form_placeholder : '';
					$_field['placeholder']	= 'Placeholder text in admin on the Inventory Create and Edit pages, in the same way that this one is being shown now';

					echo form_field( $_field );

					// --------------------------------------------------------------------------

					$_field					= array();
					$_field['key']			= 'admin_form_tip';
					$_field['label']		= 'Admin: Tip';
					$_field['placeholder']	= 'Tip for this field when rendered on the Inventory Create and Edit pages';
					$_field['default']		= isset( $meta_field->admin_form_tip ) ? $meta_field->admin_form_tip : '';
					$_field['tip']			= 'This text will be shown as a tooltip in admin, in the same way that this one is being shown now';

					echo form_field( $_field );

					// --------------------------------------------------------------------------

					$_field				= array();
					$_field['key']		= 'allow_multiple';
					$_field['label']	= 'Allow Multiple Selections';
					$_field['tip']		= 'Allow admin to specify more than one value per variant.';
					$_field['text_on']	= lang( 'yes' );
					$_field['text_off']	= lang( 'no' );
					$_field['default']	= isset( $meta_field->allow_multiple ) ? $meta_field->allow_multiple : FALSE;

					echo form_field_boolean( $_field );

					// --------------------------------------------------------------------------

					$_field				= array();
					$_field['key']		= 'is_filter';
					$_field['label']	= 'Is Filter';
					$_field['tip']		= 'Allow this field to act as a product filter on the front end.';
					$_field['text_on']	= lang( 'yes' );
					$_field['text_off']	= lang( 'no' );
					$_field['default']	= isset( $meta_field->allow_multiple ) ? $meta_field->allow_multiple : FALSE;

					echo form_field_boolean( $_field );

				?>
			</fieldset>
			<fieldset>
				<legend>Associated Product Types</legend>
				<p>
					Specify which product types should inherit this meta field. Meta fields form the basis of the sidebar filters in the front end.
				</p>
				<select name="associated_product_types[]" multiple="multiple" class="select2" data-placeholder="Select product types">
					<option value=""></option>
					<?php

						$_selected = array();

						if ( $this->input->post( 'associated_product_types' ) ) :

							$_selected[] = $this->input->post( 'associated_product_types' );

						elseif( isset( $meta_field->associated_product_types ) ) :

							foreach ( $meta_field->associated_product_types as $product_type ) :

								$_selected[] = $product_type->id;

							endforeach;

						endif;

						foreach ( $product_types as $type ) :

							$_is_selected = array_search( $type->id, $_selected ) !== FALSE ? 'selected="selected"' : '';

							echo '<option value="' . $type->id . '" ' .$_is_selected . '>';
								echo $type->label;
							echo '</option>';

						endforeach;

					?>
				</select>
			</fieldset>
			<p style="margin-top:1em;">
				<?=form_submit( 'submit', 'Save', 'class="awesome"' )?>
				<?=anchor( 'admin/shop/manage/product_type_meta' . $isFancybox, 'Cancel', 'class="awesome red confirm" data-title="Are you sure?" data-body="All unsaved changes will be lost."' )?>
			</p>
		</div>
	</section>
	<?=form_close();?>
</div>