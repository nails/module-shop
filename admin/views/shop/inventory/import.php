<p class="system-alert message">
	<strong>TODO:</strong> Allow inventory items to be completed on a spreadsheet and imported.
</p>
<div class="group-shop inventory import">
	<p>
		This facility allows multiple shop items to be imported at once. You must use the <?=anchor( 'admin/shop/inventory/import/download', APP_NAME . ' Shop Import Template' )?>.
	</p>
	<hr />
	<?=form_open()?>
		<p>
			Select your completed import spreadsheet below and click Continue to proceed to import verification.
		</p>
		<p class="import-field">
			<?=form_upload( 'dataimport' )?>
		</p>
		<p>
			<?=form_submit( 'submit', lang( 'action_continue' ), 'class="awesome"' )?>
		</p>
	<?=form_close()?>
</div>