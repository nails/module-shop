<?php

//  Include NAILS_Admin_Controller; executes common admin functionality.
require_once '_admin.php';

/**
 * Manage the shop
 *
 * @package     Nails
 * @subpackage  module-admin
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Shop extends NAILS_Admin_Controller
{
    protected $reportSources;
    protected $reportFormats;

    // --------------------------------------------------------------------------

     /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (!isModuleEnabled('nailsapp/module-shop')) {

            return false;
        }

        // --------------------------------------------------------------------------

        $d = new \stdClass();

        // --------------------------------------------------------------------------

        //  Configurations
        $d->name = 'Shop';
        $d->icon = 'fa-shopping-cart';

        // --------------------------------------------------------------------------

        //  Navigation options
        $d->funcs               = array();

        if (userHasPermission('admin.shop{0.inventory_manage')) {

            $d->funcs['inventory'] = 'Manage Inventory';
        }

        if (userHasPermission('admin.shop{0.orders_manage')) {

            $d->funcs['orders'] = 'Manage Orders';
        }

        if (userHasPermission('admin.shop{0.vouchers_manage')) {

            $d->funcs['vouchers'] = 'Manage Vouchers';
        }

        if (userHasPermission('admin.shop{0.sale_manage')) {

            $d->funcs['sales'] = 'Manage Sales';
        }

        //  @TODO: Handle permissions here?
        $d->funcs['manage'] = 'Other Managers';

        if (userHasPermission('admin.shop{0.can_generate_reports')) {

            $d->funcs['reports'] = 'Generate Reports';
        }

        if (userHasPermission('admin.shop{0.notifications_manage')) {

            $d->funcs['product_availability_notifications'] = 'Product Availability Notifications';
        }

        // --------------------------------------------------------------------------

        return $d;
    }

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

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     * @param  string $classIndex The class_index value, used when multiple admin instances are available
     * @return array
     */
    static function permissions($classIndex = null)
    {
        $permissions = parent::permissions($classIndex);

        // --------------------------------------------------------------------------

        //  Inventory
        $permissions['inventory_manage']  = 'Inventory: Manage';
        $permissions['inventory_create']  = 'Inventory: Create';
        $permissions['inventory_edit']    = 'Inventory: Edit';
        $permissions['inventory_delete']  = 'Inventory: Delete';
        $permissions['inventory_restore'] = 'Inventory: Restore';

        //  Orders
        $permissions['orders_manage']    = 'Orders: Manage';
        $permissions['orders_view']      = 'Orders: View';
        $permissions['orders_edit']      = 'Orders: Edit';
        $permissions['orders_reprocess'] = 'Orders: Reprocess';
        $permissions['orders_process']   = 'Orders: Process';

        //  Vouchers
        $permissions['vouchers_manage']     = 'Vouchers: Manage';
        $permissions['vouchers_create']     = 'Vouchers: Create';
        $permissions['vouchers_activate']   = 'Vouchers: Activate';
        $permissions['vouchers_deactivate'] = 'Vouchers: Deactivate';

        //  Attributes
        $permissions['attribute_create'] = 'Attribute: Create';
        $permissions['attribute_create'] = 'Attribute: Create';
        $permissions['attribute_edit']   = 'Attribute: Edit';
        $permissions['attribute_delete'] = 'Attribute: Delete';

        //  Brands
        $permissions['brand_manage'] = 'Brand: Manage';
        $permissions['brand_create'] = 'Brand: Create';
        $permissions['brand_edit']   = 'Brand: Edit';
        $permissions['brand_delete'] = 'Brand: Delete';

        //  Categories
        $permissions['category_manage'] = 'Category: Manage';
        $permissions['category_create'] = 'Category: Create';
        $permissions['category_edit']   = 'Category: Edit';
        $permissions['category_delete'] = 'Category: Delete';

        //  Collections
        $permissions['collection_manage'] = 'Collection: Manage';
        $permissions['collection_create'] = 'Collection: Create';
        $permissions['collection_edit']   = 'Collection: Edit';
        $permissions['collection_delete'] = 'Collection: Delete';

        //  Ranges
        $permissions['range_manage'] = 'Range: Manage';
        $permissions['range_create'] = 'Range: Create';
        $permissions['range_edit']   = 'Range: Edit';
        $permissions['range_delete'] = 'Range: Delete';

        //  Sales
        $permissions['sale_manage'] = 'Sale: Manage';
        $permissions['sale_create'] = 'Sale: Create';
        $permissions['sale_edit']   = 'Sale: Edit';
        $permissions['sale_delete'] = 'Sale: Delete';

        //  Tags
        $permissions['tag_manage'] = 'Tag: Manage';
        $permissions['tag_create'] = 'Tag: Create';
        $permissions['tag_edit']   = 'Tag: Edit';
        $permissions['tag_delete'] = 'Tag: Delete';

        //  Tax Rates
        $permissions['tax_rate_manage'] = 'Tax Rate: Manage';
        $permissions['tax_rate_create'] = 'Tax Rate: Create';
        $permissions['tax_rate_edit']   = 'Tax Rate: Edit';
        $permissions['tax_rate_delete'] = 'Tax Rate: Delete';

        //  Product Types
        $permissions['product_type_manage'] = 'Product Type: Manage';
        $permissions['product_type_create'] = 'Product Type: Create';
        $permissions['product_type_edit']   = 'Product Type: Edit';
        $permissions['product_type_delete'] = 'Product Type: Delete';

        //  Product Type Meta Fields
        $permissions['product_type_meta_manage'] = 'Product Type Meta: Manage';
        $permissions['product_type_meta_create'] = 'Product Type Meta: Create';
        $permissions['product_type_meta_edit']   = 'Product Type Meta: Edit';
        $permissions['product_type_meta_delete'] = 'Product Type Meta: Delete';

        //  Reports
        $permissions['can_generate_reports']= 'Can generate Reports';

        //  Notifications
        $permissions['notifications_manage'] = 'Can manage Product notifications';
        $permissions['notifications_create'] = 'Can create Product notifications';
        $permissions['notifications_edit']   = 'Can edit Product notifications';
        $permissions['notifications_delete'] = 'Can delete Product notifications';

        // --------------------------------------------------------------------------

        return $permissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Constructs the controller
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        //  Defaults defaults

        $this->shop_orders_group            = false;
        $this->shop_orders_where            = array();
        $this->shop_orders_actions          = array();
        $this->shop_orders_sortfields       = array();

        $this->shop_vouchers_group          = false;
        $this->shop_vouchers_where          = array();
        $this->shop_vouchers_actions        = array();
        $this->shop_vouchers_sortfields     = array();

        // --------------------------------------------------------------------------

        $this->shop_orders_sortfields[] = array('label' => 'ID', 'col' => 'o.id');
        $this->shop_orders_sortfields[] = array('label' => 'Date Placed', 'col' => 'o.created');
        $this->shop_orders_sortfields[] = array('label' => 'Last Modified', 'col' => 'o.modified');

        $this->shop_vouchers_sortfields[] = array('label' => 'ID', 'col' => 'v.id');
        $this->shop_vouchers_sortfields[] = array('label' => 'Code', 'col' => 'v.code');

        // --------------------------------------------------------------------------

        //  Load models which this controller depends on
        $this->load->model('shop/shop_model');
        $this->load->model('shop/shop_currency_model');
        $this->load->model('shop/shop_product_model');
        $this->load->model('shop/shop_product_type_model');
        $this->load->model('shop/shop_tax_rate_model');
        $this->load->model('shop/shop_product_type_meta_model');

        // --------------------------------------------------------------------------

        $this->data['shop_url'] = app_setting('url', 'shop') ? app_setting('url', 'shop') : 'shop/';
    }

}

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' ADMIN MODULES
 *
 * The following block of code makes it simple to extend one of the core admin
 * controllers. Some might argue it's a little hacky but it's a simple 'fix'
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
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION_CLASSNAME
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (!defined('NAILS_ALLOW_EXTENSION_SHOP')) {

    /**
     * Proxy class for NAILS_Shop
     */
    class Shop extends NAILS_Shop
    {
    }
}
