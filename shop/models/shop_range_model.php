<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:			shop_range_model.php
 *
 * Description:		This model handles interfacing with shop rages
 *
 **/

/**
 * OVERLOADING NAILS' MODELS
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

class NAILS_Shop_range_model extends NAILS_Model
{
	public function __construct()
	{
		parent::__construct();

		$this->_table			= NAILS_DB_PREFIX . 'shop_range';
		$this->_table_prefix	= 'sr';
	}


	// --------------------------------------------------------------------------


	protected function _getcount_common( $data = array(), $_caller = NULL )
	{
		if ( empty( $data['sort'] ) ) :

			$data['sort'] = 'label';

		else :

			$data = array( 'sort' => 'label' );

		endif;

		// --------------------------------------------------------------------------

		//	Only include active items?
		if ( isset( $data['only_active'] ) ) :

			$_only_active = (bool) $data['only_active'];

		else :

			$_only_active = TRUE;

		endif;

		if ( $_only_active ) :

			if ( ! isset( $data['where'] ) ) :

				$data['where'] = array();

			endif;

			if ( is_array( $data['where'] ) ) :

				$data['where'][] = array( 'is_active', TRUE );

			elseif ( is_string( $data['where'] ) ) :

				$data['where'] .= ' AND ' . $this->_table_prefix . '.is_active = 1';

			endif;

		endif;

		// --------------------------------------------------------------------------

		if ( ! empty( $data['include_count'] ) ) :

			if ( empty( $this->db->ar_select ) ) :

				//	No selects have been called, call this so that we don't *just* get the product count
				$_prefix = $this->_table_prefix ? $this->_table_prefix . '.' : '';
				$this->db->select( $_prefix . '*' );

			endif;

			$this->db->select( '(SELECT COUNT(*) FROM ' . NAILS_DB_PREFIX .  'shop_product_range WHERE range_id = ' . $this->_table_prefix . '.id) product_count' );

		endif;

		// --------------------------------------------------------------------------

		return parent::_getcount_common( $data, $_caller );
	}


	// --------------------------------------------------------------------------


	public function get_by_id( $id, $data = array() )
	{
		if ( ! isset( $data['only_active'] ) ) :

			$data['only_active'] = FALSE;

		endif;

		// --------------------------------------------------------------------------

		return parent::get_by_id( $id, $data );
	}


	// --------------------------------------------------------------------------


	public function get_by_ids( $ids, $data = array() )
	{
		if ( ! isset( $data['only_active'] ) ) :

			$data['only_active'] = FALSE;

		endif;

		// --------------------------------------------------------------------------

		return parent::get_by_ids( $ids, $data );
	}


	// --------------------------------------------------------------------------


	public function get_by_slug( $slug, $data = array() )
	{
		if ( ! isset( $data['only_active'] ) ) :

			$data['only_active'] = FALSE;

		endif;

		// --------------------------------------------------------------------------

		return parent::get_by_slug( $slug, $data );
	}


	// --------------------------------------------------------------------------


	public function get_by_slugs( $slugs, $data = array() )
	{
		if ( ! isset( $data['only_active'] ) ) :

			$data['only_active'] = FALSE;

		endif;

		// --------------------------------------------------------------------------

		return parent::get_by_slugs( $slugs, $data );
	}


	// --------------------------------------------------------------------------


	public function get_by_id_or_slug( $id_slug, $data = array() )
	{
		if ( ! isset( $data['only_active'] ) ) :

			$data['only_active'] = FALSE;

		endif;

		// --------------------------------------------------------------------------

		return parent::get_by_id_or_slug( $id_slug, $data );
	}


	// --------------------------------------------------------------------------


	public function create( $data = array(), $return_object = FALSE )
	{
		if ( ! empty( $data->label ) ) :

			$data->slug = $this->_generate_slug( $data->label );

		endif;

		return parent::create( $data, $return_object );
	}


	// --------------------------------------------------------------------------


	public function update( $id, $data = array() )
	{
		if ( ! empty( $data->label ) ) :

			$data->slug = $this->_generate_slug( $data->label, '', '', NULL, NULL, $id );

		endif;

		return parent::update( $id, $data );
	}


	// --------------------------------------------------------------------------


	public function format_url( $slug )
	{
		return site_url( app_setting( 'url', 'shop' ) . 'range/' . $slug );
	}


	// --------------------------------------------------------------------------


	protected function _format_object( &$object )
	{
		//	Type casting
		$object->id				= (int) $object->id;
		$object->created_by		= $object->created_by ? (int) $object->created_by : NULL;
		$object->modified_by	= $object->modified_by ? (int) $object->modified_by : NULL;
		$object->url			= $this->format_url( $object->slug );
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

if ( ! defined( 'NAILS_ALLOW_EXTENSION_SHOP_RANGE_MODEL' ) ) :

	class Shop_range_model extends NAILS_Shop_range_model
	{
	}

endif;

/* End of file shop_range_model.php */
/* Location: ./modules/shop/models/shop_range_model.php */