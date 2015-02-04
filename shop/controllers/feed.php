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
        $method = $this->uri->rsegment(2);
        
        preg_match('/^(.+?)(\.(xml|json))?$/', $method, $matches);

        $provider = !empty($matches[1]) ? strtolower($matches[1]) : 'google';
        $format   = !empty($matches[3]) ? strtolower($matches[3]) : 'xml';
        
        $this->load->model('shop_feed_model');
        $output = $this->shop_feed_model->serve($provider, $format);

        if (empty($output)) {
        
            show_404(); 
        }
        
        //  Set cache headers
        $this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
        $this->output->set_header("Cache-Control: post-check=0, pre-check=0");
        $this->output->set_header("Pragma: no-cache");
        
        //  Set content-type
        switch ($format) {
            
            case 'xml':
            
                $this->output->set_content_type('text/xml');
                break;
                
            case 'json':
            
                $this->output->set_content_type('text/json');
                break;
        }
        
        //  Set data
        $this->output->set_output($output);
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
