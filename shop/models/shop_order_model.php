<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:			shop_order_model.php
 *
 * Description:		This model handles everything to do with orders
 *
 **/

/**
 * OVERLOADING NAILS' MODELS
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

class NAILS_Shop_order_model extends NAILS_Model
{
	protected $_table;
	protected $_table_product;


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
	}


	// --------------------------------------------------------------------------


	/**
	 * Creates a new order in the system
	 * @param  object  $data       The data required to create the order
	 * @param  boolean $return_obj Whether or not to return the entire order object, or just the ID.
	 * @return mixed
	 */
	public function create( $data, $return_obj = FALSE )
	{
		//	Basket has items?
		if ( empty( $data->basket->items ) ) :

			$this->_set_error( 'Basket is empty.' );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Is the basket already associated with an order?
		if ( ! empty( $basket->order->id ) ) :

			$this->abandon( $basket->order->id );

		endif;

		// --------------------------------------------------------------------------

		$_order = new stdClass();

		//	Generate a reference
		do
		{
			//	Generate the string
			$_order->ref = date( 'Ym' ) . '-' . strtoupper( random_string( 'alpha', 8 ) ) . '-' . date( 'dH' );

			//	Test it
			$this->db->where( 'ref', $_order->ref );

		} while ( $this->db->count_all_results( NAILS_DB_PREFIX . 'shop_order' ) );

		// --------------------------------------------------------------------------

		//	User's IP address
		$_order->ip_address = $this->input->ip_address();

		// --------------------------------------------------------------------------

		//	Generate a code (used as a secondary verification method)
		$_order->code = md5( $this->input->ip_address() . '|'. time() . '|' . random_string( 'alnum', 15 ) );

		// --------------------------------------------------------------------------

		/**
		 * Set the user details. If defined in the order object use them, if not see
		 * if anyone's logged in, if not still then either bail out or leave blank.
		 */

		//	Email
		if ( ! empty( $data->contact->email ) ) :

			$_order->user_email = $data->contact->email;

		elseif ( $this->user_model->is_logged_in() ) :

			$_order->user_email = active_user( 'email' );

		else :

			$this->_set_error( 'An email address must be supplied' );
			return FALSE;

		endif;

		//	User ID
		$_user = $this->user_model->get_by_email( $_order->user_email );
		if ( $_user ) :

			$_order->user_id = $_user->id;

		elseif ( $this->user_model->is_logged_in() ) :

			$_order->user_id = active_user( 'id' );

		else :

			$_order->user_id = NULL;

		endif;
		unset($_user);

		//	First name
		if ( ! empty( $data->contact->first_name ) ) :

			$_order->user_first_name = $data->contact->first_name;

		elseif ( $this->user_model->is_logged_in() ) :

			$_order->user_first_name = active_user( 'first_name' );

		else :

			$_order->user_first_name = NULL;

		endif;

		//	Last name
		if ( ! empty( $data->contact->last_name ) ) :

			$_order->user_last_name = $data->contact->last_name;

		elseif ( $this->user_model->is_logged_in() ) :

			$_order->user_last_name = active_user( 'last_name' );

		else :

			$_order->user_last_name = NULL;

		endif;

		//	Telephone
		if ( ! empty( $data->contact->telephone ) ) :

			$_order->user_telephone = $data->contact->telephone;

		elseif ( $this->user_model->is_logged_in() ) :

			$_order->user_telephone = active_user( 'telephone' );

		else :

			$_order->user_telephone = NULL;

		endif;

		// --------------------------------------------------------------------------

		//	Set voucher ID
		if ( ! empty( $basket->voucher->id ) ) :

			$_order->voucher_id = $basket->voucher->id;

		endif;

		// --------------------------------------------------------------------------

		//	Set currency and exchange rates
		$_order->currency		= SHOP_USER_CURRENCY_CODE;
		$_order->base_currency	= SHOP_BASE_CURRENCY_CODE;

		// --------------------------------------------------------------------------

		//	Delivery Address
		$_order->shipping_line_1	= $data->delivery->line_1;
		$_order->shipping_line_2	= $data->delivery->line_2;
		$_order->shipping_town		= $data->delivery->town;
		$_order->shipping_state		= $data->delivery->state;
		$_order->shipping_postcode	= $data->delivery->postcode;
		$_order->shipping_country	= $data->delivery->country;

		//	Billing Address
		$_order->billing_line_1		= $data->billing->line_1;
		$_order->billing_line_2		= $data->billing->line_2;
		$_order->billing_town		= $data->billing->town;
		$_order->billing_state		= $data->billing->state;
		$_order->billing_postcode	= $data->billing->postcode;
		$_order->billing_country	= $data->billing->country;

		// --------------------------------------------------------------------------

		//	Set totals
		$_order->total_base_item		= $data->basket->totals->base->item;
		$_order->total_base_shipping	= $data->basket->totals->base->shipping;
		$_order->total_base_tax			= $data->basket->totals->base->tax;
		$_order->total_base_grand		= $data->basket->totals->base->grand;

		$_order->total_user_item		= $data->basket->totals->user->item;
		$_order->total_user_shipping	= $data->basket->totals->user->shipping;
		$_order->total_user_tax			= $data->basket->totals->user->tax;
		$_order->total_user_grand		= $data->basket->totals->user->grand;

		// --------------------------------------------------------------------------

		$_order->created	= date( 'Y-m-d H:i:s' );
		$_order->modified	= date( 'Y-m-d H:i:s' );

		// --------------------------------------------------------------------------

		//	Start the transaction
		$this->db->trans_begin();
		$_rollback = FALSE;

		// --------------------------------------------------------------------------

		$this->db->set( $_order );
		$this->db->insert( NAILS_DB_PREFIX . 'shop_order' );

		$_order->id = $this->db->insert_id();

		if ( $_order->id ) :

			//	Add the items
			$_items = array();

			foreach( $data->basket->items AS $item ) :

				$_temp					= array();
				$_temp['order_id']		= $_order->id;
				$_temp['product_id']	= $item->product_id;
				$_temp['product_label']	= $item->product_label;
				$_temp['variant_id']	= $item->variant_id;
				$_temp['variant_label']	= $item->variant_label;
				$_temp['quantity']		= $item->quantity;
				$_temp['tax_rate_id']	= ! empty( $item->product->tax_rate->id ) ? $item->product->tax_rate->id : NULL;

				//	Price
				$_temp['price_base_value']			= $item->variant->price->price->base->value;
				$_temp['price_base_value_inc_tax']	= $item->variant->price->price->base->value_inc_tax;
				$_temp['price_base_value_ex_tax']	= $item->variant->price->price->base->value_ex_tax;
				$_temp['price_base_value_tax']		= $item->variant->price->price->base->value_tax;

				$_temp['price_user_value']			= $item->variant->price->price->user->value;
				$_temp['price_user_value_inc_tax']	= $item->variant->price->price->user->value_inc_tax;
				$_temp['price_user_value_ex_tax']	= $item->variant->price->price->user->value_ex_tax;
				$_temp['price_user_value_tax']		= $item->variant->price->price->user->value_tax;

				//	Sale Price
				$_temp['sale_price_base_value']			= $item->variant->price->sale_price->base->value;
				$_temp['sale_price_base_value_inc_tax']	= $item->variant->price->sale_price->base->value_inc_tax;
				$_temp['sale_price_base_value_ex_tax']	= $item->variant->price->sale_price->base->value_ex_tax;
				$_temp['sale_price_base_value_tax']		= $item->variant->price->sale_price->base->value_tax;

				$_temp['sale_price_user_value']			= $item->variant->price->sale_price->user->value;
				$_temp['sale_price_user_value_inc_tax']	= $item->variant->price->sale_price->user->value_inc_tax;
				$_temp['sale_price_user_value_ex_tax']	= $item->variant->price->sale_price->user->value_ex_tax;
				$_temp['sale_price_user_value_tax']		= $item->variant->price->sale_price->user->value_tax;

				/**
				 * To order?
				 * If this item is to order then make a note in the `extra_data column so it can be rendered on invoices etc
				 */

				if ( $item->variant->stock_status == 'TO_ORDER' ) :

					//	Save the lead_time
					if ( ! isset( $item->extra_data ) ) :

						$item->extra_data = array();

					elseif( isset( $item->extra_data ) && ! is_array( $item->extra_data ) ) :

						$item->extra_data = (array) $item->extra_data;

					endif;

					$item->extra_data['to_order']				= new stdClass();
					$item->extra_data['to_order']->is_to_order	= TRUE;
					$item->extra_data['to_order']->lead_time	= $item->variant->lead_time;


				endif;

				//	Extra data
				if ( isset( $item->extra_data ) && $item->extra_data ) :

					$_temp['extra_data'] = serialize( (array) $item->extra_data );

				endif;

				$_items[] = $_temp;
				unset( $_temp );

			endforeach;

			$this->db->insert_batch( NAILS_DB_PREFIX . 'shop_order_product', $_items );

			if ( ! $this->db->affected_rows() ) :

				//	Set error message
				$_rollback = TRUE;
				$this->_set_error( 'Unable to add products to order, aborting.' );

			endif;

		else :

			//	Failed to create order
			$_rollback = TRUE;
			$this->_set_error( 'An error occurred while creating the order.' );

		endif;

		// --------------------------------------------------------------------------

		//	Return
		if ( $_rollback ) :

			$this->db->trans_rollback();
			return FALSE;

		else :

			$this->db->trans_commit();

			if ( $return_obj ) :

				return $this->get_by_id( $_order->id );

			else :

				return $_order->id;

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
		if ( ! $data )
			return FALSE;

		// --------------------------------------------------------------------------

		$this->db->set( $data );
		$this->db->set( 'modified', 'NOW()', FALSE );
		$this->db->where( 'id', $id );
		$this->db->update( NAILS_DB_PREFIX . 'shop_order' );

		return $this->db->affected_rows() ? TRUE : FALSE;
	}


	// --------------------------------------------------------------------------


	/**
	 * Deletes an existing object
	 *
	 * @access public
	 * @param int $id The ID of the object to delete
	 * @return bool
	 **/
	public function delete( $id )
	{
		$this->db->where( 'id', $id );
		$this->db->delete( NAILS_DB_PREFIX . 'shop_order' );

		return $this->db->affected_rows() ? TRUE : FALSE;
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches all objects
	 *
	 * @access public
	 * @param none
	 * @return array
	 **/
	public function get_all( $order = NULL, $limit = NULL, $where = NULL, $search = NULL )
	{
		$this->db->select( 'o.*' );
		$this->db->select( 'ue.email, u.first_name, u.last_name, u.gender, u.profile_img,ug.id user_group_id,ug.label user_group_label' );
		$this->db->select( 'v.code v_code,v.label v_label, v.type v_type, v.discount_type v_discount_type, v.discount_value v_discount_value, v.discount_application v_discount_application' );
		$this->db->select( 'v.product_type_id v_product_type_id, v.is_active v_is_active, v.is_deleted v_is_deleted, v.valid_from v_valid_from, v.valid_to v_valid_to' );

		// --------------------------------------------------------------------------

		//	Set Order
		if ( is_array( $order ) ) :

			$this->db->order_by( $order[0], $order[1] );

		endif;

		// --------------------------------------------------------------------------

		//	Set Limit
		if ( is_array( $limit ) ) :

			$this->db->limit( $limit[0], $limit[1] );

		endif;

		// --------------------------------------------------------------------------

		//	Build conditionals
		$this->_getcount_orders_common( $where, $search );

		// --------------------------------------------------------------------------

		$_orders = $this->db->get( NAILS_DB_PREFIX . 'shop_order o' )->result();

		//	Needed by the _format_*() methods
		$this->load->model( 'shop/shop_currency_model' );

		foreach ( $_orders AS $order ) :

			//	Format order object
			$this->_format_order( $order );

			// --------------------------------------------------------------------------

			//	Fetch items associated with this order
			$order->items = $this->get_items_for_order( $order->id );

		endforeach;

		return $_orders;
	}


	// --------------------------------------------------------------------------


	/**
	 * Counts the total amount of orders for a partricular query/search key. Essentially performs
	 * the same query as $this->get_all() but without limiting.
	 *
	 * @access	public
	 * @param	string	$where	An array of where conditions
	 * @param	mixed	$search	A string containing the search terms
	 * @return	int
	 *
	 **/
	public function count_orders( $where = NULL, $search = NULL )
	{
		$this->_getcount_orders_common( $where, $search );

		// --------------------------------------------------------------------------

		//	Execute Query
		return $this->db->count_all_results( NAILS_DB_PREFIX . 'shop_order o' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Counts the total amount of orders for a partricular query/search key. Essentially performs
	 * the same query as $this->get_all() but without limiting.
	 *
	 * @access	public
	 * @param	string	$where	An array of where conditions
	 * @param	mixed	$search	A string containing the search terms
	 * @return	int
	 *
	 **/
	public function count_unfulfilled_orders( $where = NULL, $search = NULL )
	{
		$this->db->where( 'fulfilment_status', 'UNFULFILLED' );
		$this->db->where( 'status', 'PAID' );

		// --------------------------------------------------------------------------

		//	Execute Query
		return $this->db->count_all_results( NAILS_DB_PREFIX . 'shop_order o' );
	}


	// --------------------------------------------------------------------------


	protected function _getcount_orders_common( $where = NULL, $search = NULL )
	{
		$this->db->join( NAILS_DB_PREFIX . 'user u', 'u.id = o.user_id', 'LEFT' );
		$this->db->join( NAILS_DB_PREFIX . 'user_email ue', 'ue.user_id = u.id AND ue.is_primary = 1', 'LEFT' );
		$this->db->join( NAILS_DB_PREFIX . 'user_group ug', 'ug.id = u.group_id', 'LEFT' );
		$this->db->join( NAILS_DB_PREFIX . 'shop_voucher v', 'v.id = o.voucher_id', 'LEFT' );

		// --------------------------------------------------------------------------

		//	Set Where
		if ( $where ) :

			$this->db->where( $where );

		endif;

		// --------------------------------------------------------------------------

		//	Set Search
		if ( $search && is_string( $search ) ) :

			//	Search is a simple string, no columns are being specified to search across
			//	so define a default set to search across

			$search								= array( 'keywords' => $search, 'columns' => array() );
			$search['columns']['id']			= 'o.id';
			$search['columns']['ref']			= 'o.ref';
			$search['columns']['user_id']		= 'o.user_id';
			$search['columns']['user_email']	= 'o.user_email';
			$search['columns']['pp_txn_id']		= 'o.pp_txn_id';

		endif;

		//	If there is a search term to use then build the search query
		if ( isset( $search[ 'keywords' ] ) && $search[ 'keywords' ] ) :

			//	Parse the keywords, look for specific column searches
			preg_match_all('/\(([a-zA-Z0-9\.\- ]+):([a-zA-Z0-9\.\- ]+)\)/', $search['keywords'], $_matches );

			if ( $_matches[1] && $_matches[2] ) :

				$_specifics = array_combine( $_matches[1], $_matches[2] );

			else :

				$_specifics = array();

			endif;

			//	Match the specific labels to a column
			if ( $_specifics ) :

				$_temp = array();
				foreach ( $_specifics AS $col => $value ) :

					if ( isset( $search['columns'][ strtolower( $col )] ) ) :

						$_temp[] = array(
							'cols'	=> $search['columns'][ strtolower( $col )],
							'value'	=> $value
						);

					endif;

				endforeach;
				$_specifics = $_temp;
				unset( $_temp );

				// --------------------------------------------------------------------------

				//	Remove controls from search string
				$search['keywords'] = preg_replace('/\(([a-zA-Z0-9\.\- ]+):([a-zA-Z0-9\.\- ]+)\)/', '', $search['keywords'] );

			endif;

			if ( $_specifics ) :

				//	We have some specifics
				foreach( $_specifics AS $specific ) :

					if ( is_array( $specific['cols'] ) ) :

						$_separator = array_shift( $specific['cols'] );
						$this->db->like( 'CONCAT_WS( \'' . $_separator . '\', ' . implode( ',', $specific['cols'] ) . ' )', $specific['value'] );

					else :

						$this->db->like( $specific['cols'], $specific['value'] );

					endif;

				endforeach;

			endif;


			// --------------------------------------------------------------------------

			if ( $search['keywords'] ) :

				$_where  = '(';

				if ( isset( $search[ 'columns' ] ) && $search[ 'columns' ] ) :

					//	We have some specifics
					foreach( $search[ 'columns' ] AS $col ) :

						if ( is_array( $col ) ) :

							$_separator = array_shift( $col );
							$_where .= 'CONCAT_WS( \'' . $_separator . '\', ' . implode( ',', $col ) . ' ) LIKE \'%' . trim( $search['keywords'] ) . '%\' OR ';

						else :

							$_where .= $col . ' LIKE \'%' . trim( $search['keywords'] ) . '%\' OR ';

						endif;

					endforeach;

				endif;

				$this->db->where( substr( $_where, 0, -3 ) . ')' );

			endif;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetch an object by it's ID
	 *
	 * @access public
	 * @param int $id The ID of the object to fetch
	 * @return	stdClass
	 **/
	public function get_by_id( $id )
	{
		$this->db->where( 'o.id', $id );
		$_result = $this->get_all();

		// --------------------------------------------------------------------------

		if ( ! $_result )
			return FALSE;

		// --------------------------------------------------------------------------

		return $_result[0];
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetch an object by it's ID
	 *
	 * @access public
	 * @param int $id The ID of the object to fetch
	 * @return	stdClass
	 **/
	public function get_by_ref( $ref )
	{
		$this->db->where( 'o.ref', $ref );
		$_result = $this->get_all();

		// --------------------------------------------------------------------------

		if ( ! $_result )
			return FALSE;

		// --------------------------------------------------------------------------

		return $_result[0];
	}


	// --------------------------------------------------------------------------


	public function get_items_for_order( $order_id )
	{
		$this->db->select( 'op.*' );
		$this->db->select( 'pt.id pt_id, pt.label pt_label, pt.ipn_method pt_ipn_method' );
		$this->db->select( 'tr.id tax_rate_id, tr.label tax_rate_label, tr.rate tax_rate_rate' );
		$this->db->select( 'v.sku v_sku' );

		$this->db->join( NAILS_DB_PREFIX . 'shop_product p', 'p.id = op.product_id' );
		$this->db->join( NAILS_DB_PREFIX . 'shop_product_type pt', 'pt.id = p.type_id' );
		$this->db->join( NAILS_DB_PREFIX . 'shop_tax_rate tr', 'tr.id = p.tax_rate_id', 'LEFT' );
		$this->db->join( NAILS_DB_PREFIX . 'shop_product_variation v', 'v.id = op.variant_id', 'LEFT' );

		$this->db->where( 'op.order_id', $order_id );
		$_items = $this->db->get( NAILS_DB_PREFIX . 'shop_order_product op' )->result();

		//	Needed by the _format_*() methods
		$this->load->model( 'shop/shop_currency_model' );

		foreach ( $_items AS $item ) :

			$this->_format_item( $item );

		endforeach;

		return $_items;
	}


	// --------------------------------------------------------------------------


	public function get_for_user( $user_id, $email )
	{
		$this->db->where_in( 'o.status', array( 'PAID', 'UNPAID' ) );
		$this->db->where( '(o.user_id = ' . $user_id . ' OR o.user_email = \'' . $email . '\')' );
		return $this->get_all();
	}


	// --------------------------------------------------------------------------


	public function get_items_for_user( $user_id, $email )
	{
		$this->db->select( 'op.id,op.product_id,op.quantity,op.title,op.price,op.sale_price,op.tax,op.shipping,op.shipping_tax,op.total' );
		$this->db->select( 'op.price_render,op.sale_price_render,op.tax_render,op.shipping_render,op.shipping_tax_render,op.total_render' );
		$this->db->select( 'op.was_on_sale,op.processed,op.refunded,op.refunded_date,op.extra_data' );
		$this->db->select( 'pt.id pt_id, pt.label pt_label, pt.ipn_method pt_ipn_method' );
		$this->db->select( 'tr.id tax_rate_id, tr.label tax_rate_label, tr.rate tax_rate_rate' );

		$this->db->join( NAILS_DB_PREFIX . 'shop_order o', 'o.id = op.order_id', 'LEFT' );
		$this->db->join( NAILS_DB_PREFIX . 'shop_product p', 'p.id = op.product_id', 'LEFT' );
		$this->db->join( NAILS_DB_PREFIX . 'shop_product_type pt', 'pt.id = p.type_id', 'LEFT' );
		$this->db->join( NAILS_DB_PREFIX . 'shop_tax_rate tr', 'tr.id = p.tax_rate_id', 'LEFT' );

		$this->db->where( '(o.user_id = ' . $user_id . ' OR o.user_email = \'' . $email . '\')' );
		$this->db->where( 'o.status', 'PAID' );

		$_items = $this->db->get( NAILS_DB_PREFIX . 'shop_order_product op' )->result();

		//	Needed by the _format_*() methods
		$this->load->model( 'shop/shop_currency_model' );

		foreach ( $_items AS $item ) :

			$this->_format_item( $item );

		endforeach;

		return $_items;
	}


	// --------------------------------------------------------------------------


	public function abandon( $order_id, $data = array() )
	{
		$data['status'] = 'ABANDONED';
		return $this->update( $order_id, $data );
	}


	// --------------------------------------------------------------------------


	public function fail( $order_id, $reason = '', $data = array() )
	{
		$data['status'] = 'FAILED';
		if ( $this->update( $order_id, $data ) ) :

			$_reason = empty( $reason ) ? 'No reason supplied.' : $reason;
			$this->note_add( $order_id, 'Failure Reason: ' . $_reason );

		else :

			return FALSE;

		endif;
	}



	// --------------------------------------------------------------------------


	public function paid( $order_id, $data = array() )
	{
		$data['status'] = 'PAID';
		return $this->update( $order_id, $data );
	}


	// --------------------------------------------------------------------------


	public function unpaid( $order_id, $data = array() )
	{
		$data['status'] = 'UNPAID';
		return $this->update( $order_id, $data );
	}


	// --------------------------------------------------------------------------


	public function cancel( $order_id, $data = array() )
	{
		$data['status'] = 'CANCELLED';
		return $this->update( $order_id, $data );
	}


	// --------------------------------------------------------------------------


	public function fulfil( $order_id, $data = array() )
	{
		$data['fulfilment_status']	= 'FULFILLED';
		$data['fulfilled']			= date( 'Y-m-d H:i:s' );

		return $this->update( $order_id, $data );
	}


	// --------------------------------------------------------------------------


	public function unfulfil( $order_id, $data = array() )
	{
		$data['fulfilment_status']	= 'UNFULFILLED';
		$data['fulfilled']			= NULL;

		return $this->update( $order_id, $data );
	}

	// --------------------------------------------------------------------------

	public function pending( $order_id, $data = array() )
	{
		$data['status'] = 'PENDING';
		return $this->update( $order_id, $data );
	}


	// --------------------------------------------------------------------------


	public function process( $order )
	{
		//	This is TODO
		return TRUE;

		// --------------------------------------------------------------------------

		//	If an ID has been passed, look it up
		if ( is_numeric( $order ) ) :

			_LOG( 'Looking up order #' . $order );
			$order = $this->get_by_id( $order );

			if ( ! $order ) :

				_LOG( 'Invalid order ID' );
				$this->_set_error( 'Invalid order ID' );
				return FALSE;

			endif;

		endif;

		// --------------------------------------------------------------------------

		_LOG( 'Processing order #' . $order->id );

		/**
		 * Loop through all the items in the order. If there's a proccessor method
		 * for the object type then begin grouping the products so we can execute
		 * the processor in a oner with all the associated products
		 */

		$_processors = array();

		foreach ( $order->items AS $item ) :

			_LOG( 'Processing item #' . $item->id . ': ' . $item->title . ' (' . $item->type->label . ')' );

			if ( $item->type->ipn_method && method_exists( $this, '_process_' . $item->type->ipn_method ) ) :

				if ( ! isset( $_processors['_process_' . $item->type->ipn_method] ) ) :

					$_processors['_process_' . $item->type->ipn_method] = array();

				endif;

				$_processors['_process_' . $item->type->ipn_method][] = $item;

			endif;

		endforeach;

		// --------------------------------------------------------------------------

		//	Execute the processors
		if ( $_processors ) :

			_LOG( 'Executing processors...' );

			foreach ( $_processors AS $method => $products ) :

				_LOG( '... ' . $method . '(); with ' . count( $products ) . ' items.' );
				call_user_func_array( array( $this, $method), array( &$products, &$order ) );

			endforeach;

		endif;

		// --------------------------------------------------------------------------

		//	Has the order been fulfilled? If all products in the order are processed
		//	then consider this order fulfilled.

		$this->db->where( 'order_id', $order->id );
		$this->db->where( 'processed', FALSE );

		if ( ! $this->db->count_all_results( NAILS_DB_PREFIX . 'shop_order_product' ) ) :

			//	No unprocessed items, consider order FULFILLED
			$this->fulfil( $order->id );

		endif;

		// --------------------------------------------------------------------------

		return TRUE;
	}


	// --------------------------------------------------------------------------


	protected function _process_download( &$items, &$order )
	{
		//	Generate links for all the items
		$_urls		= array();
		$_ids		= array();
		$_expires	= 172800; //	48 hours

		foreach( $items AS $item ) :

			$_temp			= new stdClass();
			$_temp->title	= $item->title;
			$_temp->url		= cdn_expiring_url( $item->meta->download_id, $_expires ) . '&dl=1';
			$_urls[]		= $_temp;

			$_ids[]			= $item->id;

			unset( $_temp );

		endforeach;

		// --------------------------------------------------------------------------

		//	Send the user an email with the links
		_LOG( 'Sending download email to ' . $order->user->email  . '; email contains ' . count( $_urls ) . ' expiring URLs' );

		$_email							= new stdClass();
		$_email->type					= 'shop_product_type_download';
		$_email->to_email				= $order->user->email;
		$_email->data					= array();
		$_email->data['order']			= new stdClass();
		$_email->data['order']->id		= $order->id;
		$_email->data['order']->ref		= $order->ref;
		$_email->data['order']->created	= $order->created;
		$_email->data['expires']		= $_expires;
		$_email->data['urls']			= $_urls;

		if ( $this->emailer->send( $_email, TRUE ) ) :

			//	Mark items as processed
			$this->db->set( 'processed', TRUE );
			$this->db->where_in( 'id', $_ids );
			$this->db->update( NAILS_DB_PREFIX . 'shop_order_product' );

		else :

			//	Email failed to send, alert developers
			_LOG( '!! Failed to send download links, alerting developers' );
			_LOG( implode( "\n", $this->emailer->get_errors() ) );

			send_developer_mail( 'Unable to send download email', 'Unable to send the email with download links to ' . $_email->to_email . '; order: #' . $order->id . "\n\nEmailer errors:\n\n" . print_r( $this->emailer->get_errors(), TRUE ) );

		endif;
	}


	// --------------------------------------------------------------------------


	public function send_receipt( $order, $payment_data = array(), $partial = FALSE )
	{
		//	If an ID has been passed, look it up
		if ( is_numeric( $order ) ) :

			_LOG( 'Looking up order #' . $order );
			$order = $this->get_by_id( $order );

			if ( ! $order ) :

				_LOG( 'Invalid order ID' );
				$this->_set_error( 'Invalid order ID' );
				return FALSE;

			endif;

		endif;

		// --------------------------------------------------------------------------

		$_email							= new stdClass();
		$_email->type					= $partial ? 'shop_receipt_partial' : 'shop_receipt';
		$_email->to_email				= $order->user->email;
		$_email->data					= array();
		$_email->data['order']			= $order;
		$_email->data['payment_data']	= $payment_data;

		if ( ! $this->emailer->send( $_email, TRUE ) ) :

			//	Email failed to send, alert developers
			$_email_errors = $this->emailer->get_errors();

			if ( $partial ) :

				_LOG( '!! Failed to send receipt (partial payment) to customer, alerting developers' );
				$_subject  = 'Unable to send customer receipt email (partial payment)';

			else :

				_LOG( '!! Failed to send receipt to customer, alerting developers' );
				$_subject  = 'Unable to send customer receipt email';

			endif;
			_LOG( implode( "\n", $_email_errors ) );

			$_message  = 'Unable to send the customer receipt to ' . $_email->to_email . '; order: #' . $order->id . "\n\n";
			$_message .= 'Emailer errors:' . "\n\n" . print_r( $_email_errors, TRUE );

			send_developer_mail( $_subject, $_message );

		endif;

		// --------------------------------------------------------------------------

		return TRUE;
	}


	// --------------------------------------------------------------------------


	public function send_order_notification( $order, $payment_data = array(), $partial = FALSE )
	{
		//	If an ID has been passed, look it up
		if ( is_numeric( $order ) ) :

			_LOG( 'Looking up order #' . $order );
			$order = $this->get_by_id( $order );

			if ( ! $order ) :

				_LOG( 'Invalid order ID' );
				$this->_set_error( 'Invalid order ID.' );
				return FALSE;

			endif;

		endif;

		// --------------------------------------------------------------------------

		$_email							= new stdClass();
		$_email->type					= $partial ? 'shop_notification_partial_payment' : 'shop_notification_paid';
		$_email->data					= array();
		$_email->data['order']			= $order;
		$_email->data['payment_data']	= $payment_data;

		$this->load->model( 'system/app_notification_model' );
		$_notify = $this->app_notification_model->get( 'orders', 'shop' );

		foreach ( $_notify AS $email ) :

			$_email->to_email = $email;

			if ( ! $this->emailer->send( $_email, TRUE ) ) :

				$_email_errors = $this->emailer->get_errors();

				if ( $partial ) :

					_LOG( '!! Failed to send order notification (partially payment) to ' . $email . ', alerting developers.' );
					$_subject  = 'Unable to send order notification email (partial payment)';

				else :

					_LOG( '!! Failed to send order notification to ' . $email . ', alerting developers.' );
					$_subject  = 'Unable to send order notification email';

				endif;

				_LOG( implode( "\n", $_email_errors ) );

				$_message  = 'Unable to send the order notification to ' . $email . '; order: #' . $_order->id . "\n\n";
				$_message .= 'Emailer errors:' . "\n\n" . print_r( $_email_errors, TRUE );

				send_developer_mail( $_subject, $_message );

			endif;

		endforeach;
	}


	// --------------------------------------------------------------------------


	/**
	 * Adds a note against an order
	 * @param int $order_id The order IDto add the note against
	 * @param boolean
	 */
	public function note_add( $order_id, $note )
	{
		$this->db->set( 'order_id', $order_id );
		$this->db->set( 'note', $note );

		$this->db->set( 'created', 'NOW()', FALSE );
		$this->db->set( 'modified', 'NOW()', FALSE );

		if ( $this->user_model->is_logged_in() ) :

			$this->db->set( 'created_by', active_user( 'id' ) );
			$this->db->set( 'modified_by', active_user( 'id' ) );

		else :

			$this->db->set( 'created_by', NULL );
			$this->db->set( 'modified_by', NULL );

		endif;

		$this->db->insert( NAILS_DB_PREFIX . 'shop_order_note' );

		return (bool) $this->db->affected_rows();
	}


	// --------------------------------------------------------------------------


	/**
	 * Deletes an existing order note
	 * @param  int    $order_id The Order's ID
	 * @param  int    $note_id  The note's ID
	 * @return boolean
	 */
	public function note_delete( $order_id, $note_id )
	{
		$this->db->where( 'id', $note_id );
		$this->db->where( 'order_iid', $order_id );
		$this->db->delete( NAILS_DB_PREFIX . 'shop_order_note' );
		return (bool) $this->db->affected_rows();
	}


	// --------------------------------------------------------------------------


	protected function _format_order( &$order )
	{
		//	Generic
		$order->id = (int) $order->id;

		// --------------------------------------------------------------------------

		//	User
		$order->user		= new stdClass();
		$order->user->id	= $order->user_id;

		if ( $order->user_email ) :

			$order->user->email = $order->user_email;

		else :

			$order->user->email = $order->email;

		endif;

		if ( $order->user_first_name ) :

			$order->user->first_name = $order->user_first_name;

		else :

			$order->user->first_name = $order->first_name;

		endif;

		if ( $order->user_last_name ) :

			$order->user->last_name = $order->user_last_name;

		else :

			$order->user->last_name = $order->last_name;

		endif;

		$order->user->telephone		= $order->user_telephone;
		$order->user->gender		= $order->gender;
		$order->user->profile_img	= $order->profile_img;

		$order->user->group			= new stdClass();
		$order->user->group->id		= $order->user_group_id;
		$order->user->group->label	= $order->user_group_label;

		unset( $order->user_id );
		unset( $order->user_email );
		unset( $order->user_first_name );
		unset( $order->user_last_name );
		unset( $order->user_telephone );
		unset( $order->email );
		unset( $order->first_name );
		unset( $order->last_name );
		unset( $order->gender );
		unset( $order->profile_img );
		unset( $order->user_group_id );
		unset( $order->user_group_label );

		// --------------------------------------------------------------------------

		//	Totals
		$order->totals 					= new stdClass();

		$order->totals->base			= new stdClass();
		$order->totals->base->item		= (float) $order->total_base_item;
		$order->totals->base->shipping	= (float) $order->total_base_shipping;
		$order->totals->base->tax		= (float) $order->total_base_tax;
		$order->totals->base->grand		= (float) $order->total_base_grand;

		$order->totals->base_formatted				= new stdClass();
		$order->totals->base_formatted->item		= $this->shop_currency_model->format_base( $order->totals->base->item );
		$order->totals->base_formatted->shipping	= $this->shop_currency_model->format_base( $order->totals->base->shipping );
		$order->totals->base_formatted->tax			= $this->shop_currency_model->format_base( $order->totals->base->tax );
		$order->totals->base_formatted->grand		= $this->shop_currency_model->format_base( $order->totals->base->grand );

		$order->totals->user			= new stdClass();
		$order->totals->user->item		= (float) $order->total_user_item;
		$order->totals->user->shipping	= (float) $order->total_user_shipping;
		$order->totals->user->tax		= (float) $order->total_user_tax;
		$order->totals->user->grand		= (float) $order->total_user_grand;

		$order->totals->user_formatted				= new stdClass();
		$order->totals->user_formatted->item		= $this->shop_currency_model->format_user( $order->totals->user->item );
		$order->totals->user_formatted->shipping	= $this->shop_currency_model->format_user( $order->totals->user->shipping );
		$order->totals->user_formatted->tax			= $this->shop_currency_model->format_user( $order->totals->user->tax );
		$order->totals->user_formatted->grand		= $this->shop_currency_model->format_user( $order->totals->user->grand );

		unset( $order->total_base_item );
		unset( $order->total_base_shipping );
		unset( $order->total_base_tax );
		unset( $order->total_base_grand );
		unset( $order->total_user_item );
		unset( $order->total_user_shipping );
		unset( $order->total_user_tax );
		unset( $order->total_user_grand );

		// --------------------------------------------------------------------------

		//	Shipping details
		$order->shipping_address 			= new stdClass();
		$order->shipping_address->line_1	= $order->shipping_line_1;
		$order->shipping_address->line_2	= $order->shipping_line_2;
		$order->shipping_address->town		= $order->shipping_town;
		$order->shipping_address->state		= $order->shipping_state;
		$order->shipping_address->postcode	= $order->shipping_postcode;
		$order->shipping_address->country	= $order->shipping_country;

		unset( $order->shipping_line_1 );
		unset( $order->shipping_line_2 );
		unset( $order->shipping_town );
		unset( $order->shipping_state );
		unset( $order->shipping_postcode );
		unset( $order->shipping_country );

		$order->billing_address 			= new stdClass();
		$order->billing_address->line_1		= $order->billing_line_1;
		$order->billing_address->line_2		= $order->billing_line_2;
		$order->billing_address->town		= $order->billing_town;
		$order->billing_address->state		= $order->billing_state;
		$order->billing_address->postcode	= $order->billing_postcode;
		$order->billing_address->country	= $order->billing_country;

		unset( $order->billing_line_1 );
		unset( $order->billing_line_2 );
		unset( $order->billing_town );
		unset( $order->billing_state );
		unset( $order->billing_postcode );
		unset( $order->billing_country );

		// --------------------------------------------------------------------------

		//	Vouchers
		if ( $order->voucher_id ) :

			$order->voucher							= new stdClass();
			$order->voucher->id						= (int) $order->voucher_id;
			$order->voucher->code					= $order->v_code;
			$order->voucher->label					= $order->v_label;
			$order->voucher->type					= $order->v_type;
			$order->voucher->discount_type			= $order->v_discount_type;
			$order->voucher->discount_value			= (float) $order->v_discount_value;
			$order->voucher->discount_application	= $order->v_discount_application;
			$order->voucher->product_type_id		= (int) $order->v_product_type_id;
			$order->voucher->valid_from				= $order->v_valid_from;
			$order->voucher->valid_to				= $order->v_valid_to;
			$order->voucher->is_active				= (bool) $order->v_is_active;
			$order->voucher->is_deleted				= (bool) $order->v_is_deleted;

		else :

			$order->voucher							= FALSE;

		endif;

		unset( $order->voucher_id );
		unset( $order->v_code );
		unset( $order->v_label );
		unset( $order->v_type );
		unset( $order->v_discount_type );
		unset( $order->v_discount_value );
		unset( $order->v_discount_application );
		unset( $order->v_product_type_id );
		unset( $order->v_valid_from );
		unset( $order->v_valid_to );
		unset( $order->v_is_active );
		unset( $order->v_is_deleted );
	}


	// --------------------------------------------------------------------------


	protected function _format_item( &$item )
	{
		$item->id		= (int) $item->id;
		$item->sku		= $item->v_sku;
		$item->quantity	= (int) $item->quantity;

		unset($item->v_sku);

		// --------------------------------------------------------------------------

		$item->price						= new stdClass();
		$item->price->base					= new stdClass();
		$item->price->base->value			= (float) $item->price_base_value;
		$item->price->base->value_inc_tax	= (float) $item->price_base_value_inc_tax;
		$item->price->base->value_ex_tax	= (float) $item->price_base_value_ex_tax;
		$item->price->base->value_tax		= (float) $item->price_base_value_tax;

		$item->price->base_formatted				= new stdClass();
		$item->price->base_formatted->value			= $this->shop_currency_model->format_base( $item->price->base->value );
		$item->price->base_formatted->value_inc_tax	= $this->shop_currency_model->format_base( $item->price->base->value_inc_tax );
		$item->price->base_formatted->value_ex_tax	= $this->shop_currency_model->format_base( $item->price->base->value_ex_tax );
		$item->price->base_formatted->value_tax		= $this->shop_currency_model->format_base( $item->price->base->value_tax );

		$item->price->user					= new stdClass();
		$item->price->user->value			= (float) $item->price_user_value;
		$item->price->user->value_inc_tax	= (float) $item->price_user_value_inc_tax;
		$item->price->user->value_ex_tax	= (float) $item->price_user_value_ex_tax;
		$item->price->user->value_tax		= (float) $item->price_user_value_tax;

		$item->price->user_formatted				= new stdClass();
		$item->price->user_formatted->value			= $this->shop_currency_model->format_user( $item->price->user->value );
		$item->price->user_formatted->value_inc_tax	= $this->shop_currency_model->format_user( $item->price->user->value_inc_tax );
		$item->price->user_formatted->value_ex_tax	= $this->shop_currency_model->format_user( $item->price->user->value_ex_tax );
		$item->price->user_formatted->value_tax		= $this->shop_currency_model->format_user( $item->price->user->value_tax );

		$item->sale_price						= new stdClass();
		$item->sale_price->base					= new stdClass();
		$item->sale_price->base->value			= (float) $item->sale_price_base_value;
		$item->sale_price->base->value_inc_tax	= (float) $item->sale_price_base_value_inc_tax;
		$item->sale_price->base->value_ex_tax	= (float) $item->sale_price_base_value_ex_tax;
		$item->sale_price->base->value_tax		= (float) $item->sale_price_base_value_tax;

		$item->sale_price->base_formatted					= new stdClass();
		$item->sale_price->base_formatted->value			= $this->shop_currency_model->format_base( $item->sale_price->base->value );
		$item->sale_price->base_formatted->value_inc_tax	= $this->shop_currency_model->format_base( $item->sale_price->base->value_inc_tax );
		$item->sale_price->base_formatted->value_ex_tax		= $this->shop_currency_model->format_base( $item->sale_price->base->value_ex_tax );
		$item->sale_price->base_formatted->value_tax		= $this->shop_currency_model->format_base( $item->sale_price->base->value_tax );

		$item->sale_price->user					= new stdClass();
		$item->sale_price->user->value			= (float) $item->sale_price_user_value;
		$item->sale_price->user->value_inc_tax	= (float) $item->sale_price_user_value_inc_tax;
		$item->sale_price->user->value_ex_tax	= (float) $item->sale_price_user_value_ex_tax;
		$item->sale_price->user->value_tax		= (float) $item->sale_price_user_value_tax;

		$item->sale_price->user_formatted					= new stdClass();
		$item->sale_price->user_formatted->value			= $this->shop_currency_model->format_user( $item->sale_price->user->value );
		$item->sale_price->user_formatted->value_inc_tax	= $this->shop_currency_model->format_user( $item->sale_price->user->value_inc_tax );
		$item->sale_price->user_formatted->value_ex_tax		= $this->shop_currency_model->format_user( $item->sale_price->user->value_ex_tax );
		$item->sale_price->user_formatted->value_tax		= $this->shop_currency_model->format_user( $item->sale_price->user->value_tax );

		$item->processed	= (bool) $item->processed;
		$item->refunded		= (bool) $item->refunded;

		unset( $item->price_base_value );
		unset( $item->price_base_value_inc_tax );
		unset( $item->price_base_value_ex_tax );
		unset( $item->price_base_value_tax );
		unset( $item->price_user_value );
		unset( $item->price_user_value_inc_tax );
		unset( $item->price_user_value_ex_tax );
		unset( $item->price_user_value_tax );
		unset( $item->sale_price_base_value );
		unset( $item->sale_price_base_value_inc_tax );
		unset( $item->sale_price_base_value_ex_tax );
		unset( $item->sale_price_base_value_tax );
		unset( $item->sale_price_user_value );
		unset( $item->sale_price_user_value_inc_tax );
		unset( $item->sale_price_user_value_ex_tax );
		unset( $item->sale_price_user_value_tax );


		// --------------------------------------------------------------------------

		//	Product type
		$item->type				= new stdClass();
		$item->type->id			= (int) $item->pt_id;
		$item->type->label		= $item->pt_label;
		$item->type->ipn_method	= $item->pt_ipn_method;

		unset( $item->pt_id );
		unset( $item->pt_label );
		unset( $item->pt_ipn_method );

		// --------------------------------------------------------------------------

		//	Tax rate
		$item->tax_rate				= new stdClass();
		$item->tax_rate->id			= (int) $item->tax_rate_id;
		$item->tax_rate->label		= $item->tax_rate_label;
		$item->tax_rate->rate		= (float) $item->tax_rate_rate;

		unset( $item->tax_rate_id );
		unset( $item->tax_rate_label );
		unset( $item->tax_rate_rate );

		// --------------------------------------------------------------------------

		//	Meta
		unset( $item->meta->id );
		unset( $item->meta->product_id );

		// --------------------------------------------------------------------------

		//	Extra data
		$item->extra_data = $item->extra_data ? @unserialize( $item->extra_data ) : NULL;
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

if ( ! defined( 'NAILS_ALLOW_EXTENSION_SHOP_ORDER_MODEL' ) ) :

	class Shop_order_model extends NAILS_Shop_order_model
	{
	}

endif;

/* End of file shop_order_model.php */
/* Location: ./modules/shop/models/shop_order_model.php */