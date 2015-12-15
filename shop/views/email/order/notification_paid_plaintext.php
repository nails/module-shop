<?php

/**
 * We're rendering this template using PHP as it's a bit more complex than Mustache
 * allows, specifically the order_details sub-view.
 */

?>
An order has just been completed with <?=APP_NAME?>.

Full payment has been received and the order should be processed.

<?php

$sWord = $emailObject->data->order->delivery_type === 'COLLECT' ? 'ALL' : 'SOME';

if (in_array($emailObject->data->order->delivery_type, array('COLLECT', 'DELIVER_COLLECT', 'DELIVER'))) {

    $aBox   = array();
    $aBox[] = 'IMPORTANT: ' . $sWord . ' ITEMS IN THIS ORDER WILL BE COLLECTED.';

    $aBox = array_map(function($val) {

        return str_pad($val, 55, ' ');

    }, $aBox);

    echo "\n" . '+--------------------------------------------------------+';
    echo "\n| " . implode("|\n| ", $aBox) . '|';
    echo "\n" . '+--------------------------------------------------------+';
    echo "\n\n\n";
}

$this->load->view('shop/email/order/_component/order_details_plaintext', array('order' => $emailObject->data->order));
