<div class="group-shop manage collections edit">
	<?php

		if ( $is_fancybox ) :

			echo '<h1>' . $page->title . '</h1>';
			$_class = 'system-alert';

		else :

			$_class = '';

		endif;

		echo form_open( uri_string() . $is_fancybox );

	?>
	<p class="<?=$_class?>">
		Manage which collections are available for your products. Products grouped together into a collection are deemed related and can have their own customised landing page.
	</p>
	<?=$is_fancybox ? '' : '<hr />'?>
	<ul class="tabs disabled">
		<li class="tab">
			<?=anchor( 'admin/shop/manage/collection' . $is_fancybox, 'Overview', 'class="confirm" data-title="Are you sure?" data-body="Any unsaved changes will be lost."' )?>
		</li>
		<li class="tab active">
			<?=anchor( 'admin/shop/manage/collection/create' . $is_fancybox, 'Create Collection' )?>
		</li>
	</ul>
	<section class="tabs pages">
		<div class="tab page active">
			<fieldset>
				<legend>Basic Details</legend>
				<p>
					These fields describe the collection.
				</p>
				<?php

					// --------------------------------------------------------------------------

					$_field					= array();
					$_field['key']			= 'label';
					$_field['label']		= 'Label';
					$_field['required']		= TRUE;
					$_field['default']		= isset( $collection->label ) ? $collection->label : '';
					$_field['placeholder']	= 'The label to give your collection';

					echo form_field( $_field );

					// --------------------------------------------------------------------------

					$_field					= array();
					$_field['key']			= 'cover_id';
					$_field['label']		= 'Cover Image';
					$_field['default']		= isset( $collection->cover_id ) ? $collection->cover_id : '';
					$_field['bucket']		= 'shop-collection-cover';

					echo form_field_mm_image( $_field );

					// --------------------------------------------------------------------------

					$_field					= array();
					$_field['key']			= 'description';
					$_field['label']		= 'Description';
					$_field['placeholder']	= 'This text may be used on the collection\'s overview page.';
					$_field['default']		= isset( $collection->description ) ? $collection->description : '';

					echo form_field_wysiwyg( $_field );

					// --------------------------------------------------------------------------

					$_field					= array();
					$_field['key']			= 'is_active';
					$_field['label']		= 'Active';
					$_field['default']		= isset( $collection->is_active ) ? $collection->is_active : TRUE;

					echo form_field_boolean( $_field );

				?>
			</fieldset>
			<fieldset>
				<legend>Search Engine Optimisation</legend>
				<p>
					These fields help describe the collection to search engines. These fields won't be seen publicly.
				</p>
				<?php

					$_field					= array();
					$_field['key']			= 'seo_title';
					$_field['label']		= 'SEO Title';
					$_field['sub_label']	= 'Max. 150 characters';
					$_field['placeholder']	= 'An alternative, SEO specific title for the collection.';
					$_field['default']		= isset( $collection->seo_title ) ? $collection->seo_title : '';

					echo form_field( $_field );

					// --------------------------------------------------------------------------

					$_field					= array();
					$_field['key']			= 'seo_description';
					$_field['label']		= 'SEO Description';
					$_field['sub_label']	= 'Max. 300 characters';
					$_field['type']			= 'textarea';
					$_field['placeholder']	= 'This text will be read by search engines when they\'re indexing the page. Keep this short and concise.';
					$_field['default']		= isset( $collection->seo_description ) ? $collection->seo_description : '';

					echo form_field( $_field );

					// --------------------------------------------------------------------------

					$_field					= array();
					$_field['key']			= 'seo_keywords';
					$_field['label']		= 'SEO Keywords';
					$_field['sub_label']	= 'Max. 150 characters';
					$_field['placeholder']	= 'These comma separated keywords help search engines understand the context of the page; stick to 5-10 words.';
					$_field['default']		= isset( $collection->seo_keywords ) ? $collection->seo_keywords : '';

					echo form_field( $_field );

				?>
			</fieldset>
			<p style="margin-top:1em;">
				<?=form_submit( 'submit', 'Save', 'class="awesome"' )?>
				<?=anchor( 'admin/shop/manage/collection' . $is_fancybox, 'Cancel', 'class="awesome red confirm" data-title="Are you sure?" data-body="All unsaved changes will be lost."' )?>
			</p>
		</div>
	</section>
	<?=form_close();?>
</div>
<?php

	$this->load->view( 'admin/shop/manage/collection/_footer' );