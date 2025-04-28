<?php
/*
Plugin Name: Chest AI FAQs
Description: A simple FAQ plugin for React frontend integration.
Version: 1.0
Author: Mehedi Hasan
Author URI: https://mmehedi.com
*/

defined('ABSPATH') or die('No script kiddies please!');

// Include required classes
require_once plugin_dir_path(__FILE__) . 'includes/class-chest-ai-faqs-db.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-chest-ai-faqs-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-chest-ai-faqs-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-chest-ai-faqs.php';

// Initialize the plugin
$chest_ai_faqs = new ChestAIFaqs();
$chest_ai_faqs->init();

// Hook for activation - create DB table
register_activation_hook(__FILE__, function() {
    $db = new ChestAIFaqs_DB();
    $db->create_table();
});