<?php

/**
 * This model provides voucher functionality to the Nails shop.
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Shop_voucher_model extends NAILS_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = NAILS_DB_PREFIX . 'shop_voucher';
        $this->tablePrefix = 'sv';
        $this->destructiveDelete = false;
    }

    // --------------------------------------------------------------------------

    /**
     * Redeems a voucher
     * @param  int      $voucherId The ID of the voucher to redeem
     * @param  stdClass $order     The order object
     * @return boolean
     */
    public function redeem($voucherId, $order)
    {
        if (is_numeric($order)) {

            $this->load->model('shop/shop_order_model');
            $order = $this->shop_order_model->get_by_id($order);

            if (empty($order)) {

                $this->_set_error('Invalid Order ID');
                return false;
            }
        }

        // --------------------------------------------------------------------------

        $voucher = $this->get_by_id($voucherId);

        if (empty($voucher)) {

            $this->_set_error('Invalid Voucher ID');
            return false;
        }

        // --------------------------------------------------------------------------

        switch ($voucher->type) {

            case 'GIFT_CARD':

                return $this->redeemGiftCard($voucher, $order);
                break;

            case 'LIMITED_USE':

                return $this->redeemLimitedUse($voucher, $order);
                break;

            case 'NORMAL':
            default:

                return $this->redeemNormal($voucher, $order);
                break;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the last_used and modified dates of the voucher and bumps the use_count column
     * @param  stdClass $voucher The voucher object
     * @param  stdClass $order   The order object
     * @return boolean
     */
    protected function redeemNormal($voucher, $order)
    {
        //  Bump the use count
        $this->db->set('last_used', 'NOW()', false);
        $this->db->set('modified', 'NOW()', false);
        $this->db->set('use_count', 'use_count+1', false);

        $this->db->where('id', $voucher->id);
        return $this->db->update($this->table);
    }

    // --------------------------------------------------------------------------

    /**
     * Calls redeemNormal();
     * @param  stdClass $voucher The voucher object
     * @param  stdClass $order   The order object
     * @return boolean
     */
    protected function redeemLimitedUse($voucher, $order)
    {
        return $this->redeemNormal($voucher, $order);
    }

    // --------------------------------------------------------------------------

    /**
     * @todo   Work out what this method does
     * @param  stdClass $voucher The voucher object
     * @param  stdClass $order   The order object
     * @return boolean
     */
    protected function redeemGiftCard($voucher, $order)
    {
        if ($order->requires_shipping) {

            if (app_setting('free_shipping_threshold', 'shop') <= $order->totals->sub) {

                /**
                 * The order qualifies for free shipping, ignore the discount given
                 * in discount->shipping
                 */

                $spend = $order->discount->items;

            } else {

                /**
                 * The order doesn't qualify for free shipping, include the discount
                 * given in discount->shipping
                 */

                $spend = $order->discount->items + $order->discount->shipping;
            }

        } else {

            //  The discount given by the giftcard is that of discount->items
            $spend = $order->discount->items;
        }

        //  Bump the use count
        $this->db->set('last_used', 'NOW()', false);
        $this->db->set('modified', 'NOW()', false);
        $this->db->set('use_count', 'use_count+1', false);

        // --------------------------------------------------------------------------

        //  Alter the available balance

        $this->db->set('gift_card_balance', 'gift_card_balance-' . $spend , false);

        $this->db->where('id', $voucher->id);
        return $this->db->update($this->table);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all vouchers, optionally paginated.
     * @param int    $page    The page number of the results, if null then no pagination
     * @param int    $perPage How many items per page of paginated results
     * @param mixed  $data    Any data to pass to _getcount_common()
     * @param string $_caller Internal flag to pass to _getcount_common(), contains the calling method
     * @return array
     **/
    public function get_all($page = null, $perPage = null, $data = array(), $_caller = 'GET_ALL')
    {
        $result = parent::get_all($page, $perPage, $data, false, $_caller);

        //  Handle requests for the raw query object
        if (!empty($data['RETURN_QUERY_OBJECT'])) {

            return $result;
        }

        // --------------------------------------------------------------------------

        $this->load->model('shop/shop_product_type_model');

        foreach ($result as $voucher) {

            //  Fetch extra data
            $cacheKey = 'voucher-product-type-' . $voucher->product_type_id;

            switch ($voucher->discount_application) {

                case 'PRODUCT_TYPES':

                    $cache = $this->_get_cache($cacheKey);
                    if ($cache) {

                        //  Exists in cache
                        $voucher->product = $_cache;

                    } else {

                        //  Doesn't exist, fetch and save
                        $voucher->product = $this->shop_product_type_model->get_by_id($voucher->product_type_id);
                        $this->_set_cache($cacheKey);
                    }
                    break;
            }
        }

        return $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Applies common conditionals
     * @param  mixed  $where  A conditional to pass to $this->db->where()
     * @param  string $search Keywords to restrict the query by
     * @return void
     */
    protected function _getcount_common($data = array(), $_caller = null)
    {
        //  Search
        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = array();
            }

            $data['or_like'][] = array(
                'column' => $this->tablePrefix . '.code',
                'value'  => $data['keywords']
           );
        }

        parent::_getcount_common($data, $_caller);

        $this->db->select($this->tablePrefix . '.*,u.first_name, u.last_name, u.gender, u.profile_img, ue.email');
        $this->db->join(NAILS_DB_PREFIX . 'user u', 'u.id = ' . $this->tablePrefix . '.created_by', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.user_id = u.id AND ue.is_primary = 1', 'LEFT');
    }

    // --------------------------------------------------------------------------

    /**
     * Get a voucher by its code
     * @todo Update once get_all is updated
     * @param  string $code The voucher's code
     * @param  mixed  $data Any data to pass to _getcount_common()
     * @return mixed        stdClass on success, false on failure
     */
    public function get_by_code($code, $data = array())
    {
        if (!isset($data['where'])) {

            $data['where'] = array();
        }

        $data['where'][] = array($this->tablePrefix . '.code', $id);

        $result = $this->get_all(null, null, $data, 'GET_BY_CODE');

        // --------------------------------------------------------------------------

        if (!$result) {

            return false;
        }

        // --------------------------------------------------------------------------

        return $result[0];
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether a voucher is valid or not
     * @param  string   $code   The voucher's code
     * @param  stdClass $basket The basket object
     * @return mixed            stdClass (the voucher object) on success, false on failure
     */
    public function validate($code, $basket = null)
    {
        if (!$code) {

            $this->_set_error('No voucher code supplied.');
            return false;
        }

        $voucher = $this->get_by_code($code);

        if (!$voucher) {

            $this->_set_error('Invalid voucher code.');
            return false;
        }

        // --------------------------------------------------------------------------

        /**
         * Voucher exists, now we need to check it's still valid; this depends on the
         * type of vocuher it is.
         */

        //  Is active?
        if (!$voucher->is_active) {

            $this->_set_error('Invalid voucher code.');
            return false;
        }

        //  Voucher started?
        if (strtotime($voucher->valid_from) > time()) {

            // @TODO: User user datetime functions
            $message  = 'Voucher is not available yet. This voucher becomes available on the ';
            $message .= date('jS F Y \a\t H:i', strtotime($voucher->valid_from)) . '.';

            $this->_set_error($message);
            return false;
        }

        //  Voucher expired?
        if (
            !is_null($voucher->valid_to)
            && $voucher->valid_to != '0000-00-00 00:00:00'
            && strtotime($voucher->valid_to) < time()
       ) {

            $this->_set_error('Voucher has expired.');
            return false;
        }

        if (!is_null($basket)) {

            //  Is this a shipping voucher being applied to an order with no shippable items?
            if ($voucher->discount_application == 'SHIPPING' && !$basket->requires_shipping) {

                $this->_set_error('Your order does not contian any items which require shipping, voucher not needed!');
                return false;
            }

            /**
             * Is there a shipping threshold? If so, and the voucher is type SHIPPING and
             * the threshold has been reached then prevent it being added as it doesn't
             * make sense.
             */

            if (app_setting('free_shipping_threshold', 'shop') && $voucher->discount_application == 'SHIPPING') {

                if ($basket->totals->sub >= app_setting('free_shipping_threshold', 'shop')) {

                    $this->_set_error('Your order qualifies for free shipping, voucher not needed!');
                    return false;
                }
            }

            /**
             * If the voucher applies to a particular product type, check the basket contains
             * that product, otherwise it doesn't make sense to add it
             */

            if ($voucher->discount_application == 'PRODUCT_TYPES') {

                $matched = false;

                foreach ($basket->items as $item) {

                    if ($item->type->id == $voucher->product_type_id) {

                        $matched = true;
                        break;
                    }
                }

                if (!$matched) {

                    $this->_set_error('This voucher does not apply to any items in your basket.');
                    return false;
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Check custom voucher type conditions
        \Nails\Factory::helper('string');
        $method = 'validate' . underscoreToCamelcase(strtolower($voucher->type));

        if (method_exists($this, $method)) {

            if ($this->$method($voucher)) {

                return $voucher;

            } else {

                return false;
            }

        } else {

            $this->_set_error('This voucher is corrupt and cannot be used just now.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Additional checks for vouchers of type "NORMAL"
     * @param  stdClass $voucher The voucher being validated
     * @return boolean
     */
    protected function validateNormal($voucher)
    {
        /**
         * So long as the voucher is within date limits then it's valid. If we got
         * here then it's valid and has not expired
         */

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Additional checks for vouchers of type "LIMITED_USE"
     * @param  stdClass $voucher The voucher being validated
     * @return boolean
     */
    protected function validateLimitedUse(&$voucher)
    {
        if ($voucher->use_count < $voucher->limited_use_limit) {

            return true;

        } else {

            $this->_set_error('Voucher has exceeded its use limit.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Additional checks for vouchers of type "GIFT_CARD"
     * @param  stdClass $voucher The voucher being validated
     * @return boolean
     */
    protected function validateGiftCard(&$voucher)
    {
        if ($voucher->gift_card_balance > 0) {

            return true;

        } else {

            $this->_set_error('Gift card has no available balance.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Marks a voucher as activated
     * @param  int     $voucherId The voucher's ID
     * @return boolean
     */
    public function activate($voucherId)
    {
        $voucher = $this->get_by_id($voucherId);

        if (!$voucher) {

            $this->_set_error('Invalid voucher ID');
            return false;
        }

        $data = array(
            'is_active' => true
       );

        return $this->update($voucher->id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Marks a voucher as suspended
     * @param  int     $voucherId The voucher's ID
     * @return boolean
     */
    public function suspend($voucherId)
    {
        $voucher = $this->get_by_id($voucherId);

        if (!$voucher) {

            $this->_set_error('Invalid voucher ID');
            return false;
        }

        $data = array(
            'is_active' => false
       );

        return $this->update($voucher->id, $data);
    }

    // --------------------------------------------------------------------------

    public function generateValidCode()
    {
        \Nails\Factory::helper('string');

        do {

            $code = strtoupper(random_string('alnum'));
            $this->db->where('code', $code);
            $codeExists = (bool) $this->db->count_all_results($this->table);

        } while($codeExists);

        return $code;
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

        $obj->limited_use_limit = (int) $obj->limited_use_limit;
        $obj->discount_value    = (float) $obj->discount_value;
        $obj->gift_card_balance = (float) $obj->gift_card_balance;

        //  Creator
        $obj->creator               = new \stdClass();
        $obj->creator->id           = (int) $obj->created_by;
        $obj->creator->email        = $obj->email;
        $obj->creator->first_name   = $obj->first_name;
        $obj->creator->last_name    = $obj->last_name;
        $obj->creator->profile_img  = $obj->profile_img;
        $obj->creator->gender       = $obj->gender;

        unset($obj->created_by);
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_VOUCHER_MODEL')) {

    class Shop_voucher_model extends NAILS_Shop_voucher_model
    {
    }
}
