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

class NAILS_Shop_tag_model extends NAILS_Model
{
    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();

        $this->_table        = NAILS_DB_PREFIX . 'shop_tag';
        $this->_table_prefix = 'st';

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

        if (!empty($data['include_count'])) {

            if (empty($this->db->ar_select)) {

                //  No selects have been called, call this so that we don't *just* get the product count
                $this->db->select($this->_table_prefix . '.*');
            }

            $this->db->select('(SELECT COUNT(*) FROM ' . NAILS_DB_PREFIX .  'shop_product_tag WHERE tag_id = ' . $this->_table_prefix . '.id) product_count');
        }

        // --------------------------------------------------------------------------

        //  Search
        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = array();
            }

            $data['or_like'][] = array(
                'column' => $this->_table_prefix . '.label',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->_table_prefix . '.description',
                'value'  => $data['keywords']
            );
        }

        // --------------------------------------------------------------------------

        parent::_getcount_common($data, $_caller);
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

            $data['slug'] = $this->_generate_slug($data['label']);
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

            $data['slug'] = $this->_generate_slug($data['label'], '', '', null, null, $id);
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
    public function format_url($slug)
    {
        return site_url($this->shopUrl . 'tag/' . $slug);
    }

    // --------------------------------------------------------------------------

    /**
     * Format a tag object
     * @param  stdClass &$object The tag object to format
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_TAG_MODEL')) {

    class Shop_tag_model extends NAILS_Shop_tag_model
    {
    }
}
