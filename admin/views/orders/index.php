<div class="group-shop orders browse">
	<p>
		Browse all orders which have been processed by the site from this page.
	</p>
	<?php

		$this->load->view( 'admin/shop/orders/utilities/search' );
		$this->load->view( 'admin/shop/orders/utilities/pagination' );

	?>
	<div class="table-responsive">
		<table>
			<thead>
				<tr>
					<th class="checkbox">
						<input type="checkbox" id="toggle-all" />
					</th>
					<th class="ref">Ref</th>
					<th class="datetime">Placed</th>
					<th class="user">Customer</th>
					<th class="value">Items</th>
					<th class="value">Tax</th>
					<th class="value">Shipping</th>
					<th class="value">Total</th>
					<th class="status">Status</th>
					<th class="fulfilment">Fulfilled</th>
					<th class="actions">Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php

					if ( $orders->data ) :

						foreach ( $orders->data as $order ) :

							?>
							<tr id="order-<?=$order->id?>">
								<td class="checkbox">
									<input type="checkbox" class="batch-checkbox" value="<?=$order->id?>" />
								</td>
								<td class="ref"><?=$order->ref?></td>
								<?php

									echo \Nails\Admin\Helper::loadDatetimeCell($order->created);
									echo \Nails\Admin\Helper::loadUserCell($order->user);

								?>
								<td class="value">
								<?php

									echo $order->totals->base_formatted->item;

									if ( $order->currency !== $order->base_currency ) :

										echo '<small>' . $order->totals->user_formatted->item . '</small>';

									endif;

								?>
								</td>
								<td class="value">
								<?php

									echo $order->totals->base_formatted->tax;

									if ( $order->currency !== $order->base_currency ) :

										echo '<small>' . $order->totals->user_formatted->tax . '</small>';

									endif;

								?>
								</td>
								<td class="value">
								<?php

									echo $order->totals->base_formatted->shipping;

									if ( $order->currency !== $order->base_currency ) :

										echo '<small>' . $order->totals->user_formatted->shipping . '</small>';

									endif;

								?>
								</td>
								<td class="value">
								<?php

									echo $order->totals->base_formatted->grand;

									if ( $order->currency !== $order->base_currency ) :

										echo '<small>' . $order->totals->user_formatted->grand . '</small>';

									endif;

								?>
								</td>
								<?php


									switch ( $order->status ) :

										case 'UNPAID' :		$status = 'error';		break;
										case 'PAID' :		$status = 'success';	break;
										case 'ABANDONED' :	$status = '';			break;
										case 'CANCELLED' :	$status = '';			break;
										case 'FAILED' :		$status = 'error';		break;
										case 'PENDING' :	$status = '';			break;
										default :			$status = '';			break;

									endswitch;

									echo '<td class="status ' . $status . '">';
										echo $order->status;
									echo '</td>';

									$boolValue = $order->fulfilment_status == 'FULFILLED';
									echo \Nails\Admin\Helper::loadBoolCell($boolValue, $order->fulfilled);

								?>
								<td class="actions">
									<?php

										//	Render buttons
										$_buttons = array();

										// --------------------------------------------------------------------------

										if ( userHasPermission( 'admin.shop:0.orders_view' ) ) :

											$_buttons[] = anchor( 'admin/shop/orders/view/' . $order->id, lang( 'action_view' ), 'class="awesome green small"' );
											$_buttons[] = anchor( 'admin/shop/orders/download_invoice/' . $order->id, 'Download', 'class="awesome small"' );

										endif;

										// --------------------------------------------------------------------------

										// if ( userHasPermission( 'admin.shop:0.orders_reprocess' ) ) :

										// 	$_buttons[] = anchor( 'admin/shop/orders/reprocess/' . $order->id, 'Process', 'class="awesome small orange confirm" data-title="Are you sure?" data-body="Processing the order again may result in multiple dispatch of items."' );

										// endif;

										// --------------------------------------------------------------------------

										if ( $_buttons ) :

											foreach ( $_buttons aS $button ) :

												echo $button;

											endforeach;
										else :

											echo '<span class="blank">There are no actions you can perform on this item.</span>';

										endif;

									?>
								</td>
							</tr>
							<?php

						endforeach;

					else :
						?>
						<tr>
							<td colspan="11" class="no-data">
								<p>No Orders found</p>
							</td>
						</tr>
						<?php
					endif;

				?>
			</tbody>
		</table>
		<?php

			if ( $orders->data ) :

				$_options						= array();
				$_options['']					= 'Choose';
				$_options['mark-fulfilled']		= 'Mark Fulfilled';
				$_options['mark-unfulfilled']	= 'Mark Unfulfilled';
				$_options['download']			= 'Download';

				?>
				<div class="panel" id="batch-action">
					With checked:
					<?=form_dropdown( '', $_options, NULL )?>
					<a href="#" class="awesome small">Go</a>
				</div>
				<?php

			endif;

		?>
	</div>
	<?php

		$this->load->view( 'admin/shop/orders/utilities/pagination' );

	?>
</div>

<script	type="text/javascript">

	function mark_fulfilled( order_id )
	{
		$( '#order-' + order_id ).find( 'td.fulfilment' ).removeClass( 'no' ).addClass( 'yes' ).text( '<?=lang( 'yes' )?>' );
	}

	function mark_unfulfilled( order_id )
	{
		$( '#order-' + order_id ).find( 'td.fulfilment' ).removeClass( 'yes' ).addClass( 'no' ).text( '<?=lang( 'no' )?>' );
	}
</script>