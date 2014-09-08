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

		//	Default, empty, basket
		$this->_basket = $this->_default_basket();

		//	Populate basket from session data?
		$_saved_basket = @unserialize( $this->session->userdata( $this->_sess_var ) );

		if ( empty( $_saved_basket ) && $this->user_model->is_logged_in() ) :

			//	Check the active_user data in case it exists there
			$_saved_basket = @unserialize( active_user( 'shop_basket' ) );

		endif;

		if ( ! empty( $_saved_basket ) ) :

			$this->_basket->items = $_saved_basket->items;

			$this->add_order( $_saved_basket->order );
			$this->add_customer_details( $_saved_basket->customer_details );
			$this->add_shipping_details( $_saved_basket->shipping_details );
			$this->add_payment_gateway( $_saved_basket->payment_gateway );
			$this->add_voucher( $_saved_basket->voucher );

		endif;

		// --------------------------------------------------------------------------

		//	Clear any startup errors
		$this->clear_errors();
	}


	// --------------------------------------------------------------------------


	/**
	 * Takes the internal _basket object and fills it out a little.
	 * @return stdClass
	 */
	public function get()
	{
		$_cache = $this->_get_cache( $this->_cache_key );

		if ( ! empty( $_cache ) ) :

			return $_cache;

		endif;

		// --------------------------------------------------------------------------

		//	Clone the basket object so we don't damage/alter the original
		$_basket = clone $this->_basket;

		// --------------------------------------------------------------------------

		//	First loop through all the items and fetch product information
		$this->load->model( 'shop/shop_product_model' );

		//	This variable will hold any keys which need to be unset
		$_unset = array();

		foreach ( $_basket->items AS $basket_key => $item ) :

			$item->product = $this->shop_product_model->get_by_id( $item->product_id );

			if ( ! empty( $item->product ) ) :

				//	Find the variant
				foreach( $item->product->variations AS &$v ) :

					if ( $v->id == $item->variant_id ) :

						$item->variant = $v;
						break;

					endif;

				endforeach;

				if ( empty( $item->variant ) ) :

					//	Bad variant ID, possible item has been deleted so don't get too angry
					$_unset[] = $basket_key;

				endif;

			else :

				//	Bad product ID, again, possible product was deleted or deactivated - KCCO
				$_unset[] = $basket_key;

			endif;

		endforeach;

		//	Removing anything?
		if ( ! empty( $_unset ) ) :

			foreach( $_unset AS $key ) :

				//	Remove from the local basket object
				unset( $_basket->items[$key] );

				//	Also remove from the main basket object
				unset( $this->_basket->items[$key] );

			endforeach;

			$_basket->items			= array_values( $_basket->items );
			$this->_basket->items	= array_values( $this->_basket->items );
			$_basket->items_removed	= count( $_unset );

			// --------------------------------------------------------------------------

			$this->save();

		endif;

		// --------------------------------------------------------------------------

		//	Calculate basket item costs
		foreach ( $_basket->items AS $item ) :

			$_basket->totals->base->item += $item->quantity * $item->variant->price->price->base->value_ex_tax;
			$_basket->totals->user->item += $item->quantity * $item->variant->price->price->user->value_ex_tax;

		endforeach;

		// --------------------------------------------------------------------------

		//	Calculate shipping costs
		$this->load->model( 'shop/shop_shipping_driver_model' );

		$_shipping_costs = $this->shop_shipping_driver_model->calculate( $_basket );

		$_basket->totals->base->shipping = $_shipping_costs->base;
		$_basket->totals->user->shipping = $_shipping_costs->user;

		// --------------------------------------------------------------------------

		//	Apply any discounts
		//	TODO

		// --------------------------------------------------------------------------

		//	Calculate Tax costs
		foreach ( $_basket->items AS $item ) :

			$_basket->totals->base->tax += $item->quantity * $item->variant->price->price->base->value_tax;
			$_basket->totals->user->tax += $item->quantity * $item->variant->price->price->user->value_tax;

		endforeach;

		// --------------------------------------------------------------------------

		//	Calculate grand total
		$_basket->totals->base->grand = $_basket->totals->base->item + $_basket->totals->base->shipping + $_basket->totals->base->tax;
		$_basket->totals->user->grand = $_basket->totals->user->item + $_basket->totals->user->shipping + $_basket->totals->user->tax;

		// --------------------------------------------------------------------------

		//	If item prices are inclusive of tax then show the items total + tax
		if ( ! app_setting( 'price_exclude_tax', 'shop' ) ) :

			$_basket->totals->base->item += $_basket->totals->base->tax;
			$_basket->totals->user->item += $_basket->totals->user->tax;

		endif;

		// --------------------------------------------------------------------------

		//	Format totals
		$_basket->totals->base_formatted->item		= $this->shop_currency_model->format_base( $_basket->totals->base->item );
		$_basket->totals->base_formatted->shipping	= $this->shop_currency_model->format_base( $_basket->totals->base->shipping );
		$_basket->totals->base_formatted->tax		= $this->shop_currency_model->format_base( $_basket->totals->base->tax );
		$_basket->totals->base_formatted->grand		= $this->shop_currency_model->format_base( $_basket->totals->base->grand );

		$_basket->totals->user_formatted->item		= $this->shop_currency_model->format_user( $_basket->totals->user->item );
		$_basket->totals->user_formatted->shipping	= $this->shop_currency_model->format_user( $_basket->totals->user->shipping );
		$_basket->totals->user_formatted->tax		= $this->shop_currency_model->format_user( $_basket->totals->user->tax );
		$_basket->totals->user_formatted->grand		= $this->shop_currency_model->format_user( $_basket->totals->user->grand );

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
	public function get_total( $formatted = TRUE )
	{
		if ( $formatted ) :

			return $_out->totals->user_formatted->grand;

		else :

			return $_out->totals->user->grand;

		endif;
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

		if ( $quantity < 1 ) :

			$this->_set_error( 'Quantity must be greater than 0.' );
			return FALSE;

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

		//	Check there are items
		if ( ! is_null( $_variant->quantity_available ) && $_variant->quantity_available <= 0 ) :

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
		$this->_save_session();
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
		$this->_save_session();
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
				$this->_save_session();
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
					$this->_save_session();
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
	 * Adds a voucher to a basket.§
	 * @param string $voucher_code The voucher's code
	 */
	public function add_voucher( $voucher_code )
	{
		if ( empty( $voucher_code ) ) :

			$this->_set_error( 'No voucher code supplied.' );
			return FALSE;

		endif;

		$this->load->model( 'shop/shop_voucher_model' );
		$_voucher = $this->shop_voucher_model->validate( $voucher_code, $this->get() );

		if ( $_voucher ) :

			$this->_basket->voucher = $voucher;
			return TRUE;

		else :

			$this->remove_voucher();
			$this->_set_error( $this->shop_voucher_model->last_error() );
			return FALSE;

		endif;
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
		$this->_save_session();
		$this->_save_user();
	}


	// --------------------------------------------------------------------------


	/**
	 * Generates the 'save object' which is sued by the other _save_*() methods
	 * @return stdClass()
	 */
	protected function _save_object()
	{
		$_save						= new stdClass();
		$_save->items				= $this->_basket->items;
		$_save->order				= $this->_basket->order;
		$_save->customer_details	= $this->_basket->customer->details;
		$_save->shipping_details	= $this->_basket->shipping->details;
		$_save->payment_gateway		= $this->_basket->payment_gateway;
		$_save->voucher				= $this->_basket->voucher->id;

		return serialize( $_save );
	}


	// --------------------------------------------------------------------------


	/**
	 * Saves the 'save object' to the user's meta record
	 * @return void
	 */
	protected function _save_session()
	{
		if ( ! headers_sent() ) :

			$this->session->set_userdata( $this->_sess_var, $this->_save_object() );

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Saves the 'save object' to the user's meta record
	 * @return void
	 */
	protected function _save_user()
	{
		//	If logged in, save the basket to the user's meta data for safe keeping.
		if ( $this->user_model->is_logged_in() ) :

			$_data = array( 'shop_basket' => $this->_save_object() );
			$this->user_model->update( active_user( 'id' ), $_data );

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Reset's the basket to it's default (empty) state.
	 * @return void
	 */
	public function destroy()
	{
		$this->_basket = $this->_default_basket();

		// --------------------------------------------------------------------------

		//	Invalidate the basket cache
		$this->_save_session();
		$this->_unset_cache( $this->_cache_key );
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
		$_out->shipping->details	= $this->_default_shipping_details();
		$_out->payment_gateway		= $this->_default_payment_gateway();
		$_out->voucher				= $this->_default_voucher();

		$_out->totals					= new stdClass();
		$_out->totals->base				= new stdClass();
		$_out->totals->base_formatted	= new stdClass();
		$_out->totals->user				= new stdClass();
		$_out->totals->user_formatted	= new stdClass();

		$_out->totals->base->item		= 0;
		$_out->totals->base->shipping	= 0;
		$_out->totals->base->tax		= 0;
		$_out->totals->base->grand		= 0;

		$_out->totals->base_formatted->item		= '';
		$_out->totals->base_formatted->shipping	= '';
		$_out->totals->base_formatted->tax		= '';
		$_out->totals->base_formatted->grand	= '';

		$_out->totals->user->item		= 0;
		$_out->totals->user->shipping	= 0;
		$_out->totals->user->tax		= 0;
		$_out->totals->user->grand		= 0;

		$_out->totals->user_formatted->item		= '';
		$_out->totals->user_formatted->shipping	= '';
		$_out->totals->user_formatted->tax		= '';
		$_out->totals->user_formatted->grand	= '';

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
		$_out->state		= NULL;	//	State
		$_out->postcode		= NULL;	//	Postcode
		$_out->country		= NULL;	//	Country

		return $_out;
	}


	// --------------------------------------------------------------------------


	/**
	 * Saves the user's basket to the meta table on shut down.
	 */
	public function __destruct()
	{
		$this->_save_user();
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