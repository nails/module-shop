<?php

/**
 * This model manages Shop Product Tax Rates
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Shop_tax_rate_model extends NAILS_Model
{
    public function __construct()
    {
        parent::__construct();

        $this->_table               = NAILS_DB_PREFIX . 'shop_tax_rate';
        $this->_table_prefix        = 'str';
        $this->_destructive_delete  = false;
    }


    // --------------------------------------------------------------------------


    protected function _getcount_common($data = array(), $_caller = null)
    {
        if (empty($data['sort'])) {

            $data['sort'] = 'label';

        } else {

            $data = array('sort' => 'label');

        }

        // --------------------------------------------------------------------------

        if (!empty($data['include_count'])) {

            if (empty($this->db->ar_select)) {

                //  No selects have been called, call this so that we don't *just* get the product count
                $_prefix = $this->_table_prefix ? $this->_table_prefix . '.' : '';
                $this->db->select($_prefix . '*');

            }

            $this->db->select('(SELECT COUNT(*) FROM ' . NAILS_DB_PREFIX .  'shop_product WHERE tax_rate_id = ' . $this->_table_prefix . '.id) product_count');

        }

        // --------------------------------------------------------------------------

        return parent::_getcount_common($data, $_caller);
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_TAX_RATE_MODEL')) {

    class Shop_tax_rate_model extends NAILS_Shop_tax_rate_model
    {
    }

}

/* End of file shop_tax_rate_model.php */
/* Location: ./modules/shop/models/shop_tax_rate_model.php */