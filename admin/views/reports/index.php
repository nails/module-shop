<div class="group-shop reports index">
    <p>
        Generate a variety of reports from shop data.
    </p>
    <p class="system-alert message">
        <strong>Please note:</strong> This process can take some time to execute on large Databases and
        may time out. If you are experiencing timeouts consider increasing the timeout limit for PHP temporarily
        or executing <u rel="tipsy" title="Use command: `php index.php admin shop reports`">via the command line</u>.
    </p>
    <?=form_open()?>
    <fieldset>
        <legend>Report</legend>
        <?php

            //  Report name
            $field             = array();
            $field['key']      = 'report';
            $field['label']    = 'Report';
            $field['required'] = true;
            $field['class']    = 'select2';
            $field['id']       = 'report-source';

            $options = array();
            foreach ($sources as $key => $source) {

                $respectsPeriod = $source[3] ? 1 : 0;
                $options[$key . ':' . $respectsPeriod] = $source[0] . ' - ' . $source[1];
            }

            echo form_field_dropdown($field, $options);

            // --------------------------------------------------------------------------

            //  Report name
            $field             = array();
            $field['key']      = 'period';
            $field['label']    = 'Period';
            $field['class']    = 'select2';
            $field['id']       = 'report-period';

            echo form_field_dropdown($field, $periods);

            // --------------------------------------------------------------------------

            //  Format
            $field             = array();
            $field['key']      = 'format';
            $field['label']    = 'Format';
            $field['required'] = true;
            $field['class']    = 'select2';

            $options = array();
            foreach ($formats as $key => $format) {

                $options[$key] = $format[0] . ' - ' . $format[1];

            }

            echo form_field_dropdown($field, $options);

        ?>
    </fieldset>

    <p>
        <?=form_submit('submit', 'Generate Report', 'class="awesome"')?>
    </p>
    <?=form_close()?>
</div>