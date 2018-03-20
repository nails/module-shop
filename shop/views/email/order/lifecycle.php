<?php
//  Echo'ing using PHP so that any Mustache snippets within get rendered
echo $emailObject->data->body;

$oView = \Nails\Factory::service('View');
$oView->load('shop/email/order/_component/order_details', array('order' => $emailObject->data->order));
$oView->load('shop/email/order/_component/other_details');
