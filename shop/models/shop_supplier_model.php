<?php

/**
 * This model manages Shop Product suppliers
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

use Nails\Common\Model\Base;

class Shop_supplier_model extends Base
{
    protected $shopUrl;

    // --------------------------------------------------------------------------

    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();

        $this->table       = NAILS_DB_PREFIX . 'shop_supplier';
        $this->tablePrefix = 'ss';

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
                $prefix = $this->tablePrefix ? $this->tablePrefix . '.' : '';
                $this->db->select($prefix . '*');
            }

            $query  = 'SELECT COUNT(DISTINCT(`nsps`.`product_id`)) ';
            $query .= 'FROM ' . NAILS_DB_PREFIX . 'shop_product_supplier nsps ';
            $query .= 'JOIN ' . NAILS_DB_PREFIX . 'shop_product nsp ON `nsps`.`product_id` = `nsp`.`id` ';
            $query .= 'WHERE ';
            $query .= '`nsps`.`supplier_id` = `' . $this->tablePrefix . '`.`id` ';
            $query .= 'AND `nsp`.`is_active` = 1 ';
            $query .= 'AND `nsp`.`is_deleted` = 0';

            $this->db->select('(' . $query . ') product_count', false);
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
        }

        // --------------------------------------------------------------------------

        parent::getCountCommon($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a supplier by its ID
     * @param  integer $id   The suppliers's ID
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
     * Return an array of supplierss by their IDs
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
     * Returns a supplier by its slug
     * @param  string $slug the supplier's slug
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
     * Returns an array of suppliers by their IDs
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
     * Returns a supplier by its ID or slug
     * @param  mixed  $idSlug The supplier's ID or slug
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
     * Creates a new supplier
     * @param  array   $data         The array of data to create the supplier with
     * @param  boolean $returnObject Whether to return the full supplier object or just the ID
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
     * Updates an existing supplier
     * @param  integer $id   The ID of the supplier to update
     * @param  array   $data An array of data to update the supplier with
     * @return boolean
     */
    public function update($id, $data = array())
    {
        if (!empty($data['label'])) {

            $data['slug'] = $this->generateSlug($data['label'], '', '', null, null, $id);
        }

        return parent::update($id, $data);
    }
}
