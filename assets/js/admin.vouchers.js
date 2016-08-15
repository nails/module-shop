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
            var $button = $(this);
            var client = new ZeroClipboard($button.get(0));
            client.on('ready', function() {
                client.on('aftercopy', function() {
                    $button
                        .removeClass('btn-info')
                        .addClass('success btn-success');
                    setTimeout(function() {
                        $button
                            .removeClass('success btn-success')
                            .addClass('btn-info');
                    }, 1500);
                });
            });
            client.on('error', function() {
                $button.hide();
            });
        });
    };

    // --------------------------------------------------------------------------

    return base.__construct();
};
