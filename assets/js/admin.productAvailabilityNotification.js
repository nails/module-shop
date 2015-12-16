var NAILS_Admin_Shop_Product_Availability_Notification_Browse;
NAILS_Admin_Shop_Product_Availability_Notification_Browse = function()
{
	this.__construct = function()
	{
		var _this = this;	/*	Ugly Scope Hack	*/
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
		var _action = $( '#batch-action select' ).val();

		var _body,_title;

		switch( _action )
		{
			case 'delete' :

				_title = 'Coming Soon!';
				_body = 'Deleting multiple notifications is in the pipeline and will be available soon.';

			break;
		}

		if ( _title && _body )
		{
			$('<div>').html(_body).dialog({
				title: _title,
				resizable: false,
				draggable: false,
				modal: true,
				dialogClass: "no-close",
				buttons:
				{
					OK: function()
					{
						$(this).dialog("close");
					}
				}
			});
		}
	};


	// --------------------------------------------------------------------------


	return this.__construct();
};