<?php

/**
 * We're rendering this template using PHP as it's a bit more complex than Mustache
 * allows, specifically the order_details sub-view.
 */

?>
Thank you very much for your order with <?=APP_NAME?>.

We have now received full payment for your order, please don't hesitate to contact us if you have any questions or concerns.

<?php

$sWord = $emailObject->data->order->delivery_type === 'COLLECT' ? 'ALL' : 'SOME';

if (in_array($emailObject->data->order->delivery_type, array('COLLECT', 'DELIVER_COLLECT', 'DELIVER'))) {

    $aBox   = array();
    $aBox[] = 'IMPORTANT: ' . $sWord . ' ITEMS IN THIS ORDER SHOULD BE COLLECTED FROM:';
    $aBox[] = '';

    $aAddress   = array();
    $aAddress[] = appSetting('warehouse_addr_addressee', 'nailsapp/module-shop');
    $aAddress[] = appSetting('warehouse_addr_line1', 'nailsapp/module-shop');
    $aAddress[] = appSetting('warehouse_addr_line2', 'nailsapp/module-shop');
    $aAddress[] = appSetting('warehouse_addr_town', 'nailsapp/module-shop');
    $aAddress[] = appSetting('warehouse_addr_postcode', 'nailsapp/module-shop');
    $aAddress[] = appSetting('warehouse_addr_state', 'nailsapp/module-shop');
    $aAddress[] = appSetting('warehouse_addr_country', 'nailsapp/module-shop');
    $aAddress   = array_filter($aAddress);

    if (!empty($aAddress)) {
        $aBox = array_merge($aBox, $aAddress);
    }

    $aBox = array_map(function($val) {

        return str_pad($val, 61, ' ');

    }, $aBox);

    echo "\n" . '+--------------------------------------------------------------+';
    echo "\n| " . implode("|\n| ", $aBox) . '|';
    echo "\n" . '+--------------------------------------------------------------+';
    echo "\n\n\n";
}

// --------------------------------------------------------------------------

$oView = \Nails\Factory::service('View');
$oView->load('shop/email/order/_component/order_details_plaintext', array('order' => $emailObject->data->order));
$oView->load('shop/email/order/_component/other_details_plaintext');
