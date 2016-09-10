<?php

/**
 * This model manages Shop Product sales
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

use Nails\Common\Model\Base;

class Shop_sale_model extends Base
{
    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();

        $this->table        = NAILS_DB_PREFIX . 'shop_sale';
        $this->tableAlias = 'ss';
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
        if (empty($data['sort'])) {

            $data['sort'] = 'label';

        } else {

            $data = array('sort' => 'label');
        }

        // --------------------------------------------------------------------------

        if (!empty($data['include_count'])) {

            if (empty($this->db->ar_select)) {

                //  No selects have been called, call this so that we don't *just* get the product count
                $_prefix = $this->tableAlias ? $this->tableAlias . '.' : '';
                $this->db->select($_prefix . '*');
            }

            $this->db->select('(SELECT COUNT(*) FROM ' . NAILS_DB_PREFIX .  'shop_product_sale sps LEFT JOIN ' . NAILS_DB_PREFIX . 'shop_product p ON p.id = sps.product_id  WHERE sps.sale_id = s.id AND p.is_active = 1) product_count');
        }

        // --------------------------------------------------------------------------

        parent::getCountCommon($data);
    }
}
