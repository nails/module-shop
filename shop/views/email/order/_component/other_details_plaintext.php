<?php

$invoiceCompany   = appSetting('invoice_company', 'nailsapp/module-shop');
$invoiceAddress   = appSetting('invoice_address', 'nailsapp/module-shop');
$invoiceVatNo     = appSetting('invoice_vat_no', 'nailsapp/module-shop');
$invoiceCompanyNo = appSetting('invoice_company_no', 'nailsapp/module-shop');
$invoiceFooter    = appSetting('invoice_footer', 'nailsapp/module-shop');

if (empty($invoiceCompany)||!empty($invoiceAddress)||!empty($invoiceVatNo)||!empty($invoiceCompanyNo)) {

    echo "\n\n" . 'OTHER DETAILS' . "\n";
    echo '-------------' . "\n\n";

    if (!empty($invoiceCompany)||!empty($invoiceAddress)) {

        echo 'MERCHANT' . "\n";
        echo $invoiceCompany  ? $invoiceCompany : APP_NAME;
        echo $invoiceAddress  ? "\n" . $invoiceAddress : '';
        echo "\n\n";
    }

    if (!empty($invoiceVatNo)) {

        echo 'VAT NUMBER' . "\n";
        echo $invoiceVatNo ? $invoiceVatNo : '';
        echo "\n\n";

    }

    if (!empty($invoiceCompanyNo)) {

        echo 'COMPANY NUMBER' . "\n";
        echo $invoiceCompanyNo ? $invoiceCompanyNo : '';
        echo "\n\n";
    }
}

if (!empty($invoiceFooter)) {

    echo "\n\n" . $invoiceFooter;
}
