<?php

namespace Nails\Api\Shop;

/**
 * Shop API end points: Feeds
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class Feed extends \Nails\Api\Controllers\Base
{
    public static $requiresAuthentication = true;
    protected $maintenance;

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop/shop_model');
        $this->load->model('shop/shop_feed_model');

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
            $sKeywords   = $this->input->get('keywords');
            $aCategories = $this->shop_feed_model->searchGoogleCategories($sKeywords);

            if ($aCategories !== false) {

                $aOut['results'] = $aCategories;

            } else {

                $aOut['status'] = 500;
                $aOut['error']  = $this->shop_feed_model->last_error();
            }

            return $aOut;
        }
    }
}
