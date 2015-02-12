<div class="group-shop manage tax-rate overview">
	<?php

		if ( $isFancybox ) :

			echo '<h1>' . $page->title . '</h1>';
			$_class = 'system-alert';

		else :

			$_class = '';

		endif;

	?>
	<p class="<?=$_class?>">
		Manage which tax rates the shop supports.
	</p>
	<?=$isFancybox ? '' : '<hr />'?>
	<ul class="tabs disabled">
		<li class="tab active">
			<?=anchor( 'admin/shop/manage/tax_rate' . $isFancybox, 'Overview' )?>
		</li>
		<li class="tab">
			<?=anchor( 'admin/shop/manage/taxRate/create' . $isFancybox, 'Create Tax Rate' )?>
		</li>
	</ul>
	<section class="tabs pages">
		<div class="tab page active">
			<div class="table-responsive">
				<table>
					<thead>
						<tr>
							<th class="label">Label</th>
							<th class="rate">Rate</th>
							<th class="count">Products</th>
							<th class="modified">Modified</th>
							<th class="actions">Actions</th>
						</tr>
					</thead>
					<tbody>
					<?php

						if ( $tax_rates ) :

							foreach ( $tax_rates as $tax_rate ) :

								echo '<tr>';
									echo '<td class="label">';
										echo $tax_rate->label;
									echo '</td>';
									echo '<td class="rate">';
										echo $tax_rate->rate * 100 . '%';
									echo '</td>';
									echo '<td class="count">';
										echo ! isset( $tax_rate->product_count ) ? 'Unknown' : $tax_rate->product_count;
									echo '</td>';
									echo \Nails\Admin\Helper::loadDatetimeCell($tax_rate->modified);
									echo '<td class="actions">';

										if ( userHasPermission( 'admin.shop:0.tax_rate_edit' ) ) :

											echo anchor( 'admin/shop/manage/taxRate/edit/' . $tax_rate->id . $isFancybox, lang( 'action_edit' ), 'class="awesome small"' );

										endif;

										if ( userHasPermission( 'admin.shop:0.tax_rate_delete' ) ) :

											echo anchor( 'admin/shop/manage/taxRate/delete/' . $tax_rate->id . $isFancybox, lang( 'action_delete' ), 'class="awesome small red confirm" data-title="Are you sure?" data-body="This action cannot be undone."' );

										endif;

									echo '</td>';
								echo '</tr>';

							endforeach;

						else :

							echo '<tr>';
								echo '<td colspan="4" class="no-data">';
									echo 'No Tax_rates, add one!';
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
<?php

	$this->load->view( 'admin/shop/manage/taxRate/_footer' );