var NAILS_Admin_Shop_Order_Browse;
NAILS_Admin_Shop_Order_Browse = function()
{
    this.__construct = function()
    {
        var _this = this;   /*  Ugly Scope Hack */
        $( '#toggle-all' ).on( 'click', function()
        {
            _this._toggle_all();
        });
        $( '#batch-action a' ).on( 'click', function()
        {
            _this._batch_action();
            return false;
        });
    };

    // --------------------------------------------------------------------------

    this._toggle_all = function()
    {
        var _checked = $( '#toggle-all' ).is(':checked');
        $( '.batch-checkbox' ).prop( 'checked', _checked );
    };

    // --------------------------------------------------------------------------

    this._batch_action = function()
    {
        var _action,_orders,_title,_body;

        _action = $( '#batch-action select' ).val();
        _orders = [];

        $('input.batch-checkbox:checked').each(function(){
            _orders.push($(this).val());
        });

        if (_orders.length) {

            switch(_action)
            {
                case 'mark-cancelled' :

                    this._batch_action_cancel(_orders);
                    break;

                case 'download' :

                    _title = 'Coming Soon!';
                    _body = 'Downloading multiple order invoices is in the pipeline and will be available soon.';
                    this._show_dialog(_title, _body);
                    break;

                default:

                    var regexp = /^mark\-lifecycle-\d$/;

                    if (regexp.test(_action)) {
                        var command = _action.split('-');
                        this._batch_action_lifecycle(command[2], _orders);
                    } else {
                        _title = 'Unknown command';
                        _body  = '"' + _action + '" is not a known batch command.';
                        this._show_dialog(_title, _body);
                    }
                    break;
            }

        } else {

            //  Nothing selected, complain
            _title = 'Please select some orders';
            _body  = 'You have not selected any orders. Use the checkboxes to the left ';
            _body += 'of the row and repeat the action.';

            this._show_dialog(_title, _body);

        }
    };

    // --------------------------------------------------------------------------

    this._batch_action_cancel = function(orders) {
        //  Mark these orders as cancelled!
        var _url = window.SITE_URL + 'admin/shop/orders/cancel_batch?' + $.param({ids:orders});
        window.location = _url;
    };

    // --------------------------------------------------------------------------

    this._batch_action_lifecycle = function(lifecycleId, orders) {
        //  Set lifecycle on these orders
        var _url = window.SITE_URL + 'admin/shop/orders/lifecycle_batch?' + $.param({lifecycle: lifecycleId, ids:orders});
        window.location = _url;
    };

    // --------------------------------------------------------------------------

    this._show_dialog = function(title, body) {

        $('<div>').html(body).dialog({
            title: title,
            resizable: false,
            draggable: false,
            modal: true,
            dialogClass: 'no-close',
            buttons:
            {
                OK: function()
                {
                    $(this).dialog('close');
                }
            }
        });
    };


    // --------------------------------------------------------------------------


    return this.__construct();
};
