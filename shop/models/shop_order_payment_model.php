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

use Nails\Factory;

class NAILS_Shop_order_payment_model extends NAILS_Model
{
    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();
        $this->table             = NAILS_DB_PREFIX . 'shop_order_payment';
        $this->tablePrefix       = 'sop';
        $this->defaultSortColumn = 'created';
        $this->defaultSortOrder  = 'desc';
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new payment
     * @param  array   $aData         The data to create the payment with
     * @param  boolean $bReturnObject Whether to return the object rather than the ID
     * @return mixed
     */
    public function create($aData, $bReturnObject = false)
    {
        $this->load->model('shop/shop_model');

        $oCurrencyModel = Factory::model('Currency', 'nailsapp/module-shop');

        $aData['currency_base'] = SHOP_BASE_CURRENCY_CODE;
        $aData['amount_base']   = $oCurrencyModel->convert(
            $aData['amount'],
            $aData['currency'],
            SHOP_BASE_CURRENCY_CODE
        );

        return parent::create($aData, $bReturnObject);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a payment by it's transaction ID
     * @param  string $sTransactionId The transaction ID
     * @param  string $sGateway       The gateway used to make the payment
     * @return \stdClass
     */
    public function getByTransactionId($sTransactionId, $sGateway)
    {
        $aData['where']   = array(
            array('column' => $this->tablePrefix . '.transaction_id', 'value' => $sTransactionId),
            array('column' => $this->tablePrefix . '.payment_gateway', 'value' => $sGateway)
        );

        $aResult = $this->getAll(null, null, $aData);

        if (empty($aResult)) {
            return false;
        }

        return $aResult[0];
    }

    // --------------------------------------------------------------------------

    /**
     * Returns payments for a particular order
     * @param  integer $iOrderId The order ID
     * @return array
     */
    public function getForOrder($iOrderId)
    {
        $aData['where'] = array(
            array('column' => $this->tablePrefix . '.order_id', 'value' => $iOrderId)
        );

        return $this->getAll(null, null, $aData);
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether an order is paid or not
     * @param  integer $iOrderId The Order ID
     * @return boolean
     */
    public function isOrderPaid($iOrderId)
    {
        $this->load->model('shop/shop_order_model');
        $oOrder = $this->shop_order_model->getById($iOrderId);

        if (!$oOrder) {
            $this->setError('Invalid Order ID.');
            return false;
        }

        $this->db->select('SUM(amount_base) as total_paid');
        $this->db->where('order_id', $oOrder->id);
        $oResult = $this->db->get($this->table)->row();

        return (int) $oResult->total_paid >= $oOrder->totals->base->grand;
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
