<?php

namespace Nails\Api\Shop;

/**
 * Shop API end points: Webhook
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class Webhook extends \Nails\Api\Controller\Base
{
    protected $maintenance;

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct($oApiRouter)
    {
        parent::__construct($oApiRouter);

        $this->maintenance = new \stdClass();
        $this->maintenance->enabled = (bool) app_setting('maintenance_enabled', 'shop');
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
     * Handles responses from the payment gateways
     * @param  string $gatewayName The name of the gateway to handle
     * @return array
     */
    public function anyRemap($gatewayName)
    {

        /**
         * We'll do logging for this method as it's reasonably important that
         * we keep a history of the things which happen
         */

        $this->writeLog('Webhook initialising');
        $this->writeLog('State:');
        $this->writeLog('RAW GET Data: ' . $this->input->server('QUERY_STRING'));
        $this->writeLog('RAW POST Data: ' . file_get_contents('php://input'));

        // --------------------------------------------------------------------------

        //  @todo consider not blocking this (in case a payment needs to come through)
        if ($this->maintenance->enabled) {

            $this->writeLog('***MAINTENANCE MODE***');
            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        $out = array('status' => 200);

        // --------------------------------------------------------------------------

        $this->load->model('shop/shop_payment_gateway_model');
        $result = $this->shop_payment_gateway_model->webhookCompletePayment($gatewayName, true);

        if (!$result) {

            $out['status'] = 500;
            $out['error']  = $this->shop_payment_gateway_model->last_error();
        }

        // --------------------------------------------------------------------------

        $this->writeLog('Webhook terminating');

        /**
         * Don't set a header. Most gateways will keep trying, or send false positive
         * failures if this comes back as non-200.
         */

        $this->oApiRouter->outputSendHeader(false);

        return $out;
    }
}
