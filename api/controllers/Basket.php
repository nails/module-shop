<?php

namespace Nails\Api\Shop;

/**
 * Shop API end points: Basket
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class Basket extends \ApiController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop/shop_basket_model');
    }

    // --------------------------------------------------------------------------

    /**
     * Adds an item to the basket
     * @return array
     */
    public function postAdd()
    {
        $out       = array();
        $variantId = $this->input->post('variantId');
        $quantity  = $this->input->post('quantity') ? $this->input->post('quantity') : 1;

        if (!$this->shop_basket_model->add($variantId, $$quantity)) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->last_error();
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
        $out       = array();
        $variantId = $this->input->post('variantId');

        if (!$this->shop_basket_model->remove($variantId)) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->last_error();
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
        $out       = array();
        $variantId = $this->input->post('variantId');

        if (!$this->shop_basket_model->increment($variantId)) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->last_error();
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
        $out       = array();
        $variantId = $this->input->post('variantId');

        if (!$this->shop_basket_model->decrement($variantId)) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->last_error();
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
        $out     = array();
        $voucher = $this->shop_voucher_model->validate($this->input->post('voucher'), get_basket());

        if ($voucher) {

            if (!$this->shop_basket_model->addVoucher($voucher->code)) {

                $out['status'] = 400;
                $out['error']  = $this->shop_basket_model->last_error();
            }

        } else {

            $out['status'] = 400;
            $out['error']  = $this->shop_voucher_model->last_error();
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Removes a voucher from the baskt
     * @return array
     */
    public function postRemoveVoucher()
    {
        $out = array();

        if (!$this->shop_basket_model->removeVoucher()) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->last_error();
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
        $out  = array();
        $note = $this->input->post('note');

        if (!$this->shop_basket_model->addNote($note)) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->last_error();
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
        $out      = array();
        $currency = $this->shop_currency_model->get_by_code($this->input->post('currency'));

        if ($currency) {

            $this->session->set_userdata('shop_currency', $currency->code);

            if ($this->user_model->isLoggedIn()) {

                //  Save to the user object
                $this->user_model->update(activeUser('id'), array('shop_currency' => $currency->code));
            }

        } else {

            $out['status'] = 400;
            $out['error']  = $this->shop_currency_model->last_error();
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Marks a basket as a collection order
     * @return array
     */
    public function postSetAsCollection()
    {
        $out = array();

        if (!$this->shop_basket_model->setDeliveryType('COLLECT')) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->last_error();
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
        $out = array();

        if (!$this->shop_basket_model->setDeliveryType('DELIVER')) {

            $out['status'] = 400;
            $out['error']  = $this->shop_basket_model->last_error();
        }

        return $out;
    }
}
