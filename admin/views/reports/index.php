<div class="group-shop reports index">

    <p>
        Generate a variety of reports from shop data.
    </p>
    <p class="system-alert message">
        <strong>Please note:</strong> This process can take some time to execute on large Databases and may time out. If
        you are experiencing timeouts consider increasing the timeout limit for PHP temporarily or executing
        <u rel="tipsy" title="Use command: `php index.php admin shop reports`">via the command line</u>.
    </p>

    <?=form_open()?>
    <fieldset>
        <legend>Select Report</legend>
        <?php

            //    Display Name
            $_field                    = array();
            $_field['key']            = 'report';
            $_field['label']        = 'Report';
            $_field['required']        = true;
            $_field['class']        = 'select2';

            $_options = array();
            foreach ($sources as $key => $source) {

                $_options[$key] = $source[0] . ' - ' . $source[1];

            }

            echo form_field_dropdown($_field, $_options);

        ?>
    </fieldset>

    <fieldset>
        <legend>Format</legend>
        <?php

            //    Display Name
            $_field                    = array();
            $_field['key']            = 'format';
            $_field['label']        = 'Format';
            $_field['required']        = true;
            $_field['class']        = 'select2';

            $_options = array();
            foreach ($formats as $key => $format) {

                $_options[$key] = $format[0] . ' - ' . $format[1];

            }

            echo form_field_dropdown($_field, $_options);

        ?>
    </fieldset>

    <p>
        <?=form_submit('submit', 'Generate Report', 'class="awesome"')?>
    </p>
    <?=form_close()?>
</div>