<div class="group-shop orders single">
	<div class="col-3-container">
		<div class="col-3">
			<fieldset>
				<legend>Order Details</legend>
				<div class="table-responsive">
					<table>
						<tbody>
							<tr>
								<td class="label">ID</td>
								<td class="value"><?=$order->id?></td>
							</tr>
							<tr>
								<td class="label">Ref</td>
								<td class="value"><?=$order->ref?></td>
							</tr>
							<tr>
								<td class="label">Created</td>
								<?php echo \Nails\Admin\Helper::loadDatetimeCell($order->created); ?>
							</tr>
							<tr>
								<td class="label">Modified</td>
								<?php echo \Nails\Admin\Helper::loadDatetimeCell($order->modified); ?>
							</tr>
							<tr>
								<td class="label">Voucher</td>
								<td class="value">
								<?php

									if ( empty( $order->voucher ) ) :

										echo '<span class="text-muted">No Voucher Used</span>';

									else :

										//	TODO: Show voucher details
										echo 'TODO: voucher display';

									endif;

								?>
								</td>
							</tr>
							<tr>
								<td class="label">Total</td>
								<td class="value">
								<?php

									echo $order->totals->base_formatted->grand;

									if ( $order->currency != $order->base_currency ) :

										echo '<small>';
											echo 'User checked out in ' . $order->currency . ': ' . $order->totals->user_formatted->grand;
										echo '</small>';

									endif;

								?>
								</td>
							</tr>
							<tr>
								<td class="label">Note</td>
								<td class="value">
								<?php

									if (empty($order->note)) {

										echo '<span class="text-muted">No Note Specified</span>';

									} else {

										//	TODO: Show voucher details
										echo $order->note;

									}

								?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</fieldset>
		</div>
		<div class="col-3">
			<fieldset>
				<legend>Customer Details</legend>
				<div class="table-responsive">
					<table>
						<tbody>
							<tr>
								<td class="label">Name &amp; Email</td>
								<?php echo \Nails\Admin\Helper::loadUserCell($order->user); ?>
							</tr>
							<tr>
								<td class="label">Telephone</td>
								<td class="value">
								<?php

									echo $order->user->telephone ? tel($order->user->telephone) : '<span class="text-muted">Not supplied</span>';

								?>
								</td>
							</tr>
							<tr>
								<td class="label">Billing Address</td>
								<td class="value">
								<?php

									$_address = array_filter( (array) $order->billing_address );
									echo implode( '<br />', $_address );
									echo '<small>' . anchor( 'https://www.google.com/maps/?q=' . urlencode( implode( ', ', $_address ) ), '<b class="fa fa-map-marker"></b> Map', 'target="_blank"' ) . '</small>';

								?>
								</td>
							</tr>
							<tr>
								<td class="label">Shipping Address</td>
								<td class="value">
								<?php

									$_address = array_filter( (array) $order->shipping_address );
									echo implode( '<br />', $_address );
									echo '<small>' . anchor( 'https://www.google.com/maps/?q=' . urlencode( implode( ', ', $_address ) ), '<b class="fa fa-map-marker"></b> Map', 'target="_blank"' ) . '</small>';

								?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</fieldset>
		</div>
		<div class="col-3">
			<fieldset>
				<legend>Order Status</legend>
				<div class="order-status-container">
					<div class="order-status <?=strtolower( $order->status )?>">
					<?php

						switch ( $order->status ) :

							case 'UNPAID' :

								echo '<h1><b class="fa fa-exclamation-triangle"></b> Unpaid</h1>';
								echo '<p>The user was sent to the payment gateway but has not yet completed payment.</p>';

								if ( time() - strtotime( $order->created ) > 3600 ) :

									echo '<p><small>This order looks to be quite old now, it\'s likely the user abandoned checkout.</small></p>';

								endif;

							break;

							case 'PAID' :

								echo '<h1><b class="fa fa-check-circle-o"></b> Paid</h1>';
								echo '<p>This order has been fully paid.</p>';

							break;

							case 'ABANDONED' :

								echo '<h1><b class="fa fa-times-circle"></b> Abandoned</h1>';
								echo '<p>This order was abandoned by the user prior to reaching checkout.</p>';

							break;

							case 'CANCELLED' :

								echo '<h1><b class="fa fa-times-circle"></b> Cancelled</h1>';
								echo '<p>The user cancelled this order during checkout.</p>';

							break;

							case 'FAILED' :

								echo '<h1><b class="fa fa-exclamation-triangle"></b> Failed</h1>';
								echo '<p>There was an issue processing this order. Failure details of the failure mayhave been provided from the payment gateway, if so these will be shown below.</p>';

								//	TODO: Show failure details

							break;

							case 'PENDING' :

								echo '<h1><b class="fa fa-clock-o fa-spin"></b> Pending</h1>';
								echo '<p>This order is pending action, details and further options may be shown below.</p>';

								//	TODO: Show pending reasons/actions

							break;

							default :

								$_status = ucwords( strtolower( $order->status ) );
								echo '<h1>' . $_status . '</h1>';
								echo '<p>"' . $_status . '" is not an order status I understand, there may be a problem.</p>';

							break;

						endswitch;

					?>
					</div>
				</div>
				<div class="order-status-container">
					<div class="order-status <?=strtolower($order->fulfilment_status)?>">
					<?php

						$verbFulfilled   = $order->delivery_type == 'DELIVER' ? 'fulfilled' : 'collected';
						$verbUnfulfilled = $order->delivery_type == 'DELIVER' ? 'unfulfilled' : 'uncollected';

						switch ( $order->fulfilment_status ) {

							case 'FULFILLED':

								$buttonUrl   = 'admin/shop/orders/unfulfil/' . $order->id;
								$buttonLabel = 'Mark ' . ucfirst($verbUnfulfilled);

								echo '<h1><b class="fa fa-truck"></b> ' . strtoupper($verbFulfilled) . '</h1>';
								echo '<p>';
									echo 'This order has been ' . $verbFulfilled . ', no further action is nessecary.';
								echo '</p>';
								echo '<p>';
									echo anchor($buttonUrl, $buttonLabel, 'class="awesome red"');
								echo '</p>';
								break;

							case 'UNFULFILLED':

								$buttonUrl   = 'admin/shop/orders/fulfil/' . $order->id;
								$buttonLabel = 'Mark ' . ucfirst($verbFulfilled);

								echo '<h1>' . strtoupper($verbUnfulfilled) . '</h1>';
								echo '<p>';
									echo 'This order has <strong>not</strong> been ' . $verbFulfilled . '.';
								echo '</p>';
								echo '<p>';
									echo anchor($buttonUrl, $buttonLabel, 'class="awesome green"');
								echo '</p>';
								break;

							default:

								$_status = ucwords( strtolower( $order->fulfilment_status ) );
								echo '<h1>' . $_status . '</h1>';
								echo '<p>';
								echo '"' . $_status . '" is not a fulfilment status I understand, there may be a problem.';
								echo '</p>';
								break;
						}

					?>
					</div>
				</div>
			</fieldset>
		</div>
	</div>
	<fieldset class="notcollapsable">
		<legend>Products</legend>
		<div class="table-responsive">
			<table>
				<thead>
					<tr>
						<th>Item</th>
						<th>Type</th>
						<th class="text-center">Quantity</th>
						<th class="text-center">SKU</th>
						<th class="text-center">Unit Cost</th>
						<th class="text-center">Tax</th>
						<th class="text-center">Total</th>
						<th class="text-center">Refunded</th>
						<th class="text-center">Actions</th>
					</tr>
				</thead>
				<tbody>
				<?php

					if ( ! empty( $order->items ) ) :

						foreach ( $order->items as $item ) :

							echo '<tr>';
								echo '<td>';
									echo $item->product_label;
									echo $item->variant_label != $item->product_label ? '<br /><small>' . $item->variant_label . '</small>' : '';
								echo '</td>';
								echo '<td>';
									echo $item->type->label;
								echo '</td>';
								echo '<td class="text-center">';
									echo $item->quantity;
								echo '</td>';
								echo '<td class="text-center">';
									echo $item->sku;
								echo '</td>';
								echo '<td class="text-center">';
									echo $item->price->base_formatted->value_ex_tax;
								echo '</td>';
								echo '<td class="text-center">';
									echo $item->price->base_formatted->value_tax;
								echo '</td>';
								echo '<td class="text-center">';
									echo $item->price->base_formatted->value;
								echo '</td>';
								echo '<td class="text-center">';

									if ( $item->refunded ) :

										echo 'Refunded ' . $item->refunded_date;

									else :

										echo 'No';

									endif;
								echo '</td>';
								echo '<td class="text-center">';
									echo anchor( '', 'Refund', 'class="awesome small todo"' );
								echo '</td>';
							echo '</tr>';

						endforeach;

					else :

						echo '<tr>';
							echo '<td colspan="9" class="no-data">No Items</td>';
						echo '</tr>';

					endif;

				?>
				</tbody>
			</table>
		</div>
	</fieldset>
	<fieldset class="notcollapsable">
		<legend>Payments</legend>
		<div class="table-responsive">
			<table>
				<thead>
					<tr>
						<th>Payment Gateway</th>
						<th>Transaction ID</th>
						<th>Amount</th>
						<th>Created</th>
					</tr>
				</thead>
				<tbody>
				<?php

					if ( ! empty( $payments ) ) :

						foreach ( $payments as $payment ) :

							echo '<tr>';
								echo '<td>';
									echo $payment->payment_gateway;
								echo '</td>';
								echo '<td>';
									echo $payment->transaction_id;
								echo '</td>';
								echo '<td>';
									echo $this->shop_currency_model->format_base( $payment->amount_base );
								echo '</td>';

								echo \Nails\Admin\Helper::loadDatetimeCell($payment->created);

							echo '</tr>';

						endforeach;

					else :

						echo '<tr>';
							echo '<td colspan="4" class="no-data">No Payments</td>';
						echo '</tr>';

					endif;

				?>
				</tbody>
			</table>
		</div>
	</fieldset>
</div>