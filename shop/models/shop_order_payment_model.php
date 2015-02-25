<?php

/**
 * This model manages order payments
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Shop_order_payment_model extends NAILS_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table            = NAILS_DB_PREFIX . 'shop_order_payment';
        $this->tablePrefix    = 'sop';
    }

    // --------------------------------------------------------------------------

    public function create($data)
    {
        $this->load->model('shop/shop_model');
        $this->load->model('shop/shop_currency_model');

        $data['amount_base']    = $this->shop_currency_model->convert($data['amount'], $data['currency'], SHOP_BASE_CURRENCY_CODE);
        $data['currency_base']    = SHOP_BASE_CURRENCY_CODE;

        return parent::create($data);
    }

    // --------------------------------------------------------------------------

    public function get_by_transaction_id($transaction_id, $gateway)
    {
        $_data['where']        = array();
        $_data['where'][]    = array('column' => $this->tablePrefix . '.transaction_id', 'value' => $transaction_id);
        $_data['where'][]    = array('column' => $this->tablePrefix . '.payment_gateway', 'value' => $gateway);

        $_result = $this->get_all(null, null, $_data);

        if (empty($_result)) {

            return false;

        }

        return $_result[0];
    }


    // --------------------------------------------------------------------------


    public function get_for_order($order_id)
    {
        $_data['where']        = array();
        $_data['where'][]    = array('column' => $this->tablePrefix . '.order_id', 'value' => $order_id);

        return $this->get_all(null, null, $_data);
    }


    // --------------------------------------------------------------------------


    public function order_is_paid($order_id)
    {
        $this->load->model('shop/shop_order_model');
        $_order = $this->shop_order_model->get_by_id($order_id);

        if (!$_order) {

            $this->_set_error('Invalid Order ID.');
            return false;

        }

        $this->db->select('SUM(amount_base) as total_paid');
        $this->db->where('order_id', $_order->id);
        $_result = $this->db->get($this->table)->row();

        return (float) $_result->total_paid >= $_order->totals->base->grand;
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_ORDER_PAYMENT_MODEL')) {

    class Shop_order_payment_model extends NAILS_Shop_order_payment_model
    {
    }

}

/* End of file shop_order_payment_model.php */
/* Location: ./modules/shop/models/shop_order_payment_model.php */