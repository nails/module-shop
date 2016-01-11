var NAILS_Admin_Shop_Order_View;
NAILS_Admin_Shop_Order_View = function()
{
    /**
     * Avoid scope issues in callbacks and anonymous functions by referring to `this` as `base`
     * @type {Object}
     */
    var base = this;

    // --------------------------------------------------------------------------

    /**
     * Construct the class
     * @return {Void}
     */
    base.__construct = function()
    {
        base.alertCollection();
        base.todo();
    };

    // --------------------------------------------------------------------------

    /**
     * Generates a dialog pop up if the order is unfulfilled and has collect only
     * items in it or is a collect only order.
     * @return {Void}
     */
    base.alertCollection = function()
    {
        var orderElement, fulfilmentStatus, deliveryType, numCollectItems, subject, message;

        orderElement     = $('#order');
        fulfilmentStatus = orderElement.data('fulfilment-status');
        deliveryType     = orderElement.data('delivery-type');
        numCollectItems  = orderElement.data('num-collect-items');

        if (fulfilmentStatus === 'UNFULFILLED') {

            if (deliveryType === 'COLLECT') {

                subject = 'Collection Order';
                message = 'The customer has marked that they will come to collect this order. Do not ship.';
                base.dialog(subject, message);

            } else if (deliveryType === 'DELIVER' && numCollectItems > 0) {

                subject = 'Partial Collection Order';
                message = 'This order contains some items which are marked as "collect only". Do not ship these items.';
                base.dialog(subject, message);
            }
        }
    };

    // --------------------------------------------------------------------------

    /**
     * Creates a popup dialog for todo items
     * @return {Void}
     */
    base.todo = function()
    {
        $('.todo').on('click', function()
        {
            base.dialog('Coming Soon!', 'This piece of functionality is in the works and will be available soon.');
            return false;
        });
    };

    // --------------------------------------------------------------------------

    base.dialog = function(subject, message) {

        $('<div>').html(message).dialog({
            title: subject,
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