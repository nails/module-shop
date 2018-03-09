<div class="search">
    <div class="mask">
        <b class="fa fa-refresh fa-spin fa-2x"></b>
    </div>
    <?php

        $form = array(
            'method' => 'GET'
       );
        echo form_open(null, $form);

        echo '<div class="search-text">';
        echo form_input(
            'search',
            $this->input->get('search'),
            'autocomplete="off" placeholder="' . lang('admin_search_placeholder') . '"'
       );
        echo '</div>';

        // --------------------------------------------------------------------------

        $sort = array(
            'sv.created'   => 'Created Date',
            'sv.code'      => 'Code',
            'sv.modified'  => 'Last Used',
            'sv.is_active' => 'Active State'
       );
        echo lang('admin_search_sort');
        echo form_dropdown('sort_on', $sort, $sort_on, 'class="select2"');

        // --------------------------------------------------------------------------

        $order = array(
            'asc'   => 'Ascending',
            'desc'  => 'Descending'
       );
        echo lang('admin_search_order_1');
        echo form_dropdown('order', $order, $sort_order, 'class="select2"') . lang('admin_search_order_2');

        // --------------------------------------------------------------------------

        $perPage = array(
            10  => 10,
            25  => 25,
            50  => 50,
            75  => 75,
            100 => 100
       );
        echo form_dropdown('per_page', $perPage, $pagination->per_page, 'class="select2" style="width:75px;');
        echo lang('admin_search_per_page');

        // --------------------------------------------------------------------------

        echo '<hr />';

        echo 'Type:';
        echo '<label>';
            echo form_checkbox('show[]', 'NORMAL', empty($_GET['show']) || in_array('NORMAL', $_GET['show']));
            echo 'Normal';
        echo '</label>';

        echo '<label>';
            echo form_checkbox('show[]', 'LIMITED_USE', empty($_GET['show']) || in_array('LIMITED_USE', $_GET['show']));
            echo 'Limited Use';
        echo '</label>';

        echo '<label>';
            echo form_checkbox('show[]', 'GIFT_CARD', empty($_GET['show']) || in_array('GIFT_CARD', $_GET['show']));
            echo 'Gift Card';
        echo '</label>';

        // --------------------------------------------------------------------------
        echo anchor(uri_string() . '?reset=true', lang('action_reset'), 'class="btn btn-xs btn-default right"');
        echo form_submit('submit', lang('action_search'), 'class="btn btn-xs btn-primary right"');

        // --------------------------------------------------------------------------

        echo form_close();

    ?>
</div>
<hr />