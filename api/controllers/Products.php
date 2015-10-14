<?php

/**
 * Shop API end points: Products
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Api\Shop;

class Products extends \Nails\Api\Controller\Base
{
    protected $maintenance;

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct($oApiRouter)
    {
        parent::__construct($oApiRouter);
        $this->load->model('shop/shop_model');
        $this->load->model('shop/shop_product_model');

        $this->maintenance = new \stdClass();
        $this->maintenance->enabled = (bool) app_setting('maintenance_enabled', 'shop');
        if ($this->maintenance->enabled) {

            //  Allow shop admins access
            if (userHasPermission('admin:shop:*')) {
                $this->maintenance->enabled = false;
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the maintenance ehaders and returns the status/error message
     * @return array
     */
    protected function renderMaintenance()
    {
        $this->output->set_header($this->input->server('SERVER_PROTOCOL') . ' 503 Service Temporarily Unavailable');
        $this->output->set_header('Status: 503 Service Temporarily Unavailable');
        $this->output->set_header('Retry-After: 7200');

        return array(
            'status' => '503',
            'error'  => 'Down for maintenance'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Searches products
     * @return array
     */
    public function getSearch()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        $out = array();

        $limit = (int) $this->input->get('limit');
        $limit = empty($limit) || $limit > 50 ? 50 : $limit;

        $data             = array();
        $data['where']    = array();
        $data['where'][]  = array('column' => 'p.published <=', 'value' => 'NOW()', 'escape' => false);
        $data['keywords'] = $this->input->get('keywords');

        $products = $this->shop_product_model->get_all(0, 25, $data);

        //  Return only basic details
        $out['results'] = array();

        foreach ($products as $product) {

            $out['results'][] = $this->formatProduct($product);
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of objects by its ID
     * @return array
     */
    public function getId($id = null)
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        if (empty($id)) {

            return array();
        }

        $product = $this->shop_product_model->get_by_id($id);

        if (empty($product)) {

            return array();

        } else {

            $product = $this->formatProduct($product);

            return array(
                'product' => $product
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of objects by their IDs
     * @return array
     */
    public function getIds()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        $out = array();
        $ids = trim($this->input->get('ids'));
        $ids = explode(',', $ids);
        $ids = array_filter($ids);
        $ids = array_unique($ids);

        if (empty($ids)) {

            return array();
        }

        $products = $this->shop_product_model->get_by_ids($ids);

        //  Return only basic details
        $out['products'] = array();

        foreach ($products as $product) {

            $out['products'][] = $this->formatProduct($product);
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a product for the response
     * @param  stdClass $product The product object to format
     * @return stdClass
     */
    protected function formatProduct($product)
    {
        $temp = new \stdClass();
        $temp->id = $product->id;
        $temp->label = $product->label;
        $temp->isExternal = (bool)$product->is_external;
        $temp->type = $product->type;
        $temp->url = $product->url;
        $temp->taxRate = $product->tax_rate;
        $temp->categories = $product->categories;
        $temp->featuredImg = $product->featured_img;
        $temp->gallery = $product->gallery;

        //  And each variant also
        $temp->variations = array();
        foreach ($product->variations as $variant) {

            $tempV = new \stdClass();
            $tempV->id = $variant->id;
            $tempV->sku = $variant->sku;
            $tempV->label = $variant->label;
            $tempV->quantityAvailable = $variant->quantity_available;
            $tempV->leadTime = $variant->lead_time;
            $tempV->collectionOnly = (bool) $variant->ship_collection_only;
            $tempV->featuredImg = $variant->featured_img;
            $tempV->gallery = $variant->gallery;
            $tempV->price = $variant->price_raw;

            $temp->variations[] = $tempV;
        }

        return $temp;
    }
}
