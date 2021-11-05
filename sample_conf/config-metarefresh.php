<?php

/**
 * @global string $MVConfigDefaultFilePath
 * @global string $MVConfigDirectory
 * @global string $MVEnvironment
 * @global \WikiXL\MultiVersion\MultiVersion $multiVersionInstance
 */
require_once dirname( __FILE__ ) . '/multiversion.php';

$config = $multiVersionInstance->getMetaRefreshConfig( $MVEnvironment );
