<?php
//  Echo'ing using PHP so that any Mustache snippets within get rendered
echo $emailObject->data->body_plaintext . "\n\n\n\n";

$oView = \Nails\Factory::service('View');
$oView->load('shop/email/order/_component/order_details_plaintext', array('order' => $emailObject->data->order));
$oView->load('shop/email/order/_component/other_details_plaintext');
