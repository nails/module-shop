<?php

namespace Nails\Shop\Model\Order;

use Nails\Factory;
use Nails\Common\Model\Base;
use Nails\Common\Exception\NailsException;

class Lifecycle extends Base
{
    /**
     * At minimum an order is placed, payment is confirmed then it is dispatched. These entries are semi-customisable
     * but their IDs are defined as part of installation and cannot be adjusted.
     */
    const ORDER_PLACED     = 1;
    const ORDER_PAID       = 2;
    const ORDER_DISPATCHED = 3;
    const ORDER_COLLECTED  = 4;

    // --------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
        $this->table             = NAILS_DB_PREFIX . 'shop_order_lifecycle';
        $this->tablePrefix       = 'ol';
        $this->defaultSortColumn = 'order';
    }

    // --------------------------------------------------------------------------

    public function setPlaced($iOrderId)
    {
        return $this->setLifecycle($iOrderId, static::ORDER_PLACED);
    }

    // --------------------------------------------------------------------------

    public function setPaid($iOrderId)
    {
        return $this->setLifecycle($iOrderId, static::ORDER_PAID);
    }

    // --------------------------------------------------------------------------

    public function setDispatched($iOrderId)
    {
        return $this->setLifecycle($iOrderId, static::ORDER_DISPATCHED);
    }

    // --------------------------------------------------------------------------

    public function setCollected($iOrderId)
    {
        return $this->setLifecycle($iOrderId, static::ORDER_COLLECTED);
    }

    // --------------------------------------------------------------------------

    public function setLifecycle($iOrderId, $iLifecycleId)
    {
        $oLifeCycle = $this->getById($iLifecycleId);
        if (empty($oLifeCycle)) {
            throw new NailsException('"' . $iLifecycleId . '" is not a valid lifecycle ID.');
        }

        $oOrderModel = Factory::model('Order', 'nailsapp/module-shop');
        $oOrder      = $oOrderModel->getById($iOrderId);

        if (empty($oOrder)) {
            throw new NailsException('"' . $iOrderId . '" is not a valid order ID.');
        }

        $aData = array(
            'lifecycle_id' => $oLifeCycle->id
        );


        if (!$oOrderModel->update($oOrder->id, $aData)) {
            throw new NailsException('Failed to update order. ' . $oOrderModel->lastError());
        }

        //  Send email to customer, if required
        if ($oLifeCycle->send_email) {

            $oEmail = (object) array(
                'type' => 'shop_order_lifecycle',
                'data' => (object) array(
                    'email_subject'  => $oLifeCycle->email_subject,
                    'body'           => $oLifeCycle->email_body,
                    'body_plaintext' => $oLifeCycle->email_body_plaintext,
                    'order'          => $oOrder
                )
            );

            if (!empty($oOrder->user->id)) {
                $oEmail->to_id = $oOrder->user->id;
            } elseif (!empty($oOrder->user->email)) {
                $oEmail->to_email = $oOrder->user->email;
            } else {
                //  No email to send to, bail out gracefully
                return true;
            }

            $oEmailer = Factory::service('Emailer', 'nailsapp/module-email');
            $oEmailer->send($oEmail);
        }

        return true;
    }
}
