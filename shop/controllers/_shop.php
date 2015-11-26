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

use Nails\Factory;

class NAILS_Shop_Controller extends NAILS_Controller
{
    protected $shopName;
    protected $shopUrl;
    protected $skin;
    protected $maintenance;

    protected $oPageModel;
    protected $oSkinFrontModel;
    protected $oSkinCheckoutModel;

    // --------------------------------------------------------------------------

    /**
     * Constructs the controller
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        //  Load the models
        $this->load->model('shop/shop_model');
        $this->load->model('shop/shop_basket_model');
        $this->load->model('shop/shop_brand_model');
        $this->load->model('shop/shop_category_model');
        $this->load->model('shop/shop_collection_model');
        $this->load->model('shop/shop_order_model');
        $this->load->model('shop/shop_product_model');
        $this->load->model('shop/shop_product_type_model');
        $this->load->model('shop/shop_range_model');
        $this->load->model('shop/shop_shipping_driver_model');
        $this->load->model('shop/shop_sale_model');
        $this->load->model('shop/shop_tag_model');

        $this->oPageModel = Factory::model('Page', 'nailsapp/module-shop');
        $this->oSkinModel = Factory::model('Skin', 'nailsapp/module-shop');

        if (isModuleEnabled('nailsapp/module-cms')) {
            $oCmsPageModel = Factory::model('Page', 'nailsapp/module-cms');
        }

        // --------------------------------------------------------------------------

        //  Shop's name and URL
        $this->shopName = $this->shopUrl = $this->shop_model->getShopName();
        $this->shopUrl  = $this->shopUrl = $this->shop_model->getShopUrl();

        //  Shop Pages
        $aPages    = $this->oPageModel->getAll();
        $oPageData = appSetting('pages', 'shop');

        $this->data['shop_pages'] = array();

        //  Filter out anywithout content
        if (!empty($oPageData)) {

            foreach ($aPages as $sSlug => $sTitle) {

                if (isModuleEnabled('nailsapp/module-cms')) {

                    if (!empty($oPageData->{$sSlug}->cmsPageId)) {
                        $oCmsPage = $oCmsPageModel->getById($oPageData->{$sSlug}->cmsPageId);
                        if (!empty($oCmsPage)) {
                            $this->data['shop_pages'][$sSlug] = array(
                                'slug' => $sSlug,
                                'url' => $oCmsPage->published->url,
                                'title' => $oCmsPage->published->title
                            );
                        }
                    }

                } else {

                    if (!empty($oPageData->{$sSlug}->body)) {
                        $this->data['shop_pages'][$sSlug] = array(
                            'slug' => $sSlug,
                            'url' => $this->shopUrl . 'page/' . $sSlug,
                            'title' => $sTitle
                        );
                    }
                }
            }
        }

        //  Pass data to the views
        $this->data['shop_name'] = $this->shopName;
        $this->data['shop_url']  = $this->shopUrl;

        //  Maintenance mode?
        $this->maintenance = new \stdClass();
        $this->maintenance->enabled = (bool) appSetting('maintenance_enabled', 'shop');

        if ($this->maintenance->enabled) {

            //  Allow shop admins access
            if (userHasPermission('admin:shop:*')) {

                $this->maintenance->enabled = false;
                $this->data['notice']  = '<strong>Maintenance mode is enabled</strong>';
                $this->data['notice'] .= '<br />You are a shop administrator so you have permission to view the shop while in maintenance mode.';
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Loads a shop skin
     * @param  string $skinType The skin's type (e.g front)
     * @return void
     */
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
                $skinName = appSetting('skin_front', 'shop') ?: 'nailsapp/skin-shop-front-classic';

                //  Load it
                $skin               = $this->oSkinModel->get('front', $skinName);
                $this->skin         =& $skin;
                $this->data['skin'] =& $skin;

                if (!$skin) {

                    $errorSubject  = 'Failed to load shop front skin "' . $skinName . '"';
                    $errorMessage  = 'Shop front skin "' . $skinName . '" failed to load at ' . APP_NAME;
                }
                break;

            case 'checkout':

                //  Determine the name of the skin
                $skinName = appSetting('skin_checkout', 'shop') ?: 'nailsapp/skin-shop-checkout-classic';

                //  Load it
                $skin               = $this->oSkinModel->get('checkout', $skinName);
                $this->skin         =& $skin;
                $this->data['skin'] =& $skin;

                if (!$skin) {

                    $errorSubject  = 'Failed to load shop checkout skin "' . $skin . '"';
                    $errorMessage  = 'Shop checkout skin "' . $skin . '" failed to load at ' . APP_NAME;
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

            $assets    = !empty($skin->data->assets)     ? $skin->data->assets     : array();
            $cssInline = !empty($skin->data->css_inline) ? $skin->data->css_inline : array();
            $jsInline  = !empty($skin->data->js_inline)  ? $skin->data->js_inline  : array();

            $this->loadSkinAssets($assets, $cssInline, $jsInline, site_url($skin->relativePath) . '/');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Loads any assets required by the skin
     * @param  array  $assets    An array of skin assets
     * @param  array  $cssInline An array of inline CSS
     * @param  array  $jsInline  An array of inline JS
     * @param  string $url       The URL to the skin's root directory
     * @return void
     */
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

    // --------------------------------------------------------------------------

    /**
     * Renders the shop maintenance page
     * @return void
     */
    protected function renderMaintenancePage()
    {
        $this->data['page']->title = $this->shopName + ' - Down for maintenance';

        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/maintenance', $this->data);
        $this->load->view('structure/footer', $this->data);
    }
}
