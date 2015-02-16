<div class="search">
    <div class="mask">
        <b class="fa fa-refresh fa-spin fa-2x"></b>
    </div>
    <?php

        $_form = array(
            'method'    => 'GET'
        );
        echo form_open(null, $_form);

        echo '<div class="search-text">';
        echo form_input('search', $this->input->get('search'), 'autocomplete="off" placeholder="' . lang('admin_search_placeholder') . '"');
        echo '</div>';

        // --------------------------------------------------------------------------

        if (count($categoriesFlat)) {

            $options = array('All Categories') + $categoriesFlat;

            echo '<div class="search-category" style="position:relative;">';
                echo '<div style="width:120px;position:absolute;left:0;top:8px;">Search only items in </div>';
                echo '<div style="width:100%;padding-left:120px;padding-right:10px;box-sizing:border-box">';
                    echo form_dropdown('category', $options, $category_id, 'class="select2" style="width:100%;"');
                echo '</div>';
            echo '</div>';
        }
        // --------------------------------------------------------------------------

        $_sort = array(
            'p.id'            => 'ID',
            'p.label'        => 'Label',
            'p.modified'    => 'Modified Date',
            'pt.label'        => 'Type',
            'p.is_active'    => 'Active State'
        );
        echo lang('admin_search_sort') . form_dropdown('sort_on', $_sort, $sort_on, 'class="select2"');

        // --------------------------------------------------------------------------

        $_order = array(
            'asc'    => 'Ascending',
            'desc'    => 'Descending'
        );
        echo lang('admin_search_order_1') . form_dropdown('order', $_order, $sort_order, 'class="select2"') . lang('admin_search_order_2');

        // --------------------------------------------------------------------------

        $_perpage = array(
            10 => 10,
            25 => 25,
            50 => 50,
            75 => 75,
            100 => 100
        );
        echo form_dropdown('per_page', $_perpage, $pagination->per_page, 'class="select2" style="width:75px;');
        echo lang('admin_search_per_page');

        // --------------------------------------------------------------------------

        echo anchor(uri_string(), lang('action_reset'), 'class="awesome small right"');
        echo form_submit('submit', lang('action_search'), 'class="awesome small right"');

        // --------------------------------------------------------------------------

        echo form_close();

    ?>
</div>

<hr />