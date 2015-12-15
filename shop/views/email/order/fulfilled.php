<?php

/**
 * We're rendering this template using PHP as it's a bit more complex than Mustache
 * allows, specifically the order_details sub-view.
 */

?>
<p>
    Thank you very much for your order with <?=anchor('', APP_NAME)?>.
</p>
<p>
    Your order has been marked as fulfilled, indicating the dispatch of your goods.
    If you have any questions, please get in touch using the details below.
</p>
<?php

$this->load->view('shop/email/order/_component/order_details', array('order' => $emailObject->data->order));
$this->load->view('shop/email/order/_component/other_details');
