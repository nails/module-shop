<?php
//  Echo'ing using PHP so that any Mustache snippets within get rendered
echo $emailObject->data->body;

$this->load->view('shop/email/order/_component/order_details', array('order' => $emailObject->data->order));
$this->load->view('shop/email/order/_component/other_details');
