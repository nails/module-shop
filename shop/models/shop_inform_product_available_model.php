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

use Nails\Factory;

class NAILS_Shop_inform_product_available_model extends NAILS_Model
{
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->table            = NAILS_DB_PREFIX . 'shop_inform_product_available';
        $this->tablePrefix  = 'sipa';

        // --------------------------------------------------------------------------

        $this->load->model('shop/shop_product_model');
    }

    // --------------------------------------------------------------------------

    protected function _getcount_common($data = array())
    {
        parent::_getcount_common($data);

        if (empty($data['sort'])) {

            $this->db->order_by($this->tablePrefix . '.created', 'DESC');
        }

        $this->db->select($this->tablePrefix . '.*, ue.user_id, u.first_name, u.last_name, u.profile_img, u.gender');
        $this->db->select('sp.label product_label, spv.label variation_label');

        //  Join the User tables
        $this->db->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.email = ' . $this->tablePrefix . '.email', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'user u', 'u.id = ue.user_id', 'LEFT');

        //  Join the product & variartion tables
        $this->db->join(NAILS_DB_PREFIX . 'shop_product sp', 'sp.id = ' . $this->tablePrefix . '.product_id');
        $this->db->join(NAILS_DB_PREFIX . 'shop_product_variation spv', 'spv.id = ' . $this->tablePrefix . '.variation_id');
    }

    // --------------------------------------------------------------------------

    public function add($variantId, $email)
    {
        Factory::helper('email');

        if (!valid_email($email)) {

            $this->setError('"' . $email . '" is not a valid email address.');
            return false;
        }

        $product = $this->shop_product_model->getByVariantId($variantId);

        if (!$product) {

            $this->setError('Invalid Variant ID.');
            return false;
        }

        // --------------------------------------------------------------------------

        $_data                 = array();
        $_data['product_id']   = $product->id;
        $_data['variation_id'] = $variantId;
        $_data['email']        = $email;

        return (bool) parent::create($_data);
    }

    // --------------------------------------------------------------------------

    public function inform($productId, $variationIds)
    {
        $variationIds = (array) $variationIds;
        $variationIds = array_filter($variationIds);
        $variationIds = array_unique($variationIds);

        $sent = array();

        if ($variationIds) {

            $product = $this->shop_product_model->get_by_id($productId);

            if ($product && $product->is_active && !$product->is_deleted) {

                foreach ($variationIds as $variationId) {

                    $this->db->select($this->tablePrefix . '.*');
                    $this->db->where($this->tablePrefix . '.product_id', $productId);
                    $this->db->where($this->tablePrefix . '.variation_id', $variationId);
                    $results = $this->db->get($this->table . ' ' . $this->tablePrefix)->result();

                    foreach ($results as $result) {

                        //  Have we already sent this notification?
                        $sentStr = $_email->to_email . '|' . $product->id . '|' . $variationId;

                        if (in_array($sentStr, $sent)) {

                            continue;
                        }

                        $_email                        = new \stdClass();
                        $_email->to_email              = $result->email;
                        $_email->type                  = 'shop_inform_product_available';
                        $_email->data                  = array();
                        $_email->data['product']       = $product;
                        $_email->data['variation']     = $variationId;
                        $_email->data['email_subject'] = $product->label . ' is back in stock.';

                        $this->emailer->send($_email);

                        $sent[] = $sentStr;
                    }
                }
            }
        }

        //  Delete requests
        $this->db->where('product_id', $productId);
        $this->db->where_in('variation_id', $variationIds);
        $this->db->delete($this->table);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a single object
     *
     * @param  object $obj      A reference to the object being formatted.
     * @param  array  $data     The same data array which is passed to _getcount_common, for reference if needed
     * @param  array  $integers Fields which should be cast as integers if numerical
     * @param  array  $bools    Fields which should be cast as booleans
     * @return void
     */
    protected function _format_object(&$obj, $data = array(), $integers = array(), $bools = array())
    {
        parent::_format_object($obj, $data, $integers, $bools);

        $obj->product        = new \stdClass();
        $obj->product->id    = (int) $obj->product_id;
        $obj->product->label = $obj->product_label;
        unset($obj->product_id);
        unset($obj->product_label);

        $obj->variation        = new \stdClass();
        $obj->variation->id    = (int) $obj->variation_id;
        $obj->variation->label = $obj->variation_label;
        unset($obj->variation_id);
        unset($obj->variation_label);

        $obj->user              = new \stdClass();
        $obj->user->id          = $obj->user_id;
        $obj->user->email       = $obj->email;
        $obj->user->first_name  = $obj->first_name;
        $obj->user->last_name   = $obj->last_name;
        $obj->user->profile_img = $obj->profile_img;
        $obj->user->gender      = $obj->gender;
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
