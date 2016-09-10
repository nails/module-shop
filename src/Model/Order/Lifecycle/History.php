<?php

namespace Nails\Shop\Model\Order\Lifecycle;

use Nails\Common\Model\Base;

class History extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->table       = NAILS_DB_PREFIX . 'shop_order_lifecycle_history';
        $this->tableAlias = 'olh';

        $this->addExpandableField(
            array(
                'trigger'     => 'lifecycle',
                'type'        => self::EXPANDABLE_TYPE_SINGLE,
                'property'    => 'lifecycle',
                'model'       => 'OrderLifecycle',
                'provider'    => 'nailsapp/module-shop',
                'id_column'   => 'lifecycle_id'
            )
        );
    }
}
