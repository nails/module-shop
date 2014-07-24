<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:			shop_basket_model.php
 *
 * Description:		This model handles everything to do with the user's basket
 *
 **/

/**
 * OVERLOADING NAILS' MODELS
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

class NAILS_Shop_basket_model extends NAILS_Model
{
	protected $_cache_key;
	protected $_basket;
	protected $_sess_var;


	// --------------------------------------------------------------------------


	/**
	 * Constructs the basket model, creating an empty, default basket object.
	 */
	public function __construct()
	{
		parent::__construct();

		// --------------------------------------------------------------------------

		//	Defaults
		$this->_cache_key	= 'basket';
		$this->_sess_var	= 'shop_basket';

		// --------------------------------------------------------------------------

		//	Fetch the basket
		$this->_basket = $this->session->userdata( $this->_sess_var );

		if ( empty( $this->_basket ) && $this->user_model->is_logged_in() ) :

			//	Check the active_user data in case it exists there
			$_saved_basket = @unserialize( active_user( 'shop_basket' ) );

			if ( $_saved_basket ) :

				$this->_basket = $_saved_basket;

			else :

				//	Default, empty, basket
				$this->_basket = $this->_default_basket();

			endif;

		elseif ( empty( $this->_basket ) ) :

			//	Default, empty, basket
			$this->_basket = $this->_default_basket();

		endif;

		// --------------------------------------------------------------------------

		//	Check voucher, if present, is still valid
		if ( ! empty( $this->_basket->voucher->id ) ) :

			$this->load->model( 'shop/shop_voucher_model' );
			$_voucher = $this->shop_voucher_model->validate( $this->_basket->voucher->id );

			if ( $_voucher ) :

				$this->add_voucher( $_voucher );

			else :

				$this->remove_voucher();

			endif;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Takes the internal _basket object and fills it out a little.
	 * @return stdClass
	 */
	public function get()
	{
		return $this->_basket;
		$_cache = $this->_get_cache( $this->_cache_key );

		if ( ! empty( $_cache ) ) :

			return $_cache;

		endif;

		// --------------------------------------------------------------------------

		$this->load->model( 'shop/shop_product_model' );

		// --------------------------------------------------------------------------

		$_basket								= new stdClass();
		$_basket->items							= array();
		$_basket->totals						= new stdClass();
		$_basket->totals->sub					= 0.00;
		$_basket->totals->sub_render			= 0.00;
		$_basket->totals->shipping				= 0.00;
		$_basket->totals->shipping_render		= 0.00;
		$_basket->totals->tax_shipping			= 0.00;
		$_basket->totals->tax_shipping_render	= 0.00;
		$_basket->totals->tax_items				= 0.00;
		$_basket->totals->tax_items_render		= 0.00;
		$_basket->totals->grand					= 0.00;
		$_basket->totals->grand_render			= 0.00;
		$_basket->discount						= new stdClass;
		$_basket->discount->shipping			= 0.00;
		$_basket->discount->shipping_render		= 0.00;
		$_basket->discount->items				= 0.00;
		$_basket->discount->items_render		= 0.00;
		$_basket->not_available					= array();
		$_basket->quantity_adjusted				= array();
		$_basket->requires_shipping				= FALSE;
		$_basket->customer_details				= $this->_customer_details;
		$_basket->shipping_method				= $this->_shipping_method;
		$_basket->shipping_details				= $this->_shipping_details;
		$_basket->payment_gateway				= $this->_payment_gateway;
		$_basket->order_id						= $this->_order_id;
		$_basket->voucher						= $this->_voucher_code;

		$_not_available							= array();

		//	Variable to track the amount of discount which has been used
		$_discount_total						= 0;

		// --------------------------------------------------------------------------

		foreach ( $this->_basket->items AS $basket_key => $item ) :

			//	Fetch details about product and check availability
			$_product = $this->shop_product_model->get_by_id( $item->product_id );

			//	Fetch shipping costs for this product
			if ( $_product->type->requires_shipping ) :

				$_product->shipping = $this->shop_shipping_model->get_price_for_product( $_product->id, $_basket->shipping_method );

			else :

				$_product->shipping						= new stdClass();
				$_product->shipping->price				= 0;
				$_product->shipping->price_additional	= 0;
				$_product->shipping->tax_rate			= 0;

			endif;

			if ( $_product && $_product->is_active && ( is_null( $_product->quantity_available ) || $_product->quantity_available ) ) :

				//	Product is still available, calculate all we need to calculate
				//	and format the basket object

				//	Do we need to adjust quantities?
				if ( ! is_null( $_product->quantity_available ) && $_product->quantity_available < $item->quantity ) :

					$_basket->quantity_adjusted = $_product->title;

				endif;

				// --------------------------------------------------------------------------

				$_item						= new stdClass();
				$_item->id					= $_product->id;
				$_item->title				= $_product->title;
				$_item->type				= $_product->type;
				$_item->tax					= $_product->tax;
				$_item->quantity			= $item->quantity;
				$_item->price				= $_product->price;
				$_item->price_render		= $_product->price_render;
				$_item->sale_price			= $_product->sale_price;
				$_item->sale_price_render	= $_product->sale_price_render;
				$_item->is_on_sale			= $_product->is_on_sale;
				$_item->shipping			= $_product->shipping;

				// --------------------------------------------------------------------------

				//	Calculate shipping costs & taxes
				if ( $_item->type->requires_shipping ) :

					if ( $_item->quantity == 1 ) :

						//	Just one item, flat rate
						$_shipping = $_item->shipping->price;

					else :

						//	Multiple items, first item costs `price`, then the rest are charged at `price_additional`
						$_shipping = $_item->shipping->price + ( $_item->shipping->price_additional * ( $_item->quantity-1 ) );

					endif;

					//	Shipping tax
					$_shipping_tax = $_shipping * $_item->shipping->tax_rate;

					// --------------------------------------------------------------------------

					//	At least one item in this basket requires shipping, change the flag
					$_basket->requires_shipping = TRUE;

				else :

					$_shipping		= 0;
					$_shipping_tax	= 0;

				endif;

				$_item->shipping			= $_shipping;
				$_item->shipping_render		= shop_convert_to_user( $_shipping );
				$_item->shipping_tax		= $_shipping_tax;
				$_item->shipping_tax_render	= shop_convert_to_user( $_shipping_tax );

				// --------------------------------------------------------------------------

				//	Calculate Total
				if ( $_item->is_on_sale ):

					$_item->total			= $_item->sale_price * $_item->quantity;
					$_item->total_render	= $_item->sale_price_render  * $_item->quantity;

				else :

					$_item->total			= $_item->price  * $_item->quantity;
					$_item->total_render	= $_item->price_render  * $_item->quantity;

				endif;

				// --------------------------------------------------------------------------

				//	Calculate TAX
				$_item->tax_rate		= new stdClass();
				$_item->tax_rate->id	= $_product->tax->id;
				$_item->tax_rate->label	= round_to_precision( 100 * $_product->tax->rate, 2 ) . '%';
				$_item->tax_rate->rate	= round_to_precision( $_product->tax->rate, 2 );

				$_item->tax				= $_item->total * $_product->tax->rate;
				$_item->tax_render		= $_item->total_render * $_product->tax->rate;

				// --------------------------------------------------------------------------

				//	Is there a voucher which applies to products, or a particular product type?
				$_discount			= 0;
				$_discount_render	= 0;

				if ( $_basket->voucher && $_basket->voucher->discount_application == 'PRODUCT_TYPES' && $_basket->voucher->product_type_id == $_item->type->id ) :

					if ( $_basket->voucher->discount_type == 'PERCENTAGE' ) :

						//	Simple percentage, just knock that off the product total
						//	and be done with it.

						$_discount			= ( $_item->total + $_item->tax ) * ( $_basket->voucher->discount_value / 100 );
						$_discount_render	= ( $_item->total_render + $_item->tax_render ) * ( $_basket->voucher->discount_value / 100 );

						$_basket->discount->items			+= $_discount;
						$_basket->discount->items_render	+= $_discount_render;

					elseif ( $_basket->voucher->discount_type == 'AMOUNT' ) :

						//	Specific amount, if the product price is greater than the discount amount
						//	then simply knock that off the price, if it's less then  keep track of what's
						//	been deducted

						if ( $_discount_total < $_basket->voucher->discount_value ) :

							if ( $_basket->voucher->discount_value > ( $_item->total + $_item->tax ) ) :

								//	There'll be some of the discount left over after it's been applied
								//	to this product, work out how much

								$_discount			= $_basket->voucher->discount_value - ( $_item->total + $_item->tax );
								$_discount_render	= shop_convert_to_user( $_basket->voucher->discount_value ) - ( $_item->total_render + $_item->tax_render );

							else :

								//	There'll be no discount left over, use the whole thing! ($)($)($)
								$_discount			= $_basket->voucher->discount_value;
								$_discount_render	= shop_convert_to_user( $_basket->voucher->discount_value );

							endif;

							$_basket->discount->items			+= $_discount;
							$_basket->discount->items_render	+= $_discount_render;

							$_discount_total					+= $_discount;

						endif;

					endif;

				endif;

				// --------------------------------------------------------------------------

				//	Update basket totals
				$_basket->totals->sub					+= $_item->total;
				$_basket->totals->sub_render			+= $_item->total_render;

				$_basket->totals->tax_items				+= $_item->tax;
				$_basket->totals->tax_items_render		+= $_item->tax_render;

				$_basket->totals->grand					+= $_item->tax + $_item->shipping_tax + $_item->total + $_item->shipping - $_discount;
				$_basket->totals->grand_render			+= $_item->tax_render + $_item->shipping_tax_render + $_item->total_render + $_item->shipping_render - $_discount_render;

				$_basket->totals->shipping				+= $_item->shipping;
				$_basket->totals->shipping_render		+= $_item->shipping_render;

				$_basket->totals->tax_shipping			+= $_item->shipping_tax;
				$_basket->totals->tax_shipping_render	+= $_item->shipping_tax_render;

				// --------------------------------------------------------------------------

				$_basket->items[] = $_item;

			else :

				//	No longer available
				$_not_available[] = $basket_key;

			endif;

		endforeach;

		// --------------------------------------------------------------------------

		//	If there's a free-shipping threshold, and it's been reached, apply a discount to the shipping
		if ( app_setting( 'free_shipping_threshold', 'shop' ) && $_basket->requires_shipping ) :

			if ( $_basket->totals->sub >= app_setting( 'free_shipping_threshold', 'shop' ) ) :

				$_basket->discount->shipping	= $_basket->totals->shipping + $_basket->totals->tax_shipping;
				$_basket->totals->grand			-=$_basket->discount->shipping;

				$_basket->discount->shipping_render	= $_basket->totals->shipping_render + $_basket->totals->tax_shipping_render;
				$_basket->totals->grand_render		-=$_basket->discount->shipping_render;

			endif;

		endif;

		// --------------------------------------------------------------------------

		//	Apply any vouchers which apply to just shipping
		if ( $_basket->voucher && $_basket->voucher->discount_application == 'SHIPPING' && ( ! app_setting( 'free_shipping_threshold', 'shop' ) || app_setting( 'free_shipping_threshold', 'shop' ) > $_basket->totals->sub ) ) :

			if ( $_basket->voucher->discount_type == 'PERCENTAGE' ) :

				//	Simple percentage, just knock that off the shipping total
				//	and be done with it.

				$_discount			= ( $_basket->totals->shipping + $_basket->totals->tax_shipping ) * ( $_basket->voucher->discount_value / 100 );
				$_discount_render	= ( $_basket->totals->shipping_render + $_basket->totals->tax_shipping_render ) * ( $_basket->voucher->discount_value / 100 );

			elseif ( $_basket->voucher->discount_type == 'AMOUNT' ) :

				//	Specific amount, if the product price is greater than the discount amount
				//	then simply knock that off the price, if it's less then  just discount the
				//	total cost of shipping

				if ( $_basket->voucher->discount_value > ( $_basket->totals->shipping + $_basket->totals->tax_shipping ) ) :

					//	There'll be some of the discount left over after it's been applied
					//	to this product, work out how much

					$_discount = $_basket->voucher->discount_value - ( $_basket->totals->shipping + $_basket->totals->tax_shipping );
					$_discount = $_basket->voucher->discount_value - $_discount;

					$_discount_render = shop_convert_to_user( $_basket->voucher->discount_value ) - ( $_basket->totals->shipping_render + $_basket->totals->tax_shipping_render );
					$_discount_render = shop_convert_to_user( $_basket->voucher->discount_value ) - $_discount_render;

				else :

					//	There'll be no discount left over, use the whole thing! ($)($)($)
					$_discount			= $_basket->voucher->discount_value;
					$_discount_render	= shop_convert_to_user( $_basket->voucher->discount_value );

				endif;


			endif;

			$_basket->discount->shipping		+= $_discount;
			$_basket->discount->shipping_render	+= $_discount_render;

			// --------------------------------------------------------------------------

			//	Recalculate grand total
			$_basket->totals->grand			= $_basket->totals->sub + $_basket->totals->shipping + $_basket->totals->tax_shipping + $_basket->totals->tax_items - $_basket->discount->shipping;
			$_basket->totals->grand_render	= $_basket->totals->sub_render + $_basket->totals->shipping_render + $_basket->totals->tax_shipping_render + $_basket->totals->tax_items_render - $_basket->discount->shipping_render;

		elseif ( $_basket->voucher && $_basket->voucher->discount_application == 'SHIPPING' && app_setting( 'free_shipping_threshold', 'shop' ) && app_setting( 'free_shipping_threshold', 'shop' ) < $_basket->totals->sub ) :

			//	Voucher no longer makes sense. Remove it.
			$this->_voucher_code		= FALSE;
			$_basket->voucher			= FALSE;
			$_basket->voucher_removed	= 'Your order qualifies for free shipping. Voucher no longer needed!';
			$this->remove_voucher();

		endif;


		if ( $_basket->voucher && $_basket->voucher->discount_application == 'SHIPPING' && ! $_basket->requires_shipping ) :

			//	Voucher no longer makes sense. Remove it.
			$this->_voucher_code		= FALSE;
			$_basket->voucher			= FALSE;
			$_basket->voucher_removed	= 'Your order does not contian any items which require shipping, voucher not needed!';
			$this->remove_voucher();

		endif;

		// --------------------------------------------------------------------------

		//	Apply any vouchers which apply to just items
		if ( $_basket->voucher && $_basket->voucher->discount_application == 'PRODUCTS' ) :

			if ( $_basket->voucher->discount_type == 'PERCENTAGE' ) :

				//	Simple percentage, just knock that off the shipping total
				//	and be done with it.

				$_discount			= ( $_basket->totals->sub + $_basket->totals->tax_items ) * ( $_basket->voucher->discount_value / 100 );
				$_discount_render	= ( $_basket->totals->sub_render + $_basket->totals->tax_items_render ) * ( $_basket->voucher->discount_value / 100 );

			elseif ( $_basket->voucher->discount_type == 'AMOUNT' ) :

				//	Specific amount, if the product price is greater than the discount amount
				//	then simply knock that off the price, if it's less then  just discount the
				//	total cost of shipping

				if ( $_basket->voucher->discount_value > ( $_basket->totals->sub + $_basket->totals->tax_items ) ) :

					//	There'll be some of the discount left over after it's been applied
					//	to this product, work out how much

					$_discount = $_basket->voucher->discount_value - ( $_basket->totals->sub + $_basket->totals->tax_items );
					$_discount = $_basket->voucher->discount_value - $_discount;

					$_discount_render = shop_convert_to_user( $_basket->voucher->discount_value ) - ( $_basket->totals->sub_render + $_basket->totals->tax_items_render );
					$_discount_render = shop_convert_to_user( $_basket->voucher->discount_value ) - $_discount_render;

				else :

					//	There'll be no discount left over, use the whole thing! ($)($)($)
					$_discount			= $_basket->voucher->discount_value;
					$_discount_render	= shop_convert_to_user( $_basket->voucher->discount_value );

				endif;

			endif;

			$_basket->discount->items			+= $_discount;
			$_basket->discount->items_render	+= $_discount_render;

			// --------------------------------------------------------------------------

			//	Recalculate grand total
			$_basket->totals->grand			= $_basket->totals->sub + $_basket->totals->shipping + $_basket->totals->tax_shipping + $_basket->totals->tax_items - $_basket->discount->items;
			$_basket->totals->grand_render	= $_basket->totals->sub_render + $_basket->totals->shipping_render + $_basket->totals->tax_shipping_render + $_basket->totals->tax_items_render - $_basket->discount->items_render;

		endif;

		// --------------------------------------------------------------------------

		//	Apply any vouchers which apply to both shipping and items
		if ( $_basket->voucher && $_basket->voucher->discount_application == 'ALL' ) :

			$_sdiscount			= 0;
			$_sdiscount_render	= 0;
			$_idiscount			= 0;
			$_idiscount_render	= 0;

			if ( $_basket->voucher->discount_type == 'PERCENTAGE' ) :

				//	Simple percentage, just knock that off the product and shipping totals

				//	Check free shipping threshold
				if ( ! app_setting( 'free_shipping_threshold', 'shop' ) || $_basket->totals->sub < app_setting( 'free_shipping_threshold', 'shop' ) ) :

					$_sdiscount			= ( $_basket->totals->shipping + $_basket->totals->tax_shipping ) * ( $_basket->voucher->discount_value / 100 );
					$_sdiscount_render	= ( $_basket->totals->shipping_render + $_basket->totals->tax_shipping_render ) * ( $_basket->voucher->discount_value / 100 );

				endif;

				$_idiscount			= ( $_basket->totals->sub + $_basket->totals->tax_items ) * ( $_basket->voucher->discount_value / 100 );
				$_idiscount_render	= ( $_basket->totals->sub_render + $_basket->totals->tax_items_render ) * ( $_basket->voucher->discount_value / 100 );

			elseif ( $_basket->voucher->discount_type == 'AMOUNT' ) :

				//	Specific amount; if the discount is less than the product total then deduct it from
				//	that and be done, otherwise zero the products and deduct the remaining amount from the shipping

				//	If the voucher is a giftcard then the dicount value should be the remaining balance
				if ( $_basket->voucher->type == 'GIFT_CARD' ) :

					$_discount_value		= $_basket->voucher->gift_card_balance;
					$_discount_value_render = shop_convert_to_user( $_basket->voucher->gift_card_balance );

				else :

					$_discount_value		= $_basket->voucher->discount_value;
					$_discount_value_render = shop_convert_to_user( $_basket->voucher->discount_value );

				endif;

				if ( $_discount_value <= ( $_basket->totals->sub + $_basket->totals->tax_items ) ) :

					//	Discount is the same as, or less than, the product total, just apply discount to the products
					$_idiscount			= $_discount_value;
					$_idiscount_render	= $_discount_value_render;

				else :

					//	The discount is greater than the products, apply to the shipping too
					$_idiscount			= $_basket->totals->sub + $_basket->totals->tax_items;
					$_idiscount_render	= $_basket->totals->sub_render + $_basket->totals->tax_items_render;

					$_discount			= $_discount_value - $_idiscount;
					$_discount_render	= $_discount_value_render - $_idiscount_render;

					if ( $_discount <= ( $_basket->totals->shipping + $_basket->totals->tax_shipping ) ) :

						//	Discount is less than, or the same as, the total of shipping - just remove it all
						$_sdiscount			= $_discount;
						$_sdiscount_render	= $_discount_render;

					else :

						//	Discount is greater than the shipping amount, just discount the whole shipping price
						$_sdiscount			= $_basket->totals->shipping + $_basket->totals->tax_shipping;
						$_sdiscount_render	= $_basket->totals->shipping_render + $_basket->totals->tax_shipping_render;

					endif;

				endif;

			endif;

			$_basket->discount->items			+= $_idiscount;
			$_basket->discount->items_render	+= $_idiscount_render;

			$_basket->discount->shipping		+= $_sdiscount;
			$_basket->discount->shipping_render	+= $_sdiscount_render;

			// --------------------------------------------------------------------------

			//	Recalculate grand total
			$_basket->totals->grand			= $_basket->totals->sub + $_basket->totals->shipping + $_basket->totals->tax_shipping + $_basket->totals->tax_items - $_basket->discount->shipping - $_basket->discount->items;
			$_basket->totals->grand_render	= $_basket->totals->sub_render + $_basket->totals->shipping_render + $_basket->totals->tax_shipping_render + $_basket->totals->tax_items_render - $_basket->discount->shipping_render - $_basket->discount->items_render;

		endif;

		// --------------------------------------------------------------------------

		//	Remove any unavailable items
		if ( $_not_available ) :

			foreach ( $_not_available AS $basket_key ) :

				$_basket->not_available[] = $this->_basket->items[$basket_key]->title;
				$this->_remove_by_key( $basket_key );

			endforeach;

		endif;

		// --------------------------------------------------------------------------

		//	Save to cache and spit it back
		$this->_set_cache( $this->_cache_key, $_basket );

		return $_basket;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns the number of items in the basket.
	 * @param  boolean $respect_quantity If TRUE then the number of items in the basket is counted rather than just the number of items
	 * @return int
	 */
	public function get_count( $respect_quantity = TRUE )
	{
		if ( $respect_quantity ) :

			$_count = 0;

			foreach ( $this->_basket->items AS $item ) :

				$_count += $item->quantity;

			endforeach;

			return $_count;

		else:

			return count( $this->_basket->items );

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns the total value of the basket, in the user's currency, optionally
	 * formatted.
	 * @param  boolean $include_symbol    Whether to include the currency symbol or not
	 * @param  boolean $include_thousands Whether to include the thousand seperator or not
	 * @return string
	 */
	public function get_total( $include_symbol = FALSE, $include_thousands = FALSE )
	{
		return shop_format_price( $this->get()->totals->grand_render, $include_symbol, $include_thousands );
	}


	// --------------------------------------------------------------------------


	/**
	 * Adds an item to the basket, if it's already in the basket it increments it
	 * by $quantity.
	 * @param int $variant_id The ID of the variant to add
	 * @param int $quantity   The quantity to add
	 * @return boolean
	 */
	public function add( $variant_id, $quantity = 1 )
	{
		$quantity = intval( $quantity );

		if ( empty( $quantity ) ) :

			$quantity = 1;

		endif;

		// --------------------------------------------------------------------------

		$this->load->model( 'shop/shop_product_model' );

		//	Check if item is already in the basket.
		$_key = $this->_get_basket_key_by_variant_id( $variant_id );

		// --------------------------------------------------------------------------

		if ( $_key !== FALSE ) :

			//	Already in the basket, increment
			return $this->increment( $variant_id, $quantity );

		endif;

		// --------------------------------------------------------------------------

		//	Check the product ID is valid
		$_product = $this->shop_product_model->get_by_variant_id( $variant_id );

		if ( ! $_product ) :

			$this->_set_error( 'No Product for that Variant ID.' );
			return FALSE;

		endif;

		$_variant = NULL;
		foreach ( $_product->variations AS $variant ) :

			if ( $variant_id == $variant->id ) :

				$_variant = $variant;
				break;

			endif;

		endforeach;

		if ( ! $_variant ) :

			$this->_set_error( 'Invalid Variant ID.' );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Check product is active
		if ( ! $_product->is_active ) :

			$this->_set_error( 'Product is not available.' );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Check quantity is available, if more are being requested, then reduce.
		if ( ! is_null( $_variant->quantity_available ) && $quantity > $_variant->quantity_available ) :

			$quantity = $_variant->quantity_available;

		endif;

		// --------------------------------------------------------------------------

		//	All good, add to basket
		$_temp				= new stdClass();
		$_temp->variant_id	= $variant_id;
		$_temp->product_id	= $_product->id;
		$_temp->quantity	= $quantity;

		//	TODO: remove dependency on these fields
		$_temp->product_label	= $_product->label;
		$_temp->variant_label	= $_variant->label;
		$_temp->variant_sku		= $_variant->sku;

		$this->_basket->items[]	= $_temp;

		unset( $_temp );

		// --------------------------------------------------------------------------

		//	Invalidate the basket cache
		$this->save();
		$this->_unset_cache( $this->_cache_key );

		// --------------------------------------------------------------------------

		return TRUE;
	}


	// --------------------------------------------------------------------------


	/**
	 * Removes a variant from the basket
	 * @param  int $variant_id The variant's ID
	 * @return boolean
	 */
	public function remove( $variant_id )
	{
		$_key = $this->_get_basket_key_by_variant_id( $variant_id );

		// --------------------------------------------------------------------------

		if ( $_key !== FALSE ) :

			return $this->_remove_by_key( $_key );

		else :

			$this->_set_error( 'This item is not in your basket.' );
			return FALSE;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Removes a particular item from the basket by it's key and resets the item keys
	 * @param  int $key The basket item's key
	 * @return boolean
	 */
	protected function _remove_by_key( $key )
	{
		unset( $this->_basket->items[$key] );
		$this->_basket->items = array_values( $this->_basket->items );

		// --------------------------------------------------------------------------

		//	Invalidate the basket cache
		$this->save();
		$this->_unset_cache( $this->_cache_key );

		// --------------------------------------------------------------------------

		return TRUE;
	}


	// --------------------------------------------------------------------------


	/**
	 * Increments the quantity of an item in the basket.
	 * @param  int  $variant_id   The variant's ID
	 * @param  int  $increment_by The amount to increment the item by
	 * @return boolean
	 */
	public function increment( $variant_id, $increment_by = 1 )
	{
		$_key = $this->_get_basket_key_by_variant_id( $variant_id );

		// --------------------------------------------------------------------------

		if ( $_key !== FALSE ) :

			$_can_increment = TRUE;
			$_max_increment = NULL;

			//	Check we can increment the product

			//	TODO; work out what the maximum number of items this product type can
			//	have. If $_max_increment is NULL assume no limit on incrementations

			if ( $_can_increment && ( is_null( $_max_increment ) || $increment <= $_max_increment ) ) :

				//	Increment
				$this->_basket->items[$_key]->quantity += $increment_by;

				// --------------------------------------------------------------------------

				//	Invalidate the basket cache
				$this->save();
				$this->_unset_cache( $this->_cache_key );

				// --------------------------------------------------------------------------

				return TRUE;

			else :

				$this->_set_error( 'You cannot increment this item that many times.' );
				return FALSE;

			endif;

		else :

			$this->_set_error( 'This item is not in your basket.' );
			return FALSE;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Decrements the quantity of an item in the basket, if the decremntation reaches
	 * zero then the item will be removed from the basket.
	 * @param  int  $variant_id   The variant's ID
	 * @param  int  $decrement_by The amount to decrement the item by
	 * @return boolean
	 */
	public function decrement( $variant_id, $decrement_by = 1 )
	{
		$_key = $this->_get_basket_key_by_variant_id( $variant_id );

		// --------------------------------------------------------------------------

		if ( $_key !== FALSE ) :

			$_max_decrement = $this->_basket->items[$_key]->quantity;

			if ( $_max_decrement > 1 ) :

				if ( $decrement_by >= $_max_decrement ) :

					//	The requested decrement will take the quantity to 0 or less
					//	just remove it.

					$this->remove( $variant_id );

				else :

					//	Decrement
					$this->_basket->items[$_key]->quantity -= $decrement_by;

					// --------------------------------------------------------------------------

					//	Invalidate the basket cache
					$this->save();
					$this->_unset_cache( $this->_cache_key );

				endif;

			else :

				$this->remove( $variant_id );

			endif;

			return TRUE;

		else :

			$this->_set_error( 'This item is not in your basket.' );
			return FALSE;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns the basket's "customer details" object.
	 * @return stdClass
	 */
	public function get_customer_details()
	{
		return $this->_basket->customer->details;
	}


	// --------------------------------------------------------------------------


	/**
	 * Sets the basket's "customer details" object.
	 * @return boolean
	 */
	public function add_customer_details( $details )
	{
		//	TODO: verify?
		$this->_basket->customer->details = $details;

		return TRUE;
	}


	// --------------------------------------------------------------------------


	/**
	 * Resets the basket's "customer details" object.
	 * @return void
	 */
	public function remove_customer_details()
	{
		$this->_basket->customer->details = $this->_default_customer_details();
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns the basket's "shipping method" object.
	 * @return stdClass
	 */
	public function get_shipping_method()
	{
		return $this->_basket->shipping->method;
	}


	// --------------------------------------------------------------------------


	/**
	 * Sets the basket's "shipping method" object.
	 * @return boolean
	 */
	public function add_shipping_method( $method )
	{
		//	TODO: verify?
		$this->_basket->shipping->method = $method;

		return TRUE;
	}


	// --------------------------------------------------------------------------


	/**
	 * Resets the basket's "shipping method" object.
	 * @return void
	 */
	public function remove_shipping_method()
	{
		$this->_basket->shipping->method = $this->_default_shipping_method();
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns the basket's "shipping details" object.
	 * @return stdClass
	 */
	public function get_shipping_details()
	{
		return $this->_basket->shipping->details;
	}


	// --------------------------------------------------------------------------


	/**
	 * Sets the basket's "shipping details" object.
	 * @return boolean
	 */
	public function add_shipping_details( $details )
	{
		//	TODO: verify?
		$this->_basket->shipping->details = $details;

		return TRUE;
	}


	// --------------------------------------------------------------------------


	/**
	 * Resets the basket's "shipping details" object.
	 * @return void
	 */
	public function remove_shipping_details()
	{
		$this->_basket->shipping->details = $this->_default_shipping_details();
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns the basket's "payment gatway" object.
	 * @return stdClass
	 */
	public function get_payment_gateway()
	{
		return $this->_basket->payment_gateway;
	}


	// --------------------------------------------------------------------------


	/**
	 * Sets the basket's "payment gateway" object.
	 * @return boolean
	 */
	public function add_payment_gateway( $payment_gateway )
	{
		//	TODO: verify?
		$this->_basket->payment_gateway = $payment_gateway;

		return TRUE;
	}


	// --------------------------------------------------------------------------


	/**
	 * Resets the basket's "payment gateway" object.
	 * @return void
	 */
	public function remove_payment_gateway()
	{
		$this->_basket->payment_gateway = $this->_default_payment_gateway();
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns the basket's "order" object.
	 * @return stdClass
	 */
	public function get_order()
	{
		return $this->_basket->order;
	}


	// --------------------------------------------------------------------------


	/**
	 * Sets the basket's "order" object.
	 * @return boolean
	 */
	public function add_order( $order )
	{
		//	TODO: verify?
		$this->_basket->order = $order;

		return TRUE;
	}


	// --------------------------------------------------------------------------


	/**
	 * Resets the basket's "order" object.
	 * @return void
	 */
	public function remove_order()
	{
		$this->_basket->order = $this->_default_order();
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns the basket's "voucher" object.
	 * @return stdClass
	 */
	public function get_voucher()
	{
		return $this->_basket->voucher;
	}


	// --------------------------------------------------------------------------


	/**
	 * Sets the basket's "voucher" object.
	 * @return boolean
	 */
	public function add_voucher( $voucher )
	{
		//	TODO: verify?
		$this->_basket->voucher = $voucher;

		return TRUE;
	}


	// --------------------------------------------------------------------------


	/**
	 * Resets the basket's "voucher" object.
	 * @return void
	 */
	public function remove_voucher()
	{
		$this->_basket->voucher = $this->_default_voucher();
	}


	// --------------------------------------------------------------------------


	/**
	 * Determines whether a particular variant is already in the basket.
	 * @param  int  $variant_id The ID of the variant
	 * @return boolean
	 */
	public function is_in_basket( $variant_id )
	{
		if ( $this->_get_basket_key_by_variant_id( $variant_id ) !== FALSE ) :

			return TRUE;

		else :

			return FALSE;

		endif;
	}


	// --------------------------------------------------------------------------


	public function get_variant_quantity( $variant_id )
	{
		$_key = $this->_get_basket_key_by_variant_id( $variant_id );

		if ( $_key !== FALSE ) :

			return $this->_basket->items[$_key]->quantity;

		else :

			return FALSE;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Saves the contents of the basked to the session and, if logged in, to the
	 * user's meta data
	 * @return void
	 */
	public function save()
	{
		//	Update session
		if ( ! headers_sent() ) :

			$this->session->set_userdata( $this->_sess_var, $this->_basket );

		endif;

		//	If logged in, save the basket to the user's meta data for safe keeping.
		if ( $this->user_model->is_logged_in() ) :

			$_data = array( 'shop_basket' => serialize( $this->_basket ) );
			$this->user_model->update( active_user( 'id' ), $_data );

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Reset's the basket to it's default (empty) state.
	 * @param  boolean $save Optionally, trigger an immediate save of the empty basket.
	 * @return void
	 */
	public function destroy( $save = FALSE )
	{
		$this->_basket = $this->_default_basket();

		// --------------------------------------------------------------------------

		//	Invalidate the basket cache
		$this->save();
		$this->_unset_cache( $this->_cache_key );

		// --------------------------------------------------------------------------

		if ( $save ) :

			$this->save();

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches a basket key using the variant's ID
	 * @param  int $variant_id The ID of the variant
	 * @return mixed           Int on success FALSE on failure
	 */
	protected function _get_basket_key_by_variant_id( $variant_id )
	{
		foreach( $this->_basket->items AS $key => $item ) :

			if ( $variant_id == $item->variant_id ) :

				return $key;
				break;

			endif;

		endforeach;

		// --------------------------------------------------------------------------

		return FALSE;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns a default, empty, basket object
	 * @return stdClass
	 */
	protected function _default_basket()
	{
		$_out						= new stdClass();
		$_out->items				= array();
		$_out->order				= $this->_default_order();
		$_out->customer				= new stdClass();
		$_out->customer->details	= $this->_default_customer_details();
		$_out->shipping				= new stdClass();
		$_out->shipping->method		= $this->_default_shipping_method();
		$_out->shipping->details	= $this->_default_shipping_details();
		$_out->payment_gateway		= $this->_default_payment_gateway();
		$_out->voucher				= $this->_default_voucher();

		$_out->totals				= new stdClass();
		$_out->totals->sub			= 0;
		$_out->totals->shipping		= 0;
		$_out->totals->tax			= 0;
		$_out->totals->grand		= 0;

		return $_out;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns a default, empty, basket "order" object
	 * @return stdClass
	 */
	protected function _default_order()
	{
		$_out		= new stdClass();
		$_out->id	= NULL;

		return $_out;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns a default, empty, basket "payment gateway" object
	 * @return stdClass
	 */
	protected function _default_payment_gateway()
	{
		$_out		= new stdClass();
		$_out->id	= NULL;

		return $_out;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns a default, empty, basket "voucher" object
	 * @return stdClass
	 */
	protected function _default_voucher()
	{
		$_out		= new stdClass();
		$_out->id	= NULL;
		$_out->code	= NULL;

		return $_out;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns a default, empty, basket "customer details" object
	 * @return stdClass
	 */
	protected function _default_customer_details()
	{
		$_out				= new stdClass();
		$_out->id			= NULL;
		$_out->first_name	= NULL;
		$_out->last_name	= NULL;
		$_out->email		= NULL;

		return $_out;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns a default, empty, basket "shipping details" object
	 * @return stdClass
	 */
	protected function _default_shipping_details()
	{
		//	Clear addressing as per: http://www.royalmail.com/personal/help-and-support/How-do-I-address-my-mail-correctly
		$_out				= new stdClass();
		$_out->addressee	= NULL;	//	Named addresse
		$_out->line_1		= NULL;	//	Building number and street name
		$_out->line_2		= NULL;	//	Locality name, if required
		$_out->town			= NULL;	//	Town
		$_out->postcode		= NULL;	//	Postcode
		$_out->state		= NULL;	//	State
		$_out->country		= NULL;	//	Country

		return $_out;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns a default, empty, basket "shipping method" object
	 * @return stdClass
	 */
	protected function _default_shipping_method()
	{
		$_out		= new stdClass();
		$_out->id	= NULL;

		return $_out;
	}


	// --------------------------------------------------------------------------


	/**
	 * Saves the user's basket on shut down.
	 */
	public function __destruct()
	{
		$this->save();
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

if ( ! defined( 'NAILS_ALLOW_EXTENSION_SHOP_BASKET_MODEL' ) ) :

	class Shop_basket_model extends NAILS_Shop_basket_model
	{
	}

endif;

/* End of file shop_basket_model.php */
/* Location: ./modules/shop/models/shop_basket_model.php */