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

class NAILS_Shop_supplier_model extends NAILS_Model
{
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
    protected function _getcount_common($data = array())
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

        parent::_getcount_common($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a supplier by its ID
     * @param  integer $id   The suppliers's ID
     * @param  array   $data An array of data to pass to _getcount_common();
     * @return mixed         stdClass on success, false on failure
     */
    public function get_by_id($id, $data = array())
    {
        if (!isset($data['only_active'])) {

            $data['only_active'] = false;
        }

        // --------------------------------------------------------------------------

        return parent::get_by_id($id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Return an array of supplierss by their IDs
     * @param  array  $ids  An array if IDs
     * @param  array  $data An array of data to pass to _getcount_common();
     * @return array
     */
    public function get_by_ids($ids, $data = array())
    {
        if (!isset($data['only_active'])) {

            $data['only_active'] = false;
        }

        // --------------------------------------------------------------------------

        return parent::get_by_ids($ids, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a supplier by its slug
     * @param  string $slug the supplier's slug
     * @param  array  $data An array of data to pass to _getcount_common();
     * @return mixed        stdClass on success, false on failure
     */
    public function get_by_slug($slug, $data = array())
    {
        if (!isset($data['only_active'])) {

            $data['only_active'] = false;
        }

        // --------------------------------------------------------------------------

        return parent::get_by_slug($slug, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of suppliers by their IDs
     * @param  array  $slugs An array of IDs
     * @param  array  $data  An array of data to pass to _getcount_common();
     * @return array
     */
    public function get_by_slugs($slugs, $data = array())
    {
        if (!isset($data['only_active'])) {

            $data['only_active'] = false;
        }

        // --------------------------------------------------------------------------

        return parent::get_by_slugs($slugs, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a supplier by its ID or slug
     * @param  mixed  $idSlug The supplier's ID or slug
     * @param  array  $data   An array of data to pass to _getcount_common();
     * @return mixed          stdClass on success, false on failure
     */
    public function get_by_id_or_slug($idSlug, $data = array())
    {
        if (!isset($data['only_active'])) {

            $data['only_active'] = false;
        }

        // --------------------------------------------------------------------------

        return parent::get_by_id_or_slug($idSlug, $data);
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

            $data['slug'] = $this->_generate_slug($data['label']);
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

            $data['slug'] = $this->_generate_slug($data['label'], '', '', null, null, $id);
        }

        return parent::update($id, $data);
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_SUPPLIER_MODEL')) {

    class Shop_supplier_model extends NAILS_Shop_supplier_model
    {
    }
}
