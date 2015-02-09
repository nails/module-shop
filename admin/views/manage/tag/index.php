<div class="group-shop manage tags overview">
	<?php

		if ( $isFancybox ) :

			echo '<h1>' . $page->title . '</h1>';
			$_class = 'system-alert';

		else :

			$_class = '';

		endif;

	?>
	<p class="<?=$_class?>">
		Manage the which tags are available for your products. Tags help the shop determine related products.
	</p>
	<?=$isFancybox ? '' : '<hr />'?>
	<ul class="tabs disabled">
		<li class="tab active">
			<?=anchor( 'admin/shop/manage/tag' . $isFancybox, 'Overview' )?>
		</li>
		<li class="tab">
			<?=anchor( 'admin/shop/manage/tag/create' . $isFancybox, 'Create Tag' )?>
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
							<th class="actions">Actions</th>
						</tr>
					</thead>
					<tbody>
					<?php

						if ( $tags ) :

							foreach ( $tags as $tag ) :

								echo '<tr>';
									echo '<td class="label">';

									echo $tag->label;
									echo $tag->description ? '<small>' . character_limiter( strip_tags( $tag->description ), 225 ) . '</small>' : '<small>No Description</small>';

									echo '</td>';
									echo '<td class="count">';
										echo ! isset( $tag->product_count ) ? 'Unknown' : $tag->product_count;
									echo '</td>';
									echo \Nails\Admin\Helper::loadDatetimeCell($tag->modified);
									echo '<td class="actions">';

										if ( userHasPermission( 'admin.shop:0.tag_edit' ) ) :

											echo anchor( 'admin/shop/manage/tag/edit/' . $tag->id . $isFancybox, lang( 'action_edit' ), 'class="awesome small"' );

										endif;

										if ( userHasPermission( 'admin.shop:0.tag_delete' ) ) :

											echo anchor( 'admin/shop/manage/tag/delete/' . $tag->id . $isFancybox, lang( 'action_delete' ), 'class="awesome small red confirm" data-title="Are you sure?" data-body="This action cannot be undone."' );

										endif;

										echo anchor( $shop_url . 'tag/' . $tag->slug, lang( 'action_view' ), 'class="awesome small orange" target="_blank"' );

									echo '</td>';
								echo '</tr>';

							endforeach;

						else :

							echo '<tr>';
								echo '<td colspan="4" class="no-data">';
									echo 'No Tags, add one!';
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

	$this->load->view( 'admin/shop/manage/tag/_footer' );