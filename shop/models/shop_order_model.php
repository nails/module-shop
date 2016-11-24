<?php

/**
 * This model handles interacting with orders
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

use Nails\Factory;
use Nails\Common\Model\Base;
use Nails\Common\Exception\NailsException;

class Shop_order_model extends Base
{
    protected $oCurrencyModel;
    protected $oCountryModel;
    protected $oLogger;

    // --------------------------------------------------------------------------

    /**
     * Constructs the model
     */
    public function __construct()
    {
        parent::__construct();

        $this->table       = NAILS_DB_PREFIX . 'shop_order';
        $this->tableAlias = 'o';

        $this->defaultSortColumn  = 'created';
        $this->defaultSortOrder   = 'DESC';

        $this->oCurrencyModel = Factory::model('Currency', 'nailsapp/module-shop');
        $this->oCountryModel  = Factory::model('Country');
        $this->oLogger        = Factory::service('Logger');

        $this->searchableFields = ['id','ref'];

        // --------------------------------------------------------------------------

        $this->addExpandableField(
            array(
                'trigger'     => 'lifecycle',
                'type'        => self::EXPANDABLE_TYPE_SINGLE,
                'property'    => 'lifecycle',
                'model'       => 'OrderLifecycle',
                'provider'    => 'nailsapp/module-shop',
                'id_column'   => 'lifecycle_id'
            )
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new order in the system
     * @param  object  $data      The data required to create the order
     * @param  boolean $returnObj Whether or not to return the entire order object, or just the ID.
     * @return mixed
     */
    public function create($data, $returnObj = false)
    {
        $oDb    = Factory::service('Database');
        $oInput = Factory::service('Input');
        $oNow   = Factory::factory('DateTime');

        //  Start the transaction
        $oDb->trans_begin();

        try {

            //  Basket has items?
            if (empty($data['basket']->items)) {
                throw new NailsException('Basket is empty.');
            }

            // --------------------------------------------------------------------------

            //  Is the basket already associated with an order?
            if (!empty($data['basket']->order->id)) {
                $this->abandon($data['basket']->order->id);
            }

            // --------------------------------------------------------------------------

            $order = new \stdClass();

            //  Generate a reference
            do {

                //  Generate the string
                $order->ref = $oNow->format('Ym') . '-' . strtoupper(random_string('alpha', 8)) . '-' . date('dH');

                //  Test it
                $oDb->where('ref', $order->ref);

            } while ($oDb->count_all_results(NAILS_DB_PREFIX . 'shop_order'));

            // --------------------------------------------------------------------------

            //  User's IP address
            $order->ip_address = $oInput->ipAddress();

            // --------------------------------------------------------------------------

            //  Generate a code (used as a secondary verification method)
            $order->code = md5($oInput->ipAddress() . '|' . time() . '|' . random_string('alnum', 15));

            // --------------------------------------------------------------------------

            /**
             * Set the user details. If defined in the order object use them, if not see
             * if anyone's logged in, if not still then either bail out or leave blank.
             */

            //  Email
            if (!empty($data['contact']->email)) {

                $order->user_email = $data['contact']->email;

            } elseif (isLoggedIn()) {

                $order->user_email = activeUser('email');

            } else {

                throw new NailsException('An email address must be supplied.');
            }

            //  User ID
            $user = $this->user_model->getByEmail($order->user_email);

            if ($user) {

                $order->user_id = $user->id;

            } elseif (isLoggedIn()) {

                $order->user_id = activeUser('id');

            } else {

                $order->user_id = null;
            }

            unset($user);

            //  First name
            if (!empty($data['contact']->first_name)) {

                $order->user_first_name = $data['contact']->first_name;

            } elseif (isLoggedIn()) {

                $order->user_first_name = activeUser('first_name');

            } else {

                $order->user_first_name = null;
            }

            //  Last name
            if (!empty($data['contact']->last_name)) {

                $order->user_last_name = $data['contact']->last_name;

            } elseif (isLoggedIn()) {

                $order->user_last_name = activeUser('last_name');

            } else {

                $order->user_last_name = null;
            }

            //  Telephone
            if (!empty($data['contact']->telephone)) {

                $order->user_telephone = $data['contact']->telephone;

            } elseif (isLoggedIn()) {

                $order->user_telephone = activeUser('telephone');

            } else {

                $order->user_telephone = null;
            }

            // --------------------------------------------------------------------------

            //  Set voucher ID
            if (!empty($data['basket']->voucher->id)) {
                $order->voucher_id = $data['basket']->voucher->id;
            }

            // --------------------------------------------------------------------------

            //  Order Note
            if (!empty($data['basket']->note)) {
                $order->note = $data['basket']->note;
            }

            // --------------------------------------------------------------------------

            /**
             * Does the order require shipping? It requires shipping if the option is not
             * COLLECTION and at least one of the items is not collect_only.
             */

            $order->delivery_option   = $data['basket']->shipping->option;
            $order->delivery_type     = $data['basket']->shipping->type;
            $order->requires_shipping = $data['basket']->shipping->isRequired;

            // --------------------------------------------------------------------------

            //  Set currency and exchange rates
            $order->currency      = SHOP_USER_CURRENCY_CODE;
            $order->base_currency = SHOP_BASE_CURRENCY_CODE;

            // --------------------------------------------------------------------------

            //  Delivery Address
            $order->shipping_line_1   = (string)$data['delivery']->line_1;
            $order->shipping_line_2   = (string)$data['delivery']->line_2;
            $order->shipping_town     = (string)$data['delivery']->town;
            $order->shipping_state    = (string)$data['delivery']->state;
            $order->shipping_postcode = (string)$data['delivery']->postcode;
            $order->shipping_country  = (string)$data['delivery']->country;

            //  Billing Address
            $order->billing_line_1   = (string)$data['billing']->line_1;
            $order->billing_line_2   = (string)$data['billing']->line_2;
            $order->billing_town     = (string)$data['billing']->town;
            $order->billing_state    = (string)$data['billing']->state;
            $order->billing_postcode = (string)$data['billing']->postcode;
            $order->billing_country  = (string)$data['billing']->country;

            // --------------------------------------------------------------------------

            //  Set totals
            $order->total_base_item                  = $data['basket']->totals->base->item;
            $order->total_base_item_discount         = $data['basket']->totals->base->item_discount;
            $order->total_base_shipping              = $data['basket']->totals->base->shipping;
            $order->total_base_shipping_discount     = $data['basket']->totals->base->shipping_discount;
            $order->total_base_tax_item              = $data['basket']->totals->base->tax_item;
            $order->total_base_tax_item_discount     = $data['basket']->totals->base->tax_item_discount;
            $order->total_base_tax_shipping          = $data['basket']->totals->base->tax_shipping;
            $order->total_base_tax_shipping_discount = $data['basket']->totals->base->tax_shipping_discount;
            $order->total_base_tax_combined          = $data['basket']->totals->base->tax_combined;
            $order->total_base_tax_combined_discount = $data['basket']->totals->base->tax_combined_discount;
            $order->total_base_grand                 = $data['basket']->totals->base->grand;
            $order->total_base_grand_discount        = $data['basket']->totals->base->grand_discount;

            $order->total_user_item                  = $data['basket']->totals->user->item;
            $order->total_user_item_discount         = $data['basket']->totals->user->item_discount;
            $order->total_user_shipping              = $data['basket']->totals->user->shipping;
            $order->total_user_shipping_discount     = $data['basket']->totals->user->shipping_discount;
            $order->total_user_tax_item              = $data['basket']->totals->user->tax_item;
            $order->total_user_tax_item_discount     = $data['basket']->totals->user->tax_item_discount;
            $order->total_user_tax_shipping          = $data['basket']->totals->user->tax_shipping;
            $order->total_user_tax_shipping_discount = $data['basket']->totals->user->tax_shipping_discount;
            $order->total_user_tax_combined          = $data['basket']->totals->user->tax_combined;
            $order->total_user_tax_combined_discount = $data['basket']->totals->user->tax_combined_discount;
            $order->total_user_grand                 = $data['basket']->totals->user->grand;
            $order->total_user_grand_discount        = $data['basket']->totals->user->grand_discount;

            // --------------------------------------------------------------------------

            $order->created  = $oNow->format('Y-m-d H:i:s');
            $order->modified = $oNow->format('Y-m-d H{i{s');

            // --------------------------------------------------------------------------

            $oDb->set($order);
            $oDb->insert(NAILS_DB_PREFIX . 'shop_order');

            $order->id = $oDb->insert_id();

            if ($order->id) {

                //  Add the items
                $items = array();

                foreach ($data['basket']->items as $item) {

                    $temp = array();
                    $temp['order_id']             = $order->id;
                    $temp['product_id']           = $item->product_id;
                    $temp['product_label']        = $item->product_label;
                    $temp['variant_id']           = $item->variant_id;
                    $temp['variant_label']        = $item->variant_label;
                    $temp['quantity']             = $item->quantity;
                    $temp['tax_rate_id']          = !empty($item->product->tax_rate->id) ? $item->product->tax_rate->id : null;
                    $temp['ship_collection_only'] = $item->variant->ship_collection_only;

                    //  Price
                    $temp['price_base_value_inc_tax']          = $item->price->base->value_inc_tax;
                    $temp['price_base_value_ex_tax']           = $item->price->base->value_ex_tax;
                    $temp['price_base_value_tax']              = $item->price->base->value_tax;
                    $temp['price_base_discount_value_inc_tax'] = $item->price->base->discount_value_inc_tax;
                    $temp['price_base_discount_value_ex_tax']  = $item->price->base->discount_value_ex_tax;
                    $temp['price_base_discount_value_tax']     = $item->price->base->discount_value_tax;
                    $temp['price_user_value_inc_tax']          = $item->price->user->value_inc_tax;
                    $temp['price_user_value_ex_tax']           = $item->price->user->value_ex_tax;
                    $temp['price_user_value_tax']              = $item->price->user->value_tax;
                    $temp['price_user_discount_value_inc_tax'] = $item->price->user->discount_value_inc_tax;
                    $temp['price_user_discount_value_ex_tax']  = $item->price->user->discount_value_ex_tax;
                    $temp['price_user_discount_value_tax']     = $item->price->user->discount_value_tax;

                    /**
                     * To order?
                     * If this item is to order then make a note in the `extra_data column so it can be
                     * rendered on invoices etc.
                     */

                    if ($item->variant->stock_status == 'TO_ORDER') {

                        //  Save the lead_time
                        if (!isset($item->extra_data)) {

                            $item->extra_data = array();

                        } elseif (isset($item->extra_data) && !is_array($item->extra_data)) {

                            $item->extra_data = (array)$item->extra_data;
                        }

                        $item->extra_data['to_order'] = new \stdClass();
                        $item->extra_data['to_order']->is_to_order = true;
                        $item->extra_data['to_order']->lead_time = $item->variant->lead_time;
                    }

                    //  Extra data
                    if (isset($item->extra_data) && $item->extra_data) {

                        $temp['extra_data'] = json_encode((array)$item->extra_data);
                    }

                    $items[] = $temp;
                    unset($temp);

                }

                $oDb->insert_batch(NAILS_DB_PREFIX . 'shop_order_product', $items);

                if (!$oDb->affected_rows()) {
                    throw new NailsException('Unable to add products to order.');
                }

            } else {
                throw new NailsException('An error occurred while creating the order.');
            }

            // --------------------------------------------------------------------------

            //  Set the order's lifecycle
            $oLifecycleModel = Factory::model('OrderLifecycle', 'nailsapp/module-shop');
            $oLifecycleModel->setPlaced($order->id);

            //  Commit everything
            $oDb->trans_commit();

            return $returnObj ? $this->getById($order->id) : $order->id;

        } catch (NailsException $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing object
     * @param  int   $id   The ID of the object to update
     * @param  array $data The data to update the object with
     * @return bool
     **/
    public function update($id, $data)
    {
        if (!$data) {
            return false;
        }

        // --------------------------------------------------------------------------

        $oDb = Factory::service('Database');

        $oDb->set($data);
        $oDb->set('modified', 'NOW()', false);
        $oDb->where('id', $id);
        return $oDb->update(NAILS_DB_PREFIX . 'shop_order');
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get*()
     * methods and the count() method.
     * @param  array $data Data passed from the calling method
     * @return void
     **/
    protected function getCountCommon($data = array())
    {
        $oDb = Factory::service('Database');

        //  Selects
        $oDb->select($this->tableAlias . '.*');
        $oDb->select('ue.email, u.first_name, u.last_name, u.gender, u.profile_img,ug.id user_group_id');
        $oDb->select('ug.label user_group_label');
        $oDb->select('v.code v_code,v.label v_label, v.type v_type, v.discount_type v_discount_type');
        $oDb->select('v.discount_value v_discount_value, v.discount_application v_discount_application');
        $oDb->select('v.product_type_id v_product_type_id, v.is_active v_is_active, v.is_deleted v_is_deleted');
        $oDb->select('v.valid_from v_valid_from, v.valid_to v_valid_to');

        //  Joins
        $oDb->join(NAILS_DB_PREFIX . 'user u', 'u.id = o.user_id', 'LEFT');
        $oDb->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.user_id = u.id AND ue.is_primary = 1', 'LEFT');
        $oDb->join(NAILS_DB_PREFIX . 'user_group ug', 'ug.id = u.group_id', 'LEFT');
        $oDb->join(NAILS_DB_PREFIX . 'shop_voucher v', 'v.id = o.voucher_id', 'LEFT');

        // --------------------------------------------------------------------------

        //  Search
        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = array();
            }

            $data['or_like'][] = array(
                'column' => $this->tableAlias . '.id',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tableAlias . '.ref',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tableAlias . '.user_email',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tableAlias . '.user_first_name',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tableAlias . '.user_last_name',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tableAlias . '.user_telephone',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => 'ue.email',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => 'u.first_name',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => 'u.last_name',
                'value'  => $data['keywords']
            );
        }

        parent::getCountCommon($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch an object by its ref
     * @param  int   $ref The ref of the object to fetch
     * @return mixed      stdClass on success, false on failure
     */
    public function getByRef($ref)
    {
        if (empty($ref)) {
            return false;
        }

        if (!isset($data['where'])) {

            $data['where'] = array();
        }

        $data['where'][] = array($this->tableAlias . '.ref', $ref);

        $result = $this->getAll(null, null, $data);

        if (!$result) {

            return false;
        }

        return $result[0];
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch objects by an array of Refs
     * @param  array $ref The array of refs to fetch
     * @return array
     */
    public function getByRefs($refs)
    {
        if (empty($refs)) {
            return array();
        }

        if (!isset($data['where_in'])) {

            $data['where_in'] = array();
        }

        $data['where_in'][] = array($this->tableAlias . '.ref', $refs);

        return $this->getAll(null, null, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of items contained in an order
     * @param  int   $order_id The order's ID
     * @return array
     */
    public function getItemsForOrder($order_id)
    {
        $oDb = Factory::service('Database');

        $oDb->select('op.*');
        $oDb->select('pt.id pt_id, pt.label pt_label, pt.ipn_method pt_ipn_method');
        $oDb->select('tr.id tax_rate_id, tr.label tax_rate_label, tr.rate tax_rate_rate');
        $oDb->select('v.sku v_sku');

        $oDb->join(NAILS_DB_PREFIX . 'shop_product p', 'p.id = op.product_id');
        $oDb->join(NAILS_DB_PREFIX . 'shop_product_type pt', 'pt.id = p.type_id');
        $oDb->join(NAILS_DB_PREFIX . 'shop_tax_rate tr', 'tr.id = p.tax_rate_id', 'LEFT');
        $oDb->join(NAILS_DB_PREFIX . 'shop_product_variation v', 'v.id = op.variant_id', 'LEFT');

        $oDb->where('op.order_id', $order_id);
        $items = $oDb->get(NAILS_DB_PREFIX . 'shop_order_product op')->result();

        foreach ($items as $item) {
            $this->formatObjectItem($item);
        }

        return $items;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns all paid and unpaid orders created by a particular user ID
     * @param  int   $userId the user's ID
     * @return array
     */
    public function getForUserId($userId)
    {
        $oDb = Factory::service('Database');
        $oDb->where_in($this->tableAlias . '.status', array('PAID', 'UNPAID'));
        $oDb->where($this->tableAlias . '.user_id', $userId);
        return $this->getAll();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns all paid and unpaid orders created by a particular user email
     * @param  string $email The user's email address
     * @return array
     */
    public function getForUserEmail($email)
    {
        $oDb = Factory::service('Database');
        $oDb->where_in($this->tableAlias . '.status', array('PAID', 'UNPAID'));
        $oDb->where($this->tableAlias . '.user_email', $email);
        return $this->getAll();
    }

    // --------------------------------------------------------------------------

    /**
     * Marks an order as abandoned
     * @param  int     $orderId The order's ID
     * @return boolean
     */
    public function abandon($orderId)
    {
        $data = array('status' => 'ABANDONED');
        return $this->update($orderId, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Mark an order as failed, optionally specifying a reason
     * @param  int   $orderId The order's ID
     * @param  string $reason The reason for failure
     * @return boolean
     */
    public function fail($orderId, $reason = '')
    {
        $data = array('status' => 'FAILED');
        if ($this->update($orderId, $data)) {

            $reason = empty($reason) ? 'No reason supplied.' : $reason;
            $this->note_add($orderId, 'Failure Reason: ' . $reason);

            return true;

        } else {

            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Marks an order as paid
     * @param  int     $iOrderId The order's ID
     * @return boolean
     */
    public function paid($iOrderId)
    {
        try {

            //  Update order
            $aData = array('status' => 'PAID');
            if (!$this->update($iOrderId, $aData)) {
                throw new NailsException('Failed to update order. ' . $this->lastError());
            }

            //  Set lifecycle
            $oLifecycleModel = Factory::model('OrderLifecycle', 'nailsapp/module-shop');
            $oLifecycleModel->setPaid($iOrderId);

            return true;

        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

    }

    // --------------------------------------------------------------------------

    /**
     * Marks an order as unpaid
     * @param  int     $orderId The order's ID
     * @return boolean
     */
    public function unpaid($orderId)
    {
        $data = array('status' => 'UNPAID');
        return $this->update($orderId, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Marks an order as cancelled
     * @param  mixed   $orderId The order's ID, or an array of order IDs
     * @return boolean
     */
    public function cancel($orderId)
    {
        if (is_array($orderId)) {

            return $this->cancelBatch($orderId);

        } else {

            $data = array('status' => 'CANCELLED');
            return $this->update($orderId, $data);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Batch cancel some orders
     * @param  array   $orderIds An array of order IDs to cancel
     * @return boolean
     */
    public function cancelBatch($orderIds)
    {
        if (empty($orderIds)) {
            $this->setError('No IDs were supplied.');
            return false;
        }

        $oDb = Factory::service('Database');

        $oDb->set('status', 'CANCELLED');
        $oDb->where_in('id', $orderIds);
        $oDb->set('modified', 'NOW()', false);

        if ($oDb->update(NAILS_DB_PREFIX . 'shop_order')) {

            return true;

        } else {

            $this->setError('Failed to cancel batch.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Marks an order as pending
     * @param  int     $orderId The order's ID
     * @return boolean
     */
    public function pending($order_id)
    {
        $data = array('status' => 'PENDING');
        return $this->update($orderId, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Processes an order
     * @param  int $orderId The ID of the order to process
     * @return boolean
     */
    public function process($orderId)
    {
        $this->oLogger->line('Processing order #' . $orderId);
        $order = $this->getById($orderId);

        if (!$order) {

            $this->oLogger->line('Invalid order ID');
            $this->setError('Invalid order ID');
            return false;
        }

        // --------------------------------------------------------------------------

        /**
         * Loop through all the items in the order. If there's a proccessor method
         * for the object type then begin grouping the products so we can execute
         * the processor in a oner with all the associated products
         */

        $_processors = array();

        foreach ($order->items as $item) {

            $this->oLogger->line(
                'Processing item #' . $item->id . ': ' . $item->product_label . ': ' . $item->variant_label . ' (' . $item->type->label . ')'
            );

            $methodName = 'process' . $item->type->ipn_method;

            if ($item->type->ipn_method && method_exists($this, $methodName)) {

                if (!isset($_processors[$methodName])) {

                    $_processors[$methodName] = array();
                }

                $_processors[$methodName][] = $item;
            }
        }

        // --------------------------------------------------------------------------

        //  Execute the processors
        if ($_processors) {

            $this->oLogger->line('Executing processors...');

            foreach ($_processors as $method => $products) {

                $this->oLogger->line('... ' . $method . '(); with ' . count($products) . ' items.');
                call_user_func_array(array($this, $method), array(&$products, &$order));
            }
        }

        // --------------------------------------------------------------------------

        //  Handle any vouchers
        if (!empty($order->voucher->id)) {

            $this->oLogger->line('Redeeming voucher: #' . $order->voucher->id . ': ' . $order->voucher->code . ': ' . $order->voucher->label);

            $oVoucherModel = Factory::model('Voucher', 'nailsapp/module-shop');
            if (!$oVoucherModel->redeem($order->voucher->id, $order)) {
                $this->oLogger->line('... failed with error: ' . $oVoucherModel->lastError());
            }
        }

        // --------------------------------------------------------------------------

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Send a receipt to the user
     * @param  int     $orderId     The order's ID
     * @param  array   $paymentData Payment data pertaining to the order
     * @param  boolean $partial     Whether the order is partially paid, or completely paid
     * @return boolean
     */
    public function sendReceipt($orderId, $paymentData = array(), $partial = false)
    {
        $this->oLogger->line('Looking up order #' . $orderId);
        $order = $this->getById($orderId);

        if (!$order) {
            $this->oLogger->line('Invalid order ID');
            $this->setError('Invalid order ID');
            return false;
        }

        // --------------------------------------------------------------------------

        $email                       = new \stdClass();
        $email->type                 = $partial ? 'shop_receipt_partial_payment' : 'shop_receipt';
        $email->to_email             = $order->user->email;
        $email->data                 = array();
        $email->data['order']        = $order;
        $email->data['payment_data'] = $paymentData;

        if (!$this->emailer->send($email, true)) {

            //  Email failed to send, alert developers
            $emailErrors = $this->emailer->getErrors();

            if ($partial) {

                $this->oLogger->line('!!Failed to send receipt(partial payment) to customer, alerting developers');
                $subject  = 'Unable to send customer receipt email(partial payment)';

            } else {

                $this->oLogger->line('!!Failed to send receipt to customer, alerting developers');
                $subject  = 'Unable to send customer receipt email';

            }
            $this->oLogger->line(implode("\n", $emailErrors));

            $message  = 'Unable to send the customer receipt to ' . $email->to_email . '; order: #' . $order->id . "\n\n";
            $message .= 'Emailer errors:' . "\n\n" . print_r($emailErrors, true);

            sendDeveloperMail($subject, $message);

        }

        // --------------------------------------------------------------------------

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Send the order notification to the shop manager(s)
     * @param  int     $orderId     The ID of the order
     * @param  array   $paymentData Payment data pertaining to the order
     * @param  boolean $partial     Whether the order is aprtially paid, or completely paid
     * @return boolean
     */
    public function sendOrderNotification($orderId, $paymentData = array(), $partial = false)
    {
        $this->oLogger->line('Looking up order #' . $orderId);
        $order = $this->getById($orderId);

        if (!$order) {

            $this->oLogger->line('Invalid order ID');
            $this->setError('Invalid order ID.');
            return false;
        }

        // --------------------------------------------------------------------------

        $email                       = new \stdClass();
        $email->type                 = $partial ? 'shop_notification_partial_payment' : 'shop_notification_paid';
        $email->data                 = array();
        $email->data['order']        = $order;
        $email->data['payment_data'] = $paymentData;

        $oAppNotificationModel = Factory::model('AppNotification');

        $notify = $oAppNotificationModel->get('orders', 'nailsapp/module-shop');

        foreach ($notify as $notifyEmail) {

            $email->to_email = $notifyEmail;

            if (!$this->emailer->send($email, true)) {

                $emailErrors = $this->emailer->getErrors();

                if ($partial) {

                    $this->oLogger->line(
                        '!!Failed to send order notification(partially payment) to ' . $email . ', alerting developers.'
                    );
                    $subject  = 'Unable to send order notification email (partial payment)';

                } else {

                    $this->oLogger->line('!!Failed to send order notification to ' . $email . ', alerting developers.');
                    $subject  = 'Unable to send order notification email';

                }

                $this->oLogger->line(implode("\n", $emailErrors));

                $message  = 'Unable to send the order notification to ' . $email . '; order{ #' . $order->id . "\n\n";
                $message .= 'Emailer errors:' . "\n\n" . print_r($emailErrors, true);

                sendDeveloperMail($subject, $message);
            }
        }

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Adds a note against an order
     * @param int     $orderId The order ID to add the note against
     * @param boolean
     */
    public function note_add($orderId, $note)
    {
        $oDb = Factory::service('Database');

        $oDb->set('order_id', $orderId);
        $oDb->set('note', $note);
        $oDb->set('created', 'NOW()', false);
        $oDb->set('modified', 'NOW()', false);

        if (isLoggedIn()) {

            $oDb->set('created_by', activeUser('id'));
            $oDb->set('modified_by', activeUser('id'));

        } else {

            $oDb->set('created_by', null);
            $oDb->set('modified_by', null);
        }

        $oDb->insert(NAILS_DB_PREFIX . 'shop_order_note');

        return(bool) $oDb->affected_rows();
    }

    // --------------------------------------------------------------------------

    /**
     * Deletes an existing order note
     * @param  int    $orderId The order's ID
     * @param  int    $noteId  The note's ID
     * @return boolean
     */
    public function noteDelete($orderId, $noteId)
    {
        $oDb = Factory::service('Database');
        $oDb->where('id', $noteId);
        $oDb->where('order_iid', $orderId);
        $oDb->delete(NAILS_DB_PREFIX . 'shop_order_note');
        return(bool) $oDb->affected_rows();
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

        //  Shipping
        $oObj->requires_shipping = (bool) $oObj->requires_shipping;

        $this->load->model('shop/shop_shipping_driver_model');
        $oObj->shipping_option = $this->shop_shipping_driver_model->getOption($oObj->delivery_option);

        //  User
        $oObj->user     = new \stdClass();
        $oObj->user->id = $oObj->user_id;

        if ($oObj->user_email) {

            $oObj->user->email = $oObj->user_email;

        } else {

            $oObj->user->email = $oObj->email;
        }

        if ($oObj->user_first_name) {

            $oObj->user->first_name = $oObj->user_first_name;

        } else {

            $oObj->user->first_name = $oObj->first_name;

        }

        if ($oObj->user_last_name) {

            $oObj->user->last_name = $oObj->user_last_name;

        } else {

            $oObj->user->last_name = $oObj->last_name;
        }

        $oObj->user->telephone   = $oObj->user_telephone;
        $oObj->user->gender      = $oObj->gender;
        $oObj->user->profile_img = $oObj->profile_img;

        $oObj->user->group        = new \stdClass();
        $oObj->user->group->id    = $oObj->user_group_id;
        $oObj->user->group->label = $oObj->user_group_label;

        unset($oObj->user_id);
        unset($oObj->user_email);
        unset($oObj->user_first_name);
        unset($oObj->user_last_name);
        unset($oObj->user_telephone);
        unset($oObj->email);
        unset($oObj->first_name);
        unset($oObj->last_name);
        unset($oObj->gender);
        unset($oObj->profile_img);
        unset($oObj->user_group_id);
        unset($oObj->user_group_label);

        // --------------------------------------------------------------------------

        //  Totals
        $oObj->totals = new \stdClass();

        $oObj->totals->base                        = new \stdClass();
        $oObj->totals->base->item                  = (int) $oObj->total_base_item;
        $oObj->totals->base->item_discount         = (int) $oObj->total_base_item_discount;
        $oObj->totals->base->shipping              = (int) $oObj->total_base_shipping;
        $oObj->totals->base->shipping_discount     = (int) $oObj->total_base_shipping_discount;
        $oObj->totals->base->tax_item              = (int) $oObj->total_base_tax_item;
        $oObj->totals->base->tax_item_discount     = (int) $oObj->total_base_tax_item_discount;
        $oObj->totals->base->tax_shipping          = (int) $oObj->total_base_tax_shipping;
        $oObj->totals->base->tax_shipping_discount = (int) $oObj->total_base_tax_shipping_discount;
        $oObj->totals->base->tax_combined          = (int) $oObj->total_base_tax_combined;
        $oObj->totals->base->tax_combined_discount = (int) $oObj->total_base_tax_combined_discount;
        $oObj->totals->base->grand                 = (int) $oObj->total_base_grand;
        $oObj->totals->base->grand_discount        = (int) $oObj->total_base_grand_discount;

        $oObj->totals->base_formatted                        = new \stdClass();
        $oObj->totals->base_formatted->item                  = $this->oCurrencyModel->formatBase($oObj->totals->base->item);
        $oObj->totals->base_formatted->item_discount         = $this->oCurrencyModel->formatBase($oObj->totals->base->item_discount);
        $oObj->totals->base_formatted->shipping              = $this->oCurrencyModel->formatBase($oObj->totals->base->shipping);
        $oObj->totals->base_formatted->shipping_discount     = $this->oCurrencyModel->formatBase($oObj->totals->base->shipping_discount);
        $oObj->totals->base_formatted->tax_item              = $this->oCurrencyModel->formatBase($oObj->totals->base->tax_item);
        $oObj->totals->base_formatted->tax_item_discount     = $this->oCurrencyModel->formatBase($oObj->totals->base->tax_item_discount);
        $oObj->totals->base_formatted->tax_shipping          = $this->oCurrencyModel->formatBase($oObj->totals->base->tax_shipping);
        $oObj->totals->base_formatted->tax_shipping_discount = $this->oCurrencyModel->formatBase($oObj->totals->base->tax_shipping_discount);
        $oObj->totals->base_formatted->tax_combined          = $this->oCurrencyModel->formatBase($oObj->totals->base->tax_combined);
        $oObj->totals->base_formatted->tax_combined_discount = $this->oCurrencyModel->formatBase($oObj->totals->base->tax_combined_discount);
        $oObj->totals->base_formatted->grand                 = $this->oCurrencyModel->formatBase($oObj->totals->base->grand);
        $oObj->totals->base_formatted->grand_discount        = $this->oCurrencyModel->formatBase($oObj->totals->base->grand_discount);

        $oObj->totals->user                        = new \stdClass();
        $oObj->totals->user->item                  = (int) $oObj->total_user_item;
        $oObj->totals->user->item_discount         = (int) $oObj->total_user_item_discount;
        $oObj->totals->user->shipping              = (int) $oObj->total_user_shipping;
        $oObj->totals->user->shipping_discount     = (int) $oObj->total_user_shipping_discount;
        $oObj->totals->user->tax_item              = (int) $oObj->total_user_tax_item;
        $oObj->totals->user->tax_item_discount     = (int) $oObj->total_user_tax_item_discount;
        $oObj->totals->user->tax_shipping          = (int) $oObj->total_user_tax_shipping;
        $oObj->totals->user->tax_shipping_discount = (int) $oObj->total_user_tax_shipping_discount;
        $oObj->totals->user->tax_combined          = (int) $oObj->total_user_tax_combined;
        $oObj->totals->user->tax_combined_discount = (int) $oObj->total_user_tax_combined_discount;
        $oObj->totals->user->grand                 = (int) $oObj->total_user_grand;
        $oObj->totals->user->grand_discount        = (int) $oObj->total_user_grand_discount;

        $oObj->totals->user_formatted                        = new \stdClass();
        $oObj->totals->user_formatted->item                  = $this->oCurrencyModel->formatUser($oObj->totals->user->item);
        $oObj->totals->user_formatted->item_discount         = $this->oCurrencyModel->formatUser($oObj->totals->user->item_discount);
        $oObj->totals->user_formatted->shipping              = $this->oCurrencyModel->formatUser($oObj->totals->user->shipping);
        $oObj->totals->user_formatted->shipping_discount     = $this->oCurrencyModel->formatUser($oObj->totals->user->shipping_discount);
        $oObj->totals->user_formatted->tax_item              = $this->oCurrencyModel->formatUser($oObj->totals->user->tax_item);
        $oObj->totals->user_formatted->tax_item_discount     = $this->oCurrencyModel->formatUser($oObj->totals->user->tax_item_discount);
        $oObj->totals->user_formatted->tax_shipping          = $this->oCurrencyModel->formatUser($oObj->totals->user->tax_shipping);
        $oObj->totals->user_formatted->tax_shipping_discount = $this->oCurrencyModel->formatUser($oObj->totals->user->tax_shipping_discount);
        $oObj->totals->user_formatted->tax_combined          = $this->oCurrencyModel->formatUser($oObj->totals->user->tax_combined);
        $oObj->totals->user_formatted->tax_combined_discount = $this->oCurrencyModel->formatUser($oObj->totals->user->tax_combined_discount);
        $oObj->totals->user_formatted->grand                 = $this->oCurrencyModel->formatUser($oObj->totals->user->grand);
        $oObj->totals->user_formatted->grand_discount        = $this->oCurrencyModel->formatUser($oObj->totals->user->grand_discount);


        unset($oObj->total_base_item);
        unset($oObj->total_base_item_discount);
        unset($oObj->total_base_shipping);
        unset($oObj->total_base_shipping_discount);
        unset($oObj->total_base_tax_item);
        unset($oObj->total_base_tax_item_discount);
        unset($oObj->total_base_tax_shipping);
        unset($oObj->total_base_tax_shipping_discount);
        unset($oObj->total_base_tax_combined);
        unset($oObj->total_base_tax_combined_discount);
        unset($oObj->total_base_grand);
        unset($oObj->total_base_grand_discount);
        unset($oObj->total_user_item);
        unset($oObj->total_user_item_discount);
        unset($oObj->total_user_shipping);
        unset($oObj->total_user_shipping_discount);
        unset($oObj->total_user_tax_item);
        unset($oObj->total_user_tax_item_discount);
        unset($oObj->total_user_tax_shipping);
        unset($oObj->total_user_tax_shipping_discount);
        unset($oObj->total_user_tax_combined);
        unset($oObj->total_user_tax_combined_discount);
        unset($oObj->total_user_grand);
        unset($oObj->total_user_grand_discount);

        // --------------------------------------------------------------------------

        //  Shipping details
        $oObj->shipping_address           = new \stdClass();
        $oObj->shipping_address->line_1   = $oObj->shipping_line_1;
        $oObj->shipping_address->line_2   = $oObj->shipping_line_2;
        $oObj->shipping_address->town     = $oObj->shipping_town;
        $oObj->shipping_address->state    = $oObj->shipping_state;
        $oObj->shipping_address->postcode = $oObj->shipping_postcode;
        $oObj->shipping_address->country  = $this->oCountryModel->getByCode($oObj->shipping_country);

        unset($oObj->shipping_line_1);
        unset($oObj->shipping_line_2);
        unset($oObj->shipping_town);
        unset($oObj->shipping_state);
        unset($oObj->shipping_postcode);
        unset($oObj->shipping_country);

        $oObj->billing_address           = new \stdClass();
        $oObj->billing_address->line_1   = $oObj->billing_line_1;
        $oObj->billing_address->line_2   = $oObj->billing_line_2;
        $oObj->billing_address->town     = $oObj->billing_town;
        $oObj->billing_address->state    = $oObj->billing_state;
        $oObj->billing_address->postcode = $oObj->billing_postcode;
        $oObj->billing_address->country  = $this->oCountryModel->getByCode($oObj->billing_country);

        unset($oObj->billing_line_1);
        unset($oObj->billing_line_2);
        unset($oObj->billing_town);
        unset($oObj->billing_state);
        unset($oObj->billing_postcode);
        unset($oObj->billing_country);

        // --------------------------------------------------------------------------

        //  Vouchers
        if ($oObj->voucher_id) {

            $oObj->voucher                       = new \stdClass();
            $oObj->voucher->id                   = (int) $oObj->voucher_id;
            $oObj->voucher->code                 = $oObj->v_code;
            $oObj->voucher->label                = $oObj->v_label;
            $oObj->voucher->type                 = $oObj->v_type;
            $oObj->voucher->discount_type        = $oObj->v_discount_type;
            $oObj->voucher->discount_value       = (int) $oObj->v_discount_value;
            $oObj->voucher->discount_application = $oObj->v_discount_application;
            $oObj->voucher->product_type_id      = (int) $oObj->v_product_type_id;
            $oObj->voucher->valid_from           = $oObj->v_valid_from;
            $oObj->voucher->valid_to             = $oObj->v_valid_to;
            $oObj->voucher->is_active            = (bool) $oObj->v_is_active;
            $oObj->voucher->is_deleted           = (bool) $oObj->v_is_deleted;

        } else {

            $oObj->voucher = false;
        }

        unset($oObj->voucher_id);
        unset($oObj->v_code);
        unset($oObj->v_label);
        unset($oObj->v_type);
        unset($oObj->v_discount_type);
        unset($oObj->v_discount_value);
        unset($oObj->v_discount_application);
        unset($oObj->v_product_type_id);
        unset($oObj->v_valid_from);
        unset($oObj->v_valid_to);
        unset($oObj->v_is_active);
        unset($oObj->v_is_deleted);

        // --------------------------------------------------------------------------

        //  Items
        $oObj->items = $this->getItemsForOrder($oObj->id);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats an order item
     * @param  stdClass &$item The item to format
     * @return void
     */
    protected function formatObjectItem(&$item)
    {
        parent::formatObject($item);

        // --------------------------------------------------------------------------

        $item->sku                  = $item->v_sku;
        $item->quantity             = (int) $item->quantity;
        $item->ship_collection_only = (bool) $item->ship_collection_only;

        unset($item->v_sku);

        // --------------------------------------------------------------------------

        $item->price                               = new \stdClass();
        $item->price->base                         = new \stdClass();
        $item->price->base->value_inc_tax          = (int) $item->price_base_value_inc_tax;
        $item->price->base->value_ex_tax           = (int) $item->price_base_value_ex_tax;
        $item->price->base->value_tax              = (int) $item->price_base_value_tax;
        $item->price->base->discount_value_inc_tax = (int) $item->price_base_discount_value_inc_tax;
        $item->price->base->discount_value_ex_tax  = (int) $item->price_base_discount_value_ex_tax;
        $item->price->base->discount_value_tax     = (int) $item->price_base_discount_value_tax;
        $item->price->base->discount_item          = (int) $item->price_base_discount_item;
        $item->price->base->discount_tax           = (int) $item->price_base_discount_tax;
        $item->price->base->item_total             = (int) ($item->price_base_value_ex_tax + $item->price_base_value_tax) * $item->quantity;

        $item->price->base_formatted                         = new \stdClass();
        $item->price->base_formatted->value_inc_tax          = $this->oCurrencyModel->formatBase($item->price_base_value_inc_tax);
        $item->price->base_formatted->value_ex_tax           = $this->oCurrencyModel->formatBase($item->price_base_value_ex_tax);
        $item->price->base_formatted->value_tax              = $this->oCurrencyModel->formatBase($item->price_base_value_tax);
        $item->price->base_formatted->discount_value_inc_tax = $this->oCurrencyModel->formatBase($item->price_base_discount_value_inc_tax);
        $item->price->base_formatted->discount_value_ex_tax  = $this->oCurrencyModel->formatBase($item->price_base_discount_value_ex_tax);
        $item->price->base_formatted->discount_value_tax     = $this->oCurrencyModel->formatBase($item->price_base_discount_value_tax);
        $item->price->base_formatted->discount_item          = $this->oCurrencyModel->formatBase($item->price_base_discount_item);
        $item->price->base_formatted->discount_tax           = $this->oCurrencyModel->formatBase($item->price_base_discount_tax);
        $item->price->base_formatted->item_total             = $this->oCurrencyModel->formatBase($item->price->base->item_total);

        $item->price->user                         = new \stdClass();
        $item->price->user->value_inc_tax          = (int) $item->price_user_value_inc_tax;
        $item->price->user->value_ex_tax           = (int) $item->price_user_value_ex_tax;
        $item->price->user->value_tax              = (int) $item->price_user_value_tax;
        $item->price->user->discount_value_inc_tax = (int) $item->price_user_discount_value_inc_tax;
        $item->price->user->discount_value_ex_tax  = (int) $item->price_user_discount_value_ex_tax;
        $item->price->user->discount_value_tax     = (int) $item->price_user_discount_value_tax;
        $item->price->user->discount_item          = (int) $item->price_user_discount_item;
        $item->price->user->discount_tax           = (int) $item->price_user_discount_tax;
        $item->price->user->item_total             = (int) ($item->price_user_value_ex_tax + $item->price_user_value_tax) * $item->quantity;

        $item->price->user_formatted                         = new \stdClass();
        $item->price->user_formatted->value_inc_tax          = $this->oCurrencyModel->formatUser($item->price_user_value_inc_tax);
        $item->price->user_formatted->value_ex_tax           = $this->oCurrencyModel->formatUser($item->price_user_value_ex_tax);
        $item->price->user_formatted->value_tax              = $this->oCurrencyModel->formatUser($item->price_user_value_tax);
        $item->price->user_formatted->discount_value_inc_tax = $this->oCurrencyModel->formatUser($item->price_user_discount_value_inc_tax);
        $item->price->user_formatted->discount_value_ex_tax  = $this->oCurrencyModel->formatUser($item->price_user_discount_value_ex_tax);
        $item->price->user_formatted->discount_value_tax     = $this->oCurrencyModel->formatUser($item->price_user_discount_value_tax);
        $item->price->user_formatted->discount_item          = $this->oCurrencyModel->formatUser($item->price_user_discount_item);
        $item->price->user_formatted->discount_tax           = $this->oCurrencyModel->formatUser($item->price_user_discount_tax);
        $item->price->user_formatted->item_total             = $this->oCurrencyModel->formatUser($item->price->user->item_total);

        $item->processed = (bool) $item->processed;
        $item->refunded  = (bool) $item->refunded;

        unset($item->price_base_value_inc_tax);
        unset($item->price_base_value_ex_tax);
        unset($item->price_base_value_tax);
        unset($item->price_base_discount_value_inc_tax);
        unset($item->price_base_discount_value_ex_tax);
        unset($item->price_base_discount_value_tax);

        unset($item->price_user_value_inc_tax);
        unset($item->price_user_value_ex_tax);
        unset($item->price_user_value_tax);
        unset($item->price_user_discount_value_inc_tax);
        unset($item->price_user_discount_value_ex_tax);
        unset($item->price_user_discount_value_tax);

        // --------------------------------------------------------------------------

        //  Product type
        $item->type             = new \stdClass();
        $item->type->id         = (int) $item->pt_id;
        $item->type->label      = $item->pt_label;
        $item->type->ipn_method = $item->pt_ipn_method;

        unset($item->pt_id);
        unset($item->pt_label);
        unset($item->pt_ipn_method);

        // --------------------------------------------------------------------------

        //  Tax rate
        $item->tax_rate        = new \stdClass();
        $item->tax_rate->id    = (int) $item->tax_rate_id;
        $item->tax_rate->label = $item->tax_rate_label;
        $item->tax_rate->rate  = (float) $item->tax_rate_rate;

        unset($item->tax_rate_id);
        unset($item->tax_rate_label);
        unset($item->tax_rate_rate);

        // --------------------------------------------------------------------------

        //  Meta
        unset($item->meta->id);
        unset($item->meta->product_id);

        // --------------------------------------------------------------------------

        //  Extra data
        $item->extra_data = $item->extra_data ? json_decode($item->extra_data) : null;
    }
}
