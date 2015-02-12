<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:			shop_product_type_meta_model.php
 *
 * Description:		This model handles everything to do with product tyoe meta types
 *
 **/

/**
 * OVERLOADING NAILS' MODELS
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

class NAILS_Shop_product_type_meta_model extends NAILS_Model
{
	public function __construct()
	{
		parent::__construct();

		$this->_table					= NAILS_DB_PREFIX . 'shop_product_type_meta_field';
		$this->_table_prefix			= 'ptmf';
		$this->_table_taxonomy			= NAILS_DB_PREFIX . 'shop_product_type_meta_taxonomy';
		$this->_table_taxonomy_prefix	= 'ptmt';
	}


	// --------------------------------------------------------------------------


	public function get_all( $page = NULL, $per_page = NULL, $data = array(), $include_deleted = FALSE, $_caller = 'GET_ALL' )
	{
		$_result = parent::get_all( $page, $per_page, $data, $include_deleted, $_caller );

        //  Handle requests for the raw query object
        if (!empty($data['RETURN_QUERY_OBJECT'])) {

            return $_result;
        }

		foreach ( $_result as $result ) :

			if ( isset( $data['include_associated_product_types'] ) ) :

				$this->db->select( 'pt.id,pt.label' );
				$this->db->join( NAILS_DB_PREFIX . 'shop_product_type pt', 'pt.id = ' . $this->_table_taxonomy_prefix . '.product_type_id' );
				$this->db->group_by( 'pt.id' );
				$this->db->order_by( 'pt.label' );
				$this->db->where( $this->_table_taxonomy_prefix . '.meta_field_id', $result->id );
				$result->associated_product_types = $this->db->get( $this->_table_taxonomy . ' ' . $this->_table_taxonomy_prefix )->result();

			endif;

		endforeach;

		return $_result;
	}


	// --------------------------------------------------------------------------


	public function get_by_product_type_id( $type_id )
	{
		$this->db->where( $this->_table_taxonomy_prefix . '.product_type_id', $type_id );
		return $this->_get_by_product_type();
	}


	// --------------------------------------------------------------------------


	public function get_by_product_type_ids( $type_ids )
	{
		$this->db->where_in( $this->_table_taxonomy_prefix . '.product_type_id', $type_ids );
		return $this->_get_by_product_type();
	}


	// --------------------------------------------------------------------------


	protected function _get_by_product_type()
	{
		$this->db->select( $this->_table_prefix . '.*' );
		$this->db->join( $this->_table  . ' ' . $this->_table_prefix, $this->_table_prefix . '.id = ' . $this->_table_taxonomy_prefix . '.meta_field_id' );
		$this->db->group_by( $this->_table_prefix . '.id' );
		$_result = $this->db->get( $this->_table_taxonomy . ' ' . $this->_table_taxonomy_prefix )->result();

		foreach ( $_result as $result ) :

			$this->_format_object( $result );

		endforeach;

		return $_result;
	}


	// --------------------------------------------------------------------------


	public function create( $data = array(), $return_object = FALSE )
	{
		$this->db->trans_begin();

		$_associated_product_types = isset( $data->associated_product_types ) ? $data->associated_product_types : array();
		unset( $data->associated_product_types );

		$_result = parent::create( $data );

		if ( ! $_result ) :

			$this->_set_error( 'Failed to create parent object.' );
			$this->db->trans_rollback();
			return FALSE;

		endif;

		$_id = $this->db->insert_id();

		if ( $_associated_product_types ) :

			$_data = array();

			foreach ( $_associated_product_types as $product_type_id ) :

				$_data[] = array(
					'product_type_id'	=> $product_type_id,
					'meta_field_id'		=> $_id
				);

			endforeach;

			if ( ! $this->db->insert_batch( $this->_table_taxonomy, $_data ) ) :

				$this->_set_error( 'Failed to add new product type/meta field relationships.' );
				$this->db->trans_rollback();
				return FALSE;

			endif;

		endif;

		$this->db->trans_commit();
		return $_result;
	}


	// --------------------------------------------------------------------------


	public function update( $id, $data = array() )
	{
		$this->db->trans_begin();

		$_associated_product_types = isset( $data->associated_product_types ) ? $data->associated_product_types : array();
		unset( $data->associated_product_types );

		$_result = parent::update( $id, $data );

		if ( ! $_result ) :

			$this->_set_error( 'Failed to update parent object.' );
			$this->db->trans_rollback();
			return FALSE;

		endif;

		$this->db->where( 'meta_field_id', $id );
		if ( ! $this->db->delete( $this->_table_taxonomy ) ) :

			$this->_set_error( 'Failed to remove existing product type/meta field relationships.' );
			$this->db->trans_rollback();
			return FALSE;

		endif;

		if ( $_associated_product_types ) :

			$_data = array();

			foreach ( $_associated_product_types as $product_type_id ) :

				$_data[] = array(
					'product_type_id'	=> $product_type_id,
					'meta_field_id'		=> $id
				);

			endforeach;

			if ( ! $this->db->insert_batch( $this->_table_taxonomy, $_data ) ) :

				$this->_set_error( 'Failed to add new product type/meta field relationships.' );
				$this->db->trans_rollback();
				return FALSE;

			endif;

		endif;

		$this->db->trans_commit();
		return TRUE;
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

if ( ! defined( 'NAILS_ALLOW_EXTENSION_SHOP_PRODUCT_TYPE_META_MODEL' ) ) :

	class Shop_product_type_meta_model extends NAILS_Shop_product_type_meta_model
	{
	}

endif;

/* End of file shop_product_type_meta_model.php */
/* Location: ./modules/shop/models/shop_product_type_meta_model.php */