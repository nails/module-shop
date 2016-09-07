<?php
//  Echo'ing using PHP so that any Mustache snippets within get rendered
echo $emailObject->data->body_plaintext . "\n\n\n\n";

$this->load->view('shop/email/order/_component/order_details_plaintext', array('order' => $emailObject->data->order));
$this->load->view('shop/email/order/_component/other_details_plaintext');
