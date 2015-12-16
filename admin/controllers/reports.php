<?php

/**
 * Generate shop reports
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Shop;

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Shop\Controller\BaseAdmin;

class Reports extends BaseAdmin
{
    protected $sources;
    protected $periods;
    protected $formats;
    protected $firstFY;
    protected $currentFY;
    protected $previousFY;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:shop:reports:generate')) {

            $oNavGroup = Factory::factory('Nav', 'nailsapp/module-admin');
            $oNavGroup->setLabel('Shop');
            $oNavGroup->setIcon('fa-shopping-cart');
            $oNavGroup->addAction('Generate Reports');
            return $oNavGroup;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     * @return array
     */
    public static function permissions()
    {
        $permissions = parent::permissions();

        $permissions['generate'] = 'Can generate Reports';

        return $permissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop/shop_model');

        // --------------------------------------------------------------------------

        /**
         * If a financial year start date has been specified then work out the start
         * and end dates.
         */

        $oDate                     = Factory::factory('DateTime');
        $firstFinancialYearEndDate = appSetting('firstFinancialYearEndDate', 'shop');

        if (!empty($firstFinancialYearEndDate)) {

            try {

                $yearInvterval   = new \DateInterval('P1Y');
                $secondInvterval = new \DateInterval('PT1S');
                $yearEnd         = explode('-', $firstFinancialYearEndDate);

                /**
                 * If we're sitting BEFORE
                 */

                $this->firstFY        = new \stdClass();
                $this->firstFY->start = null;
                $this->firstFY->end   = new \DateTime(implode('-', $yearEnd) . ' 23:59:59');
                $this->firstFY->start = new \DateTime(implode('-', $yearEnd) . ' 23:59:59');
                $this->firstFY->start->sub($yearInvterval)->add($secondInvterval);


                //  If we're before the
                $yearEnd[0] = $oDate->format('Y');
                $this->currentFY        = new \stdClass();
                $this->currentFY->start = null;
                $this->currentFY->end   = new \DateTime(implode('-', $yearEnd) . ' 23:59:59');
                $this->currentFY->start = new \DateTime(implode('-', $yearEnd) . ' 23:59:59');
                $this->currentFY->start->sub($yearInvterval)->add($secondInvterval);

                $yearEnd[0] = $oDate->format('Y') - 1;
                $this->previousFY        = new \stdClass();
                $this->previousFY->start = null;
                $this->previousFY->end   = new \DateTime(implode('-', $yearEnd) . ' 23:59:59');
                $this->previousFY->start = new \DateTime(implode('-', $yearEnd) . ' 23:59:59');
                $this->previousFY->start->sub($yearInvterval)->add($secondInvterval);

                /**
                 * If the end of the previous financial year falls before the
                 * start of the first financial year start date then don't offer
                 * it as an option.
                 */

                if ($this->previousFY->end < $this->firstFY->start) {

                    $this->previousFY = null;
                }

            } catch (\Exception $e) {

                //  Failed, simply do not show options
                $this->firstFY    = null;
                $this->currentFY  = null;
                $this->previousFY = null;
            }
        }

        // --------------------------------------------------------------------------

        /**
         * Define the report sources
         *
         * Each item in this array is an array which defines the source, in the
         * following format:
         *
         * array(
         *    0 => 'Source Title',
         *    1 => 'Source Description',
         *    2 => 'sourceMethod',
         *    4 => 'respectsPeriods'
         *)
         *
         * The source method should be a callable method which is prefixed with
         * source, using the above as an example, the method would be:
         *
         * sourceSourceMethod()
         *
         * This method should return an array where the indexes are the column
         * names and the values are not arrays, i.e stuff which would fit into
         * a single cell in Excel).
         */

        $this->sources = array();

        if (userHasPermission('admin:shop:inventory:manage')) {

            $this->sources[] = array(
                'Inventory',
                'Out of Stock variants',
                'OutOfStockVariants',
                false
            );
        }

        if (userHasPermission('admin:shop:orders:manage')) {

            $this->sources[] = array(
                'Sales',
                'Orders',
                'Orders',
                true
            );

            $this->sources[] = array(
                'Sales',
                'Products',
                'Products',
                true
            );
        }

        // --------------------------------------------------------------------------

        /**
         * Define the available periods for reports
         *
         * All reports should allow the suer to restrict to a certain time frame
         */

        $this->periods = array(
            'ALL_TIME'   => 'All time',
            'THIS_MONTH' => 'This Month',
            'LAST_MONTH' => 'Last Month',
        );

        if (!empty($this->currentFY)) {

            $this->periods['CURRENT_FY'] = $this->currentFY->start->format('Y') . '/' . $this->currentFY->end->format('Y');
        }

        if (!empty($this->previousFY)) {

            $this->periods['PREVIOUS_FY'] = $this->previousFY->start->format('Y') . '/' . $this->previousFY->end->format('Y');
        }

        // --------------------------------------------------------------------------

        /**
         * Define the export formats
         *
         * Each item in this array is an array which defines the formats, in the
         * following format:
         *
         * array(
         *    0 => 'Format Title',
         *    1 => 'Format Description',
         *    2 => 'FormatMethod'
         *)
         *
         * The format method should be a callable method which is prefixed with
         * format, using the above as an example, the method would be:
         *
         * formatFormatMethod($data, $returnData = false)
         *
         * Where $data is the values generated from a source method. The method
         * should handle generating the file and sending to the user, unless
         * $returnData is true, in which case it should return the file's content
         */

        $this->formats   = array();
        $this->formats[] = array(
            'CSV',
            'Easily imports to many software packages, including Microsoft Excel.',
            'Csv'
        );
        $this->formats[] = array(
            'HTML',
            'Produces an HTML table containing the data',
            'Html'
        );
        $this->formats[] = array(
            'PDF',
            'Saves a PDF using the data from the HTML export option',
            'Pdf'
        );
        $this->formats[] = array(
            'PHP Serialize',
            'Export as an object serialized using PHP\'s serialize() function',
            'Serialize'
        );
        $this->formats[] = array(
            'JSON',
            'Export as a JSON array',
            'Json'
        );

        // --------------------------------------------------------------------------

        //  @todo Move this into a common constructor
        $this->shopName = $this->shopUrl = $this->shop_model->getShopName();
        $this->shopUrl  = $this->shopUrl = $this->shop_model->getShopUrl();

        //  Pass data to the views
        $this->data['shopName'] = $this->shopName;
        $this->data['shopUrl']  = $this->shopUrl;
    }

    // --------------------------------------------------------------------------

    /**
     * Browse available reports
     * @return void
     */
    public function index()
    {
        if ($this->input->is_cli_request()) {

            return $this->indexCli();
        }

        // --------------------------------------------------------------------------

        if (!userHasPermission('admin:shop:reports:generate')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Generate Reports';

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            //  Form validation and update
            $oFormValidation = Factory::service('FormValidation');

            //  Define rules
            $oFormValidation->set_rules('report', '', 'xss_clean|required');
            $oFormValidation->set_rules('period', '', 'xss_clean');
            $oFormValidation->set_rules('format', '', 'xss_clean|required');

            //  Set Messages
            $oFormValidation->set_message('required', lang('fv_required'));

            //  Execute
            $source = $this->input->post('report');
            $source = explode(':', $source);
            $source = $source[0];
            $sourceExists = isset($this->sources[$source]);

            $period = $this->input->post('period');
            $periodExists = isset($this->periods[$period]);

            $format = $this->input->post('format');
            $formatExists = isset($this->formats[$format]);

            if ($oFormValidation->run() && $sourceExists && $periodExists && $formatExists) {

                $source = $this->sources[$source];
                $format = $this->formats[$format];

                if (!method_exists($this, 'source' . $source[2])) {

                    $this->data['error'] = 'That data source is not available.';

                } elseif (!method_exists($this, 'format' . $format[2])) {

                    $this->data['error'] = 'That format type is not available.';

                } else {

                    //  All seems well, generate the report!
                    $data = $this->{'source' . $source[2]}($period);

                    //  Anything to report?
                    if (!empty($data)) {

                        //  If $data is an array then we need to write multiple files to a zip
                        if (is_array($data)) {

                            //  Load Zip class
                            $this->load->library('zip');

                            //  Process each file
                            foreach ($data as $data) {

                                $file = $this->{'format' . $format[2]}($data, true);

                                $this->zip->add_data($file[0], $file[1]);
                            }

                            $oDate = Factory::factory('DateTime');
                            $this->zip->download('shop-report-' . $source[2] . '-' . $oDate->format('Y-m-d_H-i-s'));

                        } else {

                            $this->{'format' . $format[2]}($data);
                        }
                    }

                    return;
                }

            } elseif (!isset($this->sources[$source])) {

                $this->data['error'] = 'Invalid data source.';

            } elseif (!isset($this->formats[$format])) {

                $this->data['error'] = 'Invalid format type.';

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        $this->data['sources'] = $this->sources;
        $this->data['periods'] = $this->periods;
        $this->data['formats'] = $this->formats;

        // --------------------------------------------------------------------------

        $this->asset->load('admin.reports.min.js', 'nailsapp/module-shop');
        $this->asset->inline('<script>_nails_shop_reports = new NAILS_Admin_Shop_Reports();</script>');

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse available reports (command line)
     * @return void
     */
    protected function indexCli()
    {
        //  @todo Complete CLI functionality for report generating
        echo 'Sorry, this functionality is not complete yet. If you are experiencing ';
        echo 'timeouts please increase the timeout limit for PHP.';
    }

    // --------------------------------------------------------------------------

    /**
     * Report soure: Out of stock variants
     * @return stdClass
     */
    protected function sourceOutOfStockVariants()
    {
        if (!userHasPermission('admin:shop:inventory:manage')) {

            return false;
        }

        // --------------------------------------------------------------------------

        $out           = new \stdClass();
        $out->label    = 'Out of Stock variants';
        $out->filename = NAILS_DB_PREFIX . 'out_of_stock_variants';
        $out->fields   = array();
        $out->data     = array();

        // --------------------------------------------------------------------------

        //  Fetch all variants which are out of stock
        $this->db->select('p.id product_id, p.label product_label, v.id variation_id, v.label variation_label, v.sku, v.quantity_available');
        $this->db->select('(SELECT GROUP_CONCAT(DISTINCT `b`.`label` ORDER BY `b`.`label` SEPARATOR \', \') FROM `' . NAILS_DB_PREFIX . 'shop_product_brand` pb JOIN `' . NAILS_DB_PREFIX . 'shop_brand` b ON `b`.`id` = `pb`.`brand_id` WHERE `pb`.`product_id` = `p`.`id` GROUP BY `pb`.`product_id`) brands', false);
        $this->db->join(NAILS_DB_PREFIX . 'shop_product p', 'p.id = v.product_id', 'LEFT');
        $this->db->where('v.stock_status', 'OUT_OF_STOCK');
        $this->db->where('p.is_deleted', 0);
        $this->db->where('v.is_deleted', 0);
        $this->db->where('p.is_active', 1);
        $out->data = $this->db->get(NAILS_DB_PREFIX . 'shop_product_variation v')->result_array();

        if ($out->data) {

            $out->fields = array_keys($out->data[0]);
        }

        // --------------------------------------------------------------------------

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Report source: Orders
     * @param  string $period The period to restrict to
     * @return stdClass
     */
    protected function sourceOrders($period)
    {
        if (!userHasPermission('admin:shop:orders:manage')) {

            return false;
        }

        // --------------------------------------------------------------------------

        $out           = new \stdClass();
        $out->label    = 'Orders';
        $out->filename = NAILS_DB_PREFIX . 'orders';
        $out->fields   = array();
        $out->data     = array();

        // --------------------------------------------------------------------------

        //  Fetch all products from the order products table
        $select = array(
            'o.id',
            'o.ref',
            'o.user_id',
            'o.user_email',
            'o.user_first_name',
            'o.user_last_name',
            'o.user_telephone',
            'o.ip_address',
            'o.status',
            'o.requires_shipping',
            'o.delivery_option',
            'o.fulfilment_status',
            'o.note',
            'o.created',
            'o.modified',
            'o.fulfilled',
            'o.base_currency',
            'o.total_base_item',
            'o.total_base_shipping',
            'o.total_base_tax',
            'o.total_base_grand',
            'o.currency user_currency',
            'o.total_user_item',
            'o.total_user_shipping',
            'o.total_user_tax',
            'o.total_user_grand',
            'o.shipping_line_1',
            'o.shipping_line_2',
            'o.shipping_town',
            'o.shipping_state',
            'o.shipping_postcode',
            'o.shipping_country',
            'o.billing_line_1',
            'o.billing_line_2',
            'o.billing_town',
            'o.billing_state',
            'o.billing_postcode',
            'o.billing_country'
        );
        $this->db->select($select);
        $this->db->where('o.status', 'PAID');
        $this->db->order_by('o.created');

        //  Restrict to period
        switch ($period) {

            case 'THIS_MONTH':

                $this->db->where('MONTH(o.created) = MONTH(CURDATE())');
                $this->db->where('YEAR(o.created) = YEAR(CURDATE())');
                break;

            case 'LAST_MONTH':

                $this->db->where('MONTH(o.created) = MONTH(CURDATE() - INTERVAL 1 MONTH)');
                $this->db->where('YEAR(o.created) = YEAR(CURDATE() - INTERVAL 1 MONTH)');
                break;

            case 'CURRENT_FY':

                $this->db->where('o.created >=', $this->currentFY->start->format('Y-m-d H:i:s'));
                $this->db->where('o.created <', $this->currentFY->end->format('Y-m-d H:i:s'));
                break;

            case 'PREVIOUS_FY':

                $this->db->where('o.created >=', $this->previousFY->start->format('Y-m-d H:i:s'));
                $this->db->where('o.created <', $this->previousFY->end->format('Y-m-d H:i:s'));
                break;
        }

        $out->data = $this->db->get(NAILS_DB_PREFIX . 'shop_order o')->result_array();

        if ($out->data) {

            $out->fields = array_keys($out->data[0]);
        }

        // --------------------------------------------------------------------------

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Report source: Products
     * @param  string $period The period to restrict to
     * @return stdClass
     */
    protected function sourceProducts($period)
    {
        if (!userHasPermission('admin:shop:orders:manage')) {

            return false;
        }

        // --------------------------------------------------------------------------

        $out           = new \stdClass();
        $out->label    = 'Products';
        $out->filename = NAILS_DB_PREFIX . 'products';
        $out->fields   = array();
        $out->data     = array();

        // --------------------------------------------------------------------------

        //  Fetch all products from the order products table
        $this->db->select('o.id order_id, o.ref order_ref, o.created, op.quantity as quantity_sold, p.id product_id, p.label product_label, v.id variation_id, v.label variation_label, v.sku');
        $this->db->select('(SELECT GROUP_CONCAT(DISTINCT `b`.`label` ORDER BY `b`.`label` SEPARATOR \', \') FROM `' . NAILS_DB_PREFIX . 'shop_product_brand` pb JOIN `' . NAILS_DB_PREFIX . 'shop_brand` b ON `b`.`id` = `pb`.`brand_id` WHERE `pb`.`product_id` = `p`.`id` GROUP BY `pb`.`product_id`) brands', false);
        $this->db->join(NAILS_DB_PREFIX . 'shop_order o', 'o.id = op.order_id', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'shop_product p', 'p.id = op.variant_id', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'shop_product_variation v', 'v.product_id = p.id', 'LEFT');
        $this->db->where('o.status', 'PAID');
        $this->db->order_by('o.created');

        //  Restrict to period
        switch ($period) {

            case 'THIS_MONTH':

                $this->db->where('MONTH(o.created) = MONTH(CURDATE())');
                $this->db->where('YEAR(o.created) = YEAR(CURDATE())');
                break;

            case 'LAST_MONTH':

                $this->db->where('MONTH(o.created) = MONTH(CURDATE() - INTERVAL 1 MONTH)');
                $this->db->where('YEAR(o.created) = YEAR(CURDATE() - INTERVAL 1 MONTH)');
                break;

            case 'CURRENT_FY':

                $this->db->where('o.created >=', $this->currentFY->start->format('Y-m-d H:i:s'));
                $this->db->where('o.created <', $this->currentFY->end->format('Y-m-d H:i:s'));
                break;

            case 'PREVIOUS_FY':

                $this->db->where('o.created >=', $this->previousFY->start->format('Y-m-d H:i:s'));
                $this->db->where('o.created <', $this->previousFY->end->format('Y-m-d H:i:s'));
                break;
        }

        $out->data = $this->db->get(NAILS_DB_PREFIX . 'shop_order_product op')->result_array();

        if ($out->data) {

            $out->fields = array_keys($out->data[0]);
        }


        // --------------------------------------------------------------------------

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Report Format: CSV
     * @param  array   $data       The data to use for the report
     * @param  boolean $returnData Whether or not to return the data, or output it to the browser
     * @return mixed
     */
    protected function formatCsv($data, $returnData = false)
    {
        //  Send header
        if (!$returnData) {

            $oDate = Factory::factory('DateTime');

            $this->output->set_content_type('application/octet-stream');
            $this->output->set_header('Pragma: public');
            $this->output->set_header('Expires: 0');
            $this->output->set_header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            $this->output->set_header('Cache-Control: private', false);
            $this->output->set_header('Content-Disposition: attachment; filename=shop-report-' . $data->filename . '-' . $oDate->format('Y-m-d_H-i-s') . '.csv;');
            $this->output->set_header('Content-Transfer-Encoding: binary');
        }

        // --------------------------------------------------------------------------

        //  Set view data
        $this->data['label']  = $data->label;
        $this->data['fields'] = $data->fields;
        $this->data['data']   = $data->data;

        // --------------------------------------------------------------------------

            //  Load view
        if (!$returnData) {

            Helper::loadView('format/csv', false);

        } else {

            $out   = array();
            $out[] = $data->filename . '.csv';
            $out[] = Helper::loadView('format/csv', false, true);

            return $out;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Report Format: HTML
     * @param  array   $data       The data to use for the report
     * @param  boolean $returnData Whether or not to return the data, or output it to the browser
     * @return mixed
     */
    protected function formatHtml($data, $returnData = false)
    {
        //  Send header
        if (!$returnData) {

            $oDate = Factory::factory('DateTime');

            $this->output->set_content_type('application/octet-stream');
            $this->output->set_header('Pragma: public');
            $this->output->set_header('Expires: 0');
            $this->output->set_header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            $this->output->set_header('Cache-Control: private', false);
            $this->output->set_header('Content-Disposition: attachment; filename=shop-report-' . $data->filename . '-' . $oDate->format('Y-m-d_H-i-s') . '.html;');
            $this->output->set_header('Content-Transfer-Encoding: binary');
        }

        // --------------------------------------------------------------------------

        //  Set view data
        $this->data['label']  = $data->label;
        $this->data['fields'] = $data->fields;
        $this->data['data']   = $data->data;

        // --------------------------------------------------------------------------

        //  Load view
        if (!$returnData) {

            Helper::loadView('format/html', false);

        } else {

            $out   = array();
            $out[] = $data->filename . '.html';
            $out[] = Helper::loadView('format/html', false, true);

            return $out;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Report Format: PDF
     * @param  array   $data       The data to use for the report
     * @param  boolean $returnData Whether or not to return the data, or output it to the browser
     * @return mixed
     */
    protected function formatPdf($data, $returnData = false)
    {
        $html = $this->formatHtml($data, true);

        // --------------------------------------------------------------------------

        $this->load->library('pdf/pdf');
        $this->pdf->setPaperSize('A4', 'landscape');
        $this->pdf->load_html($html[1]);

        //  Load view
        if (!$returnData) {

            if (!$this->pdf->download($data->filename . '.pdf')) {

                $status  = 'error';
                $message = 'Failed to render PDF. ';
                $message .= $this->pdf->lastError() ? 'DOMPDF gave the following error: ' . $this->pdf->lastError() : '';

                $this->session->set_flashdata($status, $message);
                redirect('admin/shop/reports');
            }

        } else {

            try {

                $this->pdf->render();

                $out   = array();
                $out[] = $data->filename . '.pdf';
                $out[] = $this->pdf->output();

                $this->pdf->reset();

                return $out;

            } catch (Exception $e) {

                $status   = 'error';
                $message  = 'Failed to render PDF. The following exception was raised: ';
                $message .= $e->getMessage();

                $this->session->set_flashdata($status, $message);
                redirect('admin/shop/reports');
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Report Format: Serialize
     * @param  array   $data       The data to use for the report
     * @param  boolean $returnData Whether or not to return the data, or output it to the browser
     * @return mixed
     */
    protected function formatSerialize($data, $returnData = false)
    {
        //  Send header
        if (!$returnData) {

            $oDate = Factory::factory('DateTime');

            $this->output->set_content_type('application/octet-stream');
            $this->output->set_header('Pragma: public');
            $this->output->set_header('Expires: 0');
            $this->output->set_header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            $this->output->set_header('Cache-Control: private', false);
            $this->output->set_header('Content-Disposition: attachment; filename=shop-report-' . $data->filename . '-' . $oDate->format('Y-m-d_H-i-s') . '.txt;');
            $this->output->set_header('Content-Transfer-Encoding: binary');
        }

        // --------------------------------------------------------------------------

        //  Set view data
        $this->data['data'] = $data;

        // --------------------------------------------------------------------------

        //  Load view
        if (!$returnData) {

            Helper::loadView('format/serialize', false);

        } else {

            $out   = array();
            $out[] = $data->filename . '.txt';
            $out[] = Helper::loadView('format/serialize', false, true);

            return $out;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Report Format: JSON
     * @param  array   $data       The data to use for the report
     * @param  boolean $returnData Whether or not to return the data, or output it to the browser
     * @return mixed
     */
    protected function formatJson($data, $returnData = false)
    {
        //  Send header
        if (!$returnData) {

            $oDate = Factory::factory('DateTime');

            $this->output->set_content_type('application/octet-stream');
            $this->output->set_header('Pragma: public');
            $this->output->set_header('Expires: 0');
            $this->output->set_header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            $this->output->set_header('Cache-Control: private', false);
            $this->output->set_header('Content-Disposition: attachment; filename=shop-report-' . $data->filename . '-' . $oDate->format('Y-m-d_H-i-s') . '.json;');
            $this->output->set_header('Content-Transfer-Encoding: binary');
        }

        // --------------------------------------------------------------------------

        //  Set view data
        $this->data['data'] = $data;

        // --------------------------------------------------------------------------

        //  Load view
        if (!$returnData) {

            Helper::loadView('format/json', false);

        } else {

            $out   = array();
            $out[] = $data->filename . '.json';
            $out[] = Helper::loadView('format/json', false, true);

            return $out;
        }
    }
}
