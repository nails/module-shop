/* globals Mustache, _nails, alert  */
//  @todo: don't use alerts
var NAILS_Admin_Shop_Settings;
NAILS_Admin_Shop_Settings = function()
{
    this.__construct = function()
    {
        this.initMaintenance();
        this.initSortable();
        this.initDelete();
        this.initAdds();
        this.initSubmits();
        this.initWarehouseCollection();
        this.initPaymentGateways();

        // --------------------------------------------------------------------------

        //  Select2's
        $('select.select2-base').select2({ formatNoMatches: 'Ensure currency is active. No currencies match' });
    };

    // --------------------------------------------------------------------------

    this.initMaintenance = function() {

        $('#shop-settings-online .toggle').on('toggle', function(e, active) {

            if (active) {

                $('#shop-maintenance-extras').show();

            } else {

                $('#shop-maintenance-extras').hide();
            }
        });
    };

    // --------------------------------------------------------------------------

    this.initSortable = function()
    {
        var _this = this;
        $('#existing-shipping-methods tbody').sortable({
            handle: 'td:first',
            stop: function()
            {
                _this._set_order_ship();
            }
        });
    };

    // --------------------------------------------------------------------------

    this.initDelete = function()
    {

        $(document).on('click', 'a.delete-row', function()
        {
            var _item = $(this);

            $('<div>').text('Are you sure you wish to delete this item?').dialog({
                title: 'Confirm Delete',
                resizable: false,
                draggable: false,
                modal: true,
                dialogClass: "no-close",
                buttons: {
                    "Delete": function()
                    {
                        _item.closest('tr').remove();

                        //  Close dialog
                        $(this).dialog("close");

                        //  Pop up a success notice
                        $('<div>').text('The item was removed from the interface and will be deleted when you save changes.').dialog({
                            title: 'Remember to Save Changes!',
                            resizable: false,
                            draggable: false,
                            modal: true,
                            buttons: {
                                Ok: function() {
                                    $(this).dialog("close");
                                }
                            }
                        });
                    },
                    Cancel: function() {
                        $(this).dialog("close");
                    }
                }
            });

            return false;
        });
    };

    // --------------------------------------------------------------------------

    this.initAdds = function()
    {
        var _this = this;

        $('#add-new-shipping').on('click', function()
        {
            var _counter = $('#existing-shipping-methods tbody tr').length + 1;
            var _template = Mustache.render($('#template-new-shipping').html(), {counter:_counter});

            $('#existing-shipping-methods tbody').append(_template);
            $('#existing-shipping-methods tbody').sortable('refresh');
            $('td.tax_rate select.select2').select2();
            _this._set_order_ship();

            return false;
        });

        // --------------------------------------------------------------------------

        $('#add-new-tax-rate').on('click', function()
        {
            var _counter    = $('#existing-tax-rates tbody tr').length + 1;
            var _template   = Mustache.render($('#template-new-tax-rate').html(), {counter:_counter});

            $('#existing-tax-rates tbody').append(_template);

            return false;
        });
    };

    // --------------------------------------------------------------------------

    this.initWarehouseCollection = function()
    {
        $('#shop-settings-warehouse-collection .toggle').on('toggle', function(e, active)
        {
            if (active)
            {
                $('#warehouse-collection-address').show();
                _nails.addStripes();
            }
            else
            {
                $('#warehouse-collection-address').hide();
            }
        });
    };

    // --------------------------------------------------------------------------

    this._set_order_ship = function()
    {
        var _order_counter = 0;

        $('#existing-shipping-methods .order-handle input.order').each(function()
        {
            $(this).val(_order_counter);
            _order_counter++;
        });
    };

    // --------------------------------------------------------------------------

    this.initSubmits = function()
    {
        //  Shipping Methods
        $('#form-shipping-methods').on('submit', function()
        {
            var _errors = 0;
            var _value;

            $(this).find('tbody tr').each(function()
            {
                //  Courier
                _value = $(this).find('td.courier input').val();
                if (! _value.length)
                {
                    $(this).find('td.courier').addClass('error');
                    _errors++;
                }
                else
                {
                    $(this).find('td.courier').removeClass('error');
                }

                // --------------------------------------------------------------------------

                //  Method
                _value = $(this).find('td.method input').val();
                if (! _value.length)
                {
                    $(this).find('td.method').addClass('error');
                    _errors++;
                }
                else
                {
                    $(this).find('td.method').removeClass('error');
                }

                // --------------------------------------------------------------------------

                //  Price
                _value = parseInt($(this).find('td.default_price input').val(), 10);
                if (_value < 0)
                {
                    $(this).find('td.default_price').addClass('error');
                    _errors++;
                }
                else
                {
                    $(this).find('td.default_price').removeClass('error');
                }

                // --------------------------------------------------------------------------

                //  +1 Price
                _value = parseInt($(this).find('td.default_price_additional input').val(), 10);
                if (_value < 0)
                {
                    $(this).find('td.default_price_additional').addClass('error');
                    _errors++;
                }
                else
                {
                    $(this).find('td.default_price_additional').removeClass('error');
                }
            });

            if (_errors)
            {
                alert('Please check highlighted fields for errors:\n\n- A courier must be provided\n- A method must be provided\n- Price and 1+ Price must be positive');
                return false;
            }

            return true;
        });

        // --------------------------------------------------------------------------

        //  Tax Rates
        $('#form-tax-rates').on('submit', function()
        {
            var _errors = 0;
            var _value;

            $(this).find('tbody tr').each(function()
            {
                //  Label
                _value = $(this).find('td.label input').val();
                if (! _value.length)
                {
                    $(this).find('td.label').addClass('error');
                    _errors++;
                }
                else
                {
                    $(this).find('td.label').removeClass('error');
                }

                // --------------------------------------------------------------------------

                //  Rate
                _value = parseInt($(this).find('td.rate input').val(), 10);
                if (isNaN(_value) || _value > 1 || _value < 0)
                {
                    $(this).find('td.rate').addClass('error');
                    _errors++;
                }
                else
                {
                    $(this).find('td.rate').removeClass('error');
                }
            });

            if (_errors)
            {
                alert('Please check highlighted fields for errors:\n\n- A label must be provided\n- Rate must be a numeric in the range 0-1');
                return false;
            }

            return true;
        });
    };

    // --------------------------------------------------------------------------

    this.initPaymentGateways = function()
    {
        $('#payment-gateways .enabled').each(function()
        {
            var _toggle     = $(this).find('.toggle');
            var _checkbox   = $(this).find('input[type=checkbox]');

            _toggle.css({
                width:  '100px',
                height: '30px'
            });

            _toggle.toggles({
                checkbox : _checkbox,
                on : _checkbox.is(':checked'),
            });

        });
    };


    // --------------------------------------------------------------------------


    return this.__construct();
};