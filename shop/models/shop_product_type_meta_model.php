<?php

/**
 * This model manages Shop Product Type Meta fields
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

use Nails\Common\Model\Base;
use Nails\Factory;

class Shop_product_type_meta_model extends Base
{
    protected $tableTaxonomy;
    protected $tableTaxonomyPrefix;

    // --------------------------------------------------------------------------

    /**
     * construct the model
     */
    public function __construct()
    {
        parent::__construct();

        $this->table               = NAILS_DB_PREFIX . 'shop_product_type_meta_field';
        $this->tableAlias         = 'ptmf';
        $this->tableTaxonomy       = NAILS_DB_PREFIX . 'shop_product_type_meta_taxonomy';
        $this->tableTaxonomyPrefix = 'ptmt';
    }

    // --------------------------------------------------------------------------


    /**
     * Fetches all meta fields, optionally paginated.
     * @param int    $page           The page number of the results, if null then no pagination
     * @param int    $perPage        How many items per page of paginated results
     * @param mixed  $data           Any data to pass to getCountCommon()
     * @param bool   $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return array
     **/
    public function getAll($page = null, $per_page = null, $data = array(), $include_deleted = false)
    {
        //  If the first value is an array then treat as if called with getAll(null, null, $aData);
        //  @todo (Pablo - 2017-11-09) - Convert these to expandable fields
        if (is_array($page)) {
            $data = $page;
            $page = null;
        }

        $fields = parent::getAll($page, $per_page, $data, $include_deleted);
        $oDb    = Factory::service('Database');

        foreach ($fields as $field) {

            if (isset($data['includeAssociatedProductTypes'])) {

                $oDb->select('pt.id,pt.label');
                $oDb->join(NAILS_DB_PREFIX . 'shop_product_type pt', 'pt.id = ' . $this->tableTaxonomyPrefix . '.product_type_id');
                $oDb->group_by('pt.id');
                $oDb->order_by('pt.label');
                $oDb->where($this->tableTaxonomyPrefix . '.meta_field_id', $field->id);
                $field->associated_product_types = $oDb->get($this->tableTaxonomy . ' ' . $this->tableTaxonomyPrefix)->result();
            }
        }

        return $fields;
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

        //  Search
        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = array();
            }

            $data['or_like'][] = array(
                'column' => $this->tableAlias . '.label',
                'value'  => $data['keywords']
            );
        }

        // --------------------------------------------------------------------------

        parent::getCountCommon($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns meta fields which apply to a particular product type
     * @param  integer $typeId The type's ID
     * @return array
     */
    public function getByProductTypeId($typeId)
    {
        $oDb = Factory::service('Database');
        $oDb->where($this->tableTaxonomyPrefix . '.product_type_id', $typeId);
        return $this->getByProductType();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns meta fields which apply to a particular group of product types
     * @param  array $typeIds An array of IDs
     * @return array
     */
    public function getByProductTypeIds($typeIds)
    {
        $oDb = Factory::service('Database');
        $oDb->where_in($this->tableTaxonomyPrefix . '.product_type_id', $typeIds);
        return $this->getByProductType();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of meta fields which apply to a product type, abstracted
     * by getByProductTypeId() and getByProductTypeIds()
     * @return array
     */
    protected function getByProductType()
    {
        $oDb = Factory::service('Database');
        $oDb->select($this->tableAlias . '.*');
        $oDb->join($this->table  . ' ' . $this->tableAlias, $this->tableAlias . '.id = ' . $this->tableTaxonomyPrefix . '.meta_field_id');
        $oDb->group_by($this->tableAlias . '.id');
        $results = $oDb->get($this->tableTaxonomy . ' ' . $this->tableTaxonomyPrefix)->result();

        foreach ($results as $result) {

            $this->formatObject($result);
        }

        return $results;
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new product type meta field
     * @param  array   $data         The data array to create the product type meta field with
     * @param  boolean $returnObject Whether to return the full object or just the ID
     * @return mixed
     */
    public function create($data = array(), $returnObject = false)
    {
        $oDb = Factory::service('Database');
        $oDb->trans_begin();

        $associatedProductTypes = isset($data['associated_product_types']) ? $data['associated_product_types'] : array();
        unset($data['associated_product_types']);

        $result = parent::create($data, $returnObject);

        if (!$result) {

            $this->setError('Failed to create parent object.');
            $oDb->trans_rollback();
            return false;
        }

        $id = $oDb->insert_id();

        if ($associatedProductTypes) {

            $data = array();

            foreach ($associatedProductTypes as $productTypeId) {

                $data[] = array(
                    'product_type_id' => $productTypeId,
                    'meta_field_id'   => $id
                );
            }

            if (!$oDb->insert_batch($this->tableTaxonomy, $data)) {

                $this->setError('Failed to add new product type/meta field relationships.');
                $oDb->trans_rollback();
                return false;
            }
        }

        $oDb->trans_commit();
        return $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Update a product type meta field
     * @param  integer $id   The product type meta field's ID
     * @param  array   $data The data array to use in the update
     * @return boolean
     */
    public function update($id, $data = array())
    {
        $oDb = Factory::service('Database');
        $oDb->trans_begin();

        $associatedProductTypes = isset($data['associated_product_types']) ? $data['associated_product_types'] : array();
        unset($data['associated_product_types']);

        $result = parent::update($id, $data);

        if (!$result) {

            $this->setError('Failed to update parent object.');
            $oDb->trans_rollback();
            return false;
        }

        $oDb->where('meta_field_id', $id);
        if (!$oDb->delete($this->tableTaxonomy)) {

            $this->setError('Failed to remove existing product type/meta field relationships.');
            $oDb->trans_rollback();
            return false;
        }

        if ($associatedProductTypes) {

            $data = array();

            foreach ($associatedProductTypes as $productTypeId) {

                $data[] = array(
                    'product_type_id' => $productTypeId,
                    'meta_field_id'   => $id
                );
            }

            if (!$oDb->insert_batch($this->tableTaxonomy, $data)) {

                $this->setError('Failed to add new product type/meta field relationships.');
                $oDb->trans_rollback();
                return false;
            }
        }

        $oDb->trans_commit();
        return true;
    }
}
