<?php

/**
 * This model manages Shop Product collections
 *
 * @package  Nails
 * @subpackage  module-shop
 * @category    Model
 * @author    Nails Dev Team
 * @link
 */

class NAILS_Shop_collection_model extends NAILS_Model
{
    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();

        $this->_table        = NAILS_DB_PREFIX . 'shop_collection';
        $this->_table_prefix = 'sc';

        // --------------------------------------------------------------------------

        //  Shop's base URL
        $this->shopUrl = $this->shop_model->getShopUrl();
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     * @param array  $data    Data passed from the calling method
     * @param string $_caller The name of the calling method
     * @return void
     **/
    protected function _getcount_common($data = array(), $_caller = null)
    {
        //  Default sort
        if (empty($data['sort'])) {

            if (empty($data['sort'])) {

                $data['sort'] = array();
            }

            $data['sort'][] = array($this->_table_prefix . '.label', 'ASC');
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
                $prefix = $this->_table_prefix ? $this->_table_prefix . '.' : '';
                $this->db->select($prefix . '*');
            }

            $query  = 'SELECT COUNT(DISTINCT(`nspc`.`product_id`)) ';
            $query .= 'FROM ' . NAILS_DB_PREFIX . 'shop_product_collection nspc ';
            $query .= 'JOIN ' . NAILS_DB_PREFIX . 'shop_product nsp ON `nspc`.`product_id` = `nsp`.`id` ';
            $query .= 'WHERE ';
            $query .= '`nspc`.`collection_id` = `' . $this->_table_prefix . '`.`id` ';
            $query .= 'AND `nsp`.`is_active` = 1 ';
            $query .= 'AND `nsp`.`is_deleted` = 0';

            $this->db->select('(' . $query . ') product_count', false);
        }

        // --------------------------------------------------------------------------

        //  Search
        if (!empty($data['keywords'])) {

            $data['like']   = array();
            $data['like'][] = array(
                'column' => $this->_table_prefix . '.label',
                'value'  => $data['keywords']
            );
            $data['like'][] = array(
                'column' => $this->_table_prefix . '.description',
                'value'  => $data['keywords']
            );
        }

        // --------------------------------------------------------------------------

        parent::_getcount_common($data, $_caller);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a collection by its ID
     * @param  integer $id   The collection's ID
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
     * Return an array of collections by their IDs
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
     * Returns a collection by its slug
     * @param  string $slug the collection's slug
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
     * Returns an array of collections by their IDs
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
     * Returns a collection by its ID or slug
     * @param  mixed  $idSlug The collection's ID or slug
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
     * Creates a new collection
     * @param  array   $data         The array of data to create the collection with
     * @param  boolean $returnObject Whether to return the full collection object or just the ID
     * @return mixed
     */
    public function create($data = array(), $return_object = false)
    {
        if (!empty($data['label'])) {

            $data['slug'] = $this->_generate_slug($data['label']);
        }

        if (empty($data['cover_id'])) {

            $data['cover_id'] = null;
        }

        return parent::create($data, $return_object);
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing collection
     * @param  integer $id   The ID of the collection to update
     * @param  array   $data An array of data to update the collection with
     * @return boolean
     */
    public function update($id, $data = array())
    {
        if (!empty($data['label'])) {

            $data['slug'] = $this->_generate_slug($data['label'], '', '', null, null, $id);
        }

        if (empty($data['cover_id'])) {

            $data['cover_id'] = null;
        }

        return parent::update($id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a collection's URL
     * @param  string $slug The collection's slug
     * @return string
     */
    public function format_url($slug)
    {
        return site_url($this->shopUrl . 'collection/' . $slug);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a collection object
     * @param  stdClass &$object The collection object to format
     * @return void
     */
    protected function _format_object(&$object)
    {
        //  Type casting
        $object->id          = (int) $object->id;
        $object->created_by  = $object->created_by ? (int) $object->created_by : null;
        $object->modified_by = $object->modified_by ? (int) $object->modified_by : null;
        $object->url         = $this->format_url($object->slug);
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_COLLECTION_MODEL')) {

    class Shop_collection_model extends NAILS_Shop_collection_model
    {
    }
}
