<div class="group-shop manage attributes overview">
	<?php

		if ( $is_fancybox ) :

			echo '<h1>' . $page->title . '</h1>';
			$_class = 'system-alert';

		else :

			$_class = '';

		endif;

	?>
	<p class="<?=$_class?>">
		Manage which attributes are available for your products.
	</p>
	<?=$is_fancybox ? '' : '<hr />'?>
	<ul class="tabs disabled">
		<li class="tab active">
			<?=anchor( 'admin/shop/manage/attribute' . $is_fancybox, 'Overview' )?>
		</li>
		<li class="tab">
			<?=anchor( 'admin/shop/manage/attribute/create' . $is_fancybox, 'Create Attribute' )?>
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

						if ( $attributes ) :

							foreach ( $attributes as $attribute ) :

								echo '<tr>';
									echo '<td class="label">';
										echo $attribute->label;
										echo $attribute->description ? '<small>' . character_limiter( strip_tags( $attribute->description ), 225 ) . '</small>' : '<small>No Description</small>';
									echo '</td>';
									echo '<td class="count">';
										echo ! isset( $attribute->product_count ) ? 'Unknown' : $attribute->product_count;
									echo '</td>';
									echo $this->load->view( '_utilities/table-cell-datetime', array( 'datetime' => $attribute->modified ), TRUE );
									echo '<td class="actions">';

										if ( user_has_permission( 'admin.shop:0.attribute_edit' ) ) :

											echo anchor( 'admin/shop/manage/attribute/edit/' . $attribute->id . $is_fancybox, lang( 'action_edit' ), 'class="awesome small"' );

										endif;

										if ( user_has_permission( 'admin.shop:0.attribute_delete' ) ) :

											echo anchor( 'admin/shop/manage/attribute/delete/' . $attribute->id . $is_fancybox, lang( 'action_delete' ), 'class="awesome small red confirm" data-title="Are you sure?" data-body="This action cannot be undone."' );

										endif;

									echo '</td>';
								echo '</tr>';

							endforeach;

						else :

							echo '<tr>';
								echo '<td colspan="4" class="no-data">';
									echo 'No Attributes, add one!';
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

	$this->load->view( 'admin/shop/manage/attribute/_footer' );