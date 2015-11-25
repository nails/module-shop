<?php

/**
 * Shop API end points: Basket
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Api\Shop;

use Nails\Factory;

class Basket extends \Nails\Api\Controller\Base
{
    protected $maintenance;

    // --------------------------------------------------------------------------

    public function __construct($oApiRouter)
    {
        parent::__construct($oApiRouter);
        $this->load->model('shop/shop_basket_model');

        $this->maintenance = new \stdClass();
        $this->maintenance->enabled = (bool) appSetting('maintenance_enabled', 'shop');
        if ($this->maintenance->enabled) {

            //  Allow shop admins access
            if (userHasPermission('admin:shop:*')) {
                $this->maintenance->enabled = false;
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the maintenance ehaders and returns the status/error message
     * @return array
     */
    protected function renderMaintenance()
    {
        $this->output->set_header($this->input->server('SERVER_PROTOCOL') . ' 503 Service Temporarily Unavailable');
        $this->output->set_header('Status: 503 Service Temporarily Unavailable');
        $this->output->set_header('Retry-After: 7200');

        return array(
            'status' => '503',
            'error'  => 'Down for maintenance'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Adds an item to the basket
     * @return array
     */
    public function postAdd()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        $out       = array();
        $variantId = $this->input->post('variantId');
        $quantity  = $this->input->post('quantity') ? $this->input->post('quantity') : 1;

        if (!$this->shop_basket_model->add($variantId, $$quantity)) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->lastError();
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Removes an item from the basket
     * @return array
     */
    public function postRemove()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        $out       = array();
        $variantId = $this->input->post('variantId');

        if (!$this->shop_basket_model->remove($variantId)) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->lastError();
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Increments an item already in the basket
     * @return array
     */
    public function postIncrement()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        $out       = array();
        $variantId = $this->input->post('variantId');

        if (!$this->shop_basket_model->increment($variantId)) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->lastError();
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Decrements an item already in the basket; if the number reaches 0 then the
     * item is removed.
     * @return array
     */
    public function postDecrement()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        $out       = array();
        $variantId = $this->input->post('variantId');

        if (!$this->shop_basket_model->decrement($variantId)) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->lastError();
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Applies a voucer to the basket
     * @return array
     */
    public function postAddVoucher()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        $aOut          = array();
        $oVoucherModel = Factory::model('Voucher', 'nailsapp/module-shop');
        $oVoucher      = $oVoucherModel->validate($this->input->post('voucher'), getBasket());

        if ($oVoucher) {

            if (!$this->shop_basket_model->addVoucher($oVoucher->code)) {

                $aOut['status'] = 400;
                $aOut['error']  = $this->shop_basket_model->lastError();
            }

        } else {

            $aOut['status'] = 400;
            $aOut['error']  = $oVoucherModel->lastError();
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Removes a voucher from the baskt
     * @return array
     */
    public function postRemoveVoucher()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        $out = array();

        if (!$this->shop_basket_model->removeVoucher()) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->lastError();
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Adds a note to the basket
     * @return array
     */
    public function add_note()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        $out  = array();
        $note = $this->input->post('note');

        if (!$this->shop_basket_model->addNote($note)) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->lastError();
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the basket's currency
     * @return array
     */
    public function postSetCurrency()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        $aOut            = array();
        $oCurrencyModel = Factory::model('Currency', 'nailsapp/module-shop');
        $oCurrency       = $oCurrencyModel->getByCode($this->input->post('currency'));

        if ($oCurrency) {

            $this->session->set_userdata('shop_currency', $oCurrency->code);

            if ($this->user_model->isLoggedIn()) {

                //  Save to the user meta object
                $oUserMeta = Factory::model('UserMeta', 'nailsapp/module-auth');
                $oUserMeta->update(
                    NAILS_DB_PREFIX . 'user_meta_shop',
                    activeUser('id'),
                    array(
                        'currency' => $oCurrency->code
                    )
                );
            }

        } else {

            $aOut['status'] = 400;
            $aOut['error']  = $oCurrencyModel->lastError();
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Marks a basket as a collection order
     * @return array
     */
    public function postSetAsCollection()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        $out = array();

        if (!$this->shop_basket_model->setDeliveryType('COLLECT')) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->lastError();
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Marks a basket as a delivery order
     * @return array
     */
    public function postSetAsDelivery()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        $out = array();

        if (!$this->shop_basket_model->setDeliveryType('DELIVER')) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->lastError();
        }

        return $out;
    }
}
