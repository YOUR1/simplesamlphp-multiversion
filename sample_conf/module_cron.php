<?php

/**
 * @global string $MVConfigDefaultFilePath
 * @global string $MVConfigDirectory
 * @global string $MVEnvironment
 * @global \WikiXL\MultiVersion\MultiVersion $multiVersionInstance
 */
include dirname( __FILE__ ) . '/multiversion.php';

$config = $multiVersionInstance->getCronConfig();
