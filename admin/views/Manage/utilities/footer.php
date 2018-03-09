<?php

    /**
     * This view builds the _DATA array which is read by the parent
     * page when viewed as a fancybox
     */

    $options = array();

    foreach ($items as $item) {

        $temp        = new \stdClass();
        $temp->id    = $item->id;
        $temp->label = $item->label;

        $options[] = $temp;
    }

    //  Set _DATA
    echo '<script type="text/javascript">';
        echo 'var _DATA = ' . json_encode($options) . ';';
    echo '</script>';
