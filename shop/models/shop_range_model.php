<?php

/**
 * This model manages Shop Product ranges
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

use Nails\Common\Model\Base;

class Shop_range_model extends Base
{
    protected $shopUrl;

    // --------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();

        $this->table        = NAILS_DB_PREFIX . 'shop_range';
        $this->tablePrefix = 'sr';

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

            $data['sort'][] = array($this->tablePrefix . '.label', 'ASC');
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

            if (empty($this->db->ar_select)) {

                //  No selects have been called, call this so that we don't *just* get the product count
                $this->db->select($this->tablePrefix . '.*');
            }

            $sql  = 'SELECT COUNT(DISTINCT(`nspr`.`product_id`)) ';
            $sql .= 'FROM ' . NAILS_DB_PREFIX . 'shop_product_range nspr ';
            $sql .= 'JOIN ' . NAILS_DB_PREFIX . 'shop_product nsp ON `nspr`.`product_id` = `nsp`.`id` ';
            $sql .= 'WHERE ';
            $sql .= '`nspr`.`range_id` = `' . $this->tablePrefix . '`.`id` ';
            $sql .= 'AND `nsp`.`is_active` = 1 ';
            $sql .= 'AND `nsp`.`is_deleted` = 0';

            $this->db->select('(' . $sql . ') product_count', false);
        }

        // --------------------------------------------------------------------------

        //  Search
        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = array();
            }

            $data['or_like'][] = array(
                'column' => $this->tablePrefix . '.label',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tablePrefix . '.description',
                'value'  => $data['keywords']
            );
        }

        // --------------------------------------------------------------------------

        parent::getCountCommon($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a range by its ID
     * @param  integer $id   The range's ID
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
     * Return an array of ranges by their IDs
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
     * Returns a range by its slug
     * @param  string $slug the range's slug
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
     * Returns an array of ranges by their IDs
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
     * Returns a range by its ID or slug
     * @param  mixed  $idSlug The range's ID or slug
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
     * Creates a new range
     * @param  array   $data         The array of data to create the range with
     * @param  boolean $returnObject Whether to return the full range object or just the ID
     * @return mixed
     */
    public function create($data = array(), $return_object = false)
    {
        if (!empty($data['label'])) {

            $data['slug'] = $this->generateSlug($data['label']);
        }

        if (empty($data['cover_id'])) {

            $data['cover_id'] = null;
        }

        return parent::create($data, $return_object);
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing range
     * @param  integer $id   The ID of the range to update
     * @param  array   $data An array of data to update the range with
     * @return boolean
     */
    public function update($id, $data = array())
    {
        if (!empty($data->label)) {

            $data->slug = $this->generateSlug($data->label, '', '', null, null, $id);

        }

        if (empty($data->cover_id)) {

            $data->cover_id = null;
        }

        return parent::update($id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a range's URL
     * @param  string $slug The range's slug
     * @return string
     */
    public function formatUrl($slug)
    {
        return site_url($this->shopUrl . 'range/' . $slug);
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
