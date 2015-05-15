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

            $out        = array();
            $keywords   = $this->input->get('keywords');
            $categories = $this->shop_feed_model->searchGoogleCategories($keywords);

            if ($categories !== false) {

                $out['results'] = $categories;

            } else {

                $out['status'] = 500;
                $out['error']  = $this->shop_feed_model->last_error();
            }

            return $out;
        }
    }
}
