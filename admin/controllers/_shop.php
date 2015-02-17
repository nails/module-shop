<?php

class NAILS_Shop extends NAILS_Admin_Controller
{
    protected $reportSources;
    protected $reportFormats;

    // --------------------------------------------------------------------------

    /**
     * Returns an array of notifications
     * @param  string $classIndex The class_index value, used when multiple admin instances are available
     * @return array
     */
    static function notifications($classIndex = null)
    {
        $ci =& get_instance();
        $notifications = array();

        // --------------------------------------------------------------------------

        get_instance()->load->model('shop/shop_order_model');

        $notifications['orders']            = array();
        $notifications['orders']['type']    = 'alert';
        $notifications['orders']['title']   = 'Unfulfilled orders';
        $notifications['orders']['value']   = get_instance()->shop_order_model->count_unfulfilled_orders();

        // --------------------------------------------------------------------------

        return $notifications;
    }
}
