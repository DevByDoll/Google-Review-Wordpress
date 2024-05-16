<?php
/*
Plugin Name: Dollies Google Tools
Plugin URI: https://by-doll.online
Description: Dieses Plugin ermöglicht das Verwalten von Google-Rezensionen über die Google Places API. Es erfordert ein Bootstap v5.3 oder höher Theme.
Version: 1.0
Author: Christian Doll
Author URI: https://by-doll.online
License: GPLv2 or later
Text Domain: dollies-google-tools
*/

defined('ABSPATH') or die('No script kiddies please!');

$plugin_dir_path = plugin_dir_path(__FILE__);

require_once $plugin_dir_path . 'functions.php';
require_once $plugin_dir_path . 'activate.php';
require_once $plugin_dir_path . 'deactivate.php';

register_activation_hook(__FILE__, 'dollies_google_tools_activate');
register_deactivation_hook(__FILE__, 'dollies_google_tools_deactivate');
