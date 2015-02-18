<?php

//  Include _shop.php; executes common functionality
require_once '_shop.php';

/**
 * This class provides order functionality
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Orders extends NAILS_Shop_Controller
{
    /**
     * Cosntruct the controller
     */
    public function __construct()
    {
        parent::__construct();

        //  Load the skin to use
        $this->loadSkin('front');
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the invoice
     * @return void
     */
    public function invoice()
    {
        $this->data['order'] = $this->shop_order_model->get_by_ref($this->uri->segment(4));

        //  Order exist?
        if (!$this->data['order']) {

            return $this->badInvoice('Invoice does not exist.');
        }

        // --------------------------------------------------------------------------

        //  User have permission?
        $idMatch    = $this->data['order']->user->id && $this->data['order']->user->id != activeUser('id');
        $emailMatch = $this->data['order']->user->email && $this->data['order']->user->email != activeUser('email');

        if (!$this->user_model->isAdmin() && !$idMatch && !$emailMatch) {

            return $this->badInvoice('Permission Denied.');
        }

        // --------------------------------------------------------------------------

        //  Render PDF
        if (isset($_GET['dl']) && !$_GET['dl']) {

            $this->load->view($this->skin->path . 'views/order/invoice', $this->data);

        } else {

            $this->load->library('pdf/pdf');
            $this->pdf->load_view($this->skin->path . 'views/order/invoice', $this->data);
            $this->pdf->stream('INVOICE-' . $this->data['order']->ref . '.pdf');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the "bad invoice" page
     * @param  string $message The reason for the failure
     * @return void
     */
    protected function badInvoice($message)
    {
        $this->output->set_content_type('application/json');
        $this->output->set_header('Cache-Control: no-cache, must-revalidate');
        $this->output->set_header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        $this->output->set_header($this->input->server('SERVER_PROTOCOL') . ' 400 Bad Request');

        // --------------------------------------------------------------------------

        $out = array(
            'status'  => 400,
            'message' => $message
       );

        $this->output->set_output(json_encode($out));
    }
}


// --------------------------------------------------------------------------


/**
 * OVERLOADING NAILS' SHOP MODULE
 *
 * The following block of code makes it simple to extend one of the core shop
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
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (!defined('NAILS_ALLOW_EXTENSION_ORDERS')) {

    class Orders extends NAILS_Orders
    {
    }
}
