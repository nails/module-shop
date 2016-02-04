<?php

/**
 * This model manages Shop Product tags
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

use Nails\Common\Model\Base;

class Shop_tag_model extends Base
{
    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();

        $this->table        = NAILS_DB_PREFIX . 'shop_tag';
        $this->tablePrefix = 'st';

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

        if (!empty($data['include_count'])) {

            if (empty($this->db->ar_select)) {

                //  No selects have been called, call this so that we don't *just* get the product count
                $this->db->select($this->tablePrefix . '.*');
            }

            $this->db->select('(SELECT COUNT(*) FROM ' . NAILS_DB_PREFIX .  'shop_product_tag WHERE tag_id = ' . $this->tablePrefix . '.id) product_count');
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
     * Create a new tag
     * @param  array   $data         The data array to create the tag from
     * @param  boolean $returnObject Whether to return the complete object, or just the ID
     * @return mixed
     */
    public function create($data = array(), $returnObject = false)
    {
        if (!empty($data['label'])) {

            $data['slug'] = $this->generateSlug($data['label']);
        }

        if (empty($data['cover_id'])) {

            $data['cover_id'] = null;
        }

        return parent::create($data, $returnObject);
    }

    // --------------------------------------------------------------------------

    /**
     * Update a tag
     * @param  integer $id   The tag's ID
     * @param  array   $data The data array to update the tag with
     * @return boolean
     */
    public function update($id, $data = array())
    {
        if (!empty($data['label'])) {

            $data['slug'] = $this->generateSlug($data['label'], '', '', null, null, $id);
        }

        if (empty($data['cover_id'])) {

            $data['cover_id'] = null;
        }

        return parent::update($id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a tag's URL
     * @param  string $slug The tag's slug
     * @return string
     */
    public function formatUrl($slug)
    {
        return site_url($this->shopUrl . 'tag/' . $slug);
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
