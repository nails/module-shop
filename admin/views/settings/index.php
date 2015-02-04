<div class="group-settings shop">
	<p>
		Configure various aspects of the shop.
	</p>

	<hr />

	<ul class="tabs" data-tabgroup="main-tabs">
		<?php $_active = $this->input->post( 'update' ) == 'settings' || ! $this->input->post() ? 'active' : ''?>
		<li class="tab <?=$_active?>">
			<a href="#" data-tab="tab-general">General</a>
		</li>

		<?php $_active = $this->input->post( 'update' ) == 'browse' ? 'active' : ''?>
		<li class="tab <?=$_active?>">
			<a href="#" data-tab="tab-browse">Browsing</a>
		</li>

		<?php $_active = $this->input->post( 'update' ) == 'skin' ? 'active' : ''?>
		<li class="tab <?=$_active?>">
			<a href="#" data-tab="tab-skin">Skin</a>
		</li>

		<?php $_active = $this->input->post( 'update' ) == 'skin-configure' ? 'active' : ''?>
		<li class="tab <?=$_active?>">
			<a href="#" data-tab="tab-skin-config">Skin - Configure</a>
		</li>

		<?php $_active = $this->input->post( 'update' ) == 'payment_gateway' ? 'active' : ''?>
		<li class="tab <?=$_active?>">
			<a href="#" data-tab="tab-payment-gateway">Payment Gateways</a>
		</li>

		<?php $_active = $this->input->post( 'update' ) == 'currencies' ? 'active' : ''?>
		<li class="tab <?=$_active?>">
			<a href="#" data-tab="tab-currencies">Currencies</a>
		</li>

		<?php $_active = $this->input->post( 'update' ) == 'shipping' ? 'active' : ''?>
		<li class="tab <?=$_active?>">
			<a href="#" data-tab="tab-shipping">Shipping</a>
		</li>
	</ul>

	<section class="tabs pages main-tabs">

		<?php $_display = $this->input->post( 'update' ) == 'settings' || ! $this->input->post() ? 'active' : ''?>
		<div id="tab-general" class="tab page <?=$_display?> general">
			<?=form_open( NULL, 'style="margin-bottom:0;"' )?>
			<?=form_hidden( 'update', 'settings' )?>
			<p>
				Generic store settings. Use these to control some store behaviours.
			</p>
			<hr />
			<fieldset id="shop-settings-name">
				<legend>Name</legend>
				<p>
					Is this a shop? Or is it store? Maybe a boutique...?
				</p>
				<?php

					//	Shop Name
					$_field					= array();
					$_field['key']			= 'name';
					$_field['label']		= 'Shop Name';
					$_field['default']		= app_setting( $_field['key'], 'shop' ) ? app_setting( $_field['key'], 'shop' ) : 'Shop';
					$_field['placeholder']	= 'Customise the Shop\'s Name';

					echo form_field( $_field );

				?>
			</fieldset>

			<fieldset id="shop-settings-url">
				<legend>URL</legend>
				<p>
					Customise the shop's URL by specifying it here.
				</p>
				<?php

					//	Shop URL
					$_field					= array();
					$_field['key']			= 'url';
					$_field['label']		= 'Shop URL';
					$_field['default']		= app_setting( $_field['key'], 'shop' ) ? app_setting( $_field['key'], 'shop' ) : 'shop/';
					$_field['placeholder']	= 'Customise the Shop\'s URL (include trialing slash)';

					echo form_field( $_field );

				?>
			</fieldset>

			<fieldset id="shop-settings-tax">
				<legend>Taxes</legend>
				<p>
					Configure how the shop calculates taxes on the products you sell.
				</p>
				<?php

					$_field				= array();
					$_field['key']		= 'price_exclude_tax';
					$_field['label']	= 'Product price exclude Taxes';
					$_field['default']	= app_setting( $_field['key'], 'shop' );
					$_field['text_on']	= strtoupper( lang( 'yes' ) );
					$_field['text_off']	= strtoupper( lang( 'no' ) );

					echo form_field_boolean( $_field );

				?>
			</fieldset>

			<fieldset id="shop-settings-external-products">
				<legend>External Products</legend>
				<p>
					Allow the shop to list items sold by another vendor. External products are listed and categorised just like normal
					products but will link out to a third party.
				</p>
				<?php

					$_field				= array();
					$_field['key']		= 'enable_external_products';
					$_field['label']	= 'Enable External Products';
					$_field['default']	= app_setting( $_field['key'], 'shop' );
					$_field['text_on']	= strtoupper( lang( 'yes' ) );
					$_field['text_off']	= strtoupper( lang( 'no' ) );

					echo form_field_boolean( $_field );

				?>
			</fieldset>

			<fieldset id="shop-settings-invoice">
				<legend>Invoicing</legend>
				<p>
					These details will be visible on invoices and email receipts.
				</p>
				<?php

					//	Company Name
					$_field					= array();
					$_field['key']			= 'invoice_company';
					$_field['label']		= 'Company Name';
					$_field['default']		= app_setting( $_field['key'], 'shop' );
					$_field['placeholder']	= 'The registered company name.';

					echo form_field( $_field );

					// --------------------------------------------------------------------------

					//	Address
					$_field					= array();
					$_field['key']			= 'invoice_address';
					$_field['label']		= 'Company Address';
					$_field['type']			= 'textarea';
					$_field['default']		= app_setting( $_field['key'], 'shop' );
					$_field['placeholder']	= 'The address to show on the invoice.';

					echo form_field( $_field );

					// --------------------------------------------------------------------------

					//	VAT Number
					$_field					= array();
					$_field['key']			= 'invoice_vat_no';
					$_field['label']		= 'VAT Number';
					$_field['default']		= app_setting( $_field['key'], 'shop' );
					$_field['placeholder']	= 'Your VAT number, if any.';

					echo form_field( $_field );

					// --------------------------------------------------------------------------

					//	Company Number
					$_field					= array();
					$_field['key']			= 'invoice_company_no';
					$_field['label']		= 'Company Number';
					$_field['default']		= app_setting( $_field['key'], 'shop' );
					$_field['placeholder']	= 'Your company number.';

					echo form_field( $_field );

					// --------------------------------------------------------------------------

					//	Invoice Footer
					$_field					= array();
					$_field['key']			= 'invoice_footer';
					$_field['label']		= 'Footer Text';
					$_field['type']			= 'textarea';
					$_field['default']		= app_setting( $_field['key'], 'shop' );
					$_field['placeholder']	= 'Any text to include on the footer of each invoice.';
					$_field['tip']			= 'Use this space to include shop specific information such as your returns policy.';

					echo form_field( $_field );

				?>
			</fieldset>

			<fieldset id="shop-settings-warehouse-collection">
				<legend>Warehouse Collection</legend>
				<p>
					If you'd like customers to be able to collect items from your warehouse or store, then enable it below and provide collection details.
				</p>
				<?php

					$_field					= array();
					$_field['key']			= 'warehouse_collection_enabled';
					$_field['label']		= 'Enabled';
					$_field['default']		= app_setting( $_field['key'], 'shop' );

					echo form_field_boolean( $_field );

					// --------------------------------------------------------------------------

					if ( $this->input->post( $_field['key'] ) || app_setting( $_field['key'], 'shop' ) ) :

						$_style = '';

					else :

						$_style = 'style="display:none;"';

					endif;

					echo '<div id="warehouse-collection-address" ' . $_style . '>';

						$_field					= array();
						$_field['key']			= 'warehouse_addr_addressee';
						$_field['label']		= 'Addressee';
						$_field['default']		= app_setting( $_field['key'], 'shop' );
						$_field['placeholder']	= 'The person or department responsible for collection items.';

						echo form_field( $_field );

						// --------------------------------------------------------------------------

						$_field					= array();
						$_field['key']			= 'warehouse_addr_line1';
						$_field['label']		= 'Address Line 1';
						$_field['default']		= app_setting( $_field['key'], 'shop' );
						$_field['placeholder']	= 'The first line of the warehouse\'s address';

						echo form_field( $_field );

						// --------------------------------------------------------------------------

						$_field					= array();
						$_field['key']			= 'warehouse_addr_line2';
						$_field['label']		= 'Address Line 2';
						$_field['default']		= app_setting( $_field['key'], 'shop' );
						$_field['placeholder']	= 'The second line of the warehouse\'s address';

						echo form_field( $_field );

						// --------------------------------------------------------------------------

						$_field					= array();
						$_field['key']			= 'warehouse_addr_town';
						$_field['label']		= 'Address Town';
						$_field['default']		= app_setting( $_field['key'], 'shop' );
						$_field['placeholder']	= 'The town line of the warehouse\'s address';

						echo form_field( $_field );

						// --------------------------------------------------------------------------

						$_field					= array();
						$_field['key']			= 'warehouse_addr_postcode';
						$_field['label']		= 'Address Postcode';
						$_field['default']		= app_setting( $_field['key'], 'shop' );
						$_field['placeholder']	= 'The postcode line of the warehouse\'s address';

						echo form_field( $_field );

						// --------------------------------------------------------------------------

						$_field					= array();
						$_field['key']			= 'warehouse_addr_state';
						$_field['label']		= 'Address State';
						$_field['default']		= app_setting( $_field['key'], 'shop' );
						$_field['placeholder']	= 'The state line of the warehouse\'s address, if applicable';

						echo form_field( $_field );

						// --------------------------------------------------------------------------

						$_field					= array();
						$_field['key']			= 'warehouse_addr_country';
						$_field['label']		= 'Address Country';
						$_field['default']		= app_setting( $_field['key'], 'shop' );
						$_field['placeholder']	= 'The country line of the warehouse\'s address';

						echo form_field( $_field );

						// --------------------------------------------------------------------------

						$_field					= array();
						$_field['key']			= 'warehouse_collection_delivery_enquiry';
						$_field['label']		= 'Enable Delivery Enquiry';
						$_field['default']		= app_setting( $_field['key'], 'shop' );
						$_field['tip']			= 'For items which are &quot;collect only&quot;, enable a button which allows the user to submit a delivery enquiry.';

						echo form_field_boolean( $_field );

					echo '</div>';

				?>
			</fieldset>

			<fieldset id="shop-settings-misc">
				<legend>Miscellaneous</legend>
				<?php

					//	Brand Listing
					$_field					= array();
					$_field['key']			= 'page_brand_listing';
					$_field['label']		= 'Brand Listing Page';
					$_field['default']		= app_setting( $_field['key'], 'shop' );

					echo form_field_boolean( $_field, 'The page shown when the brand URL is used, but no slug is specified. Renders all the populated brands and their SEO data.' );

					// --------------------------------------------------------------------------

					//	Category Listing
					$_field					= array();
					$_field['key']			= 'page_category_listing';
					$_field['label']		= 'Category Listing Page';
					$_field['default']		= app_setting( $_field['key'], 'shop' );

					echo form_field_boolean( $_field, 'The page shown when the category URL is used, but no slug is specified. Renders all the populated categories and their SEO data.' );

					// --------------------------------------------------------------------------

					//	Collection Listing
					$_field					= array();
					$_field['key']			= 'page_collection_listing';
					$_field['label']		= 'Collection Listing Page';
					$_field['default']		= app_setting( $_field['key'], 'shop' );

					echo form_field_boolean( $_field, 'The page shown when the collection URL is used, but no slug is specified. Renders all the active collections and their SEO data.' );

					// --------------------------------------------------------------------------

					//	Range Listing
					$_field					= array();
					$_field['key']			= 'page_range_listing';
					$_field['label']		= 'Range Listing Page';
					$_field['default']		= app_setting( $_field['key'], 'shop' );

					echo form_field_boolean( $_field, 'The page shown when the range URL is used, but no slug is specified. Renders all the active ranges and their SEO data.' );

					// --------------------------------------------------------------------------

					//	Sale Listing
					$_field					= array();
					$_field['key']			= 'page_sale_listing';
					$_field['label']		= 'Sale Listing Page';
					$_field['default']		= app_setting( $_field['key'], 'shop' );

					echo form_field_boolean( $_field, 'The page shown when the sale URL is used, but no slug is specified. Renders all the active sales and their SEO data.' );

					// --------------------------------------------------------------------------

					//	Tag Listing
					$_field					= array();
					$_field['key']			= 'page_tag_listing';
					$_field['label']		= 'Tag Listing Page';
					$_field['default']		= app_setting( $_field['key'], 'shop' );

					echo form_field_boolean( $_field, 'The page shown when the tag URL is used, but no slug is specified. Renders all the populated tags and their SEO data.' );

				?>
			</fieldset>
			<p style="margin-top:1em;margin-bottom:0;">
				<?=form_submit( 'submit', lang( 'action_save_changes' ), 'class="awesome" style="margin-bottom:0;"' )?>
			</p>
			<?=form_close()?>
		</div>

		<?php $_display = $this->input->post( 'update' ) == 'browse' ? 'active' : ''?>
		<div id="tab-browse" class="tab page <?=$_display?> browse">
			<?=form_open( NULL, 'style="margin-bottom:0;"' )?>
			<?=form_hidden( 'update', 'browse' )?>
			<p>
				Configure the default browsing experience for your customers.
			</p>
			<hr />
			<fieldset id="shop-browsing-tweaks">
				<legend>Browsing Tweaks</legend>
				<?php

					$_field					= array();
					$_field['key']		= 'expand_variants';
					$_field['label']	= 'Expand Variants';
					$_field['default']	= app_setting( $_field['key'], 'shop' );
					$_field['tip']		= 'Expand product variants so that each variant is seemingly an individual product when browsing.';

					echo form_field_boolean( $_field );

				?>
			</fieldset>

			<fieldset id="shop-sorting-tweaks">
				<legend>Sorting Defaults</legend>
				<?php

					/*
					 * Changing these? Then make sure you update the skins etc too.
					 * Probably would be a good TODO to abstract these into a model/config file somewhere.
					 */

					$_field				= array();
					$_field['key']		= 'default_product_per_page';
					$_field['label']	= 'Products per Page';
					$_field['class']	= 'select2';
					$_field['default']	= app_setting( $_field['key'], 'shop' );

					$_options			= array();
					$_options['20']		= '10';
					$_options['40']		= '40';
					$_options['80']		= '80';
					$_options['100']	= '100';
					$_options['all']	= 'All';

					echo form_field_dropdown( $_field, $_options );

					// --------------------------------------------------------------------------

					$_field				= array();
					$_field['key']		= 'default_product_sort';
					$_field['label']	= 'Product Sorting';
					$_field['class']	= 'select2';
					$_field['default']	= app_setting( $_field['key'], 'shop' );

					$_options					= array();
					$_options['recent']			= 'Recently Added';
					$_options['price-low-high']	= 'Price: Low to High';
					$_options['price-high-low']	= 'Price: High to Low';
					$_options['a-z']			= 'A to Z';

					echo form_field_dropdown( $_field, $_options );

				?>
			</fieldset>
			<p style="margin-top:1em;margin-bottom:0;">
				<?=form_submit( 'submit', lang( 'action_save_changes' ), 'class="awesome" style="margin-bottom:0;"' )?>
			</p>
			<?=form_close()?>
		</div>

		<?php $_display = $this->input->post( 'update' ) == 'skin' ? 'active' : ''?>
		<div id="tab-skin" class="tab page <?=$_display?> skin">
			<?=form_open( NULL, 'style="margin-bottom:0;"' )?>
			<?=form_hidden( 'update', 'skin' )?>

			<ul class="tabs" data-tabgroup="skins">
				<li class="tab active">
					<a href="#" data-tab="tab-skin-foh">Front of House</a>
				</li>
				<li class="tab">
					<a href="#" data-tab="tab-skin-checkout">Checkout</a>
				</li>
			</ul>

			<section class="tabs pages skins">
				<div id="tab-skin-foh" class="tab page active clearfix">
					<p class="system-alert notice">
						The "Front of House" skin is responsible for the user's experience whilst browsing your store.
					</p>
					<?php

						if ( $skins_front ) :

							echo '<ul class="skins">';
							foreach ( $skins_front as $skin ) :

								$_name			= ! empty( $skin->name ) ? $skin->name : 'Untitled';
								$_description	= ! empty( $skin->description ) ? $skin->description : '';

								if ( file_exists( $skin->path . 'icon.png' ) ) :

									$_icon = $skin->url . 'icon.png';

								elseif ( file_exists( $skin->path . 'icon.jpg' ) ) :

									$_icon = $skin->url . 'icon.jpg';

								elseif ( file_exists( $skin->path . 'icon.gif' ) ) :

									$_icon = $skin->url . 'icon.gif';

								else :

									$_icon = NAILS_ASSETS_URL . 'img/admin/modules/settings/shop-skin-no-icon.png';

								endif;

								$_selected	= $skin->slug == $skin_front_selected ? TRUE : FALSE;
								$_class		= $_selected ? 'selected' : '';

								echo '<li class="skin ' . $_class . '" rel="tipsy" title="' . htmlentities( $_description, ENT_QUOTES ) . '">';
									echo '<div class="icon">' . img( $_icon ) . '</div>';
									echo '<div class="name">';
										echo $_name;
										echo '<span class="fa fa-check-circle"></span>';
									echo '</div>';
									echo form_radio( 'skin_front', $skin->slug, $_selected );
								echo '</li>';

							endforeach;
							echo '</ul>';

						else :

							echo '<p class="system-alert error">';
								echo '<strong>Error:</strong> ';
								echo 'I\'m sorry, but I couldn\'t find any front of house skins to use. This is a configuration error and should be raised with the developer.';
							echo '</p>';

						endif;

					?>
				</div>
				<div id="tab-skin-checkout" class="tab page clearfix">
					<p class="system-alert notice">
						The "Checkout" Skin is responsible for the user's basket and checkout experience.
					</p>
					<?php

						if ( $skins_checkout ) :

							echo '<ul class="skins">';
							foreach ( $skins_checkout as $skin ) :

								$_name			= ! empty( $skin->name ) ? $skin->name : 'Untitled';
								$_description	= ! empty( $skin->description ) ? $skin->description : '';

								if ( file_exists( $skin->path . 'icon.png' ) ) :

									$_icon = $skin->url . 'icon.png';

								elseif ( file_exists( $skin->path . 'icon.jpg' ) ) :

									$_icon = $skin->url . 'icon.jpg';

								elseif ( file_exists( $skin->path . 'icon.gif' ) ) :

									$_icon = $skin->url . 'icon.gif';

								else :

									$_icon = NAILS_ASSETS_URL . 'img/admin/modules/settings/shop-skin-no-icon.png';

								endif;

								$_selected	= $skin->slug == $skin_checkout_selected ? TRUE : FALSE;
								$_class		= $_selected ? 'selected' : '';

								echo '<li class="skin ' . $_class . '" rel="tipsy" title="' . htmlentities( $_description, ENT_QUOTES ) . '">';
									echo '<div class="icon">' . img( $_icon ) . '</div>';
									echo '<div class="name">';
										echo $_name;
										echo '<span class="fa fa-check-circle"></span>';
									echo '</div>';
									echo form_radio( 'skin_checkout', $skin->slug, $_selected );
								echo '</li>';

							endforeach;
							echo '</ul>';

						else :

							echo '<p class="system-alert error">';
								echo '<strong>Error:</strong> ';
								echo 'I\'m sorry, but I couldn\'t find any checkout skins to use. This is a configuration error and should be raised with the developer.';
							echo '</p>';

						endif;

					?>
				</div>
			</section>
			<p>
				<?=form_submit( 'submit', lang( 'action_save_changes' ), 'class="awesome" style="margin-bottom:0;"' )?>
			</p>
			<?=form_close()?>
		</div>

		<?php $_display = $this->input->post( 'update' ) == 'skin_config' ? 'active' : ''?>
		<div id="tab-skin-config" class="tab page <?=$_display?> skin-config">

			<ul class="tabs" data-tabgroup="skins-config">
				<li class="tab active">
					<a href="#" data-tab="tab-skin-config-foh">Front of House</a>
				</li>
				<li class="tab">
					<a href="#" data-tab="tab-skin-config-checkout">Checkout</a>
				</li>
			</ul>

			<?php

				echo form_open( NULL, 'style="margin-bottom:0;"' );
				echo form_hidden( 'update', 'skin_config' );

			?>
			<section class="tabs pages skins-config">
			<?php

				echo '<div id="tab-skin-config-foh" class="tab page active">';
				if ( ! empty( $skin_front_current ) ) :

					if ( ! empty( $skin_front_current->settings ) ) :

						echo '<p class="system-alert notice">';
							echo 'You are configuring settings for the <strong>' . $skin_front_current->name . '</strong> "Front of House" skin.';
						echo '</p>';

						echo '<div class="fieldset">';

						foreach ( $skin_front_current->settings as $setting ) :

							$_field					= array();
							$_field['key']			= ! empty( $setting->key ) ? 'skin_config[' . $skin_front_current->slug . '][' . $setting->key . ']' : '';
							$_field['label']		= ! empty( $setting->label ) ? $setting->label : '';
							$_field['placeholder']	= ! empty( $setting->placeholder ) ? $setting->placeholder : '';
							$_field['tip']			= ! empty( $setting->tip ) ? $setting->tip : '';
							$_field['type']			= ! empty( $setting->type ) ? $setting->type : '';

							if ( empty( $_field['key'] ) ) :

								continue;

							else :

								$_field['default']	= app_setting( $setting->key, 'shop-' . $skin_front_current->slug );

							endif;

							switch ( $_field['type'] ) :

								case 'bool' :
								case 'boolean' :

									echo form_field_boolean( $_field );

								break;

								case 'dropdown' :

									if ( ! empty( $setting->options ) && is_array( $setting->options ) ) :

										$_options = array();
										$_field['class'] = 'select2';

										foreach ( $setting->options as $option ) :

											if ( isset( $option->value ) ) :

												$_value = $option->value;

											else :

												$_value = NULL;

											endif;

											if ( isset( $option->label ) ) :

												$_label = $option->label;

											else :

												$_label = NULL;

											endif;

											$_options[$_value] = $_label;

										endforeach;

										echo form_field_dropdown( $_field, $_options );

									endif;

								break;

								default :

									echo form_field( $_field );

								break;

							endswitch;

						endforeach;

						echo '</div>';

					else :

						echo '<p class="system-alert message">';
							echo '<strong>Sorry,</strong> no configurable settings for the <strong>' . $skin_front_current->name . '</strong> "Front of House" skin.';
						echo '</p>';

					endif;

				else :

					echo '<p class="system-alert message">';
						echo '<strong>Sorry,</strong> no configurable settings for this skin.';
					echo '</p>';

				endif;
				echo '</div>';

				echo '<div id="tab-skin-config-checkout" class="tab page">';
				if ( ! empty( $skin_checkout_current ) ) :

					if ( ! empty( $skin_checkout_current->settings ) ) :

						echo '<p class="system-alert notice">';
							echo 'You are configuring settings for the <strong>' . $skin_checkout_current->name . '</strong> "Checkout" skin.';
						echo '</p>';

						echo '<div class="fieldset">';

						foreach ( $skin_checkout_current->settings as $setting ) :

							$_field					= array();
							$_field['key']			= ! empty( $setting->key ) ? 'skin_config[' . $skin_checkout_current->slug . '][' . $setting->key . ']' : '';
							$_field['label']		= ! empty( $setting->label ) ? $setting->label : '';
							$_field['placeholder']	= ! empty( $setting->placeholder ) ? $setting->placeholder : '';
							$_field['tip']			= ! empty( $setting->tip ) ? $setting->tip : '';
							$_field['type']			= ! empty( $setting->type ) ? $setting->type : '';

							if ( empty( $_field['key'] ) ) :

								continue;

							else :

								$_field['default']	= app_setting( $setting->key, 'shop-' . $skin_checkout_current->slug );

							endif;

							switch ( $_field['type'] ) :

								case 'bool' :
								case 'boolean' :

									echo form_field_boolean( $_field );

								break;

								case 'dropdown' :

									if ( ! empty( $setting->options ) && is_array( $setting->options ) ) :

										$_options = array();
										$_field['class'] = 'select2';

										foreach ( $setting->options as $option ) :

											if ( isset( $option->value ) ) :

												$_value = $option->value;

											else :

												$_value = NULL;

											endif;

											if ( isset( $option->label ) ) :

												$_label = $option->label;

											else :

												$_label = NULL;

											endif;

											$_options[$_value] = $_label;

										endforeach;

										echo form_field_dropdown( $_field, $_options );

									endif;

								break;

								default :

									echo form_field( $_field );

								break;

							endswitch;

						endforeach;

						echo '</div>';

					else :

						echo '<p class="system-alert message">';
							echo '<strong>Sorry,</strong> no configurable settings for the <Strong>' . $skin_checkout_current->name . '</strong> "Checkout" skin.';
						echo '</p>';

					endif;

				else :

					echo '<p class="system-alert message">';
						echo '<strong>Sorry,</strong> no configurable settings for this skin.';
					echo '</p>';

				endif;
				echo '</div>';

			?>
			</section>
			<?php

				echo '<p style="margin-top:1em;margin-bottom:0;">';
					echo form_submit( 'submit', lang( 'action_save_changes' ), 'class="awesome" style="margin-bottom:0;"' );
				echo '</p>';
				echo form_close();

			?>
		</div>

		<?php $_display = $this->input->post( 'update' ) == 'payment_gateway' ? 'active' : ''?>
		<div id="tab-payment-gateway" class="tab page <?=$_display?> payment-gateway">
			<?=form_open( NULL, 'style="margin-bottom:0;"' )?>
			<?=form_hidden( 'update', 'payment_gateway' )?>
			<p>
				Set Payment Gateway credentials.
			</p>
			<hr />
			<?php

				if ( ! empty( $payment_gateways ) ) :

					echo '<table id="payment-gateways">';
						echo '<thead class="payment-gateways">';
							echo '<tr>';
								echo '<th class="enabled">Enabled</th>';
								echo '<th class="label">Label</th>';
								echo '<th class="configure">Configure</th>';
							echo '</tr>';
						echo '</thead>';
						echo '<tbody>';

						$_enabled_payment_gateways = set_value( 'enabled_payment_gateways', app_setting( 'enabled_payment_gateways', 'shop' ) );
						$_enabled_payment_gateways = array_filter( (array) $_enabled_payment_gateways );

						foreach ( $payment_gateways as $slug ) :

							$_enabled = array_search( $slug, $_enabled_payment_gateways ) !== FALSE ? TRUE : FALSE;

							echo '<tr>';
								echo '<td class="enabled">';
									echo '<div class="toggle toggle-modern"></div>';
									echo form_checkbox( 'enabled_payment_gateways[]', $slug, $_enabled );
								echo '</td>';
								echo '<td class="label">';
									echo str_replace( '_', ' ', $slug );
								echo '</td>';
								echo '<td class="configure">';
									echo anchor( 'admin/settings/shop_pg/' . $slug, 'Configure', 'data-fancybox-type="iframe" class="fancybox awesome small"' );
								echo '</td>';
							echo '</tr>';

						endforeach;

						echo '<tbody>';
					echo '</table>';
					echo '<hr />';

				else :

					echo '<p class="system-alert error">';
						echo '<strong>No payment gateways are available.</strong>';
						echo '<br />I could not find any payment gateways. Please contact the developers on ' . mailto( APP_DEVELOPER_EMAIL ) . ' for assistance.';
					echo '</p>';

				endif;

			?>
			<p style="margin-top:1em;margin-bottom:0;">
				<?=form_submit( 'submit', lang( 'action_save_changes' ), 'class="awesome" style="margin-bottom:0;"' )?>
			</p>
			<?=form_close()?>
		</div>

		<?php $_display = $this->input->post( 'update' ) == 'currencies' ? 'active' : ''?>
		<div id="tab-currencies" class="tab page <?=$_display?> currencies">
			<?=form_open( NULL, 'style="margin-bottom:0;"' )?>
			<?=form_hidden( 'update', 'currencies' )?>
			<p>
				Configure supported currencies.
			</p>
			<hr />
			<fieldset id="shop-currencies-base">
				<legend>Base Currency</legend>
				<p>
					The base currency is the default currency of the shop. When you create a new product and define it's
					price, you are doing so in the base currency. You are free to change this but it will be reflected
					across the entire store, <em>change with <strong>extreme</strong> caution</em>.
				</p>
				<p>
				<?php

					//	Base Currency
					$_field					= array();
					$_field['key']			= 'base_currency';
					$_field['label']		= 'Base Currency';
					$_field['default']		= app_setting( $_field['key'], 'shop' );

					$_currencies = array();

					foreach ( $currencies as $c ) :

						$_currencies[$c->code] = $c->code . ' - ' . $c->label;

					endforeach;

					echo form_dropdown( $_field['key'], $_currencies, set_value( $_field['key'], $_field['default'] ), 'class="select2"' );

				?>
				</p>
			</fieldset>
			<fieldset id="shop-currencies-base">
				<legend>Additional Supported Currencies</legend>
				<p>
					Define which currencies you wish to support in your store in addition to the base currency.
				</p>
				<p class="system-alert message">
					<strong>Important:</strong> Not all payment gateways support all currencies and some must be configured to
					support a particular currency. Additional costs may apply, please choose additional currencies carefully.
				</p>
				<p>
				<?php

					$_default = set_value( 'additional_currencies', app_setting( 'additional_currencies', 'shop' ) );
					$_default = array_filter( (array) $_default );

					echo '<select name="additional_currencies[]" multiple="multiple" class="select2">';
					foreach ( $currencies as $currency ) :

						$_selected = array_search( $currency->code, $_default ) !== FALSE ? 'selected="selected"' : '';

						echo '<option value="'. $currency->code . '" ' . $_selected . '>' . $currency->code . ' - ' . $currency->label . '</option>';

					endforeach;
					echo '</select>';

				?>
				</p>
				<hr />
				<p class="system-alert message">
					<strong>Important:</strong> If you wish to support multiple currencies you must also provide an
					App ID for the <a href="https://openexchangerates.org" target="_blank">Open Exchange Rates</a>
					service. The system uses this service to calculate exchange rates for all supported currencies.
					<br />
					Find out more, and create your App, at <a href="https://openexchangerates.org" target="_blank">Open Exchange Rates</a>.
				</p>
				<?php

					$_field					= array();
					$_field['key']			= 'openexchangerates_app_id';
					$_field['label']		= 'Open Exchange Rates App ID';
					$_field['default']		= app_setting( 'openexchangerates_app_id', 'shop' );
					$_field['placeholder']	= 'Set the Open exchange Rate App ID';

					echo form_field( $_field );

				?>
			</fieldset>
			<p style="margin-top:1em;margin-bottom:0;">
				<?=form_submit( 'submit', lang( 'action_save_changes' ), 'class="awesome" style="margin-bottom:0;"' )?>
			</p>
			<?=form_close()?>
		</div>

		<?php $_display = $this->input->post( 'update' ) == 'shipping' ? 'active' : ''?>
		<div id="tab-shipping" class="tab page <?=$_display?> shipping">
			<?=form_open( NULL, 'style="margin-bottom:0;"' )?>
			<?=form_hidden( 'update', 'shipping' )?>
			<?php

			/*
			<fieldset id="shop-settings-shipping-domicile">
				<legend>Domicile</legend>
				<p>
					Where is your shop based? The domicile will be used to determine when to
					use international postage rates.
				</p>
				<?php

					$_field					= array();
					$_field['key']			= 'domicile';
					$_field['default']		= app_setting( $_field['key'], 'shop' ) ? app_setting( $_field['key'], 'shop' ) : 'GB';

					echo form_dropdown( $_field['key'], $countries_flat, set_value( $_field['key'], $_field['default'] ), 'class="select2"' );

				?>
			</fieldset>
			<fieldset id="shop-settings-shipping-countries">
				<legend>Ship To</legend>
				<p>
					Where you're willing to ship to. You can be as granular as individual countries,
					or you can choose entire continents. Your domicile will always be a shippable location.
				</p>
				<p>
					<strong>Continents</strong>
				</p>
				<p>
				<?php

					$_default = set_value( 'ship_to_continents', app_setting( 'ship_to_continents', 'shop' ) );
					$_default = array_filter( (array) $_default );

					echo '<select name="ship_to_continents[]" multiple="multiple" class="select2">';
						foreach ( $continents_flat as $key => $label ) :

							$_selected = array_search( $key, $_default ) !== FALSE ? ' selected="selected"' : '';

							echo '<option value="'. $key . '"' . $_selected . '>' . $label . '</option>';

						endforeach;
					echo '</select>';

				?>
				</p>
				<p>
					<strong>Countries</strong>
				</p>
				<p>
				<?php

					$_default = set_value( 'ship_to_countries', app_setting( 'ship_to_countries', 'shop' ) );
					$_default = array_filter( (array) $_default );

					echo '<select name="ship_to_countries[]" multiple="multiple" class="select2">';
						foreach ( $countries_flat as $key => $label ) :

							$_selected = array_search( $key, $_default ) !== FALSE ? ' selected="selected"' : '';

							echo '<option value="'. $key . '"' . $_selected . '>' . $label . '</option>';

						endforeach;
					echo '</select>';

				?>
				</p>
				<p>
					<strong>Exclude</strong> - Exclude certain countries, regardless if they fall under any of the options above.
				</p>
				<p>
				<?php

					$_default = set_value( 'ship_to_exclude', app_setting( 'ship_to_exclude', 'shop' ) );
					$_default = array_filter( (array) $_default );

					echo '<select name="ship_to_exclude[]" multiple="multiple" class="select2">';
						foreach ( $countries_flat as $key => $label ) :

							$_selected = array_search( $key, $_default ) !== FALSE ? ' selected="selected"' : '';

							echo '<option value="'. $key . '"' . $_selected . '>' . $label . '</option>';

						endforeach;
					echo '</select>';

				?>
				</p>
			</fieldset>

			<hr />

			*/

				if ( ! empty( $shipping_drivers ) ) :

					echo '<table id="shipping-modules">';
						echo '<thead class="shipping-modules">';
							echo '<tr>';
								echo '<th class="selected">Selected</th>';
								echo '<th class="label">Label</th>';
								echo '<th class="configure">Configure</th>';
							echo '</tr>';
						echo '</thead>';
						echo '<tbody>';

						$_enabled_shipping_driver = set_value( 'enabled_shipping_driver', app_setting( 'enabled_shipping_driver', 'shop' ) );

						foreach ( $shipping_drivers as $driver ) :

							$_name			= ! empty( $driver->name ) ? $driver->name : 'Untitled';
							$_description	= ! empty( $driver->description ) ? $driver->description : '';
							$_enabled		= $driver->slug == $_enabled_shipping_driver ? TRUE : FALSE;

							echo '<tr>';
								echo '<td class="selected">';
									echo form_radio( 'enabled_shipping_driver', $driver->slug, $_enabled );
								echo '</td>';
								echo '<td class="label">';
									echo $_name;
									echo $_description ? '<small>' . $_description . '</small>' : '';
								echo '</td>';
								echo '<td class="configure">';
									echo ! empty( $driver->configurable ) ? anchor( 'admin/settings/shop_sd?driver=' . $driver->slug, 'Configure', 'data-fancybox-type="iframe" class="fancybox awesome small"' ) : '';
								echo '</td>';
							echo '</tr>';

						endforeach;

						echo '<tbody>';
					echo '</table>';
					echo '<hr />';

				else :

					echo '<p class="system-alert error">';
						echo '<strong>No shipping drivers are available.</strong>';
						echo '<br />I could not find any shipping drivers. Please contact the developers on ' . mailto( APP_DEVELOPER_EMAIL ) . ' for assistance.';
					echo '</p>';

				endif;

			?>
			<p style="margin-top:1em;margin-bottom:0;">
				<?=form_submit( 'submit', lang( 'action_save_changes' ), 'class="awesome" style="margin-bottom:0;"' )?>
			</p>
			<?=form_close()?>
		</div>
	</section>
</div>