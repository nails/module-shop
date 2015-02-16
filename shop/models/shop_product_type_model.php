<?php

/**
 * This model manages Shop Product Types
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Shop_product_type_model extends NAILS_Model
{
    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();

        $this->_table              = NAILS_DB_PREFIX . 'shop_product_type';
        $this->_table_prefix       = 'spt';
        $this->_destructive_delete = false;
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

        if (!empty($data['include_count'])) {

            if (empty($this->db->ar_select)) {

                //  No selects have been called, call this so that we don't *just* get the product count
                $this->db->select($this->_table_prefix . '.*');
            }

            $this->db->select('(SELECT COUNT(*) FROM ' . NAILS_DB_PREFIX .  'shop_product WHERE type_id = ' . $this->_table_prefix . '.id) product_count');
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
            $data['or_like'][] = array(
                'column' => $this->_table_prefix . '.description',
                'value'  => $data['keywords']
            );
        }

        // --------------------------------------------------------------------------

        parent::_getcount_common($data, $_caller);
    }
}

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_PRODUCT_TYPE_MODEL')) {

    class Shop_product_type_model extends NAILS_Shop_product_type_model
    {
    }
}
