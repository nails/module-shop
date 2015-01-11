<?php

/**
 * This class provides some common shop controller functionality
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Shop_Controller extends NAILS_Controller
{
    protected $_shop_name;
    protected $_shop_url;
    protected $_skin_front;
    protected $_skin_checkout;

    // --------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        //  Check this module is enabled in settings
        if (!module_is_enabled('shop')) {

            //  Cancel execution, module isn't enabled
            show_404();
        }

        // --------------------------------------------------------------------------

        //  Load language file
        $this->lang->load('shop');

        // --------------------------------------------------------------------------

        //  Load the models
        $this->load->model('shop/shop_model');
        $this->load->model('shop/shop_basket_model');
        $this->load->model('shop/shop_brand_model');
        $this->load->model('shop/shop_category_model');
        $this->load->model('shop/shop_collection_model');
        $this->load->model('shop/shop_currency_model');
        $this->load->model('shop/shop_order_model');
        $this->load->model('shop/shop_product_model');
        $this->load->model('shop/shop_product_type_model');
        $this->load->model('shop/shop_range_model');
        $this->load->model('shop/shop_shipping_driver_model');
        $this->load->model('shop/shop_sale_model');
        $this->load->model('shop/shop_tag_model');
        $this->load->model('shop/shop_voucher_model');
        $this->load->model('shop/shop_skin_front_model');
        $this->load->model('shop/shop_skin_checkout_model');

        // --------------------------------------------------------------------------

        //  "Front of house" Skin
        $skin = app_setting('skin_front', 'shop');
        $skin = !empty($skin) ? $skin : 'shop-skin-front-classic';
        $this->_load_skin($skin, 'front');

        //  "Checkout" Skin
        $skin = app_setting('skin_checkout', 'shop');
        $skin = !empty($skin) ? $skin : 'shop-skin-checkout-classic';
        $this->_load_skin($skin, 'checkout');

        // --------------------------------------------------------------------------

        //  Shop's name
        $this->_shop_name = app_setting('name', 'shop') ? app_setting('name', 'shop') : 'Shop';

        // --------------------------------------------------------------------------

        //  Shop's base URL
        $this->_shop_url = app_setting('url', 'shop') ? app_setting('url', 'shop') : 'shop/';

        // --------------------------------------------------------------------------

        //  Pass data to the views
        $this->data['shop_name'] = $this->_shop_name;
        $this->data['shop_url']  = $this->_shop_url;
    }

    // --------------------------------------------------------------------------

    protected function _load_skin($skin, $skinType)
    {
        //  Sanity test; make sure we're loading a skin type which is supported
        switch ($skinType) {

            case 'front':

                $this->_skin_front        =& $this->shop_skin_front_model->get($skin);
                $this->data['skin_front'] =& $this->_skin_front;

                if (!$this->_skin_front) {

                    $errorSubject  = 'Failed to load shop front skin "' . $skin . '"';
                    $errorMessage  = 'Shop front skin "' . $skin . '" failed to load at ' . APP_NAME;
                    $errorMessage .= ', the following reason was given: ';
                    $errorMessage .= $this->shop_skin_front_model->last_error();
                }
                break;

            case 'checkout':

                $this->_skin_checkout        =& $this->shop_skin_checkout_model->get($skin);
                $this->data['skin_checkout'] =& $this->_skin_checkout;

                if (!$this->_skin_checkout) {

                    $errorSubject  = 'Failed to load shop checkout skin "' . $skin . '"';
                    $errorMessage  = 'Shop checkout skin "' . $skin . '" failed to load at ' . APP_NAME;
                    $errorMessage .= ', the following reason was given: ';
                    $errorMessage .= $this->shop_skin_checkout_model->last_error();
                }
                break;

            default:

                $subject = '"' . $skin_tye . '" is not a valid skin type';
                $message = 'An invalid skin type was attempted on ' . APP_NAME;
                showFatalError($subject, $message);
                break;
        }

        if (!empty($errorSubject) || !empty($errorMessage)) {

            showFatalError($errorSubject, $errorMessage);
        }
    }

    // --------------------------------------------------------------------------

    protected function _load_skin_assets($assets, $css_inline, $js_inline, $url)
    {
        //  CSS and JS
        if (!empty($assets) && is_array($assets)) {

            foreach ($assets as $asset) {

                if (is_string($asset)) {

                    $this->asset->load($url . 'assets/' . $asset);

                } else {

                    $this->asset->load($asset[0], $asset[1]);
                }
            }
        }

        // --------------------------------------------------------------------------

        //  CSS - Inline
        if (!empty($css_inline) && is_array($css_inline)) {

            foreach ($css_inline as $asset) {

                $this->asset->inline($asset, 'CSS_INLINE');
            }
        }

        // --------------------------------------------------------------------------

        //  JS - Inline
        if (!empty($js_inline) && is_array($js_inline)) {

            foreach ($js_inline as $asset) {

                $this->asset->inline($asset, 'JS_INLINE');
            }
        }
    }
}
