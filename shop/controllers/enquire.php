<?php

//  Include _shop.php; executes common functionality
require_once '_shop.php';

/**
 * This class provides enquiry functionality
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

use Nails\Factory;

class NAILS_Enquire extends NAILS_Shop_Controller
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
     * Handle delivery enquiries
     * @return void
     */
    public function delivery()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        $productId = $this->uri->rsegment('3');
        $variantId = $this->uri->rsegment('4');

        $this->data['product'] = $this->shop_product_model->getById($productId);
        $this->data['variant'] = null;

        if (!$this->data['product']) {

            show_404();
        }

        if ($variantId) {

            //  Fetch the variation
            foreach ($this->data['product']->variations as $v) {

                if ($v->id = $variantId) {

                    $this->data['variant'] = $v;
                    break;
                }
            }

            //  Check it's "collection only"
            if (!$this->data['variant'] || !$this->data['variant']->shipping->collection_only) {

                show_404();
            }
        }

        if (!$this->data['variant']) {

            //  Check that there are 'collection only' variations
            $_collect_only_variations = array();
            foreach ($this->data['product']->variations as $v) {

                if ($v->shipping->collection_only) {

                    $_collect_only_variations[] = $v;
                }
            }

            if (!count($_collect_only_variations)) {

                show_404();

            } elseif (count($_collect_only_variations) == 1) {

                $this->data['variant'] = $_collect_only_variations[0];
            }
        }

        // --------------------------------------------------------------------------

        if ($this->input->get('isModal')) {

            $this->data['headerOverride'] = 'structure/header/blank';
            $this->data['footerOverride'] = 'structure/footer/blank';
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $oFormValidation = Factory::service('FormValidation');

            $oFormValidation->set_rules('name', '', 'xss_clean|required');
            $oFormValidation->set_rules('email', '', 'xss_clean|required|valid_email');
            $oFormValidation->set_rules('telephone', '', 'xss_clean');
            $oFormValidation->set_rules('address', '', 'xss_clean|required');
            $oFormValidation->set_rules('notes', '', 'xss_clean');


            $oFormValidation->set_message('required', lang('fv_required'));
            $oFormValidation->set_message('valid_email', lang('fv_valid_email'));

            if ($oFormValidation->run()) {

                $_data                        = array();
                $_data['customer']            = new \stdClass();
                $_data['customer']->name      = $this->input->post('name');
                $_data['customer']->email     = $this->input->post('email');
                $_data['customer']->telephone = $this->input->post('telephone');
                $_data['customer']->address   = $this->input->post('address');
                $_data['customer']->notes     = $this->input->post('notes');

                $_data['product']        = new \stdClass();
                $_data['product']->id    = $this->data['product']->id;
                $_data['product']->slug  = $this->data['product']->slug;
                $_data['product']->label = $this->data['product']->label;

                foreach ($this->data['product']->variations as $v) {

                    if ($v->id == $this->input->post('variant_id')) {

                        $_data['variant']        = new \stdClass();
                        $_data['variant']->id    = $v->id;
                        $_data['variant']->sku   = $v->sku;
                        $_data['variant']->label = $v->label;
                    }
                }

                $_override              = array();
                $_override['email_tpl'] = $this->skin->path . 'views/email/delivery_enquiry';

                if (appNotificationNotify('delivery_enquiry', 'nailsapp/module-shop', $_data, $_override)) {

                    $this->data['success'] = '<strong>Success!</strong> Your enquiry was received successfully.';

                } else {

                    $this->data['error']  = '<strong>Sorry,</strong> failed to send enquiry. ';
                    $this->data['error'] .= app_notification_last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        if ($this->data['variant']) {

            $this->data['page']->title  = $this->shopName . ': Delivery enquiry about ';
            $this->data['page']->title .= '"' . $this->data['variant']->label . '"';

        } else {

            $this->data['page']->title  = $this->shopName . ': Delivery enquiry about ';
            $this->data['page']->title .= '"' . $this->data['product']->label . '"';
        }

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/enquire/index', $this->data);
        $this->load->view('structure/footer', $this->data);
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

if (!defined('NAILS_ALLOW_EXTENSION_ENQUIRE')) {

    class Enquire extends NAILS_Enquire
    {
    }
}
