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

class NAILS_Shop_order_model extends NAILS_Model
{
    /**
     * Constructs the model
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop/shop_currency_model');
        $this->load->model('app_notification_model');
        $this->load->model('country_model');

        $this->table        = NAILS_DB_PREFIX . 'shop_order';
        $this->tablePrefix = 'o';
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
        //  Basket has items?
        if (empty($data->basket->items)) {

            $this->_set_error('Basket is empty.');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Is the basket already associated with an order?
        if (!empty($basket->order->id)) {

            $this->abandon($basket->order->id);
        }

        // --------------------------------------------------------------------------

        $order = new \stdClass();

        //  Generate a reference
        do {

            //  Generate the string
            $order->ref = date('Ym') . '-' . strtoupper(random_string('alpha', 8)) . '-' . date('dH');

            //  Test it
            $this->db->where('ref', $order->ref);

        } while($this->db->count_all_results(NAILS_DB_PREFIX . 'shop_order'));

        // --------------------------------------------------------------------------

        //  User's IP address
        $order->ip_address = $this->input->ip_address();

        // --------------------------------------------------------------------------

        //  Generate a code(used as a secondary verification method)
        $order->code = md5($this->input->ip_address() . '|'. time() . '|' . random_string('alnum', 15));

        // --------------------------------------------------------------------------

        /**
         * Set the user details. If defined in the order object use them, if not see
         * if anyone's logged in, if not still then either bail out or leave blank.
         */

        //  Email
        if (!empty($data->contact->email)) {

            $order->user_email = $data->contact->email;

        } elseif ($this->user_model->isLoggedIn()) {

            $order->user_email = activeUser('email');

        } else {

            $this->_set_error('An email address must be supplied');
            return false;
        }

        //  User ID
        $user = $this->user_model->get_by_email($order->user_email);
        if ($user) {

            $order->user_id = $user->id;

        } elseif ($this->user_model->isLoggedIn()) {

            $order->user_id = activeUser('id');

        } else {

            $order->user_id = null;
        }
        unset($user);

        //  First name
        if (!empty($data->contact->first_name)) {

            $order->user_first_name = $data->contact->first_name;

        } elseif ($this->user_model->isLoggedIn()) {

            $order->user_first_name = activeUser('first_name');

        } else {

            $order->user_first_name = null;
        }

        //  Last name
        if (!empty($data->contact->last_name)) {

            $order->user_last_name = $data->contact->last_name;

        } elseif ($this->user_model->isLoggedIn()) {

            $order->user_last_name = activeUser('last_name');

        } else {

            $order->user_last_name = null;
        }

        //  Telephone
        if (!empty($data->contact->telephone)) {

            $order->user_telephone = $data->contact->telephone;

        } elseif ($this->user_model->isLoggedIn()) {

            $order->user_telephone = activeUser('telephone');

        } else {

            $order->user_telephone = null;
        }

        // --------------------------------------------------------------------------

        //  Set voucher ID
        if (!empty($basket->voucher->id)) {

            $order->voucher_id = $basket->voucher->id;
        }

        // --------------------------------------------------------------------------

        //  Order Note
        if (!empty($data->basket->note)) {

            $order->note = $data->basket->note;
        }

        // --------------------------------------------------------------------------

        //  Does the order require shipping?
        $order->delivery_type = $data->basket->shipping->type;
        if ($data->basket->shipping->type == 'DELIVER') {

            //  Delivery order, check basket for physical items
            $order->requires_shipping = false;

            foreach ($data->basket->items as $item) {

                if ($item->product->type->is_physical && !$item->variant->ship_collection_only) {

                    $order->requires_shipping = true;
                    break;
                }
            }

        } else {

            //  It's a collection order, do not ship
            $order->requires_shipping = false;
        }

        // --------------------------------------------------------------------------

        //  Set currency and exchange rates
        $order->currency      = SHOP_USER_CURRENCY_CODE;
        $order->base_currency = SHOP_BASE_CURRENCY_CODE;

        // --------------------------------------------------------------------------

        //  Delivery Address
        $order->shipping_line_1   = $data->delivery->line_1;
        $order->shipping_line_2   = $data->delivery->line_2;
        $order->shipping_town     = $data->delivery->town;
        $order->shipping_state    = $data->delivery->state;
        $order->shipping_postcode = $data->delivery->postcode;
        $order->shipping_country  = $data->delivery->country;

        //  Billing Address
        $order->billing_line_1   = $data->billing->line_1;
        $order->billing_line_2   = $data->billing->line_2;
        $order->billing_town     = $data->billing->town;
        $order->billing_state    = $data->billing->state;
        $order->billing_postcode = $data->billing->postcode;
        $order->billing_country  = $data->billing->country;

        // --------------------------------------------------------------------------

        //  Set totals
        $order->total_base_item     = $data->basket->totals->base->item;
        $order->total_base_shipping = $data->basket->totals->base->shipping;
        $order->total_base_tax      = $data->basket->totals->base->tax;
        $order->total_base_grand    = $data->basket->totals->base->grand;

        $order->total_user_item     = $data->basket->totals->user->item;
        $order->total_user_shipping = $data->basket->totals->user->shipping;
        $order->total_user_tax      = $data->basket->totals->user->tax;
        $order->total_user_grand    = $data->basket->totals->user->grand;

        // --------------------------------------------------------------------------

        $order->created  = date('Y-m-d H:i:s');
        $order->modified = date('Y-m-d H{i{s');

        // --------------------------------------------------------------------------

        //  Start the transaction
        $this->db->trans_begin();
        $rollback = false;

        // --------------------------------------------------------------------------

        $this->db->set($order);
        $this->db->insert(NAILS_DB_PREFIX . 'shop_order');

        $order->id = $this->db->insert_id();

        if ($order->id) {

            //  Add the items
            $items = array();

            foreach ($data->basket->items as $item) {

                $temp                  = array();
                $temp['order_id']      = $order->id;
                $temp['product_id']    = $item->product_id;
                $temp['product_label'] = $item->product_label;
                $temp['variant_id']    = $item->variant_id;
                $temp['variant_label'] = $item->variant_label;
                $temp['quantity']      = $item->quantity;
                $temp['tax_rate_id']   = !empty($item->product->tax_rate->id) ? $item->product->tax_rate->id : null;

                //  Price
                $temp['price_base_value']         = $item->variant->price->price->base->value;
                $temp['price_base_value_inc_tax'] = $item->variant->price->price->base->value_inc_tax;
                $temp['price_base_value_ex_tax']  = $item->variant->price->price->base->value_ex_tax;
                $temp['price_base_value_tax']     = $item->variant->price->price->base->value_tax;

                $temp['price_user_value']         = $item->variant->price->price->user->value;
                $temp['price_user_value_inc_tax'] = $item->variant->price->price->user->value_inc_tax;
                $temp['price_user_value_ex_tax']  = $item->variant->price->price->user->value_ex_tax;
                $temp['price_user_value_tax']     = $item->variant->price->price->user->value_tax;

                //  Sale Price
                $temp['sale_price_base_value']         = $item->variant->price->sale_price->base->value;
                $temp['sale_price_base_value_inc_tax'] = $item->variant->price->sale_price->base->value_inc_tax;
                $temp['sale_price_base_value_ex_tax']  = $item->variant->price->sale_price->base->value_ex_tax;
                $temp['sale_price_base_value_tax']     = $item->variant->price->sale_price->base->value_tax;

                $temp['sale_price_user_value']         = $item->variant->price->sale_price->user->value;
                $temp['sale_price_user_value_inc_tax'] = $item->variant->price->sale_price->user->value_inc_tax;
                $temp['sale_price_user_value_ex_tax']  = $item->variant->price->sale_price->user->value_ex_tax;
                $temp['sale_price_user_value_tax']     = $item->variant->price->sale_price->user->value_tax;

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

                        $item->extra_data = (array) $item->extra_data;
                    }

                    $item->extra_data['to_order']              = new \stdClass();
                    $item->extra_data['to_order']->is_to_order = true;
                    $item->extra_data['to_order']->lead_time   = $item->variant->lead_time;
                }

                //  Extra data
                if (isset($item->extra_data) && $item->extra_data) {

                    $temp['extra_data'] = serialize((array) $item->extra_data);
                }

                $items[] = $temp;
                unset($temp);

            }

            $this->db->insert_batch(NAILS_DB_PREFIX . 'shop_order_product', $items);

            if (!$this->db->affected_rows()) {

                //  Set error message
                $rollback = true;
                $this->_set_error('Unable to add products to order, aborting.');
            }

        } else {

            //  Failed to create order
            $rollback = true;
            $this->_set_error('An error occurred while creating the order.');
        }

        // --------------------------------------------------------------------------

        //  Return
        if ($rollback) {

            $this->db->trans_rollback();
            return false;

        } else {

            $this->db->trans_commit();

            if ($returnObj) {

                return $this->get_by_id($order->id);

            } else {

                return $order->id;
            }
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

        $this->db->set($data);
        $this->db->set('modified', 'NOW()', false);
        $this->db->where('id', $id);
        $this->db->update(NAILS_DB_PREFIX . 'shop_order');

        return $this->db->affected_rows() ? true : false;
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     * @param array  $data    Data passed from the calling method
     * @param string $_caller The name of the calling method
     * @return void
     **/
    protected function _getcount_common($data = array(), $_caller = null)
    {
        //  Selects
        $this->db->select($this->tablePrefix . '.*');
        $this->db->select('ue.email, u.first_name, u.last_name, u.gender, u.profile_img,ug.id user_group_id,ug.label user_group_label');
        $this->db->select('v.code v_code,v.label v_label, v.type v_type, v.discount_type v_discount_type, v.discount_value v_discount_value, v.discount_application v_discount_application');
        $this->db->select('v.product_type_id v_product_type_id, v.is_active v_is_active, v.is_deleted v_is_deleted, v.valid_from v_valid_from, v.valid_to v_valid_to');

        //  Joins
        $this->db->join(NAILS_DB_PREFIX . 'user u', 'u.id = o.user_id', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.user_id = u.id AND ue.is_primary = 1', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'user_group ug', 'ug.id = u.group_id', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'shop_voucher v', 'v.id = o.voucher_id', 'LEFT');

        // --------------------------------------------------------------------------

        //  Search
        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = array();
            }

            $data['or_like'][] = array(
                'column' => $this->tablePrefix . '.code',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tablePrefix . '.user_email',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tablePrefix . '.user_first_name',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tablePrefix . '.user_last_name',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tablePrefix . '.user_telephone',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => 'ue.email',
                'value'  => $data['keywords']
            );
            $data['like'][] = array(
                'column' => 'u.first_name',
                'value'  => $data['keywords']
            );
            $data['like'][] = array(
                'column' => 'u.last_name',
                'value'  => $data['keywords']
            );
        }

        parent::_getcount_common($data, $_caller);
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

        $data['where'][] = array($this->tablePrefix . '.ref', $ref);

        $result = $this->get_all(null, null, $data, false, 'GET_BY_REF');

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

        $data['where_in'][] = array($this->tablePrefix . '.ref', $refs);

        return $this->get_all(null, null, $data, 'GET_BY_REFS');
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of items contained in an order
     * @param  int   $order_id The order's ID
     * @return array
     */
    public function getItemsForOrder($order_id)
    {
        $this->db->select('op.*');
        $this->db->select('pt.id pt_id, pt.label pt_label, pt.ipn_method pt_ipn_method');
        $this->db->select('tr.id tax_rate_id, tr.label tax_rate_label, tr.rate tax_rate_rate');
        $this->db->select('v.sku v_sku');

        $this->db->join(NAILS_DB_PREFIX . 'shop_product p', 'p.id = op.product_id');
        $this->db->join(NAILS_DB_PREFIX . 'shop_product_type pt', 'pt.id = p.type_id');
        $this->db->join(NAILS_DB_PREFIX . 'shop_tax_rate tr', 'tr.id = p.tax_rate_id', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'shop_product_variation v', 'v.id = op.variant_id', 'LEFT');

        $this->db->where('op.order_id', $order_id);
        $items = $this->db->get(NAILS_DB_PREFIX . 'shop_order_product op')->result();

        foreach ($items as $item) {

            $this->_format_object_item($item);
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
        $this->db->where_in($this->tablePrefix . '.status', array('PAID', 'UNPAID'));
        $this->db->where($this->tablePrefix . '.user_id', $userId);
        return $this->get_all();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns all paid and unpaid orders created by a particular user email
     * @param  string $email The user's email address
     * @return array
     */
    public function getForUserEmail($email)
    {
        $this->db->where_in($this->tablePrefix . '.status', array('PAID', 'UNPAID'));
        $this->db->where($this->tablePrefix . '.user_email', $email);
        return $this->get_all();
    }

    // --------------------------------------------------------------------------

    /**
     * Counts the total amount of orders for a partricular query/search key. Essentially performs
     * the same query as $this->get_all() but without limiting.
     *
     * @access  public
     * @param   string  $where  An array of where conditions
     * @param   mixed   $search A string containing the search terms
     * @return  int
     *
     **/
    public function countUnfulfilledOrders($where = null, $search = null)
    {
        $this->db->where('fulfilment_status', 'UNFULFILLED');
        $this->db->where('status', 'PAID');
        return $this->db->count_all_results(NAILS_DB_PREFIX . 'shop_order o');
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
     * @param  int     $orderId The order's ID
     * @return boolean
     */
    public function paid($orderId)
    {
        $data = array('status' => 'PAID');
        return $this->update($orderId, $data);
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
     * @param  array   $orderIds An array of order IDs to fulfill
     * @return boolean
     */
    public function cancelBatch($orderIds)
    {
        if (empty($orderIds)) {

            $this->_set_error('No IDs were supplied.');
            return false;
        }

        $this->db->set('status', 'CANCELLED');
        $this->db->where_in('id', $orderIds);
        $this->db->set('modified', 'NOW()', false);

        if ($this->db->update(NAILS_DB_PREFIX . 'shop_order')) {

            return true;

        } else {

            $this->_set_error('Failed to cancel batch.');
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
     * Marks an order as fulfilled and optionally informs the customer
     * @param  mixed   $orderId        The order's ID, or an array of order IDs
     * @param  boolean $informCustomer Whether to inform the customer or not
     * @return boolean
     */
    public function fulfil($orderId, $informCustomer = true)
    {
        if (is_array($orderId)) {

            return $this->fulfilBatch($orderId, $informCustomer);

        } else {

            // Fetch order details
            $order = $this->get_by_id($orderId);

            // --------------------------------------------------------------------------

            //  Set fulfil data
            $data = array(
                'fulfilment_status' => 'FULFILLED',
                'fulfilled'         => date('Y-m-d H:i:s')
           );

            // --------------------------------------------------------------------------

            if ($this->update($orderId, $data)) {

                if ($informCustomer) {

                    $email                = new \stdClass();
                    $email->type          = 'shop_order_fulfilled';
                    $email->to_email      = $order->user->email;
                    $email->data          = array();
                    $email->data['order'] = $order;

                    $this->emailer->send($email, true);
                }

                return true;

            } else {

                $this->_set_error('Failed to update fulfilment status on this order.');
                return false;
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Batch fulfil some orders and optionally informs the customer
     * @param  array   $orderIds An array of order IDs to fulfill
     * @param  boolean $informCustomer Whether to inform the customer or not
     * @return boolean
     */
    public function fulfilBatch($orderIds, $informCustomer = true)
    {
        if (empty($orderIds)) {

            $this->_set_error('No IDs were supplied.');
            return false;
        }

        $this->db->set('fulfilment_status', 'FULFILLED');
        $this->db->set('fulfilled', 'NOW()', false);
        $this->db->where_in('id', $orderIds);
        $this->db->set('modified', 'NOW()', false);

        if ($this->db->update(NAILS_DB_PREFIX . 'shop_order')) {

            if ($informCustomer) {

            foreach ($orderIds as $o) {

                    // Fetch order details
                    $order = $this->get_by_id($o);

                    // --------------------------------------------------------------------------

                    $email                = new \stdClass();
                    $email->type          = 'shop_order_fulfilled';
                    $email->to_email      = $order->user->email;
                    $email->data          = array();
                    $email->data['order'] = $order;

                    $this->emailer->send($email, true);
                }
            }

            return true;

        } else {

            $this->_set_error('Failed to update fulfilment status on batch.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Marks an order as unfulfilled
     * @param  mixed   $orderId The order's ID, or an array of order IDs
     * @return boolean
     */
    public function unfulfil($orderId)
    {
        if (is_array($orderId)) {

            return $this->unfulfilBatch($orderId);

        } else {

            $data = array(
                'fulfilment_status' => 'UNFULFILLED',
                'fulfilled'         => null
           );

            return $this->update($orderId, $data);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Batch mark orders as unfulfilled
     * @param  array $orderIds An array of order IDs
     * @return boolean
     */
    public function unfulfilBatch($orderIds)
    {
        if (empty($orderIds)) {

            $this->_set_error('No IDs were supplied.');
            return false;
        }

        $this->db->set('fulfilment_status', 'UNFULFILLED');
        $this->db->set('fulfilled', null);
        $this->db->where_in('id', $orderIds);
        $this->db->set('modified', 'NOW()', false);
        return $this->db->update(NAILS_DB_PREFIX . 'shop_order');
    }

    // --------------------------------------------------------------------------

    /**
     * Processes an order
     * @param  int $orderId The ID of the order to process
     * @return boolean
     */
    public function process($orderId)
    {
        //  @todo: complete this
        return true;

        // --------------------------------------------------------------------------

        //  If an ID has been passed, look it up
        if (is_numeric($order)) {

            _LOG('Looking up order #' . $order);
            $order = $this->get_by_id($order);

            if (!$order) {

                _LOG('Invalid order ID');
                $this->_set_error('Invalid order ID');
                return false;
            }
        }

        // --------------------------------------------------------------------------

        _LOG('Processing order #' . $order->id);

        /**
         * Loop through all the items in the order. If there's a proccessor method
         * for the object type then begin grouping the products so we can execute
         * the processor in a oner with all the associated products
         */

        $_processors = array();

        foreach ($order->items as $item) {

            _LOG('Processing item #' . $item->id . ': ' . $item->title . '(' . $item->type->label . ')');

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

            _LOG('Executing processors...');

            foreach ($_processors as $method => $products) {

                _LOG('... ' . $method . '(); with ' . count($products) . ' items.');
                call_user_func_array(array($this, $method), array(&$products, &$order));
            }
        }

        // --------------------------------------------------------------------------

        /**
         * Has the order been fulfilled? If all products in the order are processed
         * then consider this order fulfilled.
         */

        $this->db->where('order_id', $order->id);
        $this->db->where('processed', false);

        if (!$this->db->count_all_results(NAILS_DB_PREFIX . 'shop_order_product')) {

            //  No unprocessed items, consider order FULFILLED
            $this->fulfil($order->id);
        }

        // --------------------------------------------------------------------------

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Processes products of type "download"
     * @param  array    &$items An array of all the products of type "download" in the order
     * @param  stdClass &$order The complete order object
     * @return void
     */
    protected function processDownload(&$items, &$order)
    {
        //  Generate links for all the items
        $urls    = array();
        $ids     = array();
        $expires = 172800;  //  48 hours

        foreach ($items as $item) {

            $temp        = new \stdClass();
            $temp->title = $item->title;
            $temp->url   = cdn_expiring_url($item->meta->download_id, $expires, true);

            $urls[] = $temp;
            $ids[]  = $item->id;

            unset($temp);
        }

        // --------------------------------------------------------------------------

        //  Send the user an email with the links
        _LOG('Sending download email to ' . $order->user->email  . '; email contains ' . count($urls) . ' expiring URLs');

        $email                         = new \stdClass();
        $email->type                   = 'shop_product_type_download';
        $email->to_email               = $order->user->email;
        $email->data                   = array();
        $email->data['order']          = new \stdClass();
        $email->data['order']->id      = $order->id;
        $email->data['order']->ref     = $order->ref;
        $email->data['order']->created = $order->created;
        $email->data['expires']        = $expires;
        $email->data['urls']           = $urls;

        if ($this->emailer->send($email, true)) {

            //  Mark items as processed
            $this->db->set('processed', true);
            $this->db->where_in('id', $ids);
            $this->db->update(NAILS_DB_PREFIX . 'shop_order_product');

        } else {

            //  Email failed to send, alert developers
            _LOG('!!Failed to send download links, alerting developers');
            _LOG(implode("\n", $this->emailer->get_errors()));

            $subject  = 'Unable to send download email';
            $message  = 'Unable to send the email with download links to ' . $email->to_email . '; ';
            $message .= 'order #' . $order->id . "\n\nEmailer errors:\n\n";
            $message .= print_r($this->emailer->get_errors(), true);

            sendDeveloperMail($subject, $message);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Send a receipt to the user
     * @param  int     $orderId     The ordr's ID
     * @param  array   $paymentData Payment data pertaining to the order
     * @param  boolean $partial     Whether the order is aprtially paid, or completely paid
     * @return boolean
     */
    public function sendReceipt($orderId, $paymentData = array(), $partial = false)
    {
        _LOG('Looking up order #' . $orderId);
        $order = $this->get_by_id($orderId);

        if (!$order) {

            _LOG('Invalid order ID');
            $this->_set_error('Invalid order ID');
            return false;
        }

        // --------------------------------------------------------------------------

        $email                       = new \stdClass();
        $email->type                 = $partial ? 'shop_receipt_partial' : 'shop_receipt';
        $email->to_email             = $order->user->email;
        $email->data                 = array();
        $email->data['order']        = $order;
        $email->data['payment_data'] = $paymentData;

        if (!$this->emailer->send($email, true)) {

            //  Email failed to send, alert developers
            $emailErrors = $this->emailer->get_errors();

            if ($partial) {

                _LOG('!!Failed to send receipt(partial payment) to customer, alerting developers');
                $subject  = 'Unable to send customer receipt email(partial payment)';

            } else {

                _LOG('!!Failed to send receipt to customer, alerting developers');
                $subject  = 'Unable to send customer receipt email';

            }
            _LOG(implode("\n", $emailErrors));

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
        _LOG('Looking up order #' . $orderId);
        $order = $this->get_by_id($orderId);

        if (!$order) {

            _LOG('Invalid order ID');
            $this->_set_error('Invalid order ID.');
            return false;
        }

        // --------------------------------------------------------------------------

        $email                       = new \stdClass();
        $email->type                 = $partial ? 'shop_notification_partial_payment' : 'shop_notification_paid';
        $email->data                 = array();
        $email->data['order']        = $order;
        $email->data['payment_data'] = $paymentData;

        $_notify = $this->app_notification_model->get('orders', 'shop');

        foreach ($_notify as $email) {

            $email->to_email = $email;

            if (!$this->emailer->send($email, true)) {

                $emailErrors = $this->emailer->get_errors();

                if ($partial) {

                    _LOG('!!Failed to send order notification(partially payment) to ' . $email . ', alerting developers.');
                    $subject  = 'Unable to send order notification email(partial payment)';

                } else {

                    _LOG('!!Failed to send order notification to ' . $email . ', alerting developers.');
                    $subject  = 'Unable to send order notification email';

                }

                _LOG(implode("\n", $emailErrors));

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
        $this->db->set('order_id', $orderId);
        $this->db->set('note', $note);
        $this->db->set('created', 'NOW()', false);
        $this->db->set('modified', 'NOW()', false);

        if ($this->user_model->isLoggedIn()) {

            $this->db->set('created_by', activeUser('id'));
            $this->db->set('modified_by', activeUser('id'));

        } else {

            $this->db->set('created_by', null);
            $this->db->set('modified_by', null);
        }

        $this->db->insert(NAILS_DB_PREFIX . 'shop_order_note');

        return(bool) $this->db->affected_rows();
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
        $this->db->where('id', $noteId);
        $this->db->where('order_iid', $orderId);
        $this->db->delete(NAILS_DB_PREFIX . 'shop_order_note');
        return(bool) $this->db->affected_rows();
    }

    // --------------------------------------------------------------------------

    /**
     * Formats an order object
     * @param  stdClass &$order The object to format
     * @return void
     */
    protected function _format_object(&$order)
    {
        //  User
        $order->user     = new \stdClass();
        $order->user->id = $order->user_id;

        if ($order->user_email) {

            $order->user->email = $order->user_email;

        } else {

            $order->user->email = $order->email;
        }

        if ($order->user_first_name) {

            $order->user->first_name = $order->user_first_name;

        } else {

            $order->user->first_name = $order->first_name;

        }

        if ($order->user_last_name) {

            $order->user->last_name = $order->user_last_name;

        } else {

            $order->user->last_name = $order->last_name;
        }

        $order->user->telephone   = $order->user_telephone;
        $order->user->gender      = $order->gender;
        $order->user->profile_img = $order->profile_img;

        $order->user->group        = new \stdClass();
        $order->user->group->id    = $order->user_group_id;
        $order->user->group->label = $order->user_group_label;

        unset($order->user_id);
        unset($order->user_email);
        unset($order->user_first_name);
        unset($order->user_last_name);
        unset($order->user_telephone);
        unset($order->email);
        unset($order->first_name);
        unset($order->last_name);
        unset($order->gender);
        unset($order->profile_img);
        unset($order->user_group_id);
        unset($order->user_group_label);

        // --------------------------------------------------------------------------

        //  Totals
        $order->totals = new \stdClass();

        $order->totals->base           = new \stdClass();
        $order->totals->base->item     = (int) $order->total_base_item;
        $order->totals->base->shipping = (int) $order->total_base_shipping;
        $order->totals->base->tax      = (int) $order->total_base_tax;
        $order->totals->base->grand    = (int) $order->total_base_grand;

        $order->totals->base_formatted           = new \stdClass();
        $order->totals->base_formatted->item     = $this->shop_currency_model->formatBase($order->totals->base->item);
        $order->totals->base_formatted->shipping = $this->shop_currency_model->formatBase($order->totals->base->shipping);
        $order->totals->base_formatted->tax      = $this->shop_currency_model->formatBase($order->totals->base->tax);
        $order->totals->base_formatted->grand    = $this->shop_currency_model->formatBase($order->totals->base->grand);

        $order->totals->user           = new \stdClass();
        $order->totals->user->item     = (int) $order->total_user_item;
        $order->totals->user->shipping = (int) $order->total_user_shipping;
        $order->totals->user->tax      = (int) $order->total_user_tax;
        $order->totals->user->grand    = (int) $order->total_user_grand;

        $order->totals->user_formatted           = new \stdClass();
        $order->totals->user_formatted->item     = $this->shop_currency_model->formatUser($order->totals->user->item);
        $order->totals->user_formatted->shipping = $this->shop_currency_model->formatUser($order->totals->user->shipping);
        $order->totals->user_formatted->tax      = $this->shop_currency_model->formatUser($order->totals->user->tax);
        $order->totals->user_formatted->grand    = $this->shop_currency_model->formatUser($order->totals->user->grand);

        unset($order->total_base_item);
        unset($order->total_base_shipping);
        unset($order->total_base_tax);
        unset($order->total_base_grand);
        unset($order->total_user_item);
        unset($order->total_user_shipping);
        unset($order->total_user_tax);
        unset($order->total_user_grand);

        // --------------------------------------------------------------------------

        //  Shipping details
        $order->shipping_address           = new \stdClass();
        $order->shipping_address->line_1   = $order->shipping_line_1;
        $order->shipping_address->line_2   = $order->shipping_line_2;
        $order->shipping_address->town     = $order->shipping_town;
        $order->shipping_address->state    = $order->shipping_state;
        $order->shipping_address->postcode = $order->shipping_postcode;
        $order->shipping_address->country  = $this->country_model->getByCode($order->shipping_country);

        unset($order->shipping_line_1);
        unset($order->shipping_line_2);
        unset($order->shipping_town);
        unset($order->shipping_state);
        unset($order->shipping_postcode);
        unset($order->shipping_country);

        $order->billing_address           = new \stdClass();
        $order->billing_address->line_1   = $order->billing_line_1;
        $order->billing_address->line_2   = $order->billing_line_2;
        $order->billing_address->town     = $order->billing_town;
        $order->billing_address->state    = $order->billing_state;
        $order->billing_address->postcode = $order->billing_postcode;
        $order->billing_address->country  = $this->country_model->getByCode($order->billing_country);

        unset($order->billing_line_1);
        unset($order->billing_line_2);
        unset($order->billing_town);
        unset($order->billing_state);
        unset($order->billing_postcode);
        unset($order->billing_country);

        // --------------------------------------------------------------------------

        //  Vouchers
        if ($order->voucher_id) {

            $order->voucher                       = new \stdClass();
            $order->voucher->id                   = (int) $order->voucher_id;
            $order->voucher->code                 = $order->v_code;
            $order->voucher->label                = $order->v_label;
            $order->voucher->type                 = $order->v_type;
            $order->voucher->discount_type        = $order->v_discount_type;
            $order->voucher->discount_value       = (float) $order->v_discount_value;
            $order->voucher->discount_application = $order->v_discount_application;
            $order->voucher->product_type_id      = (int) $order->v_product_type_id;
            $order->voucher->valid_from           = $order->v_valid_from;
            $order->voucher->valid_to             = $order->v_valid_to;
            $order->voucher->is_active            = (bool) $order->v_is_active;
            $order->voucher->is_deleted           = (bool) $order->v_is_deleted;

        } else {

            $order->voucher = false;
        }

        unset($order->voucher_id);
        unset($order->v_code);
        unset($order->v_label);
        unset($order->v_type);
        unset($order->v_discount_type);
        unset($order->v_discount_value);
        unset($order->v_discount_application);
        unset($order->v_product_type_id);
        unset($order->v_valid_from);
        unset($order->v_valid_to);
        unset($order->v_is_active);
        unset($order->v_is_deleted);

        // --------------------------------------------------------------------------

        //  Items
        $order->items = $this->getItemsForOrder($order->id);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats an order item
     * @param  stdClass &$item The item to format
     * @return void
     */
    protected function _format_object_item(&$item)
    {
        parent::_format_object($item);

        // --------------------------------------------------------------------------

        $item->sku                  = $item->v_sku;
        $item->quantity             = (int) $item->quantity;
        $item->ship_collection_only = (bool) $item->ship_collection_only;

        unset($item->v_sku);

        // --------------------------------------------------------------------------

        $item->price                      = new \stdClass();
        $item->price->base                = new \stdClass();
        $item->price->base->value         = $this->shop_currency_model->intToFloat($item->price_base_value, SHOP_BASE_CURRENCY_CODE);
        $item->price->base->value_inc_tax = $this->shop_currency_model->intToFloat($item->price_base_value_inc_tax, SHOP_BASE_CURRENCY_CODE);
        $item->price->base->value_ex_tax  = $this->shop_currency_model->intToFloat($item->price_base_value_ex_tax, SHOP_BASE_CURRENCY_CODE);
        $item->price->base->value_tax     = $this->shop_currency_model->intToFloat($item->price_base_value_tax, SHOP_BASE_CURRENCY_CODE);

        $item->price->base_formatted                = new \stdClass();
        $item->price->base_formatted->value         = $this->shop_currency_model->formatBase($item->price_base_value);
        $item->price->base_formatted->value_inc_tax = $this->shop_currency_model->formatBase($item->price_base_value_inc_tax);
        $item->price->base_formatted->value_ex_tax  = $this->shop_currency_model->formatBase($item->price_base_value_ex_tax);
        $item->price->base_formatted->value_tax     = $this->shop_currency_model->formatBase($item->price_base_value_tax);

        $item->price->user                = new \stdClass();
        $item->price->user->value         = $this->shop_currency_model->intToFloat($item->price_user_value, SHOP_USER_CURRENCY_CODE);
        $item->price->user->value_inc_tax = $this->shop_currency_model->intToFloat($item->price_user_value_inc_tax, SHOP_USER_CURRENCY_CODE);
        $item->price->user->value_ex_tax  = $this->shop_currency_model->intToFloat($item->price_user_value_ex_tax, SHOP_USER_CURRENCY_CODE);
        $item->price->user->value_tax     = $this->shop_currency_model->intToFloat($item->price_user_value_tax, SHOP_USER_CURRENCY_CODE);

        $item->price->user_formatted                = new \stdClass();
        $item->price->user_formatted->value         = $this->shop_currency_model->formatUser($item->price_user_value);
        $item->price->user_formatted->value_inc_tax = $this->shop_currency_model->formatUser($item->price_user_value_inc_tax);
        $item->price->user_formatted->value_ex_tax  = $this->shop_currency_model->formatUser($item->price_user_value_ex_tax);
        $item->price->user_formatted->value_tax     = $this->shop_currency_model->formatUser($item->price_user_value_tax);

        $item->sale_price                      = new \stdClass();
        $item->sale_price->base                = new \stdClass();
        $item->sale_price->base->value         = $this->shop_currency_model->intToFloat($item->sale_price_base_value, SHOP_BASE_CURRENCY_CODE);
        $item->sale_price->base->value_inc_tax = $this->shop_currency_model->intToFloat($item->sale_price_base_value_inc_tax, SHOP_BASE_CURRENCY_CODE);
        $item->sale_price->base->value_ex_tax  = $this->shop_currency_model->intToFloat($item->sale_price_base_value_ex_tax, SHOP_BASE_CURRENCY_CODE);
        $item->sale_price->base->value_tax     = $this->shop_currency_model->intToFloat($item->sale_price_base_value_tax, SHOP_BASE_CURRENCY_CODE);

        $item->sale_price->base_formatted                = new \stdClass();
        $item->sale_price->base_formatted->value         = $this->shop_currency_model->formatBase($item->sale_price_base_value);
        $item->sale_price->base_formatted->value_inc_tax = $this->shop_currency_model->formatBase($item->sale_price_base_value_inc_tax);
        $item->sale_price->base_formatted->value_ex_tax  = $this->shop_currency_model->formatBase($item->sale_price_base_value_ex_tax);
        $item->sale_price->base_formatted->value_tax     = $this->shop_currency_model->formatBase($item->sale_price_base_value_tax);

        $item->sale_price->user                = new \stdClass();
        $item->sale_price->user->value         = $this->shop_currency_model->intToFloat($item->sale_price_user_value, SHOP_USER_CURRENCY_CODE);
        $item->sale_price->user->value_inc_tax = $this->shop_currency_model->intToFloat($item->sale_price_user_value_inc_tax, SHOP_USER_CURRENCY_CODE);
        $item->sale_price->user->value_ex_tax  = $this->shop_currency_model->intToFloat($item->sale_price_user_value_ex_tax, SHOP_USER_CURRENCY_CODE);
        $item->sale_price->user->value_tax     = $this->shop_currency_model->intToFloat($item->sale_price_user_value_tax, SHOP_USER_CURRENCY_CODE);

        $item->sale_price->user_formatted                = new \stdClass();
        $item->sale_price->user_formatted->value         = $this->shop_currency_model->formatUser($item->sale_price_user_value);
        $item->sale_price->user_formatted->value_inc_tax = $this->shop_currency_model->formatUser($item->sale_price_user_value_inc_tax);
        $item->sale_price->user_formatted->value_ex_tax  = $this->shop_currency_model->formatUser($item->sale_price_user_value_ex_tax);
        $item->sale_price->user_formatted->value_tax     = $this->shop_currency_model->formatUser($item->sale_price_user_value_tax);

        $item->processed = (bool) $item->processed;
        $item->refunded  = (bool) $item->refunded;

        unset($item->price_base_value);
        unset($item->price_base_value_inc_tax);
        unset($item->price_base_value_ex_tax);
        unset($item->price_base_value_tax);
        unset($item->price_user_value);
        unset($item->price_user_value_inc_tax);
        unset($item->price_user_value_ex_tax);
        unset($item->price_user_value_tax);
        unset($item->sale_price_base_value);
        unset($item->sale_price_base_value_inc_tax);
        unset($item->sale_price_base_value_ex_tax);
        unset($item->sale_price_base_value_tax);
        unset($item->sale_price_user_value);
        unset($item->sale_price_user_value_inc_tax);
        unset($item->sale_price_user_value_ex_tax);
        unset($item->sale_price_user_value_tax);

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
        $item->extra_data = $item->extra_data ? @unserialize($item->extra_data) : null;
    }
}

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core shop
 * models. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further(in all honesty I just can't face understanding the whole
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
 * before including this PHP file and extend as normal(i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_ORDER_MODEL')) {

    class Shop_order_model extends NAILS_Shop_order_model
    {
    }
}
