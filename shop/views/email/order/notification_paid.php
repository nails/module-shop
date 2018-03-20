<?php

/**
 * We're rendering this template using PHP as it's a bit more complex than Mustache
 * allows, specifically the order_details sub-view.
 */

?>
<p>
    An order has just been completed with <?=anchor('', APP_NAME)?>.
</p>
<p>
    Full payment has been received and the order should be processed.
</p>
<?php

$sWord = $emailObject->data->order->delivery_type === 'COLLECT' ? 'All' : 'Some';

if (in_array($emailObject->data->order->delivery_type, array('COLLECT', 'DELIVER_COLLECT'))) {

    ?>
    <p class="heads-up warning">
        <strong>Important:</strong> <?=$sWord?> items in this order will be collected.
    </p>
    <?php
}

$oView = \Nails\Factory::service('View');
$oView->load('shop/email/order/_component/order_details', array('order' => $emailObject->data->order));
