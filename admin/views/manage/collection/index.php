<div class="group-shop manage collections overview">
	<?php

		if ( $isFancybox ) :

			echo '<h1>' . $page->title . '</h1>';
			$_class = 'system-alert';

		else :

			$_class = '';

		endif;

	?>
	<p class="<?=$_class?>">
		Manage which collections are available for your products. Products grouped together into a collection are deemed related and can have their own customised landing page.
	</p>
	<?=$isFancybox ? '' : '<hr />'?>
	<ul class="tabs disabled">
		<li class="tab active">
			<?=anchor( 'admin/shop/manage/collection' . $isFancybox, 'Overview' )?>
		</li>
		<li class="tab">
			<?=anchor( 'admin/shop/manage/collection/create' . $isFancybox, 'Create Collection' )?>
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

						if ( $collections ) :

							foreach ( $collections as $collection ) :

								echo '<tr>';
									echo '<td class="label">';

									echo $collection->label;
									echo $collection->description ? '<small>' . character_limiter( strip_tags( $collection->description ), 225 ) . '</small>' : '<small>No Description</small>';

									echo '</td>';
									echo '<td class="count">';
										echo ! isset( $collection->product_count ) ? 'Unknown' : $collection->product_count;
									echo '</td>';

									echo \Nails\Admin\Helper::loadDatetimeCell($collection->modified);
									echo \Nails\Admin\Helper::loadBoolCell($collection->is_active);

									echo '<td class="actions">';

										if ( userHasPermission( 'admin.shop:0.collection_edit' ) ) :

											echo anchor( 'admin/shop/manage/collection/edit/' . $collection->id . $isFancybox, lang( 'action_edit' ), 'class="awesome small"' );

										endif;

										if ( userHasPermission( 'admin.shop:0.collection_delete' ) ) :

											echo anchor( 'admin/shop/manage/collection/delete/' . $collection->id . $isFancybox, lang( 'action_delete' ), 'class="awesome small red confirm" data-title="Are you sure?" data-body="This action cannot be undone."' );

										endif;

										echo anchor( $shop_url . 'collection/' . $collection->slug, lang( 'action_view' ), 'class="awesome small orange" target="_blank"' );

									echo '</td>';
								echo '</tr>';

							endforeach;

						else :

							echo '<tr>';
								echo '<td colspan="4" class="no-data">';
									echo 'No Collections, add one!';
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

	$this->load->view( 'admin/shop/manage/collection/_footer' );