<?php

class ChestAIFaqs_DB
{
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'chest_ai_faqs';
    }

    // Create the database table with the 'position' column
    public function create_table()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Add 'position' column in the table creation process
        $sql = "CREATE TABLE {$this->table_name} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            show_on_home TINYINT(1) DEFAULT 0,
            position VARCHAR(255) DEFAULT '',  -- New column for position
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Insert a new FAQ
public function insert_faq($question, $answer, $show_on_home, $position = '')
{
    global $wpdb;

    // Check if the FAQ already exists based on the question
    $existing_faq = $this->get_faq_by_question($question);

    if ($existing_faq) {
        // If FAQ exists, update it
        $this->update_faq(
            $existing_faq['id'], // Use the ID of the existing FAQ to update
            $question,
            $answer,
            $show_on_home,
            $position
        );
        error_log("Updated FAQ: $question");
    } else {
        // If FAQ does not exist, insert a new one
        $wpdb->insert(
            $this->table_name,
            [
                'question' => sanitize_text_field($question),
                'answer' => sanitize_textarea_field($answer),
                'show_on_home' => $show_on_home ? 1 : 0,
                'position' => sanitize_text_field($position),  // Insert the position field
            ]
        );
        error_log("Inserted new FAQ: $question");
    }
}

    // Update an existing FAQ
    public function update_faq($id, $question, $answer, $show_on_home, $position)
    {
        global $wpdb;

        // Update FAQ with position
        $wpdb->update(
            $this->table_name,
            [
                'question' => sanitize_text_field($question),
                'answer' => sanitize_textarea_field($answer),
                'show_on_home' => $show_on_home ? 1 : 0,
                'position' => sanitize_text_field($position),  // Update the position field
            ],
            ['id' => intval($id)]
        );
    }

    // Delete an FAQ by ID
    public function delete_faq($id)
    {
        global $wpdb;

        $wpdb->delete(
            $this->table_name,
            ['id' => intval($id)]
        );
    }

    public function get_faqs($search = '', $paged = 1, $per_page = 10, $orderby = 'show_on_home', $order = 'DESC')
    {
        global $wpdb;
    
        $offset = ($paged - 1) * $per_page;
    
        $sql = "SELECT * FROM {$this->table_name}";
        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $sql .= $wpdb->prepare(" WHERE question LIKE %s OR answer LIKE %s", $like, $like);
        }
    
        // Dynamically change the ORDER BY clause based on the selected field and order
        $sql .= $wpdb->prepare(" ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $per_page, $offset);
    
        return $wpdb->get_results($sql, ARRAY_A);
    }
    

    // Get total count of FAQs (for pagination)
    public function get_faqs_count($search = '')
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$this->table_name}";
        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $sql .= $wpdb->prepare(" WHERE question LIKE %s OR answer LIKE %s", $like, $like);
        }

        return (int) $wpdb->get_var($sql);
    }

    // Get a single FAQ by ID
    public function get_faq_by_id($id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", intval($id)),
            ARRAY_A
        );
    }

    // Get all FAQs without pagination (for CSV export)
    public function get_all_faqs()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY id ASC", ARRAY_A);
    }
    // Check if a FAQ already exists by question
    public function get_faq_by_question($question)
    {
        global $wpdb;

        // Query to find a FAQ by its question
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE question = %s", $question),
            ARRAY_A
        );
    }
}
