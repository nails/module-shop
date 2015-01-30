<?php

	//	Counter string
	//	If the $variation var is passed then we're loading this in PHP and we want
	//	to prefill the fields. The form_helper functions don't pick up the fields
	//	automatically because of the Mustache ' . $_counter . ' variable.

	//	Additionally, make sure it's not === FALSE as the value can persist when
	//	this view is loaded multiple times.

	$_counter = isset( $variation ) && $counter !== FALSE ? $counter : '{{counter}}';

?>
<div id="variation-<?=$_counter?>" class="variation" data-counter="<?=$_counter?>">
	<?php

		//	Pass the vraiation ID along for the ride too
		if ( ! empty( $variation->id ) ) :

			echo form_hidden( 'variation[' . $_counter . '][id]', $variation->id );

		elseif ( ! empty( $variation['id'] ) ) :

			echo form_hidden( 'variation[' . $_counter . '][id]', $variation['id'] );

		endif;

	?>
	<div class="not-applicable">
		<p>
			<strong>The specified product type has a limited number of variations it can support.</strong>
			This variation will be deleted when you submit this form.
		</p>
	</div>
	<ul class="tabs" data-tabgroup="variation-<?=$_counter?>">
		<li class="tab active">
			<a href="#" class="tabber-variation-details" data-tab="tab-variation-<?=$_counter?>-details">Details</a>
		</li>
		<li class="tab">
			<a href="#" data-tab="tab-variation-<?=$_counter?>-meta">Meta</a>
		</li>
		<li class="tab">
			<a href="#" data-tab="tab-variation-<?=$_counter?>-pricing">Pricing</a>
		</li>
		<li class="tab">
			<a href="#" data-tab="tab-variation-<?=$_counter?>-gallery">Gallery</a>
		</li>
		<li class="tab">
			<a href="#" class="tabber-variation-shipping" data-tab="tab-variation-<?=$_counter?>-shipping">Shipping</a>
		</li>
		<li class="action">
			<a href="#" class="delete">Delete</a>
		</li>
	</ul>
	<section class="tabs pages variation-<?=$_counter?>">
		<div class="tab page active fieldset" id="tab-variation-<?=$_counter?>-details">
			<?php

				$_field					= array();
				$_field['key']			= 'variation[' . $_counter . '][label]';
				$_field['label']		= 'Label';
				$_field['required']		= TRUE;
				$_field['placeholder']	= 'Give this variation a title';
				$_field['default']		= ! empty( $variation->label ) ? $variation->label : '';

				echo form_field( $_field );

				// --------------------------------------------------------------------------

				$_field					= array();
				$_field['key']			= 'variation[' . $_counter . '][sku]';
				$_field['label']		= 'SKU';
				$_field['placeholder']	= 'This variation\'s Stock Keeping Unit; a unique offline identifier (e.g for POS or warehouses)';
				$_field['default']		= ! empty( $variation->sku ) ? $variation->sku : '';

				echo form_field( $_field );

				// --------------------------------------------------------------------------

				$_field					= array();
				$_field['key']			= 'variation[' . $_counter . '][stock_status]';
				$_field['label']		= 'Stock Status';
				$_field['class']		= 'select2 stock-status';
				$_field['required']		= TRUE;
				$_field['default']		= ! empty( $variation->stock_status ) ? $variation->stock_status : 'IN_STOCK';

				$_options					= array();
				$_options['IN_STOCK']		= 'In Stock';
				$_options['OUT_OF_STOCK']	= 'Out of Stock';

				echo form_field_dropdown( $_field, $_options );

				// --------------------------------------------------------------------------

				$_status	= set_value( 'variation[' . $_counter . '][stock_status]', $_field['default'] );
				$_display	= $_status == 'IN_STOCK' ? 'block' : 'none';

				echo '<div class="stock-status-field IN_STOCK" style="display:' . $_display . '">';

					$_field					= array();
					$_field['key']			= 'variation[' . $_counter . '][quantity_available]';
					$_field['label']		= 'Quantity Available';
					$_field['placeholder']	= 'How many units of this variation are available? Leave blank for unlimited';
					$_field['default']		= isset( $variation->quantity_available ) ? $variation->quantity_available : '';

					echo form_field( $_field );

				echo '</div>';

				// --------------------------------------------------------------------------

				$_field				= array();
				$_field['key']		= 'variation[' . $_counter . '][out_of_stock_behaviour]';
				$_field['label']	= 'Out of Stock Behaviour';
				$_field['class']	= 'select2 out-of-stock-behaviour';
				$_field['required']	= TRUE;
				$_field['default']	= ! empty( $variation->out_of_stock_behaviour ) ? $variation->out_of_stock_behaviour : 'OUT_OF_STOCK';
				$_field['tip']		= 'Specify the behaviour of the item when the quantity available of an item reaches 0.';

				$_options					= array();
				$_options['TO_ORDER']		= 'Behave as if: To Order';
				$_options['OUT_OF_STOCK']	= 'Behave as if: Out of Stock';

				echo form_field_dropdown( $_field, $_options );

				// --------------------------------------------------------------------------

				$_status	= set_value( 'variation[' . $_counter . '][out_of_stock_behaviour]', $_field['default'] );
				$_display = $_status == 'TO_ORDER' ? 'block' : 'none';

				echo '<div class="out-of-stock-behaviour-field TO_ORDER" style="display:' . $_display . '">';

					$_field					= array();
					$_field['key']			= 'variation[' . $_counter . '][out_of_stock_to_order_lead_time]';
					$_field['label']		= 'Out of Stock Lead Time';
					$_field['sub_label']	= 'Max. 50 characters';
					$_field['required']		= TRUE;
					$_field['placeholder']	= 'How long is the lead time on orders for this product when it\'s out of stock?';
					$_field['default']		= ! empty( $variation->out_of_stock_to_order_lead_time ) ? $variation->out_of_stock_to_order_lead_time : '';

					echo form_field( $_field );

				echo '</div>';

			?>
		</div>

		<div class="tab page fieldset" id="tab-variation-<?=$_counter?>-meta">
			<?php

				foreach ( $product_types_meta as $product_type_id => $fields ) :

					echo '<div class="meta-fields meta-fields-' . $product_type_id . '" style="display:none;">';

					if ( $fields ) :

						$_defaults = array();

						//	Set any default values
						if ( isset( $variation->meta ) ) :

							//	DB Data
							foreach ( $variation->meta as $variation_meta ) :

								$_defaults[$variation_meta->meta_field_id] = $variation_meta->value;

								if ( $variation_meta->allow_multiple ) :

									$_defaults[$variation_meta->meta_field_id] = implode( ',', $_defaults[$variation_meta->meta_field_id] );

								endif;

							endforeach;

						elseif ( isset( $variation['meta'][$product_type_id] ) ) :

							//	POST Data
							foreach ( $variation['meta'][$product_type_id] as $meta_field_id => $meta_field_value ) :

								$_defaults[$meta_field_id] = $meta_field_value;

							endforeach;

						endif;

						foreach ( $fields as $field ) :

							$_field					= array();
							$_field['key']			= 'variation[' . $_counter . '][meta][' . $product_type_id . '][' .  $field->id . ']';
							$_field['label']		= ! empty( $field->label )					? $field->label : '';
							$_field['sub_label']	= ! empty( $field->admin_form_sub_label )	? $field->admin_form_sub_label : '';
							$_field['placeholder']	= ! empty( $field->admin_form_placeholder )	? $field->admin_form_placeholder : '';
							$_field['tip']			= ! empty( $field->admin_form_tip )			? $field->admin_form_tip : '';
							$_field['class']		= ! empty( $field->allow_multiple )			? 'allow-multiple' : '';
							$_field['default']		= ! empty( $_defaults[$field->id] )			? $_defaults[$field->id] : '';
							$_field['info']			= ! empty( $field->allow_multiple )			? '<strong>Tip:</strong> This field accepts multiple selections, seperate multiple values with a comma or hit enter.' : '';

							echo form_field( $_field );

						endforeach;

					else :

						echo '<p>There are no extra fields for this product type.</p>';

					endif;

					echo '</div>';

				endforeach;

			?>
		</div>

		<div class="tab page" id="tab-variation-<?=$_counter?>-pricing">
			<?php if ( count( $currencies ) > 1 ) : ?>
			<p>
				Define the price points for this variation. If you'd like to set a specific price for a certain
				currency then define that also otherwise the system will calculate automatically using current
				exchange rates.
			</p>
			<?php endif; ?>
			<table class="pricing-options">
				<thead>
					<tr>
						<th>Currency</th>
						<th>Price</th>
						<th>Sale Price</th>
					</tr>
				</thead>
				<tbody>

					<!--	BASE CURRENCY	-->
					<?php

						if ( $is_first ) :

							$_attr_price		= 'data-code="' . SHOP_BASE_CURRENCY_CODE . '"';
							$_attr_price_sale	= 'data-code="' . SHOP_BASE_CURRENCY_CODE . '"';

							$_class_price		= array( 'base-price' );
							$_class_price_sale	= array( 'base-price-sale' );

						else :

							$_attr_price		= '';
							$_attr_price_sale	= '';

							$_class_price		= array( 'variation-price', SHOP_BASE_CURRENCY_CODE );
							$_class_price_sale	= array( 'variation-price-sale', SHOP_BASE_CURRENCY_CODE );

						endif;

						// --------------------------------------------------------------------------

						//	Prep the prices into an easy to access array
						$_price			= array();
						$_sale_price	= array();

						if ( ! empty( $variation->price_raw ) ) :

							foreach ( $variation->price_raw as $price ) :

								$_price[$price->currency]			= $price->price;
								$_sale_price[$price->currency]	= $price->sale_price;

							endforeach;

						endif;

					?>
					<tr>
						<td class="currency">
							<?php

								echo SHOP_BASE_CURRENCY_CODE;

								$_key = 'variation[' . $_counter . '][pricing][0][currency]';
								echo form_hidden( $_key, SHOP_BASE_CURRENCY_CODE );

							?>
						</td>
						<td class="price">
							<?php

								$_key		= 'variation[' . $_counter . '][pricing][0][price]';
								$_error		= form_error( $_key, '<span class="error show-in-tabs">', '</span>' );
								$_class		= $_class_price;
								$_default	= ! empty( $_price[SHOP_BASE_CURRENCY_CODE] ) ? $_price[SHOP_BASE_CURRENCY_CODE] : '';

								if ( $_error ) :

									$_class[] = 'error';

								endif;

								$_class = $_class ? ' class="' . implode( ' ', $_class ) . '"' : '';

								echo form_input( $_key, set_value( $_key, $_default ), 'data-prefix="' . SHOP_BASE_CURRENCY_SYMBOL . '" ' . $_attr_price . $_class . ' placeholder="Price"' );
								echo $_error;

							?>
						</td>
						<td class="price-sale">
							<?php

								$_key		= 'variation[' . $_counter . '][pricing][0][sale_price]';
								$_error		= form_error( $_key, '<span class="error show-in-tabs">', '</span>' );
								$_class		= $_class_price_sale;
								$_default	= ! empty( $_sale_price[SHOP_BASE_CURRENCY_CODE] ) ? $_sale_price[SHOP_BASE_CURRENCY_CODE] : '';

								if ( $_error ) :

									$_class[] = 'error';

								endif;

								$_class = $_class ? ' class="' . implode( ' ', $_class ) . '"' : '';

								echo form_input( $_key, set_value( $_key, $_default ), 'data-prefix="' . SHOP_BASE_CURRENCY_SYMBOL . '" ' . $_attr_price_sale . $_class . ' placeholder="Sale Price"' );
								echo $_error;

							?>
						</td>
					</tr>

					<!--	OTHER CURRENCIES	-->
					<?php

						$_counter_inside = 1;
						foreach ( $currencies as $currency ) :

							if ( $currency->code != SHOP_BASE_CURRENCY_CODE ) :

								if ( $is_first ) :

									$_attr_price		= 'data-code="' . $currency->code . '"';
									$_attr_price_sale	= 'data-code="' . $currency->code . '"';

									$_class_price		= array( 'base-price' );
									$_class_price_sale	= array( 'base-price-sale' );

								else :

									$_attr_price		= '';
									$_attr_price_sale	= '';

									$_class_price		= array( 'variation-price', $currency->code );
									$_class_price_sale	= array( 'variation-price-sale', $currency->code );

								endif;

								?>
								<tr>
									<td class="currency">
										<?php

											echo $currency->code;

											$_key = 'variation[' . $_counter . '][pricing][' . $_counter_inside . '][currency]';
											echo form_hidden( $_key, $currency->code );

										?>
									</td>
									<td class="price">
										<?php

											$_key		= 'variation[' . $_counter . '][pricing][' . $_counter_inside . '][price]';
											$_error		= form_error( $_key, '<span class="error show-in-tabs">', '</span>' );
											$_class		= $_class_price;
											$_default	= ! empty( $_price[$currency->code] ) ? $_price[$currency->code] : '';

											if ( $_error ) :

												$_class[] = 'error';

											endif;

											$_class = $_class ? ' class="' . implode( ' ', $_class ) . '"' : '';

											echo form_input( $_key, set_value( $_key, $_default ), 'data-prefix="' . $currency->symbol . '" ' . $_attr_price . $_class . ' placeholder="Calculate automatically from ' . SHOP_BASE_CURRENCY_CODE . '"' );
											echo $_error;

										?>
									</td>
									<td class="price-sale">
										<?php

											$_key		= 'variation[' . $_counter . '][pricing][' . $_counter_inside . '][sale_price]';
											$_error		= form_error( $_key, '<span class="error show-in-tabs">', '</span>' );
											$_class		= $_class_price_sale;
											$_default	= ! empty( $_sale_price[$currency->code] ) ? $_sale_price[$currency->code] : '';

											if ( $_error ) :

												$_class[] = 'error';

											endif;

											$_class = $_class ? ' class="' . implode( ' ', $_class ) . '"' : '';
											echo form_input( $_key, set_value( $_key, $_default ), 'data-prefix="' . $currency->symbol . '" ' . $_attr_price_sale . $_class . ' placeholder="Calculate automatically from ' . SHOP_BASE_CURRENCY_CODE . '"' );
											echo $_error;

										?>
									</td>
								</tr>
								<?php

								$_counter_inside++;

							endif;

						endforeach;

					?>

				</tbody>
			</table>
			<?php

				if ( $is_first ) :

					$_display = empty( $num_variants ) || $num_variants == 1 ? 'none' : 'block';
					echo '<p id="variation-sync-prices" style="display:' . $_display . '">';
					echo '<a href="#" class="awesome small orange">Sync Prices</a>';
					echo '</p>';

				endif;

			?>
		</div>

		<div class="tab page" id="tab-variation-<?=$_counter?>-gallery">
			<p>
				Specify which, if any, of the uploaded gallery images feature this product variation.
			</p>
			<?php

				//	Render, if there's POST then make sure we render it enough times
				//	Otherwise check to see if there's $item data

				if ( $this->input->post( 'gallery' ) ) :

					$_gallery	= $this->input->post( 'gallery' );
					$_selected	= isset( $_POST['variation'][$_counter]['gallery'] ) ? $_POST['variation'][$_counter]['gallery'] : array();

				elseif( ! empty( $item->gallery ) ) :

					$_gallery	= $item->gallery;
					$_selected	= ! empty( $variation->gallery ) ? $variation->gallery : array();

				else :

					$_gallery	= array();
					$_selected	= array();

				endif;

			?>
			<ul class="gallery-associations <?=! empty( $_gallery ) ? '' : 'empty' ?>">
				<li class="empty">No images have been uploaded; upload some using the <a href="#">Gallery tab</a></li>
				<?php

					if ( ! empty( $_gallery ) ) :

						foreach ( $_gallery as $image ) :

							//	Is this item selected for this variation?
							$_checked = array_search( $image, $_selected ) !== FALSE ? 'selected' : FALSE;

							echo '<li class="image object-id-' . $image . ' ' . $_checked . '">';
								echo form_checkbox( 'variation[' . $_counter . '][gallery][]', $image, (bool) $_checked );
								echo img( cdn_thumb( $image, 34, 34 ) );
							echo '</li>';

						endforeach;

					endif;

				?>
				<li class="actions">
					<a href="#" data-function="all" class="action awesome small orange">Select All</a>
					<a href="#" data-function="none" class="action awesome small orange">Select None</a>
					<a href="#" data-function="toggle" class="action awesome small orange">Toggle</a>
				</li>
			</ul>
		</div>

		<div class="tab page fieldset" id="tab-variation-<?=$_counter?>-shipping">
			<?php

				if ( ! empty( $shipping_driver ) ) :

					echo '<div class="shipping-collection-only">';

						$_field					= array();
						$_field['key']			= 'variation[' . $_counter . '][shipping][collection_only]';
						$_field['label']		= 'Collection Only';
						$_field['readonly']		= ! app_setting( 'warehouse_collection_enabled', 'shop' );
						$_field['info']			= ! app_setting( 'warehouse_collection_enabled', 'shop' ) ? '<strong>Warehouse Collection is disabled</strong>' : '';
						$_field['info']			.= ! app_setting( 'warehouse_collection_enabled', 'shop' ) && user_has_permission( 'admin.settings:0' ) ? '<br />If you wish to allow customers to collect from your warehouse you must enable it in ' . anchor( 'admin/settings/shop', 'settings', 'class="confirm" data-title="Stop Editing?" data-body="Any unsaved changes will be lost."' ) . '.' : '';
						$_field['default']		= isset( $variation->shipping->collection_only ) ? (bool) $variation->shipping->collection_only : FALSE;
						$_tip					= 'Items marked as collection only will be handled differently in checkout and reporting.';

						echo form_field_boolean( $_field, $_tip );

					echo '</div>';

					// --------------------------------------------------------------------------

					if ( ! empty( $shipping_options_variant ) ) :

						$_display = $_field['default'] ? 'none' : 'block';
						echo '<div class="shipping-driver-options" style="display:' . $_display . '">';

							//	Any further options from the shipping driver?
							foreach ( $shipping_options_variant as $field ) :

								//	Prep the field names
								if ( empty( $field['key'] ) ) :

									continue;

								endif;

								//	Order is important here as $field['key'] gets overwritte
								$_default			= isset( $variation->shipping->driver_data[$shipping_driver->slug][$field['key']] ) ? $variation->shipping->driver_data[$shipping_driver->slug][$field['key']] : '';
								$field['key']		= 'variation[' . $_counter . '][shipping][driver_data][' . $shipping_driver->slug . '][' . $field['key'] . ']';
								$field['default']	= set_value( $field['key'], $_default );

								//	TODO: Use admin form builder
								//	Asana ticket: https://app.asana.com/0/6627768688940/15891120890395

								$_type = isset( $field['type'] ) ? $field['type'] : '';

								switch ( $_type ) :

									case 'dropdown' :

										echo form_field_dropdown( $field );

									break;

									default :

										echo form_field( $field );

									break;

								endswitch;

							endforeach;

						echo '</div>';

						$_display = $_field['default'] ? 'block' : 'none';
						echo '<div class="shipping-driver-options-hidden" style="display:' . $_display . '">';
							echo '<p class="system-alert notice" style="margin-top:1em;">';
								echo 'Further shipping options have been hidden because the item is set as "collection only" and will not be included while calculating shipping costs.';
							echo '</p>';
						echo '</div>';


					endif;

				else :

					echo '<p class="system-alert message">';
						echo '<strong>No Shipping Drivers Enabled.</strong>';
						echo user_has_permission( 'admin.settings:0' ) ? '<br />You can enable and configure shipping drivers in ' . anchor( 'admin/settings/shop', 'settings', 'class="confirm" data-title="Stop Editing?" data-body="Any unsaved changes will be lost."' ) . '.' : '';
					echo '</p>';

				endif;

			?>
		</div>
	</section>
</div>