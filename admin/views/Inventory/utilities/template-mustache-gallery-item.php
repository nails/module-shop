<?php

    if (!empty($objectId)) {

        echo '<li class="gallery-item">';
            echo img(array('src' => cdnCrop($objectId, 100, 100), 'style' => 'width:100px;height:100px;'));
            echo '<a href="#" class="delete" data-object_id="' . $objectId . '"></a>';
            echo form_hidden('gallery[]', $objectId);
        echo '</li>';

    } else {

        echo '<li class="gallery-item crunching">';
            echo '<div class="crunching"></div>';
            echo form_hidden('gallery[]');
        echo '</li>';

    }
