<div class="group-shop inventory browse">
	<p>
		Browse the shop's inventory.
		<?php

			if ( user_has_permission( 'admin.shop:0.inventory_create' ) ) :

				echo anchor( 'admin/shop/inventory/import', 'Import Items', 'class="awesome small orange right"' );
				echo anchor( 'admin/shop/inventory/create', 'Add New Item', 'class="awesome small green right"' );

			endif;

		?>
	</p>
	<?php

		$this->load->view( 'admin/shop/inventory/utilities/search' );
		$this->load->view( 'admin/_utilities/pagination' );

	?>
	<div class="table-responsive">
		<table>
			<thead>
				<tr>
					<th class="id">ID</th>
					<th class="image">Image</th>
					<th class="active">Active</th>
					<th class="label">Label &amp; Description</th>
					<?php

						if ( count( $productTypes ) > 1 ) :

							echo '<th class="type">Type</th>';

						endif;
					?>
					<th class="datetime">Modified</th>
					<th class="actions">Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php

					if ( ! empty( $products ) ) :

						foreach ( $products as $item ) :

							?>
							<tr id="product-<?=$item->id?>">
								<td class="id"><?=number_format( $item->id )?></td>
								<td class="image">
									<?php

										if ( ! empty( $item->gallery[0] ) ) :

											echo anchor( cdn_serve( $item->gallery[0] ), img( cdn_scale( $item->gallery[0], 75, 75 ) ), 'class="fancybox"' );

										else :

											echo img( NAILS_ASSETS_URL . 'img/admin/modules/shop/image-icon.png' );

										endif;

									?>
								</td>
								<?php

									$viewData = array(
										'value' => $item->is_active
									);

									$this->load->view('admin/_utilities/table-cell-boolean', $viewData);

								?>
								<td class="label">
									<?=$item->label?>
									<small><?=word_limiter( strip_tags( $item->description ), 30 )?></small>
								</td>
								<?php

									if ( count( $productTypes ) > 1 ) :

										echo '<td class="type">' . $item->type->label . '</td>';

									endif;
								?>
								<?php

									$this->load->view( 'admin/_utilities/table-cell-datetime',	array( 'datetime' => $item->modified ) );

								?>
								<td class="actions">
									<?php

										//	Render buttons
										$_buttons = array();

										if ( user_has_permission( 'admin.shop:0.inventory_edit' ) ) :

											$_buttons[] = anchor( 'admin/shop/inventory/edit/' . $item->id, lang( 'action_edit' ), 'class="awesome small"' );

										endif;

										// --------------------------------------------------------------------------

										if ( user_has_permission( 'admin.shop:0.inventory_delete' ) ) :

											$_buttons[] = anchor( 'admin/shop/inventory/delete/' . $item->id, lang( 'action_delete' ), 'class="awesome small red confirm" data-title="Are you sure?" data-body="You can undo this action."' );

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
							<td colspan="7" class="no-data">
								<p>No Products Found</p>
							</td>
						</tr>
						<?php
					endif;

				?>
			</tbody>
		</table>
	</div>
	<?php

		$this->load->view( 'admin/_utilities/pagination' );

	?>
</div>