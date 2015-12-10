<p>
    An order has just been completed with <?=anchor('', APP_NAME)?>.
</p>
<p>
    Full payment has been received and the order should be processed.
</p>
<?php

$sWord = $order->delivery_type === 'COLLECT' ? 'All' : 'Some';

if (in_array($order->delivery_type, array('COLLECT', 'DELIVER_COLLECT'))) {

    ?>
    <p class="heads-up warning">
        <strong>Important:</strong> <?=$sWord?> items in this order will be collected.
    </p>
    <?php
}

$this->load->view('shop/email/order/_component/order_details', array('order' => $order));
