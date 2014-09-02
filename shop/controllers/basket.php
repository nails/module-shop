<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:		Shop - Basket
 *
 * Description:	This controller handles the user's basket
 *
 **/

/**
 * OVERLOADING NAILS' AUTH MODULE
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

//	Include _shop.php; executes common functionality
require_once '_shop.php';

class NAILS_Basket extends NAILS_Shop_Controller
{
	public function __construct()
	{
		parent::__construct();

		// --------------------------------------------------------------------------

		$this->data['return'] = $this->input->get( 'return' ) ? $this->input->get_post( 'return' ) : $this->_shop_url . 'basket';
	}


	// --------------------------------------------------------------------------


	/**
	 * Render the user's basket
	 *
	 * @access	public
	 * @return	void
	 *
	 **/
	public function index()
	{
		$this->data['page']->title = $this->_shop_name . ': Your Basket';

		// --------------------------------------------------------------------------

		$this->data['basket'] = $this->shop_basket_model->get();

		if ( ! empty( $this->data['basket']->items_removed ) && empty( $this->data['message'] ) ) :

			if ( $this->data['basket']->items_removed > 1 ) :

				$this->data['message'] = '<strong>Some items were removed.</strong> ' . $this->data['basket']->items_removed . ' items were removed from your basket because they are no longer available.';

			else :

				$this->data['message'] = '<strong>Some items were removed.</strong> An item was removed from your basket because it is no longer available.';

			endif;


		endif;

		// --------------------------------------------------------------------------

		//	Continue shopping URL
		//	Skins can render a button which takes the user to a sensible place to keep shopping

		$this->data['continue_shopping_url'] = $this->_shop_url;

		//	Most recently viewed item
		$_recently_viewed = $this->shop_product_model->get_recently_viewed();

		if ( ! empty( $_recently_viewed ) ) :

			$_product_id = end( $_recently_viewed );
			$_product		= $this->shop_product_model->get_by_id( $_product_id );

			if ( $_product && $_product->is_active ) :

				$this->data['continue_shopping_url'] .= 'product/' . $_product->slug;


			endif;

		endif;

		// --------------------------------------------------------------------------

		//	Other recently viewed items
		$this->data['recently_viewed'] = array();
		if ( ! empty( $_recently_viewed ) ) :

			$this->data['recently_viewed'] = $this->shop_product_model->get_by_ids( $_recently_viewed );

		endif;

		// --------------------------------------------------------------------------

		$this->load->view( 'structure/header',							$this->data );
		$this->load->view( $this->_skin->path . 'views/basket/index',	$this->data );
		$this->load->view( 'structure/footer',							$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Adds an item to the user's basket (fall back for when JS is not available)
	 *
	 * @access	public
	 * @return	void
	 *
	 **/
	public function add()
	{
		$_variant_id	= $this->input->get_post( 'variant_id' );
		$_quantity		= $this->input->get_post( 'quantity' ) ? $this->input->get_post( 'quantity' ) : 1;

		if ( $this->shop_basket_model->add( $_variant_id, $_quantity ) ) :

			$this->session->set_flashdata( 'success', '<strong>Success!</strong> Item was added to your basket.' );

		else :

			$this->session->set_flashdata( 'error', '<strong>Sorry,</strong> there was a problem adding to your basket: ' . $this->shop_basket_model->last_error() );

		endif;

		// --------------------------------------------------------------------------

		redirect( $this->data['return'] );
	}


	// --------------------------------------------------------------------------


	/**
	 * Removes an item from the user's basket (fall back for when JS is not available)
	 *
	 * @access	public
	 * @return	void
	 *
	 **/
	public function remove()
	{
		$_variant_id = $this->input->get_post( 'variant_id' );

		if ( $this->shop_basket_model->remove( $_variant_id ) ) :

			$this->session->set_flashdata( 'success', '<strong>Success!</strong> Item was removed from your basket.' );

		else :

			$this->session->set_flashdata( 'error', '<strong>Sorry,</strong> there was a problem removing the item from your basket: ' . $this->shop_basket_model->last_error() );

		endif;

		// --------------------------------------------------------------------------

		redirect( $this->data['return'] );
	}


	// --------------------------------------------------------------------------


	/**
	 * Empties a user's basket
	 *
	 * @access	public
	 * @return	void
	 *
	 **/
	public function destroy()
	{
		$this->shop_basket_model->destroy();

		// --------------------------------------------------------------------------

		redirect( $this->data['return'] );
	}


	// --------------------------------------------------------------------------


	/**
	 * Increment an item in the user's basket (fall back for when JS is not available)
	 *
	 * @access	public
	 * @return	void
	 *
	 **/
	public function increment()
	{
		$_variant_id = $this->input->get_post( 'variant_id' );

		if ( $this->shop_basket_model->increment( $_variant_id ) ) :

			$this->session->set_flashdata( 'success', '<strong>Success!</strong> Quantity adjusted!' );

		else :

			$this->session->set_flashdata( 'error', '<strong>Sorry,</strong> could not adjust quantity. ' . $this->shop_basket_model->last_error() );

		endif;

		// --------------------------------------------------------------------------

		redirect( $this->data['return'] );
	}


	// --------------------------------------------------------------------------


	/**
	 * Decrement an item in the user's basket (fall back for when JS is not available)
	 *
	 * @access	public
	 * @return	void
	 *
	 **/
	public function decrement()
	{
		$_variant_id = $this->input->get_post( 'variant_id' );

		if ( $this->shop_basket_model->decrement( $_variant_id ) ) :

			$this->session->set_flashdata( 'success', '<strong>Success!</strong> Quantity adjusted!' );

		else :

			$this->session->set_flashdata( 'error', '<strong>Sorry,</strong> could not adjust quantity. ' . $this->shop_basket_model->last_error() );

		endif;

		// --------------------------------------------------------------------------

		redirect( $this->data['return'] );
	}


	// --------------------------------------------------------------------------


	/**
	 * Validate and add a voucher to a basket
	 *
	 * @access	public
	 * @return	void
	 *
	 **/
	public function add_voucher()
	{
		$_voucher = $this->shop_voucher_model->validate( $this->input->get_post( 'voucher' ), get_basket() );

		if ( $_voucher ) :

			//	Validated, add to basket
			$this->session->set_flashdata( 'success', '<strong>Success!</strong> Voucher has been applied to your basket.' );
			$this->shop_basket_model->add_voucher( $_voucher->code );

		else :

			//	Failed to validate, feedback
			$this->session->set_flashdata( 'error', '<strong>Sorry,</strong> that voucher is not valid. ' . $this->shop_voucher_model->last_error() );

		endif;

		// --------------------------------------------------------------------------

		redirect( $this->data['return'] );
	}


	// --------------------------------------------------------------------------


	/**
	 * Remove any associated voucher from the user's basket
	 *
	 * @access	public
	 * @return	void
	 *
	 **/
	public function remove_voucher()
	{
		$this->shop_basket_model->remove_voucher();
		$this->session->set_flashdata( 'success', '<strong>Success!</strong> Your voucher was removed.' );

		// --------------------------------------------------------------------------

		redirect( $this->data['return'] );
	}


	// --------------------------------------------------------------------------


	/**
	 * Set the user's preferred currency
	 *
	 * @access	public
	 * @return	void
	 *
	 **/
	public function set_currency()
	{
		$_currency = $this->shop_currency_model->get_by_code( $this->input->get_post( 'currency' ) );

		if ( $_currency ) :

			//	Valid currency
			$this->session->set_userdata( 'shop_currency', $_currency->code );

			if ( $this->user_model->is_logged_in() ) :

				//	Save to the user object
				$this->user_model->update( active_user( 'id' ), array( 'shop_currency' => $_currency->code ) );

			endif;

			$this->session->set_flashdata( 'success', '<strong>Success!</strong> Your currency has been updated.' );

		else :

			//	Failed to validate, feedback
			$this->session->set_flashdata( 'error', '<strong>Sorry,</strong> that currency is not supported.' );

		endif;

		// --------------------------------------------------------------------------

		redirect( $this->data['return'] );
	}
}


// --------------------------------------------------------------------------


/**
 * OVERLOADING NAILS' SHOP MODULE
 *
 * The following block of code makes it simple to extend one of the core shop
 * controllers. Some might argue it's a little hacky but it's a simple 'fix'
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

if ( ! defined( 'NAILS_ALLOW_EXTENSION_BASKET' ) ) :

	class Basket extends NAILS_Basket
	{
	}

endif;

/* End of file basket.php */
/* Location: ./modules/shop/controllers/basket.php */