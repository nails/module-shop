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

    protected function getCountCommon($data = array())
    {
        parent::getCountCommon($data);

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

            $product = $this->shop_product_model->getById($productId);

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

                        $_email                             = new \stdClass();
                        $_email->to_email                   = $result->email;
                        $_email->type                       = 'shop_inform_product_available';
                        $_email->data                       = new \stdClass();
                        $_email->data->product              = new \stdClass();
                        $_email->data->product->label       = $product->label;
                        $_email->data->product->description = $product->description;
                        $_email->data->product->url         = $product->url;
                        $_email->data->product->img         = cdnScale($product->featured_img, 250, 250);

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
     * The getAll() method iterates over each returned item with this method so as to
     * correctly format the output. Use this to cast integers and booleans and/or organise data into objects.
     *
     * @param  object $oObj      A reference to the object being formatted.
     * @param  array  $aData     The same data array which is passed to _getcount_common, for reference if needed
     * @param  array  $aIntegers Fields which should be cast as integers if numerical and not null
     * @param  array  $aBools    Fields which should be cast as booleans if not null
     * @param  array  $aFloats   Fields which should be cast as floats if not null
     * @return void
     */
    protected function formatObject(
        &$oObj,
        $aData = array(),
        $aIntegers = array(),
        $aBools = array(),
        $aFloats = array()
    ) {

        parent::formatObject($oObj, $aData, $aIntegers, $aBools, $aFloats);

        $oObj->product        = new \stdClass();
        $oObj->product->id    = (int) $oObj->product_id;
        $oObj->product->label = $oObj->product_label;
        unset($oObj->product_id);
        unset($oObj->product_label);

        $oObj->variation        = new \stdClass();
        $oObj->variation->id    = (int) $oObj->variation_id;
        $oObj->variation->label = $oObj->variation_label;
        unset($oObj->variation_id);
        unset($oObj->variation_label);

        $oObj->user              = new \stdClass();
        $oObj->user->id          = $oObj->user_id;
        $oObj->user->email       = $oObj->email;
        $oObj->user->first_name  = $oObj->first_name;
        $oObj->user->last_name   = $oObj->last_name;
        $oObj->user->profile_img = $oObj->profile_img;
        $oObj->user->gender      = $oObj->gender;
        unset($oObj->user_id);
        unset($oObj->email);
        unset($oObj->first_name);
        unset($oObj->last_name);
        unset($oObj->profile_img);
        unset($oObj->gender);
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
