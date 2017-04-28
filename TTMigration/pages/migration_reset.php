<?php
namespace TTMigration;

access_ensure_global_level( config_get_global( 'admin_site_threshold' ) );
form_security_validate( 'ttmigration_migration_reset' );

helper_ensure_confirmed( 'This will delete all intermediate data for current migration job', 'OK' );

step_reset( 1 );
delete_key_data( 'STATUS' );

form_security_purge( 'ttmigration_migration_reset' );
print_successful_redirect( plugin_page( 'migration_overview_page', true ) );