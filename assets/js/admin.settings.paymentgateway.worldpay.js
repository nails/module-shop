var NAILS_Admin_Shop_Settings_PaymentGateway_WorldPay;
NAILS_Admin_Shop_Settings_PaymentGateway_WorldPay = function()
{
	this.__construct = function()
	{
		var _this = this;
		$('#generate-secret').on( 'click', function()
		{
			_this._generate( 'secret' );
			return false;
		});

		$('#generate-password').on( 'click', function()
		{
			_this._generate( 'password' );
			return false;
		});
	};

	// --------------------------------------------------------------------------

	this._generate = function( type )
	{
		var _current = $('#the-' + type).val();

		if ( _current.length )
		{
			var _this = this;
			$('<div>').html('This field already has a value set. Are you sure you wish to generate a new value?').dialog({
				title: 'Field already set',
				resizable: false,
				draggable: false,
				modal: true,
				dialogClass: "no-close",
				buttons:
				{
					No: function()
					{
						$(this).dialog( 'close' );
					},
					Yes: function()
					{
						$(this).dialog( 'close' );
						_this._generate_go( type );
					}
				}
			});
		}
		else
		{
			this._generate_go( type );
		}
	};

	// --------------------------------------------------------------------------

	this._generate_go = function( type )
	{
		//	Generate string
		var _generated = this._generate_string( 25 );

		// --------------------------------------------------------------------------

		var _this,_title,_message;

		_this	  = this;
		_title	  = 'Generate Code';
		_message  = '<p>Use the following value?</p>';
		_message += '<p><input type="text" readonly="readonly" value="' + _generated + '" onClick="this.select();" style="margin:0;width:100%;box-sizing:border-box;" /></p>';
		_message += '<p>I have copied this somewhere safe <input type="checkbox" class="confirm-copy" /></p>';

		$('<div>').html(_message).dialog({
			title: _title,
			resizable: false,
			draggable: false,
			width: 350,
			modal: true,
			dialogClass: "no-close",
			buttons:
			{
				Yes: function()
				{
					if ( ! $(this).find( 'input.confirm-copy' ).is(':checked') )
					{
						$(this).find( 'input.confirm-copy' ).parent().css({ 'color' : 'red', 'font-weight' : 'bold' });
						return;
					}

					$(this).dialog( 'close' );
					$('#the-' + type).val( _generated );
				},
				Regenerate: function()
				{
					$(this).dialog( 'close' );
					_this._generate_go( type );
				},
				Cancel: function()
				{
					$(this).dialog( 'close' );
				}
			}
		});

	};

	// --------------------------------------------------------------------------

	this._generate_string = function( length )
	{
		var result = '';
		var chars  = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@£$%^&*()[]{};"\'';
		for (var i = length; i > 0; --i)
		{
			result += chars[Math.round(Math.random() * (chars.length - 1))];
		}
		return result;
	};

	// --------------------------------------------------------------------------

	return this.__construct();
};