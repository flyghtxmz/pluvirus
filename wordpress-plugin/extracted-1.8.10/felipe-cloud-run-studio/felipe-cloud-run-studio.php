<?php
/**
 * Plugin Name: Menezes Studio
 * Description: Painel isolado para criar templates e gerar imagens pelo backend do WordPress.
 * Version: 1.8.35
 * Author: Menezes
 */

defined('ABSPATH') || exit;

define('FCRS_PLUGIN_FILE', __FILE__);
define('FCRS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FCRS_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once FCRS_PLUGIN_DIR . 'includes/class-fcrs-plugin-v3.php';
FCRS_Plugin::get_instance();
