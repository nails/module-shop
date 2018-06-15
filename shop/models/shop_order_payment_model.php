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
use Nails\Common\Model\Base;

class Shop_order_payment_model extends Base
{
    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();
        $this->table             = NAILS_DB_PREFIX . 'shop_order_payment';
        $this->tableAlias       = 'sop';
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
            array('column' => $this->tableAlias . '.transaction_id', 'value' => $sTransactionId),
            array('column' => $this->tableAlias . '.payment_gateway', 'value' => $sGateway)
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
            array('column' => $this->tableAlias . '.order_id', 'value' => $iOrderId)
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

        $oDb = Factory::service('Database');
        $oDb->select('SUM(amount_base) as total_paid');
        $oDb->where('order_id', $oOrder->id);
        $oResult = $oDb->get($this->table)->row();

        return (int) $oResult->total_paid >= $oOrder->totals->base->grand;
    }
}
