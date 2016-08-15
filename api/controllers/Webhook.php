<?php

/**
 * Shop API end points: Webhook
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Api\Shop;

use Nails\Factory;

class Webhook extends \Nails\Api\Controller\Base
{
    protected $oMaintenance;

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct($oApiRouter)
    {
        parent::__construct($oApiRouter);

        $this->oMaintenance          = new \stdClass();
        $this->oMaintenance->enabled = (bool) appSetting('maintenance_enabled', 'nailsapp/module-shop');
        if ($this->oMaintenance->enabled) {
            //  Allow shop admins access
            if (userHasPermission('admin:shop:*')) {
                $this->oMaintenance->enabled = false;
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the maintenance headers and returns the status/error message
     * @return array
     */
    protected function renderMaintenance()
    {
        $oInput  = Factory::service('Input');
        $oOutput = Factory::service('Output');

        $oOutput->set_header($oInput->server('SERVER_PROTOCOL') . ' 503 Service Temporarily Unavailable');
        $oOutput->set_header('Status: 503 Service Temporarily Unavailable');
        $oOutput->set_header('Retry-After: 7200');

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

        $oInput = Factory::service('Input');

        $this->writeLog('Webhook initialising');
        $this->writeLog('State:');
        $this->writeLog('RAW GET Data: ' . $oInput->server('QUERY_STRING'));
        $this->writeLog('RAW POST Data: ' . file_get_contents('php://input'));

        // --------------------------------------------------------------------------

        //  @todo consider not blocking this (in case a payment needs to come through)
        if ($this->oMaintenance->enabled) {
            $this->writeLog('***MAINTENANCE MODE***');
            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        $aOut = array('status' => 200);

        // --------------------------------------------------------------------------

        $this->load->model('shop/shop_payment_gateway_model');
        $bResult = $this->shop_payment_gateway_model->webhookCompletePayment($gatewayName, true);

        if (!$bResult) {
            $aOut['status'] = 500;
            $aOut['error']  = $this->shop_payment_gateway_model->lastError();
        }

        // --------------------------------------------------------------------------

        $this->writeLog('Webhook terminating');

        /**
         * Don't set a header. Most gateways will keep trying, or send false positive
         * failures if this comes back as non-200.
         */

        $this->oApiRouter->outputSendHeader(false);

        return $aOut;
    }
}
