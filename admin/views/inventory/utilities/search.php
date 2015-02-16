<hr />
<div class="search clearfix">
    <div class="mask">
        <b class="fa fa-refresh fa-2x"></b>
    </div>
    <?php

        parse_str($this->input->server('QUERY_STRING'), $query);
        unset($query['keywords']);
        unset($query['sortOn']);
        unset($query['sortOrder']);
        unset($query['perPage']);
        unset($query['page']);
        unset($query['filter']);

        $formAttr = array(
            'method' => 'GET'
        );

        echo form_open(null, $formAttr);

        foreach ($query as $key => $value) {

            echo form_hidden($key, $value);
        }

        if ($searchable) {

            echo '<div class="search-text">';
                echo form_input(
                    'keywords',
                    $keywords,
                    'autocomplete="off" placeholder="Type your search term and hit enter"'
                );
            echo '</div>';
        }

        // --------------------------------------------------------------------------

        echo '<span style="padding-right: 1em;">';

            if (!empty($sortColumns)) {

                //  Sort Column
                echo 'Sort results by';
                echo form_dropdown('sortOn', $sortColumns, $sortOn);

            } else {

                echo 'Sort results';
            }

            //  Sort order
            $options = array(
                'asc'  => 'Ascending',
                'desc' => 'Descending'
            );

            echo form_dropdown('sortOrder', $options, $sortOrder);

        echo '</span>';

        // --------------------------------------------------------------------------

        echo '<span style="padding-right: 1em;">';

            //  Results per page
            $options = array(
                10  => 10,
                25  => 25,
                50  => 50,
                75  => 75,
                100 => 100
            );

            echo 'Show';
            echo form_dropdown('perPage', $options, $perPage);
            echo 'results per page.';

        echo '</span>';

        // --------------------------------------------------------------------------

        //  Filters
        if (!empty($filters)) {

            echo '<hr />';
            foreach ($filters as $filterIndex => $filter) {

                echo '<span class="filterGroup">';
                    echo '<span class="filterLabel">';
                        echo $filter->label;
                    echo '</span>';

                    foreach ($filter->options as $optionIndex => $option) {

                        //  Checked or not?
                        if (!empty($_GET)) {

                            $checked = !empty($_GET['filter'][$filterIndex][$optionIndex]);

                        } else {

                            $checked = $option->checked;
                        }

                        $checked = $checked ? 'checked="checked"' : '';

                        echo '<label class="filterOption">';
                            echo '<input type="checkbox" name="filter[' . $filterIndex . '][' . $optionIndex . ']" ' . $checked . ' value="1">';
                            echo $option->label;
                        echo '</label>';
                    }

                echo '</span>';
            }
        }

        // --------------------------------------------------------------------------

        echo '<div class="actions">';

            $resetUrl  = uri_string();
            $resetUrl .= $query ? '?' . http_build_query($query) : '';

            echo anchor($resetUrl, 'Reset', 'class="awesome small grey"');
            echo '<button type="submit" class="awesome small">Search</button>';

        echo '</div>';

        // --------------------------------------------------------------------------

        echo form_close();

    ?>
</div>