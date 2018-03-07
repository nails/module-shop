<?php

//  Include _shop.php; executes common functionality
require_once '_shop.php';

/**
 * This class provides notification functionality
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

use Nails\Factory;

class NAILS_Notify extends NAILS_Shop_Controller
{
    /**
     * Cosntruct the controller
     */
    public function __construct()
    {
        parent::__construct();

        //  Load the skin to use
        $this->loadSkin('front');
    }

    // --------------------------------------------------------------------------

    /**
     * Handle notification interface
     * @return void
     */
    public function index()
    {
        $variantId = $this->uri->rsegment('2');
        $this->data['product'] = $this->shop_product_model->getByVariantId($variantId);

        if (!$this->data['product']) {

            show_404();
        }

        foreach ($this->data['product']->variations as $v) {

            if ($v->id = $variantId) {

                $this->data['variant'] = $v;
            }
        }

        if (!$this->data['variant']) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->get('isModal')) {

            $this->data['headerOverride'] = 'structure/header/blank';
            $this->data['footerOverride'] = 'structure/footer/blank';
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->model('shop/shop_inform_product_available_model');

            if ($this->shop_inform_product_available_model->add($variantId, $this->input->post('email'))) {

                $this->data['success']  = '<strong>Success!</strong> You were added to the ';
                $this->data['success'] .= 'notification list for this item.';
                $this->data['successfully_added'] = true;

            } else {

                $this->data['error']  = 'Sorry, could not add you to the mailing list. ';
                $this->data['error'] .= $this->shop_inform_product_available_model->lastError();
            }
        }

        // --------------------------------------------------------------------------

        $label = $this->data['variant']->label;
        $this->data['page']->title = $this->shopName . ': Notify when "' . $label . '" is back in stock';

        // --------------------------------------------------------------------------

        $oView = Factory::service('View');
        $oView->load('structure/header', $this->data);
        $oView->load($this->skin->path . 'views/notify/index', $this->data);
        $oView->load('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Map all calls to the index() method
     * @return void
     */
    public function _remap()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        $this->index();
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

if (!defined('NAILS_ALLOW_EXTENSION_NOTIFY')) {

    class Notify extends NAILS_Notify
    {
    }
}
