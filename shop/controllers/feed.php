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

class NAILS_Feed extends NAILS_Shop_Controller
{
    public function index()
    {
        if ($this->maintenance->enabled) {
            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        $sMethod = $this->uri->rsegment(2);

        preg_match('/^(.+?)(\.(xml|json))?$/', $sMethod, $aMatches);

        $sProvider = !empty($aMatches[1]) ? strtolower($aMatches[1]) : 'google';
        $sFormat   = !empty($aMatches[3]) ? strtolower($aMatches[3]) : 'xml';

        //  Set cache headers
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0');
        header('Pragma: no-cache');

        //  Set content-type
        switch ($sFormat) {

            case 'xml':
                header('Content-Type: text/xml');
                break;

            case 'json':
                header('Content-Type: application/json');
                break;
        }

        $this->load->model('shop_feed_model');
        $sCacheFile = $this->shop_feed_model->serve($sProvider, $sFormat);

        if (empty($sCacheFile)) {

            show_404();

        } else {

            readFileChunked($sCacheFile);
        }
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
