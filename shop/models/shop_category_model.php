<?php

/**
 * This model manages Shop Product categories
 *
 * @package  Nails
 * @subpackage  module-shop
 * @category    Model
 * @author    Nails Dev Team
 * @link
 */

use Nails\Common\Model\Base;

class Shop_category_model extends Base
{
    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();

        $this->table        = NAILS_DB_PREFIX . 'shop_category';
        $this->tablePrefix = 'sc';

        // --------------------------------------------------------------------------

        //  Shop's base URL
        $this->shopUrl = $this->shop_model->getShopUrl();
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new category
     * @param  array   $data         The data array to create the category with
     * @param  boolean $returnObject Whether to return the new category's ID or the full category object
     * @return mixed
     */
    public function create($data, $returnObject = false)
    {
        //  Some basic sanity testing
        if (empty($data['label'])) {

            $this->setError('"label" is a required field.');
            return false;
        }

        if (empty($data['cover_id'])) {

            $data['cover_id'] = null;
        }

        // --------------------------------------------------------------------------

        $this->db->trans_begin();

        //  Create a new blank object to work with
        $_data = array('label' => $data['label']);
        $_id   = parent::create($_data);

        if (!$_id) {

            $this->setError('Unable to create base category object.');
            $this->db->trans_rollback();
            return false;

        } elseif ($this->update($_id, $data)) {

            $this->db->trans_commit();

            if ($returnObject) {

                return $this->getById($_id);

            } else {

                return $_id;
            }

        } else {

            $this->db->trans_rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Update an existing category
     * @param  integer $id   The category's ID
     * @param  array   $data An aray of data to update the category with
     * @return boolean
     */
    public function update($id, $data = array())
    {
        $_data = array();

        //  Prep the data
        if (empty($data['label'])) {

            $this->setError('"label" is a required field.');
            return false;

        } else {

            $_data['label'] = trim($data['label']);
        }

        if (isset($data['parent_id'])) {

            $_data['parent_id'] = $data['parent_id'] ? $data['parent_id'] : null;

            if ($_data['parent_id'] == $id) {

                $this->setError('"parent_id" cannot be the same as the category\'s ID.');
                return false;
            }
        }

        if (isset($data['cover_id'])) {

            $_data['cover_id'] = $data['cover_id'] ? $data['cover_id'] : null;
        }

        if (isset($data['description'])) {

            $_data['description'] = $data['description'];
        }

        if (isset($data['seo_title'])) {

            $_data['seo_title'] = strip_tags($data['seo_title']);
        }

        if (isset($data['seo_description'])) {

            $_data['seo_description'] = strip_tags($data['seo_description']);
        }

        if (isset($data['seo_keywords'])) {

            $_data['seo_keywords'] = strip_tags($data['seo_keywords']);
        }

        // --------------------------------------------------------------------------

        /**
         * Generate the slug
         * If there's a parent then prefix the slug with the parent's slug
         */

        if (!empty($_data['parent_id'])) {

            $this->db->select('slug');
            $this->db->where('id', $_data['parent_id']);
            $_parent = $this->db->get($this->table)->row();

            if (empty($_parent)) {

                $_prefix = '';

                //  Also, invalid aprent, so null out parent_id
                $_data['parent_id'] = null;

            } else {

                $_prefix = $_parent->slug . '/';
            }

        } else {

            //  No parent == no prefix
            $_prefix = '';
        }

        $_data['slug']     = $this->generateSlug($_data['label'], $_prefix, '', null, null, $id);
        $_data['slug_end'] = array_pop(explode('/', $_data['slug']));

        // --------------------------------------------------------------------------

        //  Find all childen
        $_data['children_ids'] = implode(',', $this->getIdsOfChildren($id));

        if (empty($_data['children_ids'])) {

            $_data['children_ids'] = null;
        }

        //  And all the [old] parents
        $_parents = $this->get_ids_of_parents($id);

        // --------------------------------------------------------------------------

        //  Attempt the update
        $this->db->trans_begin();

        if (parent::update($id, $_data)) {

            //  Success! Generate this category's breadcrumbs
            $_data                = array();
            $_data['breadcrumbs'] = json_encode($this->generateBreadcrumbs($id));

            if (!parent::update($id, $_data)) {

                $this->db->trans_rollback();
                $this->setError('Failed to update category breadcrumbs.');
                return false;
            }

            // --------------------------------------------------------------------------

            //  Also regenerate breadcrumbs and slugs for all children
            $_children = $this->getIdsOfChildren($id);

            if ($_children) {

                foreach ($_children as $child_id) {

                    $_child_data = new \stdClass();

                    //  Breadcrumbs is easy
                    $_child_data->breadcrumbs = json_encode($this->generateBreadcrumbs($child_id));

                    //  Slugs are slightly harder, we need to get the child's parent's slug and use it as a prefix
                    $this->db->select('parent_id, label');
                    $this->db->where('id', $child_id);
                    $_child = $this->db->get($this->table)->row();

                    if (!empty($_child)) {

                        $this->db->select('slug');
                        $this->db->where('id', $_child->parent_id);
                        $_parent = $this->db->get($this->table)->row();
                        $_prefix = empty($_parent) ? '' : $_parent->slug . '/';

                        $_child_data->slug     = $this->generateSlug($_child->label, $_prefix, '', null, null, $child_id);
                        $_child_data->slug_end = array_pop(explode('/', $_child_data->slug));
                    }

                    if (!parent::update($child_id, $_child_data)) {

                        $this->db->trans_rollback();
                        $this->setError('Failed to update child category.');
                        return false;
                    }
                }
            }

            // --------------------------------------------------------------------------

            //  Fetch the new parents
            $_parents = array_merge($_parents, $this->get_ids_of_parents($id));
            $_parents = array_filter($_parents);
            $_parents = array_unique($_parents);

            foreach ($_parents as $parent_id) {

                $_data                 = array();
                $_data['children_ids'] = implode(',', $this->getIdsOfChildren($parent_id));

                if (empty($_data['children_ids'])) {

                    $_data['children_ids'] = null;
                }

                if (!parent::update($parent_id, $_data)) {

                    $this->db->trans_rollback();
                    $this->setError('Failed to update parent\'s children IDs.');
                    return false;
                }
            }

            // --------------------------------------------------------------------------

            $this->db->trans_commit();
            return true;

        } else {

            $this->db->trans_rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a category and its children
     * @param  integer $id The ID of the category to delete
     * @return boolean
     */
    public function delete($id)
    {
        $current = $this->getById($id);

        if (!$current) {

            $this->setError('Invalid Category ID');
            return false;
        }

        $parents = $this->get_ids_of_parents($id);

        // --------------------------------------------------------------------------

        $this->db->trans_begin();

        if (parent::delete($id)) {

            foreach ($parents as $parentId) {

                $data                 = array();
                $data['children_ids'] = implode(',', $this->getIdsOfChildren($parentId));

                if (empty($data['children_ids'])) {

                    $data['children_ids'] = null;
                }

                if (!parent::update($parentId, $data)) {

                    $this->db->trans_rollback();
                    $this->setError('Failed to update parent\'s children IDs.');
                    return false;
                }
            }

            $this->db->trans_commit();
            return true;

        } else {

            $this->setError('Invalid Category ID');
            $this->db->trans_rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Generate breadcrumbs for a category
     * @param  integer $id The category's ID
     * @return array
     */
    protected function generateBreadcrumbs($id)
    {
        //  Fetch current
        $this->db->select('id,slug,label');
        $this->db->where('id', $id);
        $current = $this->db->get($this->table)->result();

        if (empty($current)) {

            return false;

        } else {

            foreach ($current as &$cat) {

                $cat->id = (int) $cat->id;
            }
        }

        //  Fetch parents
        $parents = $this->get_ids_of_parents($id);

        if (!empty($parents)) {

            $this->db->select('id,slug,label');
            $this->db->where_in('id', $parents);
            $parents = $this->db->get($this->table)->result();

            if (!empty($parents)) {

                foreach ($parents as &$cat) {

                    $cat->id = (int) $cat->id;
                }
            }
        }

        //  Finally, build breadcrumbs
        return array_merge($parents, $current);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of the IDs of all aprent categories
     * @param  integer $id The ID of the category whose parents to search for
     * @return array
     */
    public function get_ids_of_parents($id)
    {
        $return = array();

        $this->db->select('parent_id');
        $this->db->where('id', $id);
        $parent = $this->db->get($this->table)->row();

        if (!empty($parent->parent_id)) {

            $temp   = array($parent->parent_id);
            $return = array_merge($return, $temp, $this->get_ids_of_parents($parent->parent_id));
        }

        return array_unique(array_filter($return));
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches the IDs of a category's descendants
     * @param  int     $id            The ID of the starting category
     * @param  boolean $onlyImmediate Whether to recurscively fetch all descendants, or just the immediate descendants
     * @return array
     */
    public function getIdsOfChildren($id, $onlyImmediate = false)
    {
        $return = array();

        $this->db->select('id');
        $this->db->where('parent_id', $id);
        $children = $this->db->get($this->table)->result();

        if ($onlyImmediate) {

            foreach ($children as $child) {

                $return[] = $child->id;
            }

        } else {

            if (!empty($children)) {

                foreach ($children as $child) {

                    $_temp   = array($child->id);
                    $return = array_merge($return, $_temp, $this->getIdsOfChildren($child->id, false));
                }
            }
        }

        return array_unique(array_filter($return));
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a category's descendants in object form
     * @param  integer $categoryId    The ID of the category
     * @param  boolean $onlyImmediate Whether to only looka s far as the immediate children
     * @param  array   $data          An array of data to pass to getByIds()
     * @return array
     */
    public function get_children($categoryId, $onlyImmediate = false, $data = array())
    {
        $children = $this->getIdsOfChildren($categoryId, $onlyImmediate);

        if (!empty($children)) {

            return $this->getByIds($children, $data);
        }

        return array();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of a category's siblings' IDs
     * @param  integer $categoryId The category's ID
     * @return array
     */
    public function get_ids_of_siblings($categoryId)
    {
        $this->db->select('parent_id');
        $this->db->where('id', $categoryId);
        $parent = $this->db->get($this->table)->row();

        if (!empty($parent->parent_id)) {

            return array();
        }

        $this->db->where('id !=', $categoryId);
        return $this->getIdsOfChildren($parent->parent_id, true);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of a category's siblings
     * @param  integer $categoryId The category's ID
     * @param  array   $data       An array of data to pass to getByIds()
     * @return array
     */
    public function getSiblings($categoryId, $data = array())
    {
        $children = $this->get_ids_of_siblings($categoryId);

        if (!empty($children)) {

            return $this->getByIds($children, $data);
        }

        return array();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of all categories, nested
     * @param  array  $data An array of data to pass to getAll()
     * @return array
     */
    public function getAllNested($data = array())
    {
        return $this->nestItems($this->getAll(null, null, $data), null);
    }

    // --------------------------------------------------------------------------

    /**
     * Get all top level categories (i.e., those without a parent)
     * @param  array  $data an aray of data to pass to getAll()
     * @return array
     */
    public function getTopLevel($data = array())
    {
        if (empty($data['where'])) {

            $data['where'][] = array('parent_id', null);
        }

        if (!isset($data['include_count'])) {

            $data['include_count'] = true;
        }
        $this->getAll(null, null, $data);

        // --------------------------------------------------------------------------

        return $this->getAll(null, null, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Nest items
     * Hat tip to Timur: http://stackoverflow.com/a/9224696/789224
     * @param  array   &$list  An array of items to nest
     * @param  integer $parent The parent's ID
     * @return array
     */
    protected function nestItems(&$list, $parent = null)
    {
        $result = array();

        for ($i = 0, $c = count($list); $i < $c; $i++) {

            if ($list[$i]->parent_id == $parent) {

                $list[$i]->children = $this->nestItems($list, $list[$i]->id);
                $result[]           = $list[$i];
            }
        }

        return $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Get all nested categories as a flat array
     * @param  string $separator The string ot use as the category separator
     * @return array
     */
    public function getAllNestedFlat($separator = ' &rsaquo; ')
    {
        $categories = $this->getAll(null, null);
        $out        = array();

        foreach ($categories as $cat) {

            $out[$cat->id] = array();

            foreach ($cat->breadcrumbs as $crumb) {

                $out[$cat->id][] = $crumb->label;
            }

            $out[$cat->id] = implode($separator, $out[$cat->id]);
        }

        return $out;
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

            $data['sort'][] = array($this->tablePrefix . '.slug', 'ASC');
        }

        // --------------------------------------------------------------------------

        if (!empty($data['include_count'])) {

            if (empty($this->db->ar_select)) {

                //   No selects have been called, call this so that we don't *just* get the product count
                $prefix = $this->tablePrefix ? $this->tablePrefix . '.' : '';
                $this->db->select($prefix . '*');
            }

            $query  = 'SELECT COUNT(DISTINCT(`nspc`.`product_id`)) ';
            $query .= 'FROM ' . NAILS_DB_PREFIX . 'shop_product_category nspc ';
            $query .= 'JOIN ' . NAILS_DB_PREFIX . 'shop_product nsp ON `nspc`.`product_id` = `nsp`.`id` ';
            $query .= 'WHERE ';
            $query .= '(';
            $query .= '`nspc`.`category_id` = `' . $this->tablePrefix . '`.`id` ';
            $query .= 'OR FIND_IN_SET (`nspc`.`category_id`, `' . $this->tablePrefix . '`.`children_ids`)';
            $query .= ') ';
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

            $data['or_like'][] = array($this->tablePrefix . '.label', $data['keywords']);
            $data['or_like'][] = array($this->tablePrefix . '.description', $data['keywords']);
        }

        // --------------------------------------------------------------------------

        parent::getCountCommon($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a category's URL
     * @param  string $slug The category's slug
     * @return string
     */
    public function formatUrl($slug)
    {
        return site_url($this->shopUrl . 'category/' . $slug);
    }

    // --------------------------------------------------------------------------

    /**
     * If the seo_description or seo_keywords fields are empty this method will
     * generate some content for category.
     * @param  object $category A category object
     * @return void
     */
    public function generateSeoContent(&$category)
    {
        /**
         * Autogenerate some SEO content if it's not been set
         * Buy {{CATEGORY}} at {{STORE}}
         **/

        if (empty($category->seo_description)) {

            //  Base string
            $category->seo_description = 'Buy ' . $category->label . ' at ' . APP_NAME;
            $category->seo_description = htmlentities($category->seo_description);
        }

        if (empty($category->seo_keywords)) {

            //  Sanitise the description
            $description = strip_tags($category->description);
            $description = html_entity_decode($description);

            //  Append the category names, including parents
            foreach ($category->breadcrumbs as $crumb) {

                $description .=  ' ' . strtolower($crumb->label);
            }
            //  Trim and remove stop words
            $description = trim($description);
            $description = removeStopWords($description);

            //  Break it up and get the most frequently occurring words
            $description = strtolower($description);
            $description = str_replace("\n", ' ', strip_tags($description));
            $description = str_word_count($description, 1);
            $description = array_count_values($description);
            arsort($description);
            $description = array_keys($description);
            $description = array_slice($description, 0, 10);
            $category->seo_keywords = $description;

            //  Implode and encode entities
            $category->seo_keywords = array_unique($category->seo_keywords);
            $category->seo_keywords = implode(',', $category->seo_keywords);
            $category->seo_keywords = htmlentities($category->seo_keywords);
        }
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

        $oObj->children    = array();
        $oObj->breadcrumbs = (array) @json_decode($oObj->breadcrumbs);
        $oObj->depth       = count(explode('/', $oObj->slug)) - 1;
        $oObj->url         = $this->formatUrl($oObj->slug);
    }
}
