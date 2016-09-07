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
        base.confirmLifecycle();
        base.todo();
    };

    // --------------------------------------------------------------------------

    /**
     * Generates a dialog pop up if the order has collect only
     * items in it or is a collect only order.
     * @return {Void}
     */
    base.alertCollection = function()
    {
        var didSetLifecycle, orderElement, lifecycleId, deliveryType, numCollectItems, lifecyclePaid, lifecycleCollected, subject, message;

        orderElement    = $('#order');
        didSetLifecycle = orderElement.data('did-set-lifecycle');
        console.log(didSetLifecycle);
        if (didSetLifecycle) {
            return;
        }

        lifecycleId     = orderElement.data('lifecycle-id');
        deliveryType    = orderElement.data('delivery-type');
        numCollectItems = orderElement.data('num-collect-items');

        //  Magic numbers correspond to predefined lifecycle IDs
        lifecyclePaid      = 2;
        lifecycleCollected = 4;

        if (lifecycleId > lifecycleCollected || lifecycleId == lifecyclePaid) {

            if (deliveryType === 'COLLECTION') {

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
     * Generates a dialog pop up if the order has collect only
     * items in it or is a collect only order.
     * @return {Void}
     */
    base.confirmLifecycle = function()
    {
        $('a.order-lifecycle__step').on('click', function() {

            var subject = 'Please Confirm';
            var label   = $(this).data('label');
            var href    = $(this).attr('href');
            var message = '<p>Please confirm you would like to set this order to the <strong>' + label + '</strong> stage of its lifecycle.</p>';

            if ($(this).data('will-send-email')) {
                message += '<p>This action <strong>will</strong> send the customer an email.</p>';
            }

            $('<div>').html(message).dialog({
                title: subject,
                resizable: false,
                draggable: false,
                modal: true,
                dialogClass: 'no-close',
                buttons:  {
                    OK: function()  {
                        window.location.href = href;
                        $(this).dialog('close');
                    },
                    Cancel: function()  {
                        $(this).dialog('close');
                    }
                }
            });

            return false;
        });
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
