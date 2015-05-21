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

class Feed extends \ApiController
{
    public static $requiresAuthentication = true;

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop/shop_model');
        $this->load->model('shop/shop_feed_model');
    }

    // --------------------------------------------------------------------------

    /**
     * Searches the Google Shopping categories text file
     * @return array
     */
    public function getSearchGoogleCategories()
    {
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
