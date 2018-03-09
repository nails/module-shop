<html>
    <head>
        <title><?=$label?></title>
        <style type="text/css">

            html,
            body
            {
                font-family: "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
                font-weight: 300;
                padding:0;
                margin:0;
                font-size: 14px;
                line-height: 22px;
            }

            table
            {
                border: 1px solid #CCC;
                padding: 2px;
                width: 100%;
            }

            table caption
            {
                padding: 1.5em;
                font-weight: bold;
                font-size:1.25em;
            }

            table thead th,
            table tfoot th
            {
                padding:0.5em;
                background:#EFEFEF;
                text-align: left;
            }

            table thead th
            {
                border-bottom:1px solid #CCC;
            }

            table tfoot th
            {
                border-top:1px solid #CCC;
            }

            table tbody td
            {
                padding:0.75em;
                border-right: 1px dotted #CECECE;
            }

            table tbody td:last-of-type
            {
                border-right: 0;
            }

        </style>
    </head>
    <body>
        <table border="0" cellspacing="0" cellpadding="0">
            <caption><?=$label?></caption>
            <thead>
                <tr>
                    <?php

                        for ($x=0; $x < count($fields); $x++) :

                            echo $x != 0 ? '                    ' : '';
                            echo '<th>' . $fields[$x] . '</th>' . "\n";

                        endfor;

                    ?>
                </tr>
            </thead>
            <tbody>
                <?php

                    for ($i=0; $i< count($data); $i++) :

                        echo $i != 0 ? '                ' : '';
                        echo '<tr>' . "\n";

                        $_data = array_values($data[$i]);
                        for ($x=0; $x < count($_data); $x++) :

                            echo '                    ';
                            echo '<td>' . $_data[$x] . '</td>' . "\n";

                        endfor;

                        echo '                ';
                        echo '</tr>' . "\n";

                    endfor;

                ?>
            </tbody>
            <tfoot>
                <tr>
                    <?php

                        for ($x=0; $x < count($fields); $x++) :

                            echo $x != 0 ? '                    ' : '';
                            echo '<th>' . $fields[$x] . '</th>' . "\n";

                        endfor;

                    ?>
                </tr>
            </tfoot>
        </table>
    </body>
</html>