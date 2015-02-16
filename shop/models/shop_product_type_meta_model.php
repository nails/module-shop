<?php

/**
 * This model manages Shop Product Type Meta fields
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Shop_product_type_meta_model extends NAILS_Model
{
    /**
     * construct the model
     */
    public function __construct()
    {
        parent::__construct();

        $this->_table                 = NAILS_DB_PREFIX . 'shop_product_type_meta_field';
        $this->_table_prefix          = 'ptmf';
        $this->_table_taxonomy        = NAILS_DB_PREFIX . 'shop_product_type_meta_taxonomy';
        $this->_table_taxonomy_prefix = 'ptmt';
    }

    // --------------------------------------------------------------------------


    /**
     * Fetches all meta fields, optionally paginated.
     * @param int    $page           The page number of the results, if null then no pagination
     * @param int    $perPage        How many items per page of paginated results
     * @param mixed  $data           Any data to pass to _getcount_common()
     * @param bool   $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @param string $_caller        Internal flag to pass to _getcount_common(), contains the calling method
     * @return array
     **/
    public function get_all($page = null, $per_page = null, $data = array(), $include_deleted = false, $_caller = 'GET_ALL')
    {
        $fields = parent::get_all($page, $per_page, $data, $include_deleted, $_caller);

        //  Handle requests for the raw query object
        if (!empty($data['RETURN_QUERY_OBJECT'])) {

            return $fields;
        }

        foreach ($fields as $field) {

            if (isset($data['includeAssociatedProductTypes'])) {

                $this->db->select('pt.id,pt.label');
                $this->db->join(NAILS_DB_PREFIX . 'shop_product_type pt', 'pt.id = ' . $this->_table_taxonomy_prefix . '.product_type_id');
                $this->db->group_by('pt.id');
                $this->db->order_by('pt.label');
                $this->db->where($this->_table_taxonomy_prefix . '.meta_field_id', $field->id);
                $field->associated_product_types = $this->db->get($this->_table_taxonomy . ' ' . $this->_table_taxonomy_prefix)->result();
            }
        }

        return $fields;
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     * @param array  $data    Data passed from the calling method
     * @param string $_caller The name of the calling method
     * @return void
     **/
    protected function _getcount_common($data = array(), $_caller = null)
    {
        //  Default sort
        if (empty($data['sort'])) {

            if (empty($data['sort'])) {

                $data['sort'] = array();
            }

            $data['sort'][] = array($this->_table_prefix . '.label', 'ASC');
        }

        // --------------------------------------------------------------------------

        //  Search
        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = array();
            }

            $data['or_like'][] = array(
                'column' => $this->_table_prefix . '.label',
                'value'  => $data['keywords']
            );
        }

        // --------------------------------------------------------------------------

        parent::_getcount_common($data, $_caller);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns meta fields which apply to a particular product type
     * @param  integer $typeId The type's ID
     * @return array
     */
    public function getByProductTypeId($typeId)
    {
        $this->db->where($this->_table_taxonomy_prefix . '.product_type_id', $typeId);
        return $this->getByProductType();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns meta fields which apply to a particular group of product types
     * @param  array $typeIds An array of IDs
     * @return array
     */
    public function getByProductTypeIds($typeIds)
    {
        $this->db->where_in($this->_table_taxonomy_prefix . '.product_type_id', $typeIds);
        return $this->getByProductType();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of meta fields which apply to a product type, abstracted
     * by getByProductTypeId() and getByProductTypeIds()
     * @return array
     */
    protected function getByProductType()
    {
        $this->db->select($this->_table_prefix . '.*');
        $this->db->join($this->_table  . ' ' . $this->_table_prefix, $this->_table_prefix . '.id = ' . $this->_table_taxonomy_prefix . '.meta_field_id');
        $this->db->group_by($this->_table_prefix . '.id');
        $result = $this->db->get($this->_table_taxonomy . ' ' . $this->_table_taxonomy_prefix)->result();

        foreach ($result as $result) {

            $this->_format_object($result);
        }

        return $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new product type meta field
     * @param  array   $data         The data array to create the product type meta field with
     * @param  boolean $returnObject Whether to return the full object or just the ID
     * @return mixed
     */
    public function create($data = array(), $returnObject = false)
    {
        $this->db->trans_begin();

        $associatedProductTypes = isset($data['associated_product_types']) ? $data['associated_product_types'] : array();
        unset($data['associated_product_types']);

        $result = parent::create($data, $returnObject);

        if (!$result) {

            $this->_set_error('Failed to create parent object.');
            $this->db->trans_rollback();
            return false;
        }

        $id = $this->db->insert_id();

        if ($associatedProductTypes) {

            $data = array();

            foreach ($associatedProductTypes as $productTypeId) {

                $data[] = array(
                    'product_type_id' => $productTypeId,
                    'meta_field_id'   => $id
                );
            }

            if (!$this->db->insert_batch($this->_table_taxonomy, $data)) {

                $this->_set_error('Failed to add new product type/meta field relationships.');
                $this->db->trans_rollback();
                return false;
            }
        }

        $this->db->trans_commit();
        return $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Update a product type meta field
     * @param  integer $id   The product type meta field's ID
     * @param  array   $data The data array to use in the update
     * @return boolean
     */
    public function update($id, $data = array())
    {
        $this->db->trans_begin();

        $associatedProductTypes = isset($data['associated_product_types']) ? $data['associated_product_types'] : array();
        unset($data['associated_product_types']);

        $result = parent::update($id, $data);

        if (!$result) {

            $this->_set_error('Failed to update parent object.');
            $this->db->trans_rollback();
            return false;
        }

        $this->db->where('meta_field_id', $id);
        if (!$this->db->delete($this->_table_taxonomy)) {

            $this->_set_error('Failed to remove existing product type/meta field relationships.');
            $this->db->trans_rollback();
            return false;
        }

        if ($associatedProductTypes) {

            $data = array();

            foreach ($associatedProductTypes as $productTypeId) {

                $data[] = array(
                    'product_type_id' => $productTypeId,
                    'meta_field_id'   => $id
                );
            }

            if (!$this->db->insert_batch($this->_table_taxonomy, $data)) {

                $this->_set_error('Failed to add new product type/meta field relationships.');
                $this->db->trans_rollback();
                return false;
            }
        }

        $this->db->trans_commit();
        return true;
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_PRODUCT_TYPE_META_MODEL')) {

    class Shop_product_type_meta_model extends NAILS_Shop_product_type_meta_model
    {
    }
}
