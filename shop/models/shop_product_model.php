<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:			shop_product_model.php
 *
 * Description:		This model handles everything to do with products
 *
 **/

/**
 * OVERLOADING NAILS' MODELS
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

class NAILS_Shop_product_model extends NAILS_Model
{
	protected $_table;


	// --------------------------------------------------------------------------


	/**
	 * Model constructor
	 *
	 * @access public
	 * @param none
	 * @return void
	 **/
	public function __construct()
	{
		parent::__construct();

		// --------------------------------------------------------------------------

		$this->_table					= NAILS_DB_PREFIX . 'shop_product';
		$this->_table_prefix			= 'p';

		$this->_table_attribute						= NAILS_DB_PREFIX . 'shop_product_attribute';
		$this->_table_brand							= NAILS_DB_PREFIX . 'shop_product_brand';
		$this->_table_category						= NAILS_DB_PREFIX . 'shop_product_category';
		$this->_table_collection					= NAILS_DB_PREFIX . 'shop_product_collection';
		$this->_table_gallery						= NAILS_DB_PREFIX . 'shop_product_gallery';
		$this->_table_range							= NAILS_DB_PREFIX . 'shop_product_range';
		$this->_table_sale							= NAILS_DB_PREFIX . 'shop_sale_product';
		$this->_table_tag							= NAILS_DB_PREFIX . 'shop_product_tag';
		$this->_table_variation						= NAILS_DB_PREFIX . 'shop_product_variation';
		$this->_table_variation_gallery				= NAILS_DB_PREFIX . 'shop_product_variation_gallery';
		$this->_table_variation_product_type_meta	= NAILS_DB_PREFIX . 'shop_product_variation_product_type_meta';
		$this->_table_variation_price				= NAILS_DB_PREFIX . 'shop_product_variation_price';
		$this->_table_type							= NAILS_DB_PREFIX . 'shop_product_type';
		$this->_table_tax_rate						= NAILS_DB_PREFIX . 'shop_tax_rate';

		// --------------------------------------------------------------------------

		$this->_destructive_delete = FALSE;

		// --------------------------------------------------------------------------

		//	Shop's base URL
		$this->_shop_url = app_setting( 'url', 'shop' ) ? app_setting( 'url', 'shop' ) : 'shop/';
	}


	// --------------------------------------------------------------------------


	/**
	 * Creates a new object
	 *
	 * @access public
	 * @param array $data The data to create the object with
	 * @param bool $return_obj Whether to return just the new ID or the full object
	 * @return mixed
	 **/
	public function create( $data = array(), $return_obj = FALSE )
	{
		//	Do all we need to do with the incoming data
		$data = $this->_create_update_prep_data( $data );

		if ( ! $data ) :

			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Execute
		$_id = $this->_create_update_execute( $data );

		//	Wrap it all up
		if ( $_id ) :

			if ( $return_obj ) :

				return $this->get_by_id( $_id );

			else :

				return $_id;

			endif;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Updates an existing object
	 *
	 * @access public
	 * @param int $id The ID of the object to update
	 * @param array $data The data to update the object with
	 * @return bool
	 **/
	public function update( $id, $data = array() )
	{
		$_current = $this->get_by_id( $id );

		if ( ! $_current ) :

			$this->_set_error( 'Invalid product ID' );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Do all we need to do with the incoming data
		$_data = $this->_create_update_prep_data( $data, $id );

		if ( ! $_data ) :

			return FALSE;

		endif;

		$_data->id = $id;

		// --------------------------------------------------------------------------

		//	Execute
		$_id = $this->_create_update_execute( $_data );

		//	Wrap it all up
		if ( $_id ) :

			return TRUE;

		else :

			return FALSE;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Prepares data, ready for the DB
	 * @param  array $data Raw data to use for the update/create
	 * @param  int   $id   If updating, the ID of the item being updated
	 * @return mixed stdClass on success, FALSE of failure
	 */
	protected function _create_update_prep_data( $data, $id = NULL )
	{
		//	Quick check of incoming data
		$_data = new stdClass();

		if ( empty( $data['label'] ) ) :

			$this->_set_error( 'Label is a required field.' );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Slug
		//	====

		$_data->slug = $this->_generate_slug( $data['label'], '', '', $this->_table, NULL, $id );

		//	Product Info
		//	============

		$_data->type_id = isset( $data['type_id'] ) ? (int) $data['type_id']	: NULL;

		if ( ! $_data->type_id ) :

			$this->_set_error( 'Product type must be defined.' );
			return FALSE;

		endif;

		$_data->label		= isset( $data['label'] )		? trim( $data['label'] )		: NULL;
		$_data->is_active	= isset( $data['is_active'] )	? (bool) $data['is_active']		: FALSE;
		$_data->is_deleted	= isset( $data['is_deleted'] )	? (bool) $data['is_deleted']	: FALSE;
		$_data->brands		= isset( $data['brands'] )		? $data['brands']				: array();
		$_data->categories	= isset( $data['categories'] )	? $data['categories']			: array();
		$_data->tags		= isset( $data['tags'] )		? $data['tags']					: array();

		if ( app_setting( 'enable_external_products', 'shop' ) ) :

			$_data->is_external				= isset( $data['is_external'] )				? (bool) $data['is_external']		: FALSE;
			$_data->external_vendor_label	= isset( $data['external_vendor_label'] )	? $data['external_vendor_label']	: '';
			$_data->external_vendor_url		= isset( $data['external_vendor_url'] )		? $data['external_vendor_url']		: '';

		endif;

		$_data->tax_rate_id	= isset( $data['tax_rate_id'] ) &&	(int) $data['tax_rate_id']	? (int) $data['tax_rate_id']	: NULL;

		// --------------------------------------------------------------------------

		//	Description
		//	===========
		$_data->description	= isset( $data['description'] ) ? $data['description']	: NULL;

		// --------------------------------------------------------------------------

		//	Variants - Loop variants
		//	========================

		if ( ! isset( $data['variation'] ) || ! $data['variation'] ) :

			$this->_set_error( 'At least one variation is required.' );
			return FALSE;

		endif;

		$_data->variation	= array();
		$_product_type		= $this->shop_product_type_model->get_by_id( $_data->type_id );

		if ( ! $_product_type ) :

			$this->_set_error( 'Invalid Product Type' );
			return FALSE;

		else :

			$_data->is_physical = $_product_type->is_physical;

		endif;

		$this->load->model( 'shop/shop_product_type_meta_model' );
		$_product_type_meta = $this->shop_product_type_meta_model->get_by_product_type_id( $_product_type->id );

		$_sku_tracker = array();

		foreach ( $data['variation'] AS $index => $v ) :

			//	Details
			//	-------

			$_data->variation[$index] = new stdClass();

			//	If there's an ID note it down, we'll be using it later as a switch between INSERT and UPDATE
			if ( ! empty( $v['id'] ) ) :

				$_data->variation[$index]->id = $v['id'];

			endif;

			$_data->variation[$index]->label	= isset( $v['label'] )	? $v['label']	: NULL;
			$_data->variation[$index]->sku		= isset( $v['sku'] )	? $v['sku']		: NULL;

			$_sku_tracker[] = $_data->variation[$index]->sku;

			//	Stock
			//	-----
			$_data->variation[$index]->stock_status = isset( $v['stock_status'] ) ? $v['stock_status'] : 'OUT_OF_STOCK';

			switch ( $_data->variation[$index]->stock_status ) :

				case 'IN_STOCK' :

					$_data->variation[$index]->quantity_available	= is_numeric( $v['quantity_available'] ) ? (int) $v['quantity_available'] : NULL;
					$_data->variation[$index]->lead_time			= NULL;

				break;

				case 'TO_ORDER' :

					$_data->variation[$index]->quantity_available	= NULL;
					$_data->variation[$index]->lead_time			= isset( $v['lead_time'] ) ? $v['lead_time'] : NULL;

				break;

				case 'OUT_OF_STOCK' :

					//	Shhh, be vewy qwiet, we're huntin' wabbits.
					$_data->variation[$index]->quantity_available	= NULL;
					$_data->variation[$index]->lead_time			= NULL;

				break;

			endswitch;

			//	Out of Stock Behaviour
			//	----------------------

			$_data->variation[$index]->out_of_stock_behaviour = isset( $v['out_of_stock_behaviour'] ) ? $v['out_of_stock_behaviour'] : 'OUT_OF_STOCK';

			switch ( $_data->variation[$index]->out_of_stock_behaviour ) :

				case 'TO_ORDER' :

					$_data->variation[$index]->out_of_stock_to_order_lead_time = isset( $v['out_of_stock_to_order_lead_time'] ) ? $v['out_of_stock_to_order_lead_time'] : NULL;

				break;

				case 'OUT_OF_STOCK' :

					//	Shhh, be vewy qwiet, we're huntin' wabbits.
					$_data->variation[$index]->out_of_stock_to_order_lead_time = NULL;

				break;

			endswitch;

			//	Meta
			//	----

			$_data->variation[$index]->meta = array();

			//	No need to set variation ID, that will be set later on during execution
			if ( isset( $v['meta'][$_data->type_id] ) ) :

				foreach( $v['meta'][$_data->type_id] AS $field_id => $value ) :

					if ( ! empty( $value ) ) :

						/**
						 * Test to see if this field allows multiple values, if it does then explode
						 * it and create multiple elements, if not, leave as is
						 */

						foreach( $_product_type_meta AS $meta ) :

							if (  $meta->id == $field_id ) :

								$_allow_multiple = TRUE;
								break;

							endif;

						endforeach;

						if ( empty( $_allow_multiple ) ) :

							$_temp					= array();
							$_temp['meta_field_id']	= $field_id;
							$_temp['value']			= $value;
							$_data->variation[$index]->meta[] = $_temp;

						else :

							$_values = explode( ',', $value );
							foreach ( $_values AS $val ) :

								$_temp					= array();
								$_temp['meta_field_id']	= $field_id;
								$_temp['value']			= $val;
								$_data->variation[$index]->meta[] = $_temp;

							endforeach;

						endif;

					endif;

				endforeach;

			endif;

			//	Pricing
			//	-------
			$_data->variation[$index]->pricing = array();

			if ( isset( $v['pricing'] ) ) :

				//	At the very least the base price must be defined
				$_base_price_set = FALSE;
				foreach( $v['pricing'] AS $price_index => $price ) :

					if ( empty( $price['currency'] ) ) :

						$this->_set_error( '"Currency" field is required for all variant prices.' );
						return FALSE;

					endif;

					$_data->variation[$index]->pricing[$price_index]				= new stdClass();
					$_data->variation[$index]->pricing[$price_index]->currency		= $price['currency'];
					$_data->variation[$index]->pricing[$price_index]->price			= ! empty( $price['price'] )		? (float) $price['price']		: NULL;
					$_data->variation[$index]->pricing[$price_index]->sale_price	= ! empty( $price['sale_price'] )	? (float) $price['sale_price']	: NULL;

					if ( $price['currency'] == SHOP_BASE_CURRENCY_CODE ) :

						$_base_price_set = TRUE;

					endif;

				endforeach;

				if ( ! $_base_price_set ) :

					$this->_set_error( 'The ' . SHOP_BASE_CURRENCY_CODE . ' price must be set for all variants.' );
					return FALSE;

				endif;

			endif;

			//	Gallery Associations
			//	--------------------
			$_data->variation[$index]->gallery = array();

			if ( isset( $v['gallery'] ) ) :

				foreach( $v['gallery'] AS $gallery_index => $image ) :

					$this->form_validation->set_rules( 'variation[' . $index . '][gallery][' . $gallery_index . ']',	'',	'xss_clean' );

					if( $image ) :

						$_data->variation[$index]->gallery[] = $image;

					endif;

				endforeach;

			endif;

			//	Shipping
			//	--------

			$_data->variation[$index]->shipping = new stdClass();

			if ( $_product_type->is_physical ) :

				$_data->variation[$index]->shipping->collection_only	= isset( $v['shipping']['collection_only'] ) ? (bool) $v['shipping']['collection_only'] : FALSE;
				$_data->variation[$index]->shipping->driver_data		= isset( $v['shipping']['driver_data'] ) ? $v['shipping']['driver_data'] : NULL;

			else :

				$_data->variation[$index]->shipping->collection_only	= FALSE;
				$_data->variation[$index]->shipping->driver_data		= NULL;

			endif;

		endforeach;

		//	Duplicate SKUs?
		$_sku_tracker	= array_filter( $_sku_tracker );
		$_count			= array_count_values( $_sku_tracker );

		if ( count( $_count ) != count( $_sku_tracker ) ) :

			//	If only one occurance of everything then the count on both
			//	should be the same, if not then it'll vary.

			$this->_set_error( 'All variations which have defined SKUs must be unique.' );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Gallery
		$_data->gallery			= isset( $data['gallery'] )			? $data['gallery']			: array();

		// --------------------------------------------------------------------------

		//	Attributes
		$_data->attributes		= isset( $data['attributes'] )		? $data['attributes']		: array();

		// --------------------------------------------------------------------------

		//	Ranges & Collections
		$_data->ranges			= isset( $data['ranges'] )			? $data['ranges']			: array();
		$_data->collections		= isset( $data['collections'] )		? $data['collections']		: array();

		// --------------------------------------------------------------------------

		//	SEO
		$_data->seo_title		= isset( $data['seo_title'] )		? $data['seo_title']		: NULL;
		$_data->seo_description	= isset( $data['seo_description'] )	? $data['seo_description']	: NULL;
		$_data->seo_keywords	= isset( $data['seo_keywords'] )	? $data['seo_keywords']		: NULL;

		// --------------------------------------------------------------------------

		return $_data;
	}


	// --------------------------------------------------------------------------


	/**
	 * Actually executes the DB Call
	 * @param  stdClass $data The object returned from _create_update_prep_data();
	 * @return mixed    ID (int) on success, FALSE on failure
	 */
	protected function _create_update_execute( $data )
	{
		/**
		 * Fetch the current state of the item if an ID is set
		 * We'll use this later on in the shipping driver section to see what data we're updating
		 */

		if ( ! empty( $data->id ) ) :

			$_current = $this->get_by_id( $data->id );

		else :

			$_current = FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Load dependant models
		$this->load->model( 'shop/shop_shipping_driver_model' );

		// --------------------------------------------------------------------------

		//	Start the transaction, safety first!
		$this->db->trans_begin();
		$_rollback = FALSE;

		//	Add the product
		$this->db->set( 'slug',				$data->slug );
		$this->db->set( 'type_id',			$data->type_id );
		$this->db->set( 'label',			$data->label );
		$this->db->set( 'description',		$data->description );
		$this->db->set( 'seo_title',		$data->seo_title );
		$this->db->set( 'seo_description',	$data->seo_description );
		$this->db->set( 'seo_keywords',		$data->seo_keywords );
		$this->db->set( 'tax_rate_id',		$data->tax_rate_id );
		$this->db->set( 'is_active',		$data->is_active );
		$this->db->set( 'is_deleted',		$data->is_deleted );

		if ( app_setting( 'enable_external_products', 'shop' ) ) :

			$this->db->set( 'is_external',				$data->is_external );
			$this->db->set( 'external_vendor_label',	$data->external_vendor_label );
			$this->db->set( 'external_vendor_url',		$data->external_vendor_url );

		endif;

		if ( empty( $data->id ) ) :

			$this->db->set( 'created',			'NOW()', FALSE );

			if ( $this->user_model->is_logged_in() ) :

				$this->db->set( 'created_by',	active_user( 'id' ) );

			endif;

		endif;

		$this->db->set( 'modified',			'NOW()', FALSE );

		if ( $this->user_model->is_logged_in() ) :

			$this->db->set( 'modified_by',	active_user( 'id' ) );

		endif;

		if ( ! empty( $data->id ) ) :

			$this->db->where( 'id', $data->id );
			$_result = $this->db->update( $this->_table );
			$_action = 'update';

		else :

			$_result = $this->db->insert( $this->_table );
			$_action = 'create';
			$data->id = $this->db->insert_id();

		endif;

		if ( $_result ) :

			//	The following items are all handled, and error, in the [mostly] same way
			//	loopy loop for clarity and consistency.

			$_types = array();

			//					//Items to loop			//Field name		//Plural human		//Table name
			$_types[]	= array( $data->attributes,		'attribute_id',		'attributes',		$this->_table_attribute );
			$_types[]	= array( $data->brands,			'brand_id',			'brands',			$this->_table_brand );
			$_types[]	= array( $data->categories,		'category_id',		'categories',		$this->_table_category );
			$_types[]	= array( $data->collections,	'collection_id',	'collections',		$this->_table_collection );
			$_types[]	= array( $data->gallery,		'object_id',		'gallery items',	$this->_table_gallery );
			$_types[]	= array( $data->ranges,			'range_id',			'ranges',			$this->_table_range );
			$_types[]	= array( $data->tags,			'tag_id',			'tags',				$this->_table_tag );

			foreach ( $_types AS $type ) :

				list( $_items, $_field, $_type, $_table ) = $type;

				//	Clear old items
				$this->db->where( 'product_id', $data->id );
				if ( ! $this->db->delete( $_table ) ) :

					$this->_set_error( 'Failed to clear old product ' . $_type . '.' );
					$_rollback = TRUE;
					break;

				endif;

				$_temp = array();
				switch( $_field ) :

					case 'attribute_id' :

						foreach( $_items AS $item ) :

							$_temp[] = array( 'product_id' => $data->id, 'attribute_id' => $item['attribute_id'], 'value' => $item['value'] );

						endforeach;

					break;

					case 'object_id' :

						$_counter = 0;
						foreach( $_items AS $item_id ) :

							$_temp[] = array( 'product_id' => $data->id, $_field => $item_id, 'order' => $_counter );
							$_counter++;

						endforeach;

					break;

					default :

						foreach( $_items AS $item_id ) :

							$_temp[] = array( 'product_id' => $data->id, $_field => $item_id );

						endforeach;

					break;

				endswitch;

				if ( $_temp ) :

					if ( ! $this->db->insert_batch( $_table, $_temp ) ) :

						$this->_set_error( 'Failed to add product ' . $_type . '.' );
						$_rollback = TRUE;

					endif;

				endif;

			endforeach;


			//	Product Variations
			//	==================

			if ( ! $_rollback ) :

				$_counter = 0;

				//	Keep a note of the variants we deal with, we'll
				//	want to mark any we don't deal with as deleted

				$_variant_id_tracker = array();

				foreach( $data->variation AS $index => $v ) :

					//	Product Variation: Details
					//	==========================

					$this->db->set( 'label',	$v->label );
					$this->db->set( 'sku',		$v->sku );
					$this->db->set( 'order',	$_counter );


					//	Product Variation: Stock Status
					//	===============================

					$this->db->set( 'stock_status',			$v->stock_status );
					$this->db->set( 'quantity_available',	$v->quantity_available );
					$this->db->set( 'lead_time',			$v->lead_time );

					//	Product Variation: Out of Stock Behaviour
					//	=========================================

					$this->db->set( 'out_of_stock_behaviour',			$v->out_of_stock_behaviour );
					$this->db->set( 'out_of_stock_to_order_lead_time',	$v->out_of_stock_to_order_lead_time );


					//	Product Variation: Shipping
					//	===========================

					$this->db->set( 'ship_collection_only',		$v->shipping->collection_only );

					if ( ! empty( $v->id ) ) :

						//	A variation ID exists, find it and update just the specific field.
						foreach( $_current->variations AS $variation ) :

							if ( $variation->id != $v->id ) :

								continue;

							else :

								$_current_driver_data = $variation->shipping->driver_data;
								break;

							endif;

						endforeach;

					endif;

					$_enabled_driver = $this->shop_shipping_driver_model->get_enabled();

					if ( $_enabled_driver ) :

						if ( ! empty( $_current_driver_data ) ) :

							//	Data exists, only update the specific bitty.
							$_current_driver_data[$_enabled_driver->slug] = $v->shipping->driver_data[$_enabled_driver->slug];
							$this->db->set( 'ship_driver_data', serialize( $_current_driver_data ) );

						else :

							//	Nothing exists, use whatever's been passed
							$this->db->set( 'ship_driver_data', serialize( $v->shipping->driver_data ) );

						endif;

					endif;

					// --------------------------------------------------------------------------

					if ( ! empty( $v->id ) ) :

						//	Existing variation, update what's there
						$this->db->where( 'id', $v->id );
						$_result = $this->db->update( $this->_table_variation );
						$_action = 'update';

						$_variant_id_tracker[] = $v->id;

					else :

						//	New variation, add it.
						$this->db->set( 'product_id', $data->id );
						$_result = $this->db->insert( $this->_table_variation );
						$_action = 'create';

						$_variant_id_tracker[] = $this->db->insert_id();

						$v->id = $this->db->insert_id();

					endif;

					if ( $_result ) :

						//	Product Variation: Gallery
						//	==========================

						$this->db->where( 'variation_id', $v->id );
						if ( ! $this->db->delete( $this->_table_variation_gallery ) ) :

							$this->_set_error( 'Failed to clear gallery items for variant with label "' . $v->label . '"' );
							$_rollback = TRUE;

						endif;

						if  (! $_rollback ) :

							$_temp = array();
							foreach( $v->gallery AS $object_id ) :

								$_temp[] = array(
									'variation_id'	=> $v->id,
									'object_id'		=> $object_id
								);

							endforeach;

							if ( $_temp ) :

								if ( ! $this->db->insert_batch( $this->_table_variation_gallery, $_temp ) ) :

									$this->_set_error( 'Failed to update gallery items variant with label "' . $v->label . '"' );
									$_rollback = TRUE;

								endif;

							endif;

						endif;


						//	Product Variation: Meta
						//	=======================

						if ( ! $_rollback ) :

							foreach( $v->meta AS &$meta ) :

								$meta['variation_id'] = $v->id;

							endforeach;

							$this->db->where( 'variation_id', $v->id );

							if ( ! $this->db->delete( $this->_table_variation_product_type_meta ) ) :

								$this->_set_error( 'Failed to clear meta data for variant with label "' . $v->label . '"' );
								$_rollback = TRUE;

							endif;

							if ( ! $_rollback && ! empty( $v->meta ) ) :

								if ( ! $this->db->insert_batch( $this->_table_variation_product_type_meta, $v->meta ) ) :

									$this->_set_error( 'Failed to update meta data for variant with label "' . $v->label . '"' );
									$_rollback = TRUE;

								endif;

							endif;

						endif;


						//	Product Variation: Price
						//	========================

						if ( ! $_rollback ) :

							$this->db->where( 'variation_id', $v->id );
							if ( ! $this->db->delete( $this->_table_variation_price ) ) :

								$this->_set_error( 'Failed to clear price data for variant with label "' . $v->label . '"' );
								$_rollback = TRUE;

							endif;

							if ( ! $_rollback ) :

								foreach( $v->pricing AS &$price ) :

									$price->variation_id = $v->id;

									$price = (array) $price;

								endforeach;

								if ( $v->pricing ) :

									if ( ! $this->db->insert_batch( $this->_table_variation_price, $v->pricing ) ) :

										$this->_set_error( 'Failed to update price data for variant with label "' . $v->label . '"' );
										$_rollback = TRUE;

									endif;

								endif;

							endif;

						endif;

					else :

						$this->_set_error( 'Unable to ' . $_action . ' variation with label "' . $v->label . '".' );
						$_rollback = TRUE;
						break;

					endif;

					$_counter++;

				endforeach;

				//	Mark all untouched variants as deleted
				if ( ! $_rollback ) :

					$this->db->set( 'is_deleted', TRUE );
					$this->db->where( 'product_id', $data->id );
					$this->db->where_not_in( 'id', $_variant_id_tracker );

					if ( ! $this->db->update( $this->_table_variation ) ) :

						$this->_set_error( 'Unable to delete old variations.' );
						$_rollback = TRUE;

					endif;

				endif;

			endif;

		else :

			$this->_set_error( 'Failed to ' . $_action . ' base product.' );
			$_rollback = TRUE;

		endif;


		// --------------------------------------------------------------------------

		//	Wrap it all up
		if ( $this->db->trans_status() === FALSE || $_rollback ) :

			$this->db->trans_rollback();
			return FALSE;

		else :

			$this->db->trans_commit();

			// --------------------------------------------------------------------------

			//	Inform any persons who may have subscribed to a 'keep me informed' notification
			$_variants_available = array();

			$this->db->select( 'id' );
			$this->db->where( 'product_id', $data->id );
			$this->db->where( 'is_deleted', FALSE );
			$this->db->where( 'stock_status', 'IN_STOCK' );
			$this->db->where( '(quantity_available IS NULL OR quantity_available > 0)' );
			$_variants_available_raw = $this->db->get( $this->_table_variation	 )->result();
			$_variants_available = array();

			foreach( $_variants_available_raw AS $v ) :

				$_variants_available[] = $v->id;

			endforeach;

			if ( $_variants_available ) :

				if ( ! $this->load->model_is_loaded( 'shop_inform_product_available_model' ) ) :

					$this->load->model( 'shop/shop_inform_product_available_model' );

				endif;

				$this->shop_inform_product_available_model->inform( $data->id, $_variants_available );

			endif;



			// --------------------------------------------------------------------------

			return $data->id;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Marks a product as deleted
	 *
	 * @access public
	 * @param int $id The ID of the object to delete
	 * @return bool
	 **/
	public function delete( $id )
	{
		return parent::update( $id, array( 'is_deleted' => TRUE ) );
	}


	// --------------------------------------------------------------------------


	/**
	 * Restores a deleted object
	 *
	 * @access public
	 * @param int $id The ID of the object to delete
	 * @return bool
	 **/
	public function restore( $id )
	{
		return parent::update( $id, array( 'is_deleted' => FALSE ) );
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches all products
	 * @access public
	 * @param int $page The page number of the results, if NULL then no pagination
	 * @param int $per_page How many items per page of paginated results
	 * @param mixed $data Any data to pass to _getcount_common()
	 * @param bool $include_deleted If non-destructive delete is enabled then this flag allows you to include deleted items
	 * @param string $_caller Internal flag to pass to _getcount_common(), contains the calling method
	 * @return array
	 **/
	public function get_all( $page = NULL, $per_page = NULL, $data = array(), $include_deleted = FALSE, $_caller = 'GET_ALL' )
	{
	    $this->load->model('shop/shop_category_model');

		$_products = parent::get_all( $page, $per_page, $data, $include_deleted, $_caller );

		foreach ( $_products AS $product ) :

			//	Format
			$this->_format_product_object( $product );

			// --------------------------------------------------------------------------

			//	Fetch associated content

			//	Attributes
			//	==========
			$this->db->select( 'pa.attribute_id id, a.label, pa.value' );
			$this->db->where( 'pa.product_id', $product->id );
			$this->db->join( NAILS_DB_PREFIX . 'shop_attribute a', 'a.id = pa.attribute_id' );
			$product->attributes = $this->db->get( $this->_table_attribute . ' pa' )->result();

			//	Brands
			//	======
			$this->db->select( 'b.id, b.slug, b.label, b.logo_id, b.is_active' );
			$this->db->where( 'pb.product_id', $product->id );
			$this->db->join( NAILS_DB_PREFIX . 'shop_brand b', 'b.id = pb.brand_id' );
			$product->brands = $this->db->get( $this->_table_brand . ' pb' )->result();

			//	Categories
			//	==========
			$this->db->select( 'c.id, c.slug, c.label, c.breadcrumbs' );
			$this->db->where( 'pc.product_id', $product->id );
			$this->db->join( NAILS_DB_PREFIX . 'shop_category c', 'c.id = pc.category_id' );
			$product->categories = $this->db->get( $this->_table_category . ' pc' )->result();
			foreach( $product->categories AS $category ) :

                $category->url = $this->shop_category_model->format_url( $category->slug );

            endforeach;

			//	Collections
			//	===========
			$this->db->select( 'c.id, c.slug, c.label' );
			$this->db->where( 'pc.product_id', $product->id );
			$this->db->join( NAILS_DB_PREFIX . 'shop_collection c', 'c.id = pc.collection_id' );
			$product->collections = $this->db->get( $this->_table_collection . ' pc' )->result();

			//	Gallery
			//	=======
			$this->db->select( 'object_id' );
			$this->db->where( 'product_id', $product->id );
			$this->db->order_by( 'order' );
			$_temp = $this->db->get( $this->_table_gallery )->result();

			$product->gallery = array();
			foreach( $_temp AS $image ) :

				$product->gallery[] = (int) $image->object_id;

			endforeach;

			//	Featured image
			//	==============
			if ( ! empty( $product->gallery[0] ) ) :

				$product->featured_img = $product->gallery[0];

			else :

				$product->featured_img = NULL;

			endif;

			//	Range
			//	=====
			$this->db->select( 'r.id, r.slug, r.label' );
			$this->db->where( 'pr.product_id', $product->id );
			$this->db->join( NAILS_DB_PREFIX . 'shop_range r', 'r.id = pr.range_id' );
			$product->ranges = $this->db->get( $this->_table_range . ' pr' )->result();

			//	Tags
			//	====
			$this->db->select( 't.id, t.slug, t.label' );
			$this->db->where( 'pt.product_id', $product->id );
			$this->db->join( NAILS_DB_PREFIX . 'shop_tag t', 't.id = pt.tag_id' );
			$product->tags = $this->db->get( $this->_table_tag . ' pt' )->result();

			//	Variations
			//	==========
			$this->db->select( 'pv.*' );
			$this->db->where( 'pv.product_id', $product->id );
			if ( empty( $data['include_deleted_variants'] ) ) :

				$this->db->where( 'pv.is_deleted', FALSE );

			endif;
			$this->db->order_by( 'pv.order' );
			$product->variations = $this->db->get( $this->_table_variation . ' pv' )->result();

			foreach( $product->variations AS &$v ) :

				//	Meta
				//	====

				$this->db->select( 'a.id,a.meta_field_id,b.label,a.value,b.allow_multiple' );
				$this->db->join( NAILS_DB_PREFIX . 'shop_product_type_meta_field b', 'a.meta_field_id = b.id' );
				$this->db->where( 'variation_id', $v->id );
				$_meta_raw = $this->db->get( $this->_table_variation_product_type_meta . ' a' )->result();

				//	Merge `allow_multiple` fields into one
				$v->meta = array();
				foreach( $_meta_raw AS $meta ) :

					if ( ! isset( $v->meta[$meta->meta_field_id] ) ) :

						$v->meta[$meta->meta_field_id] = $meta;

					endif;

					if ( $meta->allow_multiple ) :

						if ( ! is_array( $v->meta[$meta->meta_field_id]->value ) ) :

							//	Grab the current value and turn `value` into an array
							$_temp = $v->meta[$meta->meta_field_id]->value;
							$v->meta[$meta->meta_field_id]->value	= array();
							$v->meta[$meta->meta_field_id]->value[]	= $_temp;

						else :

							$v->meta[$meta->meta_field_id]->value[]	= $meta->value;

						endif;

					else :

						//	Overwrite previous entry
						$v->meta[$meta->meta_field_id]->value = $meta->value;

					endif;

				endforeach;


				//	Gallery
				//	=======

				$this->db->where( 'variation_id', $v->id );
				$_temp = $this->db->get( $this->_table_variation_gallery )->result();
				$v->gallery = array();

				foreach( $_temp AS $image ) :

					$v->gallery[] = $image->object_id;

				endforeach;

				if ( ! empty( $v->gallery[0] ) ) :

					$v->featured_img = $v->gallery[0];

				else :

					$v->featured_img = NULL;

				endif;

				//	Raw Price
				//	=========

				$this->db->select( 'pvp.currency, pvp.price, pvp.sale_price' );
				$this->db->where( 'pvp.variation_id', $v->id );
				$_price = $this->db->get( $this->_table_variation_price . ' pvp' )->result();

				$v->price_raw	= new stdClass();
				$v->price		= new stdClass();

				foreach ( $_price AS $price ) :

					$v->price_raw->{$price->currency} = $price;

				endforeach;

				$this->_format_variation_object( $v );

				//	Calculated Price
				//	================

				//	Fields
				$_prototype_fields					= new stdClass();
				$_prototype_fields->value			= 0;
				$_prototype_fields->value_inc_tax	= 0;
				$_prototype_fields->value_ex_tax	= 0;
				$_prototype_fields->value_tax		= 0;

				//	Clone the fields for each price, we gotta use a deep copy 'hack' to avoid references.
				$v->price->price					= new stdClass();
				$v->price->price->base				= unserialize( serialize( $_prototype_fields ) );
				$v->price->price->base_formatted	= unserialize( serialize( $_prototype_fields ) );
				$v->price->price->user				= unserialize( serialize( $_prototype_fields ) );
				$v->price->price->user_formatted	= unserialize( serialize( $_prototype_fields ) );

				//	And an exact clone for the sale price
				$v->price->sale_price = unserialize( serialize( $v->price->price ) );

				$_base_price = isset( $v->price_raw->{SHOP_BASE_CURRENCY_CODE} ) ? $v->price_raw->{SHOP_BASE_CURRENCY_CODE} : NULL;
				$_user_price = isset( $v->price_raw->{SHOP_USER_CURRENCY_CODE} ) ? $v->price_raw->{SHOP_USER_CURRENCY_CODE} : NULL;

				if ( empty( $_base_price )  ) :

					$_subject = 'Product missing price for base currency (' . SHOP_BASE_CURRENCY_CODE . ')';
					$_message = 'Product #' . $product->id . ' does not contain a price for the shop\'s base currency, ' . SHOP_BASE_CURRENCY_CODE . '.';
					show_fatal_error( $_subject, $_message );

				endif;

				if ( empty( $_user_price )  ) :

					$_subject = 'Product missing price for currency (' . SHOP_USER_CURRENCY_CODE . ')';
					$_message = 'Product #' . $product->id . ' does not contain a price for currency, ' . SHOP_USER_CURRENCY_CODE . '.';
					show_fatal_error( $_subject, $_message );

				endif;

				//	Define the base prices first
				$v->price->price->base->value		= $_base_price->price;
				$v->price->sale_price->base->value	= $_base_price->sale_price;

				$v->price->price->base_formatted->value			= $this->shop_currency_model->format_base( $v->price->price->base->value );
				$v->price->sale_price->base_formatted->value	= $this->shop_currency_model->format_base( $v->price->sale_price->base->value );

				// --------------------------------------------------------------------------

				/**
				 * If the user's currency preferences aren't the same as the
				 * base currency then we need to do some conversions
				 */

				if ( SHOP_USER_CURRENCY_CODE != SHOP_BASE_CURRENCY_CODE ) :

					//	Price, first
					if ( empty( $_user_price->price ) ) :

						//	The user's price is empty() so we should automatically calculate it from the base price
						$_price = $this->shop_currency_model->convert_base_to_user( $_base_price->price );

						if ( ! $_price ) :

							show_fatal_error( 'Failed to convert currency', 'Could not convert from ' . SHOP_BASE_CURRENCY_CODE . ' to ' . SHOP_USER_CURRENCY_CODE . '. ' . $this->shop_currency_model->last_error() );

						endif;

					else :

						//	A price has been explicitly set for this currency, so render it as is this
						$_price = $_user_price->price;

					endif;

					//	Formatting not for visual purposes but to get value into the proper format
					$v->price->price->user->value = number_format( $_price, SHOP_USER_CURRENCY_PRECISION, '.', '' );

					// --------------------------------------------------------------------------

					//	Sale price, second
					if ( empty( $_user_price->sale_price ) ) :

						//	The user's sale_price is empty() so we should automatically calculate it from the base price
						$_sale_price = $this->shop_currency_model->convert_base_to_user( $_base_price->sale_price );

						if ( ! $_price ) :

							show_fatal_error( 'Failed to convert currency', 'Could not convert from ' . SHOP_BASE_CURRENCY_CODE . ' to ' . SHOP_USER_CURRENCY_CODE . '. ' . $this->shop_currency_model->last_error() );

						endif;

					else :

						//	A sale_price has been explicitly set for this currency, so render it as is
						$_sale_price = $_user_price->sale_price;

					endif;

					//	Formatting not for visual purposes but to get value into the proper format
					$v->price->sale_price->user->value = number_format( $_sale_price, SHOP_USER_CURRENCY_PRECISION, '.', '' );

				else :

					//	Formatting not for visual purposes but to get value into the proper format
					$v->price->price->user->value		= number_format( $v->price->price->base->value, SHOP_USER_CURRENCY_PRECISION, '.', '' );
					$v->price->sale_price->user->value	= number_format( $v->price->sale_price->base->value, SHOP_USER_CURRENCY_PRECISION, '.', '' );

				endif;

				// --------------------------------------------------------------------------

				//	Tax pricing
				if ( app_setting( 'price_exclude_tax', 'shop' ) ) :

					//	Prices do not include any applicable taxes
					$v->price->price->base->value_ex_tax = $v->price->price->base->value;
					$v->price->price->user->value_ex_tax = $v->price->price->user->value;

					//	Work out the ex-tax price by working out the tax and adding
					if ( ! empty( $product->tax_rate->rate ) ) :

						$v->price->price->base->value_tax		= $product->tax_rate->rate * $v->price->price->base->value_ex_tax;
						$v->price->price->base->value_tax		= round( $v->price->price->base->value_tax, SHOP_BASE_CURRENCY_PRECISION );
						$v->price->price->user->value_tax		= $product->tax_rate->rate * $v->price->price->user->value_ex_tax;
						$v->price->price->user->value_tax		= round( $v->price->price->user->value_tax, SHOP_USER_CURRENCY_PRECISION );

						$v->price->price->base->value_inc_tax	= $v->price->price->base->value_ex_tax + $v->price->price->base->value_tax;
						$v->price->price->user->value_inc_tax	= $v->price->price->user->value_ex_tax + $v->price->price->user->value_tax;

					else :

						$v->price->price->base->value_tax		= 0;
						$v->price->price->user->value_tax		= 0;

						$v->price->price->base->value_inc_tax	= $v->price->price->base->value_ex_tax;
						$v->price->price->user->value_inc_tax	= $v->price->price->user->value_ex_tax;

					endif;

					// --------------------------------------------------------------------------

					//	Sale price next...
					$v->price->sale_price->base->value_ex_tax = $v->price->sale_price->base->value;
					$v->price->sale_price->user->value_ex_tax = $v->price->sale_price->user->value;

					//	Work out the ex-tax price by working out the tax and subtracting
					if ( ! empty( $product->tax_rate->rate ) ) :

						$v->price->sale_price->base->value_tax		= $product->tax_rate->rate * $v->price->sale_price->base->value_ex_tax;
						$v->price->sale_price->base->value_tax		= round( $v->price->sale_price->base->value_tax, SHOP_BASE_CURRENCY_PRECISION );
						$v->price->sale_price->user->value_tax		= $product->tax_rate->rate * $v->price->sale_price->user->value_ex_tax;
						$v->price->sale_price->user->value_tax		= round( $v->price->sale_price->user->value_tax, SHOP_USER_CURRENCY_PRECISION );

						$v->price->sale_price->base->value_inc_tax	= $v->price->sale_price->base->value_ex_tax + $v->price->sale_price->base->value_tax;
						$v->price->sale_price->user->value_inc_tax	= $v->price->sale_price->user->value_ex_tax + $v->price->sale_price->user->value_tax;

					else :

						$v->price->sale_price->base->value_tax		= 0;
						$v->price->sale_price->user->value_tax		= 0;

						$v->price->sale_price->base->value_inc_tax	= $v->price->sale_price->base->value_ex_tax;
						$v->price->sale_price->user->value_inc_tax	= $v->price->sale_price->user->value_ex_tax;

					endif;

				else :

					//	Prices are inclusive of any applicable taxes
					$v->price->price->base->value_inc_tax = $v->price->price->base->value;
					$v->price->price->user->value_inc_tax = $v->price->price->user->value;

					//	Work out the ex-tax price by working out the tax and subtracting
					if ( ! empty( $product->tax_rate->rate ) ) :

						$v->price->price->base->value_tax		= ($product->tax_rate->rate * $v->price->price->base->value_inc_tax) / (1 + $product->tax_rate->rate);
						$v->price->price->base->value_tax		= round( $v->price->price->base->value_tax, SHOP_BASE_CURRENCY_PRECISION );
						$v->price->price->user->value_tax		= ($product->tax_rate->rate * $v->price->price->user->value_inc_tax) / (1 + $product->tax_rate->rate);
						$v->price->price->user->value_tax		= round( $v->price->price->user->value_tax, SHOP_USER_CURRENCY_PRECISION );

						$v->price->price->base->value_ex_tax	= $v->price->price->base->value_inc_tax - $v->price->price->base->value_tax;
						$v->price->price->user->value_ex_tax	= $v->price->price->user->value_inc_tax - $v->price->price->user->value_tax;

					else :

						$v->price->price->base->value_tax		= 0;
						$v->price->price->user->value_tax		= 0;

						$v->price->price->base->value_ex_tax	= $v->price->price->base->value_inc_tax;
						$v->price->price->user->value_ex_tax	= $v->price->price->user->value_inc_tax;

					endif;

					// --------------------------------------------------------------------------

					//	Sale price next...
					$v->price->sale_price->base->value_inc_tax = $v->price->sale_price->base->value;
					$v->price->sale_price->user->value_inc_tax = $v->price->sale_price->user->value;

					//	Work out the ex-tax price by working out the tax and subtracting
					if ( ! empty( $product->tax_rate->rate ) ) :

						$v->price->sale_price->base->value_tax		= ($product->tax_rate->rate * $v->price->sale_price->base->value_inc_tax) / (1 + $product->tax_rate->rate);
						$v->price->sale_price->base->value_tax		= round( $v->price->sale_price->base->value_tax, SHOP_BASE_CURRENCY_PRECISION );
						$v->price->sale_price->user->value_tax		= ($product->tax_rate->rate * $v->price->sale_price->user->value_inc_tax) / (1 + $product->tax_rate->rate);
						$v->price->sale_price->user->value_tax		= round( $v->price->sale_price->user->value_tax, SHOP_USER_CURRENCY_PRECISION );

						$v->price->sale_price->base->value_ex_tax	= $v->price->sale_price->base->value_inc_tax - $v->price->sale_price->base->value_tax;
						$v->price->sale_price->user->value_ex_tax	= $v->price->sale_price->user->value_inc_tax - $v->price->sale_price->user->value_tax;

					else :

						$v->price->sale_price->base->value_tax		= 0;
						$v->price->sale_price->user->value_tax		= 0;

						$v->price->sale_price->base->value_ex_tax	= $v->price->sale_price->base->value_inc_tax;
						$v->price->sale_price->user->value_ex_tax	= $v->price->sale_price->user->value_inc_tax;

					endif;

				endif;

				// --------------------------------------------------------------------------

				//	Price Formatting
				$v->price->price->base_formatted->value			= $this->shop_currency_model->format_base( $v->price->price->base->value );
				$v->price->price->base_formatted->value_inc_tax	= $this->shop_currency_model->format_base( $v->price->price->base->value_inc_tax );
				$v->price->price->base_formatted->value_ex_tax	= $this->shop_currency_model->format_base( $v->price->price->base->value_ex_tax );
				$v->price->price->base_formatted->value_tax		= $this->shop_currency_model->format_base( $v->price->price->base->value_tax );

				$v->price->price->user_formatted->value			= $this->shop_currency_model->format_user( $v->price->price->user->value );
				$v->price->price->user_formatted->value_inc_tax	= $this->shop_currency_model->format_user( $v->price->price->user->value_inc_tax );
				$v->price->price->user_formatted->value_ex_tax	= $this->shop_currency_model->format_user( $v->price->price->user->value_ex_tax );
				$v->price->price->user_formatted->value_tax		= $this->shop_currency_model->format_user( $v->price->price->user->value_tax );

				$v->price->sale_price->base_formatted->value			= $this->shop_currency_model->format_base( $v->price->sale_price->base->value );
				$v->price->sale_price->base_formatted->value_inc_tax	= $this->shop_currency_model->format_base( $v->price->sale_price->base->value_inc_tax );
				$v->price->sale_price->base_formatted->value_ex_tax		= $this->shop_currency_model->format_base( $v->price->sale_price->base->value_ex_tax );
				$v->price->sale_price->base_formatted->value_tax		= $this->shop_currency_model->format_base( $v->price->sale_price->base->value_tax );

				$v->price->sale_price->user_formatted->value			= $this->shop_currency_model->format_user( $v->price->sale_price->user->value );
				$v->price->sale_price->user_formatted->value_inc_tax	= $this->shop_currency_model->format_user( $v->price->sale_price->user->value_inc_tax );
				$v->price->sale_price->user_formatted->value_ex_tax		= $this->shop_currency_model->format_user( $v->price->sale_price->user->value_ex_tax );
				$v->price->sale_price->user_formatted->value_tax		= $this->shop_currency_model->format_user( $v->price->sale_price->user->value_tax );

				// --------------------------------------------------------------------------

				//	Product User Price ranges
				if ( empty( $product->price ) ) :

					$product->price = new stdClass();

				endif;

				if ( empty( $product->price->user ) ) :

					$product->price->user = new stdClass();

					$product->price->user->max_price			= NULL;
					$product->price->user->max_price_inc_tax	= NULL;
					$product->price->user->max_price_ex_tax		= NULL;

					$product->price->user->min_price			= NULL;
					$product->price->user->min_price_inc_tax	= NULL;
					$product->price->user->min_price_ex_tax		= NULL;

					$product->price->user->max_sale_price			= NULL;
					$product->price->user->max_sale_price_inc_tax	= NULL;
					$product->price->user->max_sale_price_ex_tax	= NULL;

					$product->price->user->min_sale_price			= NULL;
					$product->price->user->min_sale_price_inc_tax	= NULL;
					$product->price->user->min_sale_price_ex_tax	= NULL;

				endif;

				if ( empty( $product->price->user_formatted ) ) :

					$product->price->user_formatted = new stdClass();

					$product->price->user_formatted->max_price			= NULL;
					$product->price->user_formatted->max_price_inc_tax	= NULL;
					$product->price->user_formatted->max_price_ex_tax		= NULL;

					$product->price->user_formatted->min_price			= NULL;
					$product->price->user_formatted->min_price_inc_tax	= NULL;
					$product->price->user_formatted->min_price_ex_tax	= NULL;

					$product->price->user_formatted->max_sale_price			= NULL;
					$product->price->user_formatted->max_sale_price_inc_tax	= NULL;
					$product->price->user_formatted->max_sale_price_ex_tax	= NULL;

					$product->price->user_formatted->min_sale_price			= NULL;
					$product->price->user_formatted->min_sale_price_inc_tax	= NULL;
					$product->price->user_formatted->min_sale_price_ex_tax	= NULL;

				endif;

				if ( is_null( $product->price->user->max_price ) || $v->price->price->user->value > $product->price->user->max_price ) :

					$product->price->user->max_price			= $v->price->price->user->value;
					$product->price->user->max_price_inc_tax	= $v->price->price->user->value_inc_tax;
					$product->price->user->max_price_ex_tax		= $v->price->price->user->value_ex_tax;

					$product->price->user_formatted->max_price			= $v->price->price->user_formatted->value;
					$product->price->user_formatted->max_price_inc_tax	= $v->price->price->user_formatted->value_inc_tax;
					$product->price->user_formatted->max_price_ex_tax	= $v->price->price->user_formatted->value_ex_tax;

				endif;

				if ( is_null( $product->price->user->min_price ) || $v->price->price->user->value < $product->price->user->min_price ) :

					$product->price->user->min_price			= $v->price->price->user->value;
					$product->price->user->min_price_inc_tax	= $v->price->price->user->value_inc_tax;
					$product->price->user->min_price_ex_tax		= $v->price->price->user->value_ex_tax;

					$product->price->user_formatted->min_price			= $v->price->price->user_formatted->value;
					$product->price->user_formatted->min_price_inc_tax	= $v->price->price->user_formatted->value_inc_tax;
					$product->price->user_formatted->min_price_ex_tax	= $v->price->price->user_formatted->value_ex_tax;

				endif;

				if ( is_null( $product->price->user->max_sale_price ) || $v->price->sale_price->user->value > $product->price->user->max_sale_price ) :

					$product->price->user->max_sale_price			= $v->price->sale_price->user->value;
					$product->price->user->max_sale_price_inc_tax	= $v->price->sale_price->user->value_inc_tax;
					$product->price->user->max_sale_price_ex_tax	= $v->price->sale_price->user->value_ex_tax;

					$product->price->user_formatted->max_sale_price			= $v->price->sale_price->user_formatted->value;
					$product->price->user_formatted->max_sale_price_inc_tax	= $v->price->sale_price->user_formatted->value_inc_tax;
					$product->price->user_formatted->max_sale_price_ex_tax	= $v->price->sale_price->user_formatted->value_ex_tax;

				endif;

				if ( is_null( $product->price->user->min_sale_price ) || $v->price->sale_price->user->value < $product->price->user->min_sale_price ) :

					$product->price->user->min_sale_price			= $v->price->sale_price->user->value;
					$product->price->user->min_sale_price_inc_tax	= $v->price->sale_price->user->value_inc_tax;
					$product->price->user->min_sale_price_ex_tax	= $v->price->sale_price->user->value_ex_tax;

					$product->price->user_formatted->min_sale_price			= $v->price->sale_price->user_formatted->value;
					$product->price->user_formatted->min_sale_price_inc_tax	= $v->price->sale_price->user_formatted->value_inc_tax;
					$product->price->user_formatted->min_sale_price_ex_tax	= $v->price->sale_price->user_formatted->value_ex_tax;

				endif;

			endforeach;

			//	Range strings
			if ( $product->price->user->max_price == $product->price->user->min_price ) :

				$product->price->user_formatted->price_string = $product->price->user_formatted->min_price;

			else :

				$product->price->user_formatted->price_string = 'From ' . $product->price->user_formatted->min_price;

			endif;

			if ( $product->price->user->max_sale_price == $product->price->user->min_sale_price ) :

				$product->price->user_formatted->sale_price_string = $product->price->user_formatted->min_sale_price;

			else :

				$product->price->user_formatted->sale_price_string = 'From ' . $product->price->user_formatted->min_sale_price;

			endif;

		endforeach;

		// --------------------------------------------------------------------------

		return $_products;
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches an item by it's ID; overriding to specify the `include_inactive` flag by default
	 * @param  int   $id   The ID of the product to fetch
	 * @param  array $data An array of mutation options
	 * @return mixed       FALSE on failre, stdClass on success
	 */
	public function get_by_id( $id, $data = array() )
	{
		if ( ! isset( $data['include_inactive'] ) ) :

			$data['include_inactive'] = TRUE;

		endif;

		return parent::get_by_id( $id, $data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches items by their IDs; overriding to specify the `include_inactive` flag by default
	 * @param  array $ids  An array of product IDs to fetch
	 * @param  array $data An array of mutation options
	 * @return array
	 */
	public function get_by_ids( $ids, $data = array() )
	{
		if ( ! isset( $data['include_inactive'] ) ) :

			$data['include_inactive'] = TRUE;

		endif;

		return parent::get_by_ids( $ids, $data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches an item by it's slug; overriding to specify the `include_inactive` flag by default
	 * @param  string $slug The Slug of the product to fetch
	 * @param  array  $data An array of mutation options
	 * @return mixed        FALSE on failre, stdClass on success
	 */
	public function get_by_slug( $slug, $data = array() )
	{
		if ( ! isset( $data['include_inactive'] ) ) :

			$data['include_inactive'] = TRUE;

		endif;

		return parent::get_by_slug( $slug, $data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches items by their slugs; overriding to specify the `include_inactive` flag by default
	 * @param  array $ids  An array of product Slugs to fetch
	 * @param  array $data An array of mutation options
	 * @return array
	 */
	public function get_by_slugs( $slugs, $data = array() )
	{
		if ( ! isset( $data['include_inactive'] ) ) :

			$data['include_inactive'] = TRUE;

		endif;

		return parent::get_by_slugs( $slugs, $data );
	}


	// --------------------------------------------------------------------------


	public function get_by_variant_id( $variant_id )
	{
		$this->db->select( 'product_id' );
		$this->db->where( 'id', $variant_id );
		$this->db->where( 'is_deleted', FALSE );
		$_variant = $this->db->get( $this->_table_variation )->row();

		if ( $_variant ) :

			return $this->get_by_id( $_variant->product_id );

		else :

			return FALSE;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * This method applies the conditionals which are common across the get_*()
	 * methods and the count() method.
	 * @access public
	 * @param string $data Data passed from the calling method
	 * @param string $_caller The name of the calling method
	 * @return void
	 **/
	protected function _getcount_common( $data = array(), $_caller = NULL )
	{
		parent::_getcount_common( $data, $_caller );

		// --------------------------------------------------------------------------

		//	Selects
		if ( empty( $data['_do_not_select'] ) ) :

			$this->db->select( $this->_table_prefix . '.*' );
			$this->db->select( 'pt.label type_label, pt.max_per_order type_max_per_order, pt.is_physical type_is_physical' );
			$this->db->select( 'tr.label tax_rate_label, tr.rate tax_rate_rate' );

		endif;

		//	Joins
		$this->db->join( $this->_table_type . ' pt', 'p.type_id = pt.id' );
		$this->db->join( $this->_table_tax_rate . ' tr', 'p.tax_rate_id = tr.id', 'LEFT' );

		//	Default sort
		if ( empty( $data['sort'] ) ) :

			$this->db->order_by( $this->_table_prefix . '.label' );

		endif;

		//	Search
		if ( ! empty( $data['search'] ) ) :

			//	Because fo the sub query we need to manually create the where clause,
			//	'cause Active Record is a big pile of $%!@

			$_search	= $this->db->escape_like_str( $data['search'] );

			$_where		= array();
			$_where[]	= $this->_table_prefix . '.id IN ( SELECT product_id FROM ' . NAILS_DB_PREFIX . 'shop_product_variation WHERE label LIKE \'%' . $_search . '%\' OR sku LIKE \'%' . $_search . '%\')' ;
			$_where[]	= $this->_table_prefix . '.id LIKE \'%' . $_search  . '%\'';
			$_where[]	= $this->_table_prefix . '.label LIKE \'%' . $_search  . '%\'';
			$_where[]	= $this->_table_prefix . '.description LIKE \'%' . $_search  . '%\'';
			$_where[]	= $this->_table_prefix . '.seo_description LIKE \'%' . $_search  . '%\'';
			$_where[]	= $this->_table_prefix . '.seo_keywords LIKE \'%' . $_search  . '%\'';
			$_where		= '(' . implode( ' OR ', $_where ) . ')';

			$this->db->where( $_where );

		endif;

		// --------------------------------------------------------------------------

		//	Unless told otherwise, only return active items
		if ( empty( $data['include_inactive'] ) ) :

			$this->db->where( $this->_table_prefix . '.is_active', TRUE );

		endif;

		// --------------------------------------------------------------------------

		//	Restricting to brand, category etc?

		//	Brands
		//	======

		if ( ! empty( $data['brand_id'] ) ) :

			$_where = $this->_table_prefix . '.id IN ( SELECT product_id FROM ' . $this->_table_brand . ' WHERE brand_id ';

			if ( is_array( $data['brand_id'] ) ) :

				$_brand_ids = array_map( array( $this->db, 'escape' ), $data['brand_id'] );
				$_where .= 'IN (' . implode( ',', $_brand_ids ) . ')';

			else :

				$_where .= '= ' . $this->db->escape( $data['brand_id'] );

			endif;

			$_where .= ')';

			$this->db->where( $_where );

		endif;


		//	Categories
		//	==========

		if ( ! empty( $data['category_id'] ) ) :

			$_where = $this->_table_prefix . '.id IN ( SELECT product_id FROM ' . $this->_table_category . ' WHERE category_id ';

			if ( is_array( $data['category_id'] ) ) :

				$category_ids = array_map( array( $this->db, 'escape' ), $data['category_id'] );
				$_where .= 'IN (' . implode( ',', $category_ids ) . ')';

			else :

				$_where .= '= ' . $this->db->escape( $data['category_id'] );

			endif;

			$_where .= ')';

			$this->db->where( $_where );

		endif;


		//	Collections
		//	===========

		if ( ! empty( $data['collection_id'] ) ) :

			$_where = $this->_table_prefix . '.id IN ( SELECT product_id FROM ' . $this->_table_collection . ' WHERE collection_id ';

			if ( is_array( $data['collection_id'] ) ) :

				$collection_ids = array_map( array( $this->db, 'escape' ), $data['collection_id'] );
				$_where .= 'IN (' . implode( ',', $collection_ids ) . ')';

			else :

				$_where .= '= ' . $this->db->escape( $data['collection_id'] );

			endif;

			$_where .= ')';

			$this->db->where( $_where );

		endif;


		//	Ranges
		//	======

		if ( ! empty( $data['range_id'] ) ) :

			$_where = $this->_table_prefix . '.id IN ( SELECT product_id FROM ' . $this->_table_range . ' WHERE range_id ';

			if ( is_array( $data['range_id'] ) ) :

				$range_ids = array_map( array( $this->db, 'escape' ), $data['range_id'] );
				$_where .= 'IN (' . implode( ',', $range_ids ) . ')';

			else :

				$_where .= '= ' . $this->db->escape( $data['range_id'] );

			endif;

			$_where .= ')';

			$this->db->where( $_where );

		endif;


		//	Sales
		//	=====

		if ( ! empty( $data['sale_id'] ) ) :

			$_where = $this->_table_prefix . '.id IN ( SELECT product_id FROM ' . $this->_table_sale . ' WHERE sale_id ';

			if ( is_array( $data['sale_id'] ) ) :

				$sale_ids = array_map( array( $this->db, 'escape' ), $data['sale_id'] );
				$_where .= 'IN (' . implode( ',', $sale_ids ) . ')';

			else :

				$_where .= '= ' . $this->db->escape( $data['sale_id'] );

			endif;

			$_where .= ')';

			$this->db->where( $_where );

		endif;


		//	Tags
		//	====

		if ( ! empty( $data['tag_id'] ) ) :

			$_where = $this->_table_prefix . '.id IN ( SELECT product_id FROM ' . $this->_table_tag . ' WHERE tag_id ';

			if ( is_array( $data['tag_id'] ) ) :

				$tag_ids = array_map( array( $this->db, 'escape' ), $data['tag_id'] );
				$_where .= 'IN (' . implode( ',', $tag_ids ) . ')';

			else :

				$_where .= '= ' . $this->db->escape( $data['tag_id'] );

			endif;

			$_where .= ')';

			$this->db->where( $_where );

		endif;

		// --------------------------------------------------------------------------

		//	Filtering?
		//	This is a beastly one, only do stuff if it's been requested

		if ( empty( $data['_ignore_filters'] ) && ! empty( $data['filter'] ) ) :

			//	Join the avriation table
			$this->db->join( $this->_table_variation . ' spv', $this->_table_prefix . '.id = spv.product_id' );

			foreach ( $data['filter'] AS $meta_field_id => $values ) :

				$_values = $values;
				$_values = array_filter( $_values );
				$_values = array_unique( $_values );

				foreach( $_values AS &$value ) :

					$value = $this->db->escape( $value );

				endforeach;

				$_values = implode( ',', $_values );

				$this->db->join( $this->_table_variation_product_type_meta . ' spvptm' . $meta_field_id , 'spvptm' . $meta_field_id . '.variation_id = spv.id AND spvptm' . $meta_field_id . '.meta_field_id = \'' . $meta_field_id . '\' AND spvptm' . $meta_field_id . '.value IN (' . $_values . ')' );

			endforeach;

			$this->db->group_by( $this->_table_prefix . '.id' );

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches all products which feature a particular brand
	 * @access public
	 * @param int $page The page number of the results, if NULL then no pagination
	 * @param int $per_page How many items per page of paginated results
	 * @param mixed $data Any data to pass to _getcount_common()
	 * @param bool $include_deleted If non-destructive delete is enabled then this flag allows you to include deleted items
	 * @return array
	 **/
	public function get_for_brand( $brand_id, $page = NULL, $per_page = NULL, $data = array(), $include_deleted = FALSE )
	{
		$data['brand_id'] = $brand_id;
		return $this->get_all( $page, $per_page, $data, $include_deleted, 'GET_FOR_BRAND' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Counts all products which feature a particular brand
	 * @access public
	 * @param mixed $data Any data to pass to _getcount_common()
	 * @param bool $include_deleted If non-destructive delete is enabled then this flag allows you to include deleted items
	 * @return array
	 **/
	public function count_for_brand( $brand_id, $data = array(), $include_deleted = FALSE )
	{
		$data['brand_id'] = $brand_id;
		return $this->count_all( $data, $include_deleted, 'COUNT_FOR_BRAND' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches all products which feature a particular category
	 * @access public
	 * @param int $page The page number of the results, if NULL then no pagination
	 * @param int $per_page How many items per page of paginated results
	 * @param mixed $data Any data to pass to _getcount_common()
	 * @param bool $include_deleted If non-destructive delete is enabled then this flag allows you to include deleted items
	 * @return array
	 **/
	public function get_for_category( $category_id, $page = NULL, $per_page = NULL, $data = array(), $include_deleted = FALSE )
	{
		//	Fetch this category's children also
		$this->load->model( 'shop/shop_category_model' );
		$data['category_id'] = array_merge( array( $category_id ), $this->shop_category_model->get_ids_of_children( $category_id ) );
		return $this->get_all( $page, $per_page, $data, $include_deleted, 'GET_FOR_CATEGORY' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Counts all products which feature a particular category
	 * @access public
	 * @param mixed $data Any data to pass to _getcount_common()
	 * @param bool $include_deleted If non-destructive delete is enabled then this flag allows you to include deleted items
	 * @return array
	 **/
	public function count_for_category( $category_id, $data = array(), $include_deleted = FALSE )
	{
		//	Fetch this category's children also
		$this->load->model( 'shop/shop_category_model' );
		$data['category_id'] = array_merge( array( $category_id ), $this->shop_category_model->get_ids_of_children( $category_id ) );
		return $this->count_all( $data, $include_deleted, 'COUNT_FOR_CATEGORY' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches all products which feature a particular collection
	 * @access public
	 * @param int $page The page number of the results, if NULL then no pagination
	 * @param int $per_page How many items per page of paginated results
	 * @param mixed $data Any data to pass to _getcount_common()
	 * @param bool $include_deleted If non-destructive delete is enabled then this flag allows you to include deleted items
	 * @return array
	 **/
	public function get_for_collection( $collection_id, $page = NULL, $per_page = NULL, $data = array(), $include_deleted = FALSE )
	{
		$data['collection_id'] = $collection_id;
		return $this->get_all( $page, $per_page, $data, $include_deleted, 'GET_FOR_COLLECTION' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Counts all products which feature a particular collection
	 * @access public
	 * @param mixed $data Any data to pass to _getcount_common()
	 * @param bool $include_deleted If non-destructive delete is enabled then this flag allows you to include deleted items
	 * @return array
	 **/
	public function count_for_collection( $collection_id, $data = array(), $include_deleted = FALSE )
	{
		$data['collection_id'] = $collection_id;
		return $this->count_all( $data, $include_deleted, 'COUNT_FOR_COLLECTION' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches all products which feature a particular range
	 * @access public
	 * @param int $page The page number of the results, if NULL then no pagination
	 * @param int $per_page How many items per page of paginated results
	 * @param mixed $data Any data to pass to _getcount_common()
	 * @param bool $include_deleted If non-destructive delete is enabled then this flag allows you to include deleted items
	 * @return array
	 **/
	public function get_for_range( $range_id, $page = NULL, $per_page = NULL, $data = array(), $include_deleted = FALSE )
	{
		$data['range_id'] = $range_id;
		return $this->get_all( $page, $per_page, $data, $include_deleted, 'GET_FOR_RANGE' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Counts all products which feature a particular range
	 * @access public
	 * @param mixed $data Any data to pass to _getcount_common()
	 * @param bool $include_deleted If non-destructive delete is enabled then this flag allows you to include deleted items
	 * @return array
	 **/
	public function count_for_range( $range_id, $data = array(), $include_deleted = FALSE )
	{
		$data['range_id'] = $range_id;
		return $this->count_all( $data, $include_deleted, 'COUNT_FOR_RANGE' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches all products which feature a particular sale
	 * @access public
	 * @param int $page The page number of the results, if NULL then no pagination
	 * @param int $per_page How many items per page of paginated results
	 * @param mixed $data Any data to pass to _getcount_common()
	 * @param bool $include_deleted If non-destructive delete is enabled then this flag allows you to include deleted items
	 * @return array
	 **/
	public function get_for_sale( $sale_id, $page = NULL, $per_page = NULL, $data = array(), $include_deleted = FALSE )
	{
		$data['sale_id'] = $sale_id;
		return $this->get_all( $page, $per_page, $data, $include_deleted, 'GET_FOR_SALE' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Counts all products which feature a particular sale
	 * @access public
	 * @param mixed $data Any data to pass to _getcount_common()
	 * @param bool $include_deleted If non-destructive delete is enabled then this flag allows you to include deleted items
	 * @return array
	 **/
	public function count_for_sale( $sale_id, $data = array(), $include_deleted = FALSE )
	{
		$data['sale_id'] = $sale_id;
		return $this->count_all( $data, $include_deleted, 'COUNT_FOR_SALE' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches all products which feature a particular tag
	 * @access public
	 * @param int $page The page number of the results, if NULL then no pagination
	 * @param int $per_page How many items per page of paginated results
	 * @param mixed $data Any data to pass to _getcount_common()
	 * @param bool $include_deleted If non-destructive delete is enabled then this flag allows you to include deleted items
	 * @return array
	 **/
	public function get_for_tag( $tag_id, $page = NULL, $per_page = NULL, $data = array(), $include_deleted = FALSE )
	{
		$data['tag_id'] = $tag_id;
		return $this->get_all( $page, $per_page, $data, $include_deleted, 'GET_FOR_TAG' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Counts all products which feature a particular tag
	 * @access public
	 * @param mixed $data Any data to pass to _getcount_common()
	 * @param bool $include_deleted If non-destructive delete is enabled then this flag allows you to include deleted items
	 * @return array
	 **/
	public function count_for_tag( $tag_id, $data = array(), $include_deleted = FALSE )
	{
		$data['tag_id'] = $tag_id;
		return $this->count_all( $data, $include_deleted, 'COUNT_FOR_TAG' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Formats a product's URL
	 * @param  string $slug The product's slug
	 * @return string       The product's URL
	 */
	public function format_url( $slug )
	{
		return site_url( $this->_shop_url . 'product/' . $slug );
	}


	// --------------------------------------------------------------------------


	/**
	 * Formats a product object
	 * @param  stdClass $product The product object to format
	 * @return void
	 */
	protected function _format_product_object( &$product )
	{
		//	Type casting
		$product->id			= (int) $product->id;
		$product->is_active		= (bool) $product->is_active;
		$product->is_deleted	= (bool) $product->is_deleted;

		//	Product type
		$product->type					= new stdClass();
		$product->type->id				= (int) $product->type_id;
		$product->type->label			= $product->type_label;
		$product->type->max_per_order	= (int) $product->type_max_per_order;
		$product->type->is_physical		= $product->type_is_physical;

		unset( $product->type_id );
		unset( $product->type_label );
		unset( $product->type_max_per_order );
		unset( $product->type_is_physical );

		//	Tax Rate
		$product->tax_rate			= new stdClass();
		$product->tax_rate->id		= (int) $product->tax_rate_id;
		$product->tax_rate->label	= $product->tax_rate_label;
		$product->tax_rate->rate	= $product->tax_rate_rate;

		unset( $product->tax_rate_id );
		unset( $product->tax_rate_label );
		unset( $product->tax_rate_rate );

		//	URL
		$product->url = $this->format_url( $product->slug );
	}


	// --------------------------------------------------------------------------


	/**
	 * If the seo_description or seo_keywords fields are empty this method will
	 * generate some content for them.
	 * @param  object $product A product object
	 * @return void
	 */
	public function generate_seo_content( &$product )
	{
		/**
		 * Autogenerate some SEO content if it's not been set
		 * Buy {{PRODUCT}} at {{STORE}} ({{CATEGORIES}}) - {{DESCRIPTION,FIRST SENTENCE}}
		 **/

		if ( empty( $product->seo_description ) ) :

			//	Base string
			$product->seo_description = 'Buy ' . $product->label . ' at ' . APP_NAME;

			//	Add up to 3 categories
			if ( ! empty( $product->categories ) ) :

				$_categories_arr	= array();
				$_counter			= 0;

				foreach( $product->categories AS $category ) :

					$_categories_arr[] = $category->label;

					$_counter++;

					if ( $_counter == 3 ) :

						break;

					endif;

				endforeach;

				$product->seo_description .= ' (' . implode( ', ', $_categories_arr ) . ')';

			endif;

			//	Add the first sentence of the description
			$_description = strip_tags( $product->description );
			$product->seo_description .= ' - ' . substr( $_description, 0, strpos( $_description, '.' ) + 1 );

			//	Encode entities
			$product->seo_description = htmlentities( $product->seo_description );

		endif;

		if ( empty( $product->seo_keywords ) ) :

			//	Extract common keywords
			$this->lang->load( 'shop/shop' );
			$_common = explode( ',', lang( 'shop_common_words' ) );
			$_common = array_unique( $_common );
			$_common = array_filter( $_common );

			//	Remove them and return the most popular words
			$_description = strtolower( $product->description );
			$_description = str_replace( "\n", ' ', strip_tags( $_description ) );
			$_description = str_word_count( $_description, 1 );
			$_description = array_count_values( $_description	);
			arsort( $_description );
			$_description = array_keys( $_description );
			$_description = array_diff( $_description, $_common );
			$_description = array_slice( $_description, 0, 10 );

			$product->seo_keywords = implode( ',', $_description );

			//	Encode entities
			$product->seo_keywords = htmlentities( $product->seo_keywords );

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Adds a product as a recently viewed item and saves it to the user's meta
	 * data if they're logged in.
	 * @param int $product_id The product's ID
	 */
	public function add_as_recently_viewed( $product_id )
	{
		//	Session
		$_recently_viewed = $this->session->userdata( 'shop_recently_viewed' );

		if ( empty( $_recently_viewed ) ) :

			$_recently_viewed = array();

		endif;

		//	If this product is already there, remove it
		$_search = array_search( $product_id, $_recently_viewed );
		if ( $_search !== FALSE ) :

			unset( $_recently_viewed[$_search] );

		endif;

		//	Pop it on the end
		$_recently_viewed[] = (int) $product_id;

		//	Restrict to 6 most recently viewed items
		$_recently_viewed = array_slice( $_recently_viewed, -6 );

		$this->session->set_userdata( 'shop_recently_viewed', $_recently_viewed );

		// --------------------------------------------------------------------------

		//	Logged in?
		if ( $this->user->is_logged_in() ) :

			$_data = array( 'shop_recently_viewed' => json_encode( $_recently_viewed ) );
			$this->user_model->update( active_user( 'id' ), $_data );

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns an array of recently viewed products
	 * @return array
	 */
	public function get_recently_viewed()
	{
		//	Session
		$_recently_viewed = $this->session->userdata( 'shop_recently_viewed' );

		// --------------------------------------------------------------------------

		//	Logged in?
		if ( empty( $_recently_viewed ) && $this->user->is_logged_in() ) :

			$_recently_viewed = active_user( 'shop_recently_viewed' );

		endif;

		// --------------------------------------------------------------------------

		return array_filter( (array) $_recently_viewed );
	}


	// --------------------------------------------------------------------------


	public function get_filters_for_products( $data )
	{
		if ( ! $this->_table ) :

			show_error( get_called_class() . '::count_all() Table variable not set' );

		else :

			$_table	 = $this->_table_prefix ? $this->_table . ' ' . $this->_table_prefix : $this->_table;

		endif;

		// --------------------------------------------------------------------------

		/**
		 * Get all variations which appear within this result set; then determine which
		 * product types these variations belong too. From that we can work out which
		 * filters need fetched, their values and (maybe) the number of products each
		 * filter value contains.
		 */

		//	Fetch the products in the result set
		$data['_do_not_select']		= TRUE;
		$data['_ignore_filters']	= TRUE;
		$this->_getcount_common( $data, 'GET_FILTERS_FOR_PRODUCTS' );
		$this->db->select( 'p.id, p.type_id' );
		$_product_ids_raw	= $this->db->get( $_table )->result();
		$_product_ids		= array();
		$_product_type_ids	= array();

		foreach( $_product_ids_raw AS $pid ) :

			$_product_ids[]			= $pid->id;
			$_product_type_ids[]	= $pid->type_id;

		endforeach;

		$_product_ids		= array_unique( $_product_ids );
		$_product_ids		= array_filter( $_product_ids );
		$_product_type_ids	= array_unique( $_product_type_ids );
		$_product_type_ids	= array_filter( $_product_type_ids );

		unset( $_product_ids_raw );

		if ( empty( $_product_ids ) ) :

			//	No products returned, nothing else to do
			return array();

		else :

			/**
			 * Now fetch the variants in the result set, we'll use these
			 * to restrict the values we show in the filters
			 */

			$this->db->select( 'id' );
			$this->db->where_in( 'product_id', $_product_ids );
			$_variant_ids_raw	= $this->db->get( $this->_table_variation )->result();
			$_variant_ids		= array();

			foreach( $_variant_ids_raw AS $vid ) :

				$_variant_ids[] = $vid->id;

			endforeach;

			$_variant_ids = array_unique( $_variant_ids );
			$_variant_ids = array_filter( $_variant_ids );

			unset( $_variant_ids_raw );

			/**
			 * For each product type, get it's associated meta content and then fetch
			 * the distinct values from the values table
			 */

			$this->load->model( 'shop/shop_product_type_meta_model' );
			$_meta_fields = $this->shop_product_type_meta_model->get_by_product_type_ids( $_product_type_ids );

			/**
			 * Now start putting together the filters array; this is basically just the
			 * field label & ID with all potential values of the result set.
			 */

			$_filters = array();

			foreach ( $_meta_fields AS $field ) :

				//	Ignore ones which aren't set as filters
				if ( empty( $field->is_filter ) ) :

					continue;

				endif;

				$_temp = new stdClass();
				$_temp->id		= $field->id;
				$_temp->label	= $field->label;

				$this->db->select( 'DISTINCT(`value`) `value`, COUNT(variation_id) product_count' );
				$this->db->where( 'meta_field_id', $field->id );
				$this->db->where( 'value !=', '' );
				$this->db->where_in( 'variation_id', $_variant_ids );
				$this->db->group_by( 'value' );
				$_temp->values = $this->db->get( $this->_table_variation_product_type_meta )->result();

				if ( ! empty( $_temp->values ) ) :

					$_filters[] = $_temp;

				endif;

				unset( $_temp );

			endforeach;

			unset( $_meta_fields );

			// --------------------------------------------------------------------------

			return $_filters;

		endif;
	}


	// --------------------------------------------------------------------------


	public function get_filters_for_products_in_brand( $brand_id, $data = array() )
	{
		$data['brand_id'] = $brand_id;
		return $this->get_filters_for_products( $data );
	}


	// --------------------------------------------------------------------------


	public function get_filters_for_products_in_category( $category_id, $data = array() )
	{
		//	Fetch this category's children also
		$this->load->model( 'shop/shop_category_model' );
		$data['category_id'] = array_merge( array( $category_id ), $this->shop_category_model->get_ids_of_children( $category_id ) );
		return $this->get_filters_for_products( $data );
	}


	// --------------------------------------------------------------------------


	public function get_filters_for_products_in_collection( $collection_id, $data = array() )
	{
		$data['collection_id'] = $collection_id;
		return $this->get_filters_for_products( $data );
	}


	// --------------------------------------------------------------------------


	public function get_filters_for_products_in_range( $range_id, $data = array() )
	{
		$data['range_id'] = $range_id;
		return $this->get_filters_for_products( $data );
	}


	// --------------------------------------------------------------------------


	public function get_filters_for_products_in_sale( $sale_id, $data = array() )
	{
		$data['sale_id'] = $sale_id;
		return $this->get_filters_for_products( $data );
	}


	// --------------------------------------------------------------------------


	public function get_filters_for_products_in_tag( $tag_id, $data = array() )
	{
		$data['tag_id'] = $tag_id;
		return $this->get_filters_for_products( $data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Formats a variation object
	 * @param  stdClass $variation The variation object to format
	 * @return void
	 */
	protected function _format_variation_object( &$variation )
	{
		//	Type casting
		$variation->id					= (int) $variation->id;
		$variation->product_id			= (int) $variation->product_id;
		$variation->order				= (int) $variation->order;
		$variation->is_deleted			= (bool) $variation->is_deleted;
		$variation->quantity_available	= is_numeric( $variation->quantity_available ) ? (int) $variation->quantity_available : NULL;

		//	Gallery
		if ( ! empty( $variation->gallery ) && is_array( $variation->gallery ) ) :

			foreach ( $variation->gallery AS &$object_id ) :

				$object_id	= (int) $object_id;

			endforeach;

		endif;

		//	Price
		if ( ! empty( $variation->price_raw ) && is_array( $variation->price_raw ) ) :

			foreach ( $variation->price_raw AS $price ) :

				$price->price		= (float) $price->price;
				$price->sale_price	= (float) $price->sale_price;

			endforeach;

		endif;

		//	Shipping data
		$variation->shipping					= new stdClass();
		$variation->shipping->collection_only	= (bool) $variation->ship_collection_only;
		$variation->shipping->driver_data		= @unserialize( $variation->ship_driver_data );

		unset( $variation->ship_length );
		unset( $variation->ship_width );
		unset( $variation->ship_height );
		unset( $variation->ship_measurement_unit );
		unset( $variation->ship_weight );
		unset( $variation->ship_weight_unit );
		unset( $variation->ship_collection_only );
		unset( $variation->ship_driver_data );

		//	Stock status
		if ( $variation->stock_status == 'OUT_OF_STOCK' ) :

			switch ( $variation->out_of_stock_behaviour ) :

				case 'TO_ORDER' :

					//	Set the original values, in case they're needed
					$variation->stock_status_original	= $variation->stock_status;
					$variation->lead_time_original		= $variation->lead_time;

					//	And... override!
					$variation->stock_status	= 'TO_ORDER';
					$variation->lead_time		= $variation->out_of_stock_to_order_lead_time ? $variation->out_of_stock_to_order_lead_time : $variation->lead_time;

				break;

				case 'OUT_OF_STOCK' :
				default :

					//	Nothing to do.

				break;

			endswitch;

			unset( $variation->out_of_stock_behaviour  );
			unset( $variation->out_of_stock_to_order_lead_time );

		endif;
	}
}


// --------------------------------------------------------------------------


/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core shop
 * models. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if ( ! defined( 'NAILS_ALLOW_EXTENSION_SHOP_PRODUCT_MODEL' ) ) :

	class Shop_product_model extends NAILS_Shop_product_model
	{
	}

endif;

/* End of file shop_product_model.php */
/* Location: ./modules/shop/models/shop_product_model.php */