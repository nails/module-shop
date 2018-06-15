<?php

/**
 * This model manages Shop Product Types
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

use Nails\Common\Model\Base;
use Nails\Factory;

class Shop_product_type_model extends Base
{
    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();

        $this->table              = NAILS_DB_PREFIX . 'shop_product_type';
        $this->tableAlias       = 'spt';
        $this->destructiveDelete = false;
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

        if (!empty($data['include_count'])) {

            $oDb = Factory::service('Database');
            if (empty($oDb->ar_select)) {

                //  No selects have been called, call this so that we don't *just* get the product count
                $oDb->select($this->tableAlias . '.*');
            }

            $oDb->select('(SELECT COUNT(*) FROM ' . NAILS_DB_PREFIX .  'shop_product WHERE type_id = ' . $this->tableAlias . '.id) product_count');
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
}
