<?php
/**
 * Bootstrap for public interface.
 *
 * @version $Id$
 * @copyright Center for History and New Media, 2007-2008
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Omeka
 **/

// Ladies and Gentlemen, start your timers
define('APP_START', microtime(true));

// Define the directory and web paths.
require_once 'paths.php';

// Define the public theme directory path.
define('THEME_DIR', BASE_DIR . DIRECTORY_SEPARATOR . $site['public_theme']);

// Initialize Omeka.
require_once 'Omeka/Core.php';
$core = new Omeka_Core;
$core->initialize();

// Call the dispatcher which echos the response object automatically
$core->dispatch();

// Ladies and Gentlemen, stop your timers
if ((boolean) $config->debug->timer) {
    echo microtime(true) - APP_START;
}

// We're done here.