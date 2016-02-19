<?php

$invoiceCompany   = appSetting('invoice_company', 'nailsapp/module-shop');
$invoiceAddress   = appSetting('invoice_address', 'nailsapp/module-shop');
$invoiceVatNo     = appSetting('invoice_vat_no', 'nailsapp/module-shop');
$invoiceCompanyNo = appSetting('invoice_company_no', 'nailsapp/module-shop');
$invoiceFooter    = appSetting('invoice_footer', 'nailsapp/module-shop');

if (!empty($invoiceCompany)||!empty($invoiceAddress)||!empty($invoiceVatNo)||!empty($invoiceCompanyNo)) {

    ?>
    <h2>Other Details</h2>
    <table class="default-style">
        <tbody>
        <?php

        if (!empty($invoiceCompany)||!empty($invoiceAddress)) {

            ?>
            <tr>
                <td class="left-header-cell">Merchant</td>
                <td>
                <?php

                echo $invoiceCompany ? '<strong>' . $invoiceCompany . '</strong>' : '<strong>' . APP_NAME . '</strong>';
                echo $invoiceAddress ? '<br />' . nl2br($invoiceAddress) . '<br />' : '';

                ?>
                </td>
            </tr>
            <?php
        }

        if (!empty($invoiceVatNo)) {

            ?>
            <tr>
                <td class="left-header-cell">VAT Number</td>
                <td>
                    <?=$invoiceVatNo ? $invoiceVatNo : ''?>
                </td>
            </tr>
            <?php
        }

        if (!empty($invoiceCompanyNo)) {

            ?>
            <tr>
                <td class="left-header-cell">Company Number</td>
                <td>
                    <?=$invoiceCompanyNo ? $invoiceCompanyNo : ''?>
                </td>
            </tr>
            <?php
        }

        ?>
        </tbody>
    </table>
    <?php
}

if (!empty($invoiceFooter)) {

    ?>
    <p>
        <small><?=$invoiceFooter?></small>
    </p>
    <?php
}