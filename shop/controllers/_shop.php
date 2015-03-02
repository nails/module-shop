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
    protected $shopName;
    protected $shopUrl;
    protected $skin;

    // --------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        //  Check this module is enabled in settings
        if (!isModuleEnabled('nailsapp/module-shop')) {

            //  Cancel execution, module isn't enabled
            show_404();
        }

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

        //  Shop's name and URL
        $this->shopName = $this->shopUrl = $this->shop_model->getShopName();
        $this->shopUrl  = $this->shopUrl = $this->shop_model->getShopUrl();

        //  Pass data to the views
        $this->data['shop_name'] = $this->shopName;
        $this->data['shop_url']  = $this->shopUrl;
    }

    // --------------------------------------------------------------------------

    protected function loadSkin($skinType)
    {
        //  Define some vars
        $assets    = array();
        $cssInline = array();
        $jsInline  = array();

        //  Sanity test; make sure we're loading a skin type which is supported
        switch ($skinType) {

            case 'front':

                //  Determine the name of the skin
                $skinName = app_setting('skin_front', 'shop');
                $skinName = !empty($skinName) ? $skinName : 'shop-skin-front-classic';

                //  Load it
                $skin               = $this->shop_skin_front_model->get($skinName);
                $this->skin         =& $skin;
                $this->data['skin'] =& $skin;

                if (!$skin) {

                    $errorSubject  = 'Failed to load shop front skin "' . $skinName . '"';
                    $errorMessage  = 'Shop front skin "' . $skinName . '" failed to load at ' . APP_NAME;
                    $errorMessage .= ', the following reason was given: ';
                    $errorMessage .= $this->shop_skin_front_model->last_error();

                }
                break;

            case 'checkout':

                //  Determine the name of the skin
                $skinName = app_setting('skin_checkout', 'shop');
                $skinName = !empty($skinName) ? $skinName : 'shop-skin-checkout-classic';

                //  Load it
                $skin               = $this->shop_skin_checkout_model->get($skinName);
                $this->skin         =& $skin;
                $this->data['skin'] =& $skin;

                if (!$skin) {

                    $errorSubject  = 'Failed to load shop checkout skin "' . $skin . '"';
                    $errorMessage  = 'Shop checkout skin "' . $skin . '" failed to load at ' . APP_NAME;
                    $errorMessage .= ', the following reason was given: ';
                    $errorMessage .= $this->shop_skin_checkout_model->last_error();

                }
                break;

            default:

                $errorSubject = '"' . $skinType . '" is not a valid skin type';
                $errorMessage = 'An invalid skin type was attempted on ' . APP_NAME;
                break;
        }

        /**
         * If we encountered any errors loading the skin, fall over. If everything
         * was buttery smooth then load any assets that the skin needs.
         */

        if (!empty($errorSubject) || !empty($errorMessage)) {

            showFatalError($errorSubject, $errorMessage);

        } else {

            $assets    = !empty($skin->assets)     ? $skin->assets     : array();
            $cssInline = !empty($skin->css_inline) ? $skin->css_inline : array();
            $jsInline  = !empty($skin->js_inline)  ? $skin->js_inline  : array();

            $this->loadSkinAssets($assets, $cssInline, $jsInline, $skin->url);
        }
    }

    // --------------------------------------------------------------------------

    protected function loadSkinAssets($assets, $cssInline, $jsInline, $url)
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
        if (!empty($cssInline) && is_array($cssInline)) {

            foreach ($cssInline as $asset) {

                $this->asset->inline($asset, 'CSS-INLINE');
            }
        }

        // --------------------------------------------------------------------------

        //  JS - Inline
        if (!empty($jsInline) && is_array($jsInline)) {

            foreach ($jsInline as $asset) {

                $this->asset->inline($asset, 'JS-INLINE');
            }
        }
    }
}
