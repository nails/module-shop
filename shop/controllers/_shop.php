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
        $oPageData = appSetting('pages', 'nailsapp/module-shop');

        $this->data['shop_pages'] = array();

        //  Filter out any  without content
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
        $this->maintenance->enabled = (bool) appSetting('maintenance_enabled', 'nailsapp/module-shop');

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
        $oSkin              = $this->oSkinModel->getEnabled($skinType);
        $this->skin         = $oSkin;
        $this->data['skin'] = $oSkin;

        $assets    = !empty($oSkin->data->assets)     ? $oSkin->data->assets     : array();
        $cssInline = !empty($oSkin->data->css_inline) ? $oSkin->data->css_inline : array();
        $jsInline  = !empty($oSkin->data->js_inline)  ? $oSkin->data->js_inline  : array();

        $this->loadSkinAssets($assets, $cssInline, $jsInline, site_url($oSkin->relativePath) . '/');
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
