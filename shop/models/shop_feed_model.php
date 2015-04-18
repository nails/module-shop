<?php

/**
 * This model manages the Shop Product feed
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Shop_feed_model extends NAILS_Model
{
    public function generate($provider, $format)
    {
        $methodProvider = strtolower($provider);
        $methodFormat   = ucfirst(strtolower($format));
        $method         = $methodProvider . 'Write' . $methodFormat;

        if (method_exists($this, $method)) {

            $data = $this->getShopData();

            $xml = $this->{$method}($data);
            if ($xml) {

                $this->load->helper('file');
                $cacheFile = DEPLOY_CACHE_DIR . 'shop-feed-' . $provider . '-' . date("Y-m-d") . '.' . $format;

                if (!write_file($cacheFile, $xml)) {

                    $this->_set_error('Failed to write shop feed to disk "' . $provider . '" in format "' . $xml . '"');

                } else {

                    return true;
                }

            } else {

                $this->_set_error('Failed to generate data for "' . $provider . '" in format "' . $xml . '"');
                return false;
            }

        } else {

            $this->_set_error('Invalid feed parameters.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    public function serve($provider, $format)
    {
        //  Check cache for file, if it exists, server it up with the appropriate cache headers, if not, generate and thens erve
        $cacheFile = DEPLOY_CACHE_DIR . 'shop-feed-' . $provider  . '-' . date("Y-m-d") . '.' . $format;

        if (is_file($cacheFile)) {

            return @file_get_contents($cacheFile);
        }

        //  File doesn't exist, attempt to generate
        if ($this->generate($provider, $format)) {

            return @file_get_contents($cacheFile);

        } else {

            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Render the feed
     * @return array
     */
    protected function getShopData()
    {
        $products = $this->shop_product_model->get_all();
        $out      = array();

        foreach($products as $p) {
            foreach($p->variations as $v) {

                $temp = new \stdClass();

                //  General product fields
                $temp->title       = $v->label;
                $temp->url         = $p->url;
                $temp->description = trim(strip_tags($p->description));
                $temp->productId   = $p->id;
                $temp->variantId   = $v->id;
                $temp->condition   = 'new';
                $temp->sku         = $v->sku;

                // --------------------------------------------------------------------------

                //  Work out the brand
                if (isset($p->brands[0])) {

                    $temp->brand = $p->brands[0]->label;

                } else {

                    $temp->brand = app_setting('invoice_company','shop');
                }

                // --------------------------------------------------------------------------

                //  Work out the product type (category)
                if (!empty($p->categories)) {

                    $category = array();
                    foreach ($p->categories as $c) {

                        $category[] = $c->label;
                    }

                    $temp->category = implode (", ", $category);

                } else {

                    $temp->category = '';
                }

                // --------------------------------------------------------------------------

                //  Set the product image
                if ($p->featured_img) {

                    $temp->image = cdn_serve($p->featured_img);

                } else {

                    $temp->image = '';
                }

                // --------------------------------------------------------------------------

                //  Stock status
                if ($v->stock_status == 'IN_STOCK') {

                    $temp->availability = 'in stock';

                } else {

                    $temp->availability = 'out of stock';
                }

                // --------------------------------------------------------------------------

                $shippingData = $this->shop_shipping_driver_model->calculateVariant($v->id);

                //   Calculate price and price of shipping
                $temp->price = $p->price->user->min_price . ' ' . app_setting('base_currency','shop');
                $temp->shipping_country = app_setting('warehouse_addr_country', 'shop');
                $temp->shipping_service = 'Standard';
                $temp->shipping_price = $shippingData->base . ' ' . app_setting('base_currency','shop');

                // --------------------------------------------------------------------------

                $out[] = $temp;

            }
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    protected function googleWriteXml($data)
    {

        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">';
        $xml .= '<channel>';
        $xml .= '<title><![CDATA[' . app_setting('invoice_company', 'shop') . ']]></title>';
        $xml .= '<description><![CDATA[' . app_setting('invoice_address', 'shop') . ']]></description>';
        $xml .= '<link><![CDATA[' . BASE_URL . ']]></link>';

        foreach ($data as $item) {

            $xml .= '<item>';
                $xml .= '<g:id>' . $item->productId . '.' . $item->variantId . '</g:id>';
                $xml .= '<title><![CDATA[' . htmlentities($item->title) . ']]></title>';
                $xml .= '<description><![CDATA[' . htmlentities($item->description) . ']]></description>';
                $xml .= '<g:product_type><![CDATA[' . htmlentities($item->category) . ']]></g:product_type>';
                $xml .= '<link>' . $item->url . '</link>';
                $xml .= '<g:image_link>' . $item->image . '</g:image_link>';
                $xml .= '<g:condition>' . $item->condition . '</g:condition>';
                $xml .= '<g:availability>' . $item->availability . '</g:availability>';
                $xml .= '<g:price>' . $item->price . '</g:price>';
                $xml .= '<g:brand><![CDATA[' . htmlentities($item->brand) . ']]></g:brand>';
                $xml .= '<g:gtin>' . $item->sku . '</g:gtin>';
                $xml .= '<g:shipping>';
                    $xml .= '<g:country>' . htmlentities($item->shipping_country) . '</g:country>';
                    $xml .= '<g:service>' . $item->shipping_service . '</g:service>';
                    $xml .= '<g:price>' . $item->shipping_price . '</g:price>';
                $xml .= '</g:shipping>';
            $xml .= '</item>';
        }

        $xml .= '</channel>';
        $xml .= '</rss>';

        return $xml;
    }
}


// --------------------------------------------------------------------------


/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core shop
 * models. Some might argue it's a little hacky but it's a simple 'fix'
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_FEED_MODEL')) {

    class Shop_feed_model extends NAILS_Shop_feed_model
    {
    }
}
