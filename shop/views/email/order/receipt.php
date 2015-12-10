<p>
    Thank you very much for your order with <?=anchor('', APP_NAME)?>.
</p>
<p>
    We have now received full payment for your order, please don't hesitate
    to contact us if you have any questions or concerns.
</p>
<?php

$sWord = $order->delivery_type === 'COLLECT' ? 'All' : 'Some';

if (in_array($order->delivery_type, array('COLLECT', 'DELIVER_COLLECT'))) {

    $aAddress   = array();
    $aAddress[] = appSetting('warehouse_addr_addressee', 'shop');
    $aAddress[] = appSetting('warehouse_addr_line1', 'shop');
    $aAddress[] = appSetting('warehouse_addr_line2', 'shop');
    $aAddress[] = appSetting('warehouse_addr_town', 'shop');
    $aAddress[] = appSetting('warehouse_addr_postcode', 'shop');
    $aAddress[] = appSetting('warehouse_addr_state', 'shop');
    $aAddress[] = appSetting('warehouse_addr_country', 'shop');
    $aAddress   = array_filter($aAddress);

    if ($aAddress) {

        ?>
        <div class="heads-up warning">
            <strong>Important:</strong> <?=$sWord?> items in this order should be collected from:
            <hr>
            <?=implode('<br />', $aAddress)?>
        </div>
        <?php

    } else {

        ?>
        <p class="heads-up warning">
            <strong>Important:</strong> <?=$sWord?> items in this order should be collected.
        </p>
        <?php
    }
}

// --------------------------------------------------------------------------

$this->load->view('shop/email/order/_component/order_details', array('order' => $order));
$this->load->view('shop/email/order/_component/other_details');
