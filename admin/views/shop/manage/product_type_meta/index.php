<div class="group-shop manage product-type-meta overview">
	<?php

		if ( $is_fancybox ) :

			echo '<h1>' . $page->title . '</h1>';
			$_class = 'system-alert';

		else :

			$_class = '';

		endif;

	?>
	<p class="<?=$_class?>">
		Product Type Meta fields allow the shop to store additional information for variants. The store
		also uses this data to provide a user friendly filtering system which responds to the products
		available in the current view.
	</p>
	<?=$is_fancybox ? '' : '<hr />'?>
	<ul class="tabs disabled">
		<li class="tab active">
			<?=anchor( 'admin/shop/manage/product_type_meta' . $is_fancybox, 'Overview' )?>
		</li>
		<li class="tab">
			<?=anchor( 'admin/shop/manage/product_type_meta/create' . $is_fancybox, 'Create Product Type Meta Field' )?>
		</li>
	</ul>
	<section class="tabs pages">
		<div class="tab page active">
			<div class="table-responsive">
				<table>
					<thead>
						<tr>
							<th class="label">Label</th>
							<th class="label">Associated Product Types</th>
							<th class="modified">Modified</th>
							<th class="actions">Actions</th>
						</tr>
					</thead>
					<tbody>
					<?php

						if ( $meta_fields ) :

							foreach ( $meta_fields as $field ) :

								echo '<tr>';
									echo '<td class="label">';
										echo $field->label;
									echo '</td>';
									echo '<td class="associated">';
										foreach ( $field->associated_product_types as $association ) :

											echo '<span class="badge">' . anchor( 'admin/shop/manage/product_type/edit/' . $association->id, $association->label ) . '</span>';

										endforeach;
									echo '</td>';
									echo $this->load->view( '_utilities/table-cell-datetime', array( 'datetime' => $field->modified ), TRUE );
									echo '<td class="actions">';

										if ( user_has_permission( 'admin.shop:0.product_type_meta_edit' ) ) :

											echo anchor( 'admin/shop/manage/product_type_meta/edit/' . $field->id . $is_fancybox, lang( 'action_edit' ), 'class="awesome small"' );

										endif;

										if ( user_has_permission( 'admin.shop:0.product_type_meta_delete' ) ) :

											echo anchor( 'admin/shop/manage/product_type_meta/delete/' . $field->id . $is_fancybox, lang( 'action_delete' ), 'class="awesome small red confirm" data-title="Are you sure?" data-body="This action cannot be undone."' );

										endif;

									echo '</td>';
								echo '</tr>';

							endforeach;

						else :

							echo '<tr>';
								echo '<td colspan="8" class="no-data">';
									echo 'No Meta Fields, add one!';
								echo '</td>';
							echo '</tr>';

						endif;

					?>
					</tbody>
				</table>
			</div>
		</div>
	</section>
</div>