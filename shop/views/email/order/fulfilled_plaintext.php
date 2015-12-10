Thank you very much for your order with <?=APP_NAME?>.

Your order has been marked as fulfilled, indicating the dispatch of your goods. If you have any questions, please get in touch using the details below.

<?php

$this->load->view('shop/email/order/_component/order_details_plaintext', array('order' => $order));
$this->load->view('shop/email/order/_component/other_details_plaintext');
