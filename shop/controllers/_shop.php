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
    protected $skinFront;
    protected $skinCheckout;

    // --------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        //  Check this module is enabled in settings
        if (!isModuleEnabled('shop')) {

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
        $this->loadSkin($skin, 'front');

        //  "Checkout" Skin
        $skin = app_setting('skin_checkout', 'shop');
        $skin = !empty($skin) ? $skin : 'shop-skin-checkout-classic';
        $this->loadSkin($skin, 'checkout');

        // --------------------------------------------------------------------------

        //  Shop's name and URL
        $this->shopName = $this->shopUrl = $this->shop_model->getShopName();
        $this->shopUrl  = $this->shopUrl = $this->shop_model->getShopUrl();

        // --------------------------------------------------------------------------

        //  Pass data to the views
        $this->data['shop_name'] = $this->shopName;
        $this->data['shop_url']  = $this->shopUrl;

        // --------------------------------------------------------------------------

        //  Load appropriate skin assets
        $assets    = !empty($this->skinFront->assets)     ? $this->skinFront->assets     : array();
        $cssInline = !empty($this->skinFront->css_inline) ? $this->skinFront->css_inline : array();
        $jsInline  = !empty($this->skinFront->js_inline)  ? $this->skinFront->js_inline  : array();

        $this->loadSkinAssets($assets, $cssInline, $jsInline, $this->skinFront->url);
    }

    // --------------------------------------------------------------------------

    protected function loadSkin($skin, $skinType)
    {
        //  Sanity test; make sure we're loading a skin type which is supported
        switch ($skinType) {

            case 'front':

                $this->skinFront          =& $this->shop_skin_front_model->get($skin);
                $this->data['skin_front'] =& $this->skinFront;

                if (!$this->skinFront) {

                    $errorSubject  = 'Failed to load shop front skin "' . $skin . '"';
                    $errorMessage  = 'Shop front skin "' . $skin . '" failed to load at ' . APP_NAME;
                    $errorMessage .= ', the following reason was given: ';
                    $errorMessage .= $this->shop_skin_front_model->last_error();
                }
                break;

            case 'checkout':

                $this->skinCheckout          =& $this->shop_skin_checkout_model->get($skin);
                $this->data['skin_checkout'] =& $this->skinCheckout;

                if (!$this->skinCheckout) {

                    $errorSubject  = 'Failed to load shop checkout skin "' . $skin . '"';
                    $errorMessage  = 'Shop checkout skin "' . $skin . '" failed to load at ' . APP_NAME;
                    $errorMessage .= ', the following reason was given: ';
                    $errorMessage .= $this->shop_skin_checkout_model->last_error();
                }
                break;

            default:

                $subject = '"' . $skinType . '" is not a valid skin type';
                $message = 'An invalid skin type was attempted on ' . APP_NAME;
                showFatalError($subject, $message);
                break;
        }

        if (!empty($errorSubject) || !empty($errorMessage)) {

            showFatalError($errorSubject, $errorMessage);
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
