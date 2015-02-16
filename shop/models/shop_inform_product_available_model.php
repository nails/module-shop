<?php

/**
 * This model manages product availability requests
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Shop_inform_product_available_model extends NAILS_Model
{
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->_table            = NAILS_DB_PREFIX . 'shop_inform_product_available';
        $this->_table_prefix    = 'sipa';

        // --------------------------------------------------------------------------

        $this->load->model('shop/shop_product_model');
    }


    // --------------------------------------------------------------------------


    protected function _getcount_common($data = array(), $_caller = null)
    {
        parent::_getcount_common($data, $_caller);

        if (empty($data['sort'])) {

            $this->db->order_by($this->_table_prefix . '.created', 'DESC');

        }

        $this->db->select($this->_table_prefix . '.*, ue.user_id, u.first_name, u.last_name, u.profile_img, u.gender');
        $this->db->select('sp.label product_label, spv.label variation_label');

        //    Join the User tables
        $this->db->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.email = ' . $this->_table_prefix . '.email', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'user u', 'u.id = ue.user_id', 'LEFT');

        //    Join the product & variartion tables
        $this->db->join(NAILS_DB_PREFIX . 'shop_product sp', 'sp.id = ' . $this->_table_prefix . '.product_id');
        $this->db->join(NAILS_DB_PREFIX . 'shop_product_variation spv', 'spv.id = ' . $this->_table_prefix . '.variation_id');
    }


    // --------------------------------------------------------------------------


    public function add($variant_id, $email)
    {
        $this->load->helper('email');

        if (!valid_email($email)) {

            $this->_set_error('"' . $email . '" is not a valid email address.');
            return false;

        }

        $_product = $this->shop_product_model->getByVariantId($variant_id);

        if (!$_product) {

            $this->_set_error('Invalid Variant ID.');
            return false;

        }

        // --------------------------------------------------------------------------

        $_data                    = array();
        $_data['product_id']    = $_product->id;
        $_data['variation_id']    = $variant_id;
        $_data['email']            = $email;

        return (bool) parent::create($_data);
    }


    // --------------------------------------------------------------------------


    public function inform($product_id, $variation_ids)
    {
        $variation_ids = (array) $variation_ids;
        $variation_ids = array_filter($variation_ids);
        $variation_ids = array_unique($variation_ids);

        $_sent = array();

        if ($variation_ids) {

            $_product = $this->shop_product_model->get_by_id($product_id);

            if ($_product && $_product->is_active && !$_product->is_deleted) {

                foreach ($variation_ids as $variation_id) {

                    $this->db->select($this->_table_prefix . '.*');
                    $this->db->where($this->_table_prefix . '.product_id', $product_id);
                    $this->db->where($this->_table_prefix . '.variation_id', $variation_id);
                    $_result = $this->db->get($this->_table . ' ' . $this->_table_prefix)->result();

                    foreach ($_result as $result) {

                        //    Have we already sent this notification?
                        $_sent_string = $_email->to_email . '|' . $_product_id . '|' . $variation_id;

                        if (in_array($_sent_string, $_sent)) {

                            continue;

                        }

                        $_email                            = new \stdClass();
                        $_email->to_email                = $result->email;
                        $_email->type                    = 'shop_inform_product_available';
                        $_email->data                    = array();
                        $_email->data['product']        = $_product;
                        $_email->data['variation']        = $variation_id;
                        $_email->data['email_subject']    = $_product->label . ' is back in stock.';

                        $this->emailer->send($_email);

                        $_sent[] = $_sent_string;

                    }

                }

            }

        }

        //    Delete requests
        $this->db->where('product_id', $product_id);
        $this->db->where_in('variation_id', $variation_ids);
        $this->db->delete($this->_table);
    }


    // --------------------------------------------------------------------------


    protected function _format_object(&$obj)
    {
        parent::_format_object($obj);

        $obj->product            = new \stdClass();
        $obj->product->id        = (int) $obj->product_id;
        $obj->product->label    = $obj->product_label;
        unset($obj->product_id);
        unset($obj->product_label);

        $obj->variation            = new \stdClass();
        $obj->variation->id        = (int) $obj->variation_id;
        $obj->variation->label    = $obj->variation_label;
        unset($obj->variation_id);
        unset($obj->variation_label);

        $obj->user                = new \stdClass();
        $obj->user->id            = $obj->user_id;
        $obj->user->email        = $obj->email;
        $obj->user->first_name    = $obj->first_name;
        $obj->user->last_name    = $obj->last_name;
        $obj->user->profile_img    = $obj->profile_img;
        $obj->user->gender        = $obj->gender;
        unset($obj->user_id);
        unset($obj->email);
        unset($obj->first_name);
        unset($obj->last_name);
        unset($obj->profile_img);
        unset($obj->gender);
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_INFORM_PRODUCT_AVAILABLE_MODEL')) {

    class Shop_inform_product_available_model extends NAILS_Shop_inform_product_available_model
    {
    }

}

/* End of file shop_inform_product_available_model.php */
/* Location: ./modules/shop/models/shop_inform_product_available_model.php */