<div class="group-shop product-availability-notifications browse">
	<p>
		The following people have requested notification when items are back in stock.
		<?php

			if ( userHasPermission( 'admin.shop:0.manage_create' ) ) :

				echo anchor( 'admin/shop/availability/create', 'Add New Notification', 'class="awesome small green right"' );

			endif;

		?>
	</p>
	<hr />
	<div class="table-responsive">
		<table>
			<thead>
				<tr>
					<th class="checkbox">
						<input type="checkbox" id="toggle-all" />
					</th>
					<th class="user">User</th>
					<th class="product">Product</th>
					<th class="created">Created</th>
					<th class="actions text-center">Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php

					if ( ! empty( $notifications ) ) :

						foreach ( $notifications as $item ) :

							?>
							<tr id="notification-<?=$item->id?>">
								<td class="checkbox">
									<input type="checkbox" class="batch-checkbox" value="<?=$item->id?>" />
								</td>

								<?php

									echo \Nails\Admin\Helper::loadUserCell($item->user);

								?>
								<td class="product">
								<?php

									echo '<strong>' . anchor( 'admin/shop/inventory/edit/' . $item->product->id, $item->product->label ) . '</strong>';

									if ( $item->product->label != $item->variation->label ) :

										echo '<br />' . $item->variation->label;

									endif;

								?>
								</td>
								<?php

									echo \Nails\Admin\Helper::loadDatetimeCell($item->created);

								?>
								<td class="actions text-center">
									<?php

										//	Render buttons
										$_buttons = array();

										if ( userHasPermission( 'admin.shop:0.notifications_edit' ) ) :

											$_buttons[] = anchor( 'admin/shop/availability/edit/' . $item->id, lang( 'action_edit' ), 'class="awesome small"' );

										endif;

										// --------------------------------------------------------------------------

										if ( userHasPermission( 'admin.shop:0.notifications_delete' ) ) :

											$_buttons[] = anchor( 'admin/shop/availability/delete/' . $item->id, lang( 'action_delete' ), 'class="awesome small red confirm" data-title="Are you sure?" data-body="This action cannot be undone."' );

										endif;

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
							<td colspan="5" class="no-data">
								<p>No Product Availability Notifications Found</p>
							</td>
						</tr>
						<?php
					endif;

				?>
			</tbody>
		</table>
		<?php

			if ( ! empty( $notifications ) ) :

				$_options			= array();
				$_options['']		= 'Choose';
				$_options['delete']	= 'Delete';

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
</div>