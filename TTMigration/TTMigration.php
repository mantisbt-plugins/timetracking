<?php

class TTMigrationPlugin extends MantisPlugin {
	function register() {
		$this->name = 'Time Tracking Migration Tool';
		$this->description = 'Data Migration tool from MantisBT native time tracking to TimeTracking Plugin';
		$this->page = 'migration_overview';

		$this->version = '3.0-dev';
		$this->requires = array(
			'MantisCore' => '2.0',
			'TimeTracking' => '3.0'
		);

		$this->author = 'Carlos Proensa';
		$this->contact = '';
		$this->url = 'https://github.com/mantisbt-plugins/timetracking';
	}
}

