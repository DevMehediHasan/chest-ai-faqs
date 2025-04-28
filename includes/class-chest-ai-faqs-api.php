<?php

class ChestAIFaqs_API {

    private $db;

    public function __construct($db) {
        $this->db = $db;
        add_action('rest_api_init', [$this, 'register_api_routes']);
    }

    // Register custom REST API routes
    public function register_api_routes() {
        // Register the '/all' route for fetching all FAQs
        register_rest_route('chest-ai-faqs/v1', '/all', [
            'methods' => 'GET',
            'callback' => [$this, 'get_all_faqs'],
            'permission_callback' => '__return_true', // Public access (you can change this based on your needs)
        ]);

        // Register the '/home' route for fetching FAQs marked to show on the homepage
        register_rest_route('chest-ai-faqs/v1', '/home', [
            'methods' => 'GET',
            'callback' => [$this, 'get_home_faqs'], // The callback method to handle this route
            'permission_callback' => '__return_true', // Public access
        ]);
    }

    // Fetch all FAQs (for '/all' endpoint)
    public function get_all_faqs() {
        return $this->db->get_all_faqs();
    }

    // Fetch FAQs for Home Page (for '/home' endpoint)
    public function get_home_faqs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'chest_ai_faqs'; // Make sure to replace this with your actual table name
        
        // Get FAQs that are marked to show on the homepage (show_on_home = 1)
        $faqs = $wpdb->get_results("SELECT * FROM $table_name WHERE show_on_home = 1");

        // Group the FAQs by position (e.g., left, right) based on the position column
        $leftFAQs = [];
        $rightFAQs = [];
        
        foreach ($faqs as $faq) {
            if (strpos($faq->position, 'left') !== false) {
                $leftFAQs[] = $faq; // Add FAQ to the left column if it has "left" in the position
            } elseif (strpos($faq->position, 'right') !== false) {
                $rightFAQs[] = $faq; // Add FAQ to the right column if it has "right" in the position
            }
        }

        // Return the grouped FAQs as a response
        return rest_ensure_response([
            'left' => $leftFAQs, // Return left column FAQs
            'right' => $rightFAQs, // Return right column FAQs
        ]);
    }
}

