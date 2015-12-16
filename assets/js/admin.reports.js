/* globals _nails  */
var NAILS_Admin_Shop_Reports;
NAILS_Admin_Shop_Reports = function()
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
        $('#report-source').on('change', function() {
            base.onReportChange();
        });

        base.onReportChange();
    };

    // --------------------------------------------------------------------------

    base.onReportChange = function()
    {
        var val = $('#report-source').val().split(':');

        if (val[1] === "0") {

            $('#report-period').closest('div.field').hide();

        } else {

            $('#report-period').closest('div.field').show();
        }

        _nails.addStripes();
    };

    // --------------------------------------------------------------------------

    return base.__construct();
};