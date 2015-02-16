<?php

/**
 * This model manages Shop Product ranges
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Shop_range_model extends NAILS_Model
{
    public function __construct()
    {
        parent::__construct();

        $this->_table           = NAILS_DB_PREFIX . 'shop_range';
        $this->_table_prefix    = 'sr';

        // --------------------------------------------------------------------------

        //  Shop's base URL
        $this->shopUrl = $this->shop_model->getShopUrl();
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

        //  Only include active items?
        if (isset($data['only_active'])) {

            $_only_active = (bool) $data['only_active'];

        } else {

            $_only_active = true;

        }

        if ($_only_active) {

            if (!isset($data['where'])) {

                $data['where'] = array();

            }

            if (is_array($data['where'])) {

                $data['where'][] = array('is_active', true);

            } elseif (is_string($data['where'])) {

                $data['where'] .= ' AND ' . $this->_table_prefix . '.is_active = 1';

            }

        }

        // --------------------------------------------------------------------------

        if (!empty($data['include_count'])) {

            if (empty($this->db->ar_select)) {

                //  No selects have been called, call this so that we don't *just* get the product count
                $_prefix = $this->_table_prefix ? $this->_table_prefix . '.' : '';
                $this->db->select($_prefix . '*');

            }

            $query  = 'SELECT COUNT(DISTINCT(`nspr`.`product_id`)) ';
            $query .= 'FROM ' . NAILS_DB_PREFIX . 'shop_product_range nspr ';
            $query .= 'JOIN ' . NAILS_DB_PREFIX . 'shop_product nsp ON `nspr`.`product_id` = `nsp`.`id` ';
            $query .= 'WHERE ';
            $query .= '`nspr`.`range_id` = `' . $this->_table_prefix . '`.`id` ';
            $query .= 'AND `nsp`.`is_active` = 1 ';
            $query .= 'AND `nsp`.`is_deleted` = 0';

            $this->db->select('(' . $query . ') product_count', false);

        }

        // --------------------------------------------------------------------------

        return parent::_getcount_common($data, $_caller);
    }


    // --------------------------------------------------------------------------


    public function get_by_id($id, $data = array())
    {
        if (!isset($data['only_active'])) {

            $data['only_active'] = false;

        }

        // --------------------------------------------------------------------------

        return parent::get_by_id($id, $data);
    }


    // --------------------------------------------------------------------------


    public function get_by_ids($ids, $data = array())
    {
        if (!isset($data['only_active'])) {

            $data['only_active'] = false;

        }

        // --------------------------------------------------------------------------

        return parent::get_by_ids($ids, $data);
    }


    // --------------------------------------------------------------------------


    public function get_by_slug($slug, $data = array())
    {
        if (!isset($data['only_active'])) {

            $data['only_active'] = false;

        }

        // --------------------------------------------------------------------------

        return parent::get_by_slug($slug, $data);
    }


    // --------------------------------------------------------------------------


    public function get_by_slugs($slugs, $data = array())
    {
        if (!isset($data['only_active'])) {

            $data['only_active'] = false;

        }

        // --------------------------------------------------------------------------

        return parent::get_by_slugs($slugs, $data);
    }


    // --------------------------------------------------------------------------


    public function get_by_id_or_slug($id_slug, $data = array())
    {
        if (!isset($data['only_active'])) {

            $data['only_active'] = false;

        }

        // --------------------------------------------------------------------------

        return parent::get_by_id_or_slug($id_slug, $data);
    }


    // --------------------------------------------------------------------------


    public function create($data = array(), $return_object = false)
    {
        if (!empty($data->label)) {

            $data->slug = $this->_generate_slug($data->label);
        }

        if (empty($data->cover_id)) {

            $data->cover_id = null;
        }

        return parent::create($data, $return_object);
    }


    // --------------------------------------------------------------------------


    public function update($id, $data = array())
    {
        if (!empty($data->label)) {

            $data->slug = $this->_generate_slug($data->label, '', '', null, null, $id);

        }

        if (empty($data->cover_id)) {

            $data->cover_id = null;
        }

        return parent::update($id, $data);
    }


    // --------------------------------------------------------------------------


    public function format_url($slug)
    {
        return site_url($this->shopUrl . 'range/' . $slug);
    }


    // --------------------------------------------------------------------------


    protected function _format_object(&$object)
    {
        //  Type casting
        $object->id             = (int) $object->id;
        $object->created_by     = $object->created_by ? (int) $object->created_by : null;
        $object->modified_by    = $object->modified_by ? (int) $object->modified_by : null;
        $object->url            = $this->format_url($object->slug);
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_RANGE_MODEL')) {

    class Shop_range_model extends NAILS_Shop_range_model
    {
    }

}

/* End of file shop_range_model.php */
/* Location: ./modules/shop/models/shop_range_model.php */