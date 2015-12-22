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

use Nails\Factory;

class NAILS_Shop_feed_model extends NAILS_Model
{
    protected $cacheFile;

    // --------------------------------------------------------------------------

    /**
     * Generates the feed
     * @param  string  $provider The provider to generate for
     * @param  string  $format   The format to generate
     * @return boolean
     */
    public function generate($provider, $format)
    {
        $methodProvider = strtolower($provider);
        $methodFormat   = ucfirst(strtolower($format));
        $method         = $methodProvider . 'Write' . $methodFormat;

        if (method_exists($this, $method)) {

            $data     = $this->getShopData();
            $fileData = $this->{$method}($data);

            if ($fileData) {

                Factory::helper('file');
                $cacheFile = $this->getCacheFile($provider, $format);

                if (write_file($cacheFile, $fileData)) {

                    return true;

                } else {

                    $error = 'Failed to write shop feed to disk "' . $provider . '" in format "' . $format . '"';
                    $this->setError($error);
                    return false;
                }

            } else {

                $error = 'Failed to generate data for "' . $provider . '" in format "' . $format . '"';
                $this->setError($error);
                return false;
            }

        } else {

            $error = 'Invalid feed parameters.';
            $this->setError($error);
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the path of the cache file for the appropriate feed file.
     * @param  string $provider The provider to serve
     * @param  string $format   The format to serve
     * @return string
     */
    public function serve($provider, $format)
    {
        $cacheFile = $this->getCacheFile($provider, $format);

        if (is_file($cacheFile)) {
            return $cacheFile;
        }

        //  File doesn't exist, attempt to generate
        if ($this->generate($provider, $format)) {

            return $cacheFile;

        } else {

            return null;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of the shop items for the feed generators
     * @return array
     */
    protected function getShopData()
    {
        $oCurrencyModel    = Factory::model('Currency', 'nailsapp/module-shop');
        $sBaseCurrency     = appSetting('base_currency', 'shop');
        $oBaseCurrency     = $oCurrencyModel->getByCode($sBaseCurrency);
        $sWarehouseCountry = appSetting('warehouse_addr_country', 'shop');
        $sInvoiceCompany   = appSetting('invoice_company', 'shop');
        $products          = $this->shop_product_model->getAll();
        $out               = array();

        foreach ($products as $p) {
            foreach ($p->variations as $v) {

                $temp = new \stdClass();

                //  General product fields
                if ($p->label != $v->label) {

                    $temp->title = $p->label . ' - ' . $v->label;

                } else {

                    $temp->title = $p->label;
                }

                $temp->url             = $p->url;
                $temp->description     = trim(strip_tags($p->description));
                $temp->productId       = $p->id;
                $temp->variantId       = $v->id;
                $temp->condition       = 'new';
                $temp->sku             = $v->sku;
                $temp->google_category = $p->google_category;

                // --------------------------------------------------------------------------

                //  Work out the brand
                if (isset($p->brands[0])) {

                    $temp->brand = $p->brands[0]->label;

                } else {

                    $temp->brand = $sInvoiceCompany;
                }

                // --------------------------------------------------------------------------

                //  Work out the product type (category)
                if (!empty($p->categories)) {

                    $category = array();
                    foreach ($p->categories as $c) {

                        $category[] = $c->label;
                    }

                    $temp->category = implode(', ', $category);

                } else {

                    $temp->category = '';
                }

                // --------------------------------------------------------------------------

                //  Set the product image
                if ($p->featured_img) {

                    $temp->image = cdnServe($p->featured_img);

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

                //  Calculate price and price of shipping
                /**
                 * Tax/VAT should NOT be included:
                 * https://support.google.com/merchants/answer/2704214
                 */

                $sPrice         = $oCurrencyModel->formatBase($p->price->user->min_price_ex_tax, false);
                $sTax           = $oCurrencyModel->formatBase($p->price->user->min_price_tax, false);
                $sShippingPrice = $oCurrencyModel->formatBase($shippingData->base, false);

                $temp->price = $sPrice . ' ' . $oBaseCurrency->code;
                $temp->tax   = $sTax . ' ' . $oBaseCurrency->code;
                $temp->shipping_country = $sWarehouseCountry;
                $temp->shipping_service = 'Standard';
                $temp->shipping_price   = $sShippingPrice . ' ' . $oBaseCurrency->code;

                // --------------------------------------------------------------------------

                $out[] = $temp;
            }
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns XML data as expected by Google
     * @param  array $items The item array as generated by getShopData()
     * @return string
     */
    protected function googleWriteXml($items)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">';
        $xml .= '<channel>';
        $xml .= '<title><![CDATA[' . appSetting('invoice_company', 'shop') . ']]></title>';
        $xml .= '<description><![CDATA[' . appSetting('invoice_address', 'shop') . ']]></description>';
        $xml .= '<link><![CDATA[' . BASE_URL . ']]></link>';

        foreach ($items as $item) {

            $xml .= '<item>';
                $xml .= '<g:id>' . $item->productId . '.' . $item->variantId . '</g:id>';
                $xml .= '<title><![CDATA[' . htmlentities($item->title) . ']]></title>';
                $xml .= '<description><![CDATA[' . htmlentities($item->description) . ']]></description>';
                $xml .= '<g:product_type><![CDATA[' . htmlentities($item->category) . ']]></g:product_type>';
                $xml .= $item->google_category ? '<g:google_product_category>' . htmlentities($item->google_category) . '</g:google_product_category>' : '';
                $xml .= '<link>' . $item->url . '</link>';
                $xml .= '<g:image_link>' . $item->image . '</g:image_link>';
                $xml .= '<g:condition>' . $item->condition . '</g:condition>';
                $xml .= '<g:availability>' . $item->availability . '</g:availability>';
                $xml .= '<g:price>' . $item->price . '</g:price>';
                $xml .= '<g:tax>' . $item->tax . '</g:tax>';
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

    // --------------------------------------------------------------------------

    /**
     * Returns the path of the cachefile
     * @param  string $provider The provider to supply for
     * @param  string $format   The format to return in
     * @return string
     */
    protected function getCacheFile($provider, $format)
    {
        if (empty($this->cacheFile)) {

            $oDate = Factory::factory('DateTime');
            $this->cacheFile = DEPLOY_CACHE_DIR . 'shop-feed-' . $provider . '-' . $oDate->format('Y-m-d') . '.' . $format;
        }

        return $this->cacheFile;
    }

    // --------------------------------------------------------------------------

    public function searchGoogleCategories($term)
    {
        //  Open the cachefile, if it's not available then fetch a new one
        $cacheFile = DEPLOY_CACHE_DIR . 'shop-feed-google-categories-' . date('m-Y') . '.txt';

        if (!file_exists($cacheFile)) {

            //  @todo handle multiple locales
            $data = file_get_contents('http://www.google.com/basepages/producttype/taxonomy.en-GB.txt');

            if (empty($data)) {

                $this->setError('Failed to fetch feed from Google.');
                return false;
            }

            file_put_contents($cacheFile, $data);
        }

        $handle = fopen($cacheFile, 'r');
        $aResults = array();

        if ($handle) {

            while (($line = fgets($handle)) !== false) {

                if (substr($line, 0, 1) === '#') {
                    continue;
                }

                if (preg_match('/' . $term . '/i', $line)) {

                    $aResults[] = $line;
                }
            }

            fclose($handle);

            return $aResults;

        } else {

            $this->setError('Failed to read feed from cache.');
            return false;
        }
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
