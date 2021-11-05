<?php

/**
 * The SAML_ENV and SAML_DOMAIN environment variables are passed by the webserver or the cronjob
 * that's running the task. This allows flexible selection of what config files to be loaded
 *
 * We usually store the default-config.php in the same directory as site-specific config files. 
 */

$MVEnvironment = getenv( 'SAML_ENV' );
$MVConfigBaseDirectory = '/path/to/config';
$MVConfigDirectoryName = getenv( 'SAML_DOMAIN' );

$MVConfigDefaultFilePath = $MVConfigBaseDirectory . 'default-config.php';
$MVConfigDirectory = $MVConfigBaseDirectory . $MVConfigDirectoryName;

$multiVersionInstance = WikiXL\MultiVersion\MultiVersion::factory( $MVConfigDefaultFilePath, $MVConfigDirectory );
