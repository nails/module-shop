<?php

/**
 * Shop API end points: Feeds
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Api\Shop;

use Nails\Factory;

class Feed extends \Nails\Api\Controller\Base
{
    /**
     * Require the user be authenticated to use any endpoint
     */
    const REQUIRE_AUTH = true;

    // --------------------------------------------------------------------------

    protected $maintenance;

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct($oApiRouter)
    {
        parent::__construct($oApiRouter);

        $this->maintenance          = new \stdClass();
        $this->maintenance->enabled = (bool) appSetting('maintenance_enabled', 'nailsapp/module-shop');

        if ($this->maintenance->enabled) {

            //  Allow shop admins access
            if (userHasPermission('admin:shop:*')) {
                $this->maintenance->enabled = false;
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
        $oOutput = Factory::service('Output');
        $oOutput->set_header($this->input->server('SERVER_PROTOCOL') . ' 503 Service Temporarily Unavailable');
        $oOutput->set_header('Status: 503 Service Temporarily Unavailable');
        $oOutput->set_header('Retry-After: 7200');

        return array(
            'status' => '503',
            'error'  => 'Down for maintenance'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Searches the Google Shopping categories text file
     * @return array
     */
    public function getSearchGoogleCategories()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        if (!userHasPermission('admin:shop:inventory:create') && !userHasPermission('admin:shop:inventory:edit')) {

            return array(
                'status' => 401,
                'error'  => 'You do not have permission to access this method.'
            );

        } else {

            $aOut        = array();
            $oFeedModel  = Factory::model('Feed', 'nailsapp/module-shop');
            $sKeywords   = $this->input->get('keywords');
            $aCategories = $oFeedModel->searchGoogleCategories($sKeywords);

            if ($aCategories !== false) {

                $aOut['results'] = $aCategories;

            } else {

                $aOut['status'] = 500;
                $aOut['error']  = $oFeedModel->lastError();
            }

            return $aOut;
        }
    }

}
