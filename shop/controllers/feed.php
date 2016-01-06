<?php

//  Include _shop.php; executes common functionality
require_once '_shop.php';

/**
 * This class provides front end shop feed functionality
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

use Nails\Factory;

use Nails\Shop\Exception\FeedException;

class NAILS_Feed extends NAILS_Shop_Controller
{
    public function index()
    {
        if ($this->maintenance->enabled) {
            $this->renderMaintenancePage();
            return;
        }

        $sDriver = $this->uri->rsegment(2) . '/' . $this->uri->rsegment(3);

        //  Test for a cache file first, if it's there serve that
        $oDate         = Factory::factory('DateTime');
        $sDate         = $oDate->format('Y-m-d');
        $sCacheHeaders = DEPLOY_CACHE_DIR . 'shop-feed-' . $sDate . '-' . str_replace(DIRECTORY_SEPARATOR, '-', $sDriver) . '-headers.txt';
        $sCacheData    = DEPLOY_CACHE_DIR . 'shop-feed-' . $sDate . '-' . str_replace(DIRECTORY_SEPARATOR, '-', $sDriver) . '-data.txt';

        if (!file_exists($sCacheData)) {

            $oFeedModel      = Factory::model('Feed', 'nailsapp/module-shop');
            $oDriverInstance = $oFeedModel->getInstance($sDriver);

            if (empty($oDriverInstance)) {
                show_404();
            }

            //  Create the cache files
            $oHandleHeaders = fopen($sCacheHeaders, 'w+');
            if (!$oHandleHeaders) {
                throw new FeedException('Failed to create header cache file', 2);
            }

            $oHandleData = fopen($sCacheData, 'w+');
            if (!$oHandleData) {
                unlink($sCacheHeaders);
                throw new FeedException('Failed to create data cache file', 1);
            }

            if (!$oDriverInstance->generate($oHandleHeaders, $oHandleData)) {
                unlink($sCacheHeaders);
                unlink($sCacheData);
                throw new FeedException('Driver failed to generate feed', 3);
            }

            fclose($oHandleHeaders);
            fclose($oHandleData);
        }

        // --------------------------------------------------------------------------

        //  Send headers
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0');
        header('Pragma: no-cache');

        //  Any additional headers
        $oHandle = fopen($sCacheHeaders, 'r');
        if ($oHandle) {
            while (($sLine = fgets($oHandle)) !== false) {
                if (!empty($sLine)) {
                    header($sLine);
                }
            }
            fclose($oHandle);
        }

        //  Send the data
        readFileChunked($sCacheData);
    }

    // --------------------------------------------------------------------------

    public function _remap()
    {
        return $this->index();
    }
}

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' SHOP MODULE
 *
 * The following block of code makes it simple to extend one of the core shop
 * controllers. Some might argue it's a little hacky but it's a simple 'fix'
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

if (!defined('NAILS_ALLOW_EXTENSION_FEED')) {

    class Feed extends NAILS_Feed
    {
    }
}
