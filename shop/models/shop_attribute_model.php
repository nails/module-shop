<?php

/**
 * This model manages Shop Product attributes
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

use Nails\Common\Model\Base;
use Nails\Factory;

class Shop_attribute_model extends Base
{
    /**
     * Cosntruct the model
     */
    public function __construct()
    {
        parent::__construct();

        $this->table        = NAILS_DB_PREFIX . 'shop_attribute';
        $this->tableAlias = 'sa';
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

            $sql  = 'SELECT COUNT(*) FROM ' . NAILS_DB_PREFIX .  'shop_product_attribute ';
            $sql .= 'WHERE `attribute_id` = `' . $this->tableAlias . '`.`id`';

            $oDb->select('(' . $sql . ') product_count');
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
