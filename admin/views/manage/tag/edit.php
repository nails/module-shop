<div class="group-shop manage tags edit">
	<?php

		if ( $isFancybox ) :

			echo '<h1>' . $page->title . '</h1>';
			$_class = 'system-alert';

		else :

			$_class = '';

		endif;

		echo form_open( uri_string() . $isFancybox );

	?>
	<p class="<?=$_class?>">
		Manage which tags are available for your products. Products grouped together into a tag are deemed related and can have their own customised landing page.
	</p>
	<?=$isFancybox ? '' : '<hr />'?>
	<ul class="tabs disabled">
		<li class="tab">
			<?=anchor( 'admin/shop/manage/tag' . $isFancybox, 'Overview', 'class="confirm" data-title="Are you sure?" data-body="Any unsaved changes will be lost."' )?>
		</li>
		<li class="tab active">
			<?=anchor( 'admin/shop/manage/tag/create' . $isFancybox, 'Create Tag' )?>
		</li>
	</ul>
	<section class="tabs pages">
		<div class="tab page active">
			<fieldset>
				<legend>Basic Details</legend>
				<p>
					These fields describe the tag.
				</p>
				<?php

					// --------------------------------------------------------------------------

					$_field					= array();
					$_field['key']			= 'label';
					$_field['label']		= 'Label';
					$_field['required']		= TRUE;
					$_field['default']		= isset( $tag->label ) ? $tag->label : '';
					$_field['placeholder']	= 'The label to give your tag';

					echo form_field( $_field );

					// --------------------------------------------------------------------------

					$_field					= array();
					$_field['key']			= 'cover_id';
					$_field['label']		= 'Cover Image';
					$_field['default']		= isset( $tag->cover_id ) ? $tag->cover_id : '';
					$_field['bucket']		= 'shop-tag-cover';

					echo form_field_mm_image( $_field );

					// --------------------------------------------------------------------------

					$_field					= array();
					$_field['key']			= 'description';
					$_field['label']		= 'Description';
					$_field['placeholder']	= 'This text may be used on the tag\'s overview page.';
					$_field['default']		= isset( $tag->description ) ? $tag->description : '';

					echo form_field_wysiwyg( $_field );

				?>
			</fieldset>
			<fieldset>
				<legend>Search Engine Optimisation</legend>
				<p>
					These fields help describe the tag to search engines. These fields won't be seen publicly.
				</p>
				<?php

					$_field					= array();
					$_field['key']			= 'seo_title';
					$_field['label']		= 'SEO Title';
					$_field['sub_label']	= 'Max. 150 characters';
					$_field['placeholder']	= 'An alternative, SEO specific title for the tag.';
					$_field['default']		= isset( $tag->seo_title ) ? $tag->seo_title : '';

					echo form_field( $_field );

					// --------------------------------------------------------------------------

					$_field					= array();
					$_field['key']			= 'seo_description';
					$_field['label']		= 'SEO Description';
					$_field['sub_label']	= 'Max. 300 characters';
					$_field['type']			= 'textarea';
					$_field['placeholder']	= 'This text will be read by search engines when they\'re indexing the page. Keep this short and concise.';
					$_field['default']		= isset( $tag->seo_description ) ? $tag->seo_description : '';

					echo form_field( $_field );

					// --------------------------------------------------------------------------

					$_field					= array();
					$_field['key']			= 'seo_keywords';
					$_field['label']		= 'SEO Keywords';
					$_field['sub_label']	= 'Max. 150 characters';
					$_field['placeholder']	= 'These comma separated keywords help search engines understand the context of the page; stick to 5-10 words.';
					$_field['default']		= isset( $tag->seo_keywords ) ? $tag->seo_keywords : '';

					echo form_field( $_field );

				?>
			</fieldset>
			<p style="margin-top:1em;">
				<?=form_submit( 'submit', 'Save', 'class="awesome"' )?>
				<?=anchor( 'admin/shop/manage/tag' . $isFancybox, 'Cancel', 'class="awesome red confirm" data-title="Are you sure?" data-body="All unsaved changes will be lost."' )?>
			</p>
		</div>
	</section>
	<?=form_close();?>
</div>
<?php

	$this->load->view( 'admin/shop/manage/tag/_footer' );