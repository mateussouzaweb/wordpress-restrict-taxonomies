<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

delete_option( 'restrict_taxonomies_options' );
delete_option( 'restrict_taxonomies_user_options' );