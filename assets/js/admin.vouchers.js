/* globals ZeroClipboard */
var NAILS_Admin_Shop_Vouchers;
NAILS_Admin_Shop_Vouchers = function()
{
    /**
     * Avoid scope issues in callbacks and anonymous functions by referring to `this` as `base`
     * @type {Object}
     */
    var base = this;

    // --------------------------------------------------------------------------

    /**
     * Constructs the class and binds to all the things
     * @return {void}
     */
    base.__construct = function() {
        base.initCopyButtons();
    };

    // --------------------------------------------------------------------------

    base.initCopyButtons = function() {
        $('button.copy-code').each(function() {
            var client = new ZeroClipboard(this);
            client.on('ready', function() {
                client.on('aftercopy', function(event) {
                    $(event.target).removeClass('btn-info').addClass('success btn-success');
                    setTimeout(function() {
                        $(event.target).removeClass('success btn-success').addClass('btn-info');
                    }, 1500);
                });
            });
        });
    };

    // --------------------------------------------------------------------------

    return base.__construct();
};