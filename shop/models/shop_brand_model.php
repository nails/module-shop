<?php

/**
 * This model manages Shop Product brands
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

use Nails\Common\Model\Base;
use Nails\Factory;

class Shop_brand_model extends Base
{
    protected $shopUrl;

    // --------------------------------------------------------------------------

    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();

        $this->table       = NAILS_DB_PREFIX . 'shop_brand';
        $this->tableAlias = 'sb';

        // --------------------------------------------------------------------------

        //  Shop's base URL
        $this->shopUrl = $this->shop_model->getShopUrl();
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     * @param  array $data Data passed from the calling method
     * @return void
     **/
    protected function getCountCommon($data = array())
    {
        //  Default sort
        if (empty($data['sort'])) {

            if (empty($data['sort'])) {

                $data['sort'] = array();
            }

            $data['sort'][] = array($this->tableAlias . '.label', 'ASC');
        }

        // --------------------------------------------------------------------------

        //  Only include active items?
        if (isset($data['only_active'])) {

            $onlyActive = (bool) $data['only_active'];

        } else {

            $onlyActive = true;
        }

        if ($onlyActive) {

            if (!isset($data['where'])) {

                $data['where'] = array();
            }

            $data['where'][] = array('is_active', true);
        }

        // --------------------------------------------------------------------------

        if (!empty($data['include_count'])) {

            $oDb = Factory::service('Database');

            if (empty($oDb->ar_select)) {

                //  No selects have been called, call this so that we don't *just* get the product count
                $prefix = $this->tableAlias ? $this->tableAlias . '.' : '';
                $oDb->select($prefix . '*');
            }

            $query  = 'SELECT COUNT(DISTINCT(`nspb`.`product_id`)) ';
            $query .= 'FROM ' . NAILS_DB_PREFIX . 'shop_product_brand nspb ';
            $query .= 'JOIN ' . NAILS_DB_PREFIX . 'shop_product nsp ON `nspb`.`product_id` = `nsp`.`id` ';
            $query .= 'WHERE ';
            $query .= '`nspb`.`brand_id` = `' . $this->tableAlias . '`.`id` ';
            $query .= 'AND `nsp`.`is_active` = 1 ';
            $query .= 'AND `nsp`.`is_deleted` = 0';

            $oDb->select('(' . $query . ') product_count', false);
        }

        // --------------------------------------------------------------------------

        //  Search
        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = array();
            }

            $data['or_like'][] = array(
                'column' => $this->tableAlias . '.label',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tableAlias . '.description',
                'value'  => $data['keywords']
            );
        }

        // --------------------------------------------------------------------------

        parent::getCountCommon($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a brand by its ID
     * @param  integer $id   The brand's ID
     * @param  array   $data An array of data to pass to getCountCommon();
     * @return mixed         stdClass on success, false on failure
     */
    public function getById($id, $data = array())
    {
        if (!isset($data['only_active'])) {

            $data['only_active'] = false;
        }

        // --------------------------------------------------------------------------

        return parent::getById($id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Return an array of brands by their IDs
     * @param  array  $ids  An array if IDs
     * @param  array  $data An array of data to pass to getCountCommon();
     * @return array
     */
    public function getByIds($ids, $data = array())
    {
        if (!isset($data['only_active'])) {

            $data['only_active'] = false;
        }

        // --------------------------------------------------------------------------

        return parent::getByIds($ids, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a brand by its slug
     * @param  string $slug the brand's slug
     * @param  array  $data An array of data to pass to getCountCommon();
     * @return mixed        stdClass on success, false on failure
     */
    public function getBySlug($slug, $data = array())
    {
        if (!isset($data['only_active'])) {

            $data['only_active'] = false;
        }

        // --------------------------------------------------------------------------

        return parent::getBySlug($slug, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of brands by their IDs
     * @param  array  $slugs An array of IDs
     * @param  array  $data  An array of data to pass to getCountCommon();
     * @return array
     */
    public function getBySlugs($slugs, $data = array())
    {
        if (!isset($data['only_active'])) {

            $data['only_active'] = false;
        }

        // --------------------------------------------------------------------------

        return parent::getBySlugs($slugs, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a brand by its ID or slug
     * @param  mixed  $idSlug The brand's ID or slug
     * @param  array  $data   An array of data to pass to getCountCommon();
     * @return mixed          stdClass on success, false on failure
     */
    public function getByIdOrSlug($idSlug, $data = array())
    {
        if (!isset($data['only_active'])) {

            $data['only_active'] = false;
        }

        // --------------------------------------------------------------------------

        return parent::getByIdOrSlug($idSlug, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new brand
     * @param  array   $data         The array of data to create the brand with
     * @param  boolean $returnObject Whether to return the full brand object or just the ID
     * @return mixed
     */
    public function create($data = array(), $returnObject = false)
    {
        if (!empty($data['label'])) {

            $data['slug'] = $this->generateSlug($data['label']);
        }

        return parent::create($data, $returnObject);
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing brand
     * @param  integer $id   The ID of the brand to update
     * @param  array   $data An array of data to update the brand with
     * @return boolean
     */
    public function update($id, $data = array())
    {
        if (!empty($data['label'])) {

            $data['slug'] = $this->generateSlug($data['label'], '', '', null, null, $id);
        }

        return parent::update($id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a brand's URL
     * @param  string $slug The brand's slug
     * @return string
     */
    public function formatUrl($slug)
    {
        return site_url($this->shopUrl . 'brand/' . $slug);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a single object
     *
     * The getAll() method iterates over each returned item with this method so as to
     * correctly format the output. Use this to cast integers and booleans and/or organise data into objects.
     *
     * @param  object $oObj      A reference to the object being formatted.
     * @param  array  $aData     The same data array which is passed to _getcount_common, for reference if needed
     * @param  array  $aIntegers Fields which should be cast as integers if numerical and not null
     * @param  array  $aBools    Fields which should be cast as booleans if not null
     * @param  array  $aFloats   Fields which should be cast as floats if not null
     * @return void
     */
    protected function formatObject(
        &$oObj,
        $aData = array(),
        $aIntegers = array(),
        $aBools = array(),
        $aFloats = array()
    ) {

        parent::formatObject($oObj, $aData, $aIntegers, $aBools, $aFloats);
        $oObj->url = $this->formatUrl($oObj->slug);
    }
}
