<?php

/**
 * This class registers some handlers for shop settings
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Shop;

class Settings extends \AdminController
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        $navGroup = new \Nails\Admin\Nav('Settings');
        $navGroup->addMethod('Shop');

        return $navGroup;
    }

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop/shop_model');

        // --------------------------------------------------------------------------

        //  @todo Move this into a common constructor
        $this->shopName = $this->shopUrl = $this->shop_model->getShopName();
        $this->shopUrl  = $this->shopUrl = $this->shop_model->getShopUrl();

        //  Pass data to the views
        $this->data['shopName'] = $this->shopName;
        $this->data['shopUrl']  = $this->shopUrl;
    }

    // --------------------------------------------------------------------------

    /**
     * Manage Shop settings
     * @return void
     */
    public function shop()
    {
        if (!isModuleEnabled('nailsapp/module-shop')) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Shop';

        // --------------------------------------------------------------------------

        //  Load models
        $this->load->model('shop/shop_model');
        $this->load->model('shop/shop_currency_model');
        $this->load->model('shop/shop_shipping_driver_model');
        $this->load->model('shop/shop_payment_gateway_model');
        $this->load->model('shop/shop_tax_rate_model');
        $this->load->model('shop/shop_skin_front_model');
        $this->load->model('shop/shop_skin_checkout_model');
        $this->load->model('country_model');

        // --------------------------------------------------------------------------

        //  Process POST
        if ($this->input->post()) {

            $method =  $this->input->post('update');

            if (method_exists($this, '_shop_update_' . $method)) {

                $this->{'_shop_update_' . $method}();

            } else {

                $this->data['error'] = 'I can\'t determine what type of update you are trying to perform.';
            }
        }

        // --------------------------------------------------------------------------

        //  Get data
        $this->data['settings']         = app_setting(null, 'shop', true);
        $this->data['payment_gateways'] = $this->shop_payment_gateway_model->get_available();
        $this->data['shipping_drivers'] = $this->shop_shipping_driver_model->getAvailable();
        $this->data['currencies']       = $this->shop_currency_model->get_all();
        $this->data['tax_rates']        = $this->shop_tax_rate_model->get_all();
        $this->data['tax_rates_flat']   = $this->shop_tax_rate_model->get_all_flat();
        $this->data['countries_flat']   = $this->country_model->getAllFlat();
        $this->data['continents_flat']  = $this->country_model->getAllContinentsFlat();
        array_unshift($this->data['tax_rates_flat'], 'No Tax');

        //  "Front of house" skins
        $this->data['skins_front']         = $this->shop_skin_front_model->get_available();
        $this->data['skin_front_selected'] = app_setting('skin_front', 'shop') ? app_setting('skin_front', 'shop') : 'shop-skin-front-classic';
        $this->data['skin_front_current']  = $this->shop_skin_front_model->get($this->data['skin_front_selected']);

        //  "Checkout" skins
        $this->data['skins_checkout']         = $this->shop_skin_checkout_model->get_available();
        $this->data['skin_checkout_selected'] = app_setting('skin_checkout', 'shop') ? app_setting('skin_checkout', 'shop') : 'shop-skin-checkout-classic';
        $this->data['skin_checkout_current']  = $this->shop_skin_checkout_model->get($this->data['skin_checkout_selected']);

        // --------------------------------------------------------------------------

        //  Load assets
        $this->asset->load('nails.admin.shop.settings.min.js', true);
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->inline('<script>_nails_settings = new NAILS_Admin_Shop_Settings();</script>');

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Set Shop settings
     * @return void
     */
    protected function _shop_update_settings()
    {
        //  Prepare update
        $settings                                          = array();
        $settings['name']                                  = $this->input->post('name');
        $settings['url']                                   = $this->input->post('url');
        $settings['price_exclude_tax']                     = $this->input->post('price_exclude_tax');
        $settings['enable_external_products']              = (bool) $this->input->post('enable_external_products');
        $settings['invoice_company']                       = $this->input->post('invoice_company');
        $settings['invoice_company']                       = $this->input->post('invoice_company');
        $settings['invoice_address']                       = $this->input->post('invoice_address');
        $settings['invoice_vat_no']                        = $this->input->post('invoice_vat_no');
        $settings['invoice_company_no']                    = $this->input->post('invoice_company_no');
        $settings['invoice_footer']                        = $this->input->post('invoice_footer');
        $settings['warehouse_collection_enabled']          = (bool) $this->input->post('warehouse_collection_enabled');
        $settings['warehouse_addr_addressee']              = $this->input->post('warehouse_addr_addressee');
        $settings['warehouse_addr_line1']                  = $this->input->post('warehouse_addr_line1');
        $settings['warehouse_addr_line2']                  = $this->input->post('warehouse_addr_line2');
        $settings['warehouse_addr_town']                   = $this->input->post('warehouse_addr_town');
        $settings['warehouse_addr_postcode']               = $this->input->post('warehouse_addr_postcode');
        $settings['warehouse_addr_state']                  = $this->input->post('warehouse_addr_state');
        $settings['warehouse_addr_country']                = $this->input->post('warehouse_addr_country');
        $settings['warehouse_collection_delivery_enquiry'] = (bool) $this->input->post('warehouse_collection_delivery_enquiry');
        $settings['page_brand_listing']                    = $this->input->post('page_brand_listing');
        $settings['page_category_listing']                 = $this->input->post('page_category_listing');
        $settings['page_collection_listing']               = $this->input->post('page_collection_listing');
        $settings['page_range_listing']                    = $this->input->post('page_range_listing');
        $settings['page_sale_listing']                     = $this->input->post('page_sale_listing');
        $settings['page_tag_listing']                      = $this->input->post('page_tag_listing');

        // --------------------------------------------------------------------------

        //  Sanitize shop url
        $settings['url'] .= substr($settings['url'], -1) != '/' ? '/' : '';

        // --------------------------------------------------------------------------

        if ($this->app_setting_model->set($settings, 'shop')) {

            $this->data['success'] = 'Store settings have been saved.';

            // --------------------------------------------------------------------------

            //  Rewrite routes
            $this->load->model('routes_model');
            if (!$this->routes_model->update('shop')) {

                $this->data['warning'] = '<strong>Warning:</strong> while the shop settings were updated, the routes file could not be updated. The shop may not behave as expected,';
            }

        } else {

            $this->data['error'] = 'There was a problem saving settings.';
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Set Shop Browse settings
     * @return void
     */
    protected function _shop_update_browse()
    {
        //  Prepare update
        $settings                             = array();
        $settings['expand_variants']          = (bool) $this->input->post('expand_variants');
        $settings['default_product_per_page'] = $this->input->post('default_product_per_page');
        $settings['default_product_per_page'] = is_numeric($settings['default_product_per_page']) ? (int) $settings['default_product_per_page'] : $settings['default_product_per_page'];
        $settings['default_product_sort']     = $this->input->post('default_product_sort');

        // --------------------------------------------------------------------------

        if ($this->app_setting_model->set($settings, 'shop')) {

            $this->data['success'] = 'Browsing settings have been saved.';

        } else {

            $this->data['error'] = 'There was a problem saving settings.';
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Set Shop skin settings
     * @return void
     */
    protected function _shop_update_skin()
    {
        //  Prepare update
        $settings                  = array();
        $settings['skin_front']    = $this->input->post('skin_front');
        $settings['skin_checkout'] = $this->input->post('skin_checkout');

        // --------------------------------------------------------------------------

        if ($this->app_setting_model->set($settings, 'shop')) {

            $this->data['success'] = 'Skin settings have been saved.';

        } else {

            $this->data['error'] = 'There was a problem saving settings.';
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Set Shop skin config
     * @return void
     */
    protected function _shop_update_skin_config()
    {
        //  Prepare update
        $configs = (array) $this->input->post('skin_config');
        $configs = array_filter($configs);
        $success = true;

        foreach ($configs as $slug => $configs) {

            //  Clear out the grouping; booleans not specified should be assumed false
            $this->app_setting_model->deleteGroup('shop-' . $slug);

            //  New settings
            $settings = array();
            foreach ($configs as $key => $value) {

                $settings[$key] = $value;
            }

            if ($settings) {

                if (!$this->app_setting_model->set($settings, 'shop-' . $slug)) {

                    $success = false;
                    break;
                }
            }
        }

        // --------------------------------------------------------------------------

        if ($success) {

            $this->data['success'] = 'Skin settings have been saved.';

        } else {

            $this->data['error'] = 'There was a problem saving settings.';
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Set Shop Payment Gateway settings
     * @return [type] [description]
     */
    protected function _shop_update_payment_gateway()
    {
        //  Prepare update
        $settings                             = array();
        $settings['enabled_payment_gateways'] = array_filter((array) $this->input->post('enabled_payment_gateways'));

        // --------------------------------------------------------------------------

        if ($this->app_setting_model->set($settings, 'shop')) {

            $this->data['success'] = 'Payment Gateway settings have been saved.';

        } else {

            $this->data['error'] = 'There was a problem saving settings.';
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Set Shop Currency settings
     * @return void
     */
    protected function _shop_update_currencies()
    {
        //  Prepare update
        $settings                          = array();
        $settings['base_currency']         = $this->input->post('base_currency');
        $settings['additional_currencies'] = $this->input->post('additional_currencies');

        $settings_encrypted                             = array();
        $settings_encrypted['openexchangerates_app_id'] = $this->input->post('openexchangerates_app_id');

        // --------------------------------------------------------------------------

        $this->db->trans_begin();
        $rollback = false;

        if (!$this->app_setting_model->set($settings, 'shop')) {

            $error    = $this->app_setting_model->last_error();
            $rollback = true;
        }

        if (!$this->app_setting_model->set($settings_encrypted, 'shop', null, true)) {

            $error    = $this->app_setting_model->last_error();
            $rollback = true;
        }

        if ($rollback) {

            $this->db->trans_rollback();
            $this->data['error'] = 'There was a problem saving currency settings. ' . $error;

        } else {

            $this->db->trans_commit();
            $this->data['success'] = 'Currency settings were saved.';

            // --------------------------------------------------------------------------

            /**
             * If there are multiple currencies and an Open Exchange Rates App ID provided
             * then attempt a sync
             */

            if (!empty($settings['additional_currencies']) && !empty($settings_encrypted['openexchangerates_app_id'])) {

                $this->load->model('shop/shop_currency_model');

                if (!$this->shop_currency_model->sync()) {

                    $this->data['message'] = '<strong>Warning:</strong> an attempted sync with Open Exchange Rates service failed with the following reason: ' . $this->shop_currency_model->last_error();

                } else {

                    $this->data['notice'] = '<strong>Currency Sync Complete.</strong><br />The system successfully synced with the Open Exchange Rates service.';
                }
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Set Shop shipping settings
     * @return void
     */
    protected function _shop_update_shipping()
    {
        //  Prepare update
        $settings                            = array();
        $settings['enabled_shipping_driver'] = $this->input->post('enabled_shipping_driver');

        // --------------------------------------------------------------------------

        if ($this->app_setting_model->set($settings, 'shop')) {

            $this->data['success'] = 'Shipping settings have been saved.';

        } else {

            $this->data['error'] = 'There was a problem saving settings.';
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Set Payment Gateway settings
     * @return void
     */
    public function shop_pg()
    {
        //  Check if valid gateway
        $this->load->model('shop/shop_payment_gateway_model');

        $gateway    = $this->uri->segment(4) ? strtolower($this->uri->segment(4)) : '';
        $available = $this->shop_payment_gateway_model->is_available($gateway);

        if ($available) {

            $params = $this->shop_payment_gateway_model->get_default_params($gateway);

            $this->data['params']       = $params;
            $this->data['gateway_name'] = ucwords(str_replace('_', ' ', $gateway));
            $this->data['gateway_slug'] = $this->shop_payment_gateway_model->get_correct_casing($gateway);

            //  Handle POST
            if ($this->input->post()) {

                $this->load->library('form_validation');

                foreach ($params as $key => $value) {

                    if ($key == 'testMode') {

                        $this->form_validation->set_rules('omnipay_' . $this->data['gateway_slug'] . '_' . $key, '', 'xss_clean');

                    } else {

                        $this->form_validation->set_rules('omnipay_' . $this->data['gateway_slug'] . '_' . $key, '', 'xss_clean|required');
                    }
                }

                //  Additional params
                switch ($gateway) {

                    case 'paypal_express':

                        $this->form_validation->set_rules('omnipay_' . $this->data['gateway_slug'] . '_brandName', '', 'xss_clean');
                        $this->form_validation->set_rules('omnipay_' . $this->data['gateway_slug'] . '_headerImageUrl', '', 'xss_clean');
                        $this->form_validation->set_rules('omnipay_' . $this->data['gateway_slug'] . '_logoImageUrl', '', 'xss_clean');
                        $this->form_validation->set_rules('omnipay_' . $this->data['gateway_slug'] . '_borderColor', '', 'xss_clean');
                        break;
                }

                $this->form_validation->set_message('required', lang('fv_required'));

                if ($this->form_validation->run()) {

                    $settings           = array();
                    $settings_encrypted = array();

                    //  Customisation params
                    $settings['omnipay_' . $this->data['gateway_slug'] . '_customise_label'] = $this->input->post('omnipay_' . $this->data['gateway_slug'] . '_customise_label');
                    $settings['omnipay_' . $this->data['gateway_slug'] . '_customise_img']   = $this->input->post('omnipay_' . $this->data['gateway_slug'] . '_customise_img');

                    //  Gateway params
                    foreach ($params as $key => $value) {

                        $settings_encrypted['omnipay_' . $this->data['gateway_slug'] . '_' . $key] = $this->input->post('omnipay_' . $this->data['gateway_slug'] . '_' . $key);
                    }

                    //  Additional params
                    switch ($gateway) {

                        case 'stripe':

                            $settings_encrypted['omnipay_' . $this->data['gateway_slug'] . '_publishableKey'] = $this->input->post('omnipay_' . $this->data['gateway_slug'] . '_publishableKey');
                            break;
                    }

                    $this->db->trans_begin();

                    $result           = $this->app_setting_model->set($settings, 'shop', null, false);
                    $result_encrypted = $this->app_setting_model->set($settings_encrypted, 'shop', null, true);

                    if ($this->db->trans_status() !== false && $result && $result_encrypted) {

                        $this->db->trans_commit();
                        $this->data['success'] = '' . $this->data['gateway_name'] . ' Payment Gateway settings have been saved.';

                    } else {

                        $this->db->trans_rollback();
                        $this->data['error'] = 'There was a problem saving the ' . $this->data['gateway_name'] . ' Payment Gateway settings.';
                    }

                } else {

                    $this->data['error'] = lang('fv_there_were_errors');
                }
            }

            //  Handle modal viewing
            if ($this->input->get('isFancybox')) {

                $this->data['headerOverride'] = 'structure/headerBlank';
                $this->data['footerOverride'] = 'structure/footerBlank';
            }

            //  Render the interface
            $this->data['page']->title = 'Shop Payment Gateway Configuration &rsaquo; ' . $this->data['gateway_name'];

            if (method_exists($this, '_shop_pg_' . $gateway)) {

                //  Specific configuration form available
                $this->{'_shop_pg_' . $gateway}();

            } else {

                //  Show the generic gateway configuration form
                $this->_shop_pg_generic($gateway);
            }

        } else {

            //  Bad gateway name
            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Renders a generic Payment Gateway configuration interface
     * @return void
     */
    protected function _shop_pg_generic()
    {
        \Nails\Admin\Helper::loadView('shop_pg/generic');
    }

    // --------------------------------------------------------------------------

    /**
     * Renders an interface specific for WorldPay
     * @return void
     */
    protected function _shop_pg_worldpay()
    {
        $this->asset->load('nails.admin.shop.settings.paymentgateway.worldpay.min.js', 'NAILS');
        $this->asset->inline('<script>_worldpay_config = new NAILS_Admin_Shop_Settings_PaymentGateway_WorldPay();</script>');

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('shop_pg/worldpay');
    }

    // --------------------------------------------------------------------------

    /**
     * Renders an interface specific for Stripe
     * @return void
     */
    protected function _shop_pg_stripe()
    {
        //  Additional params
        \Nails\Admin\Helper::loadView('shop_pg/stripe');
    }

    // --------------------------------------------------------------------------

    /**
     * Renders an interface specific for PayPal_Express
     * @return void
     */
    protected function _shop_pg_paypal_express()
    {
        //  Additional params
        \Nails\Admin\Helper::loadView('shop_pg/paypal_express');
    }

    // --------------------------------------------------------------------------

    /**
     * Set Shipping Driver settings
     * @return void
     */
    public function shop_sd()
    {
        $this->load->model('shop/shop_shipping_driver_model');

        $body = $this->shop_shipping_driver_model->configure($this->input->get('driver'));

        if (empty($body)) {

            show_404();
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Shop Shipping Driver Configuration &rsaquo; ';

        // --------------------------------------------------------------------------

        if ($this->input->get('isFancybox')) {

            $this->data['headerOverride'] = 'structure/headerBlank';
            $this->data['footerOverride'] = 'structure/footerBlank';

        }

        // --------------------------------------------------------------------------

        dumpanddie('todo');
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/settings/shop_sd', array('body' => $body));
        $this->load->view('structure/footer', $this->data);
    }
}
