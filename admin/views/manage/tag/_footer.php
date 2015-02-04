<?php

	//	Build the options, requires an ID and a Label
	$_options = array();

	foreach ( $tags as $tag ) :

		$_temp			= new stdClass();
		$_temp->id		= $tag->id;
		$_temp->label	= $tag->label;

		$_options[] = $_temp;

	endforeach;

	//	Set _DATA
	echo '<script type="text/javascript">';
	echo 'var _DATA = ' . json_encode( $_options ) . ';';
	echo '</script>';