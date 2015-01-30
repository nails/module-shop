<div class="group-shop manage brands overview">
	<?php

		if ( $is_fancybox ) :

			echo '<h1>' . $page->title . '</h1>';
			$_class = 'system-alert';

		else :

			$_class = '';

		endif;

	?>
	<p class="<?=$_class?>">
		Manage which brands are available for your products.
	</p>
	<?=$is_fancybox ? '' : '<hr />'?>
	<ul class="tabs disabled">
		<li class="tab active">
			<?=anchor( 'admin/shop/manage/brand' . $is_fancybox, 'Overview' )?>
		</li>
		<li class="tab">
			<?=anchor( 'admin/shop/manage/brand/create' . $is_fancybox, 'Create Brand' )?>
		</li>
	</ul>
	<section class="tabs pages">
		<div class="tab page active">
			<div class="table-responsive">
				<table>
					<thead>
						<tr>
							<th class="label">Label &amp; Description</th>
							<th class="count">Products</th>
							<th class="modified">Modified</th>
							<th class="active">Active</th>
							<th class="actions">Actions</th>
						</tr>
					</thead>
					<tbody>
					<?php

						if ( $brands ) :

							foreach ( $brands as $brand ) :

								echo '<tr>';
									echo '<td class="label">';

										echo '<div class="thumbnail">';
										if ( $brand->logo_id ) :

											echo anchor( cdn_serve( $brand->logo_id), img( cdn_thumb( $brand->logo_id, 32, 32 ) ), 'class="fancybox"' );

										else :

											echo img( NAILS_ASSETS_URL . 'img/admin/modules/shop/manager/no-image.jpg' );

										endif;
										echo '</div>';

										echo '<div class="content">';
											echo $brand->label;
											echo $brand->description ? '<small>' . character_limiter( strip_tags( $brand->description ), 225 ) . '</small>' : '<small>No Description</small>';
										echo '</div>';

									echo '</td>';
									echo '<td class="count">';
										echo ! isset( $brand->product_count ) ? 'Unknown' : $brand->product_count;
									echo '</td>';

									$viewData = array(
										'datetime' => $brand->modified
									);

									$this->load->view('admin/_utilities/table-cell-datetime', $viewData);

									$viewData = array(
										'value' => $brand->is_active
									);

									$this->load->view('admin/_utilities/table-cell-boolean', $viewData);

									echo '<td class="actions">';

										if ( user_has_permission( 'admin.shop:0.brand_edit' ) ) :

											echo anchor( 'admin/shop/manage/brand/edit/' . $brand->id . $is_fancybox, lang( 'action_edit' ), 'class="awesome small"' );

										endif;

										if ( user_has_permission( 'admin.shop:0.brand_delete' ) ) :

											echo anchor( 'admin/shop/manage/brand/delete/' . $brand->id . $is_fancybox, lang( 'action_delete' ), 'class="awesome small red confirm" data-title="Are you sure?" data-body="This action cannot be undone."' );

										endif;

										echo anchor( $shop_url . 'brand/' . $brand->slug, lang( 'action_view' ), 'class="awesome small orange" target="_blank"' );

									echo '</td>';
								echo '</tr>';

							endforeach;

						else :

							echo '<tr>';
								echo '<td colspan="4" class="no-data">';
									echo 'No Brands, add one!';
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

	$this->load->view( 'admin/shop/manage/brand/_footer' );