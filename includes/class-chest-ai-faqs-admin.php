<?php

class ChestAIFaqs_Admin
{

    private $db;

    public function __construct($db)
    {
        $this->db = $db;

        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_post_save_faq', [$this, 'save_faq']);
        add_action('admin_post_update_faq', [$this, 'update_faq']);
        add_action('admin_post_delete_faq', [$this, 'delete_faq']);
        add_action('admin_post_bulk_delete_faqs', [$this, 'bulk_delete_faqs']);
        add_action('admin_post_export_faqs', [$this, 'export_faqs']);
        add_action('admin_post_import_faqs', [$this, 'import_faqs']);
    }

    public function admin_menu()
    {
        add_menu_page(
            'Chest AI FAQs',
            'Chest AI FAQs',
            'manage_options',
            'chest-ai-faqs-manage',
            [$this, 'manage_faqs_page'],
            'dashicons-editor-help'
        );

        add_submenu_page(
            'chest-ai-faqs-manage',
            'Add New FAQ',
            'Add New FAQ',
            'manage_options',
            'chest-ai-faqs-add',
            [$this, 'add_faq_page']
        );

        add_submenu_page(
            'chest-ai-faqs-manage',
            'Export FAQs',
            'Export FAQs',
            'manage_options',
            'chest-ai-faqs-export',
            [$this, 'export_faqs_page']
        );

        add_submenu_page(
            'chest-ai-faqs-manage',
            'Import FAQs',
            'Import FAQs',
            'manage_options',
            'chest-ai-faqs-import',
            [$this, 'import_faqs_page']
        );
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], '11', true);
    }

    public function add_faq_page()
    {
        $is_edit = isset($_GET['edit']);
        $faq = null;

        // Fetch all FAQs to check which positions are already taken
        $existing_positions = [];
        if (!$is_edit) {
            $all_faqs = $this->db->get_all_faqs(); // Fetch all FAQs from the database
            foreach ($all_faqs as $existing_faq) {
                if ($existing_faq['position']) {
                    $existing_positions[] = $existing_faq['position'];
                }
            }
        } else {
            // If editing, fetch the specific FAQ and exclude its position from the disabled ones
            $faq_id = intval($_GET['edit']);
            $faq = $this->db->get_faq_by_id($faq_id);

            if (!$faq) {
                echo '<div class="notice notice-error"><p>FAQ not found.</p></div>';
                return;
            }

            // Fetch all FAQs excluding the current FAQ's position
            $all_faqs = $this->db->get_all_faqs();
            foreach ($all_faqs as $existing_faq) {
                if ($existing_faq['position'] && $existing_faq['position'] !== $faq['position']) {
                    $existing_positions[] = $existing_faq['position'];
                }
            }
        }

        // Define the position options (only for first 10 FAQs)
        $positions = [
            '' => 'Select Position (for homepage)',  // Blank option for default
            'left 1' => 'Left 1',
            'left 2' => 'Left 2',
            'left 3' => 'Left 3',
            'left 4' => 'Left 4',
            'left 5' => 'Left 5',
            'right 1' => 'Right 1',
            'right 2' => 'Right 2',
            'right 3' => 'Right 3',
            'right 4' => 'Right 4',
            'right 5' => 'Right 5',
        ];

?>
        <div class="wrap">
            <h1><?php echo $is_edit ? 'Edit FAQ' : 'Add New FAQ'; ?></h1>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="<?php echo $is_edit ? 'update_faq' : 'save_faq'; ?>">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($faq['id']); ?>">
                <?php endif; ?>

                <?php wp_nonce_field('save_faq_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="question">Question</label></th>
                        <td><input type="text" name="question" id="question" class="regular-text" required value="<?php echo esc_attr($faq['question'] ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="answer">Answer</label></th>
                        <td><textarea name="answer" id="answer" class="large-text" rows="5" required><?php echo esc_textarea($faq['answer'] ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="show_on_home">Show on Home Page?</label></th>
                        <td><input type="checkbox" name="show_on_home" id="show_on_home" value="1" <?php echo (!empty($faq['show_on_home'])) ? 'checked' : ''; ?>></td>
                    </tr>
                    <tr>
                        <th><label for="position">Position</label></th>
                        <td>
                            <select name="position" id="position">
                                <?php
                                foreach ($positions as $key => $value) {
                                    // Disable the options that are already used
                                    $disabled = in_array($key, $existing_positions) ? 'disabled' : '';
                                    $selected = ($faq['position'] == $key) ? 'selected' : '';
                                    echo "<option value='$key' $selected $disabled>$value</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>

                <p><input type="submit" class="button button-primary" value="<?php echo $is_edit ? 'Update FAQ' : 'Add FAQ'; ?>"></p>
            </form>
        </div>
    <?php
    }


    public function manage_faqs_page()
    {
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 10;

        $total = $this->db->get_faqs_count($search);
        $faqs = $this->db->get_faqs($search, $paged, $per_page);

        $base_url = admin_url('admin.php?page=chest-ai-faqs-manage');

    ?>
        <div class="wrap">
            <h1>Manage FAQs</h1>

            <!-- Display the total number of FAQs -->
            <p><strong>Total FAQs: <?php echo esc_html($total); ?></strong></p>


            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'FAQ saved successfully!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    });
                </script>
            <?php endif; ?>

            <form method="get" action="">
                <input type="hidden" name="page" value="chest-ai-faqs-manage">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search FAQs...">
                <input type="submit" class="button" value="Search">
            </form>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="bulk-delete-form">
                <?php wp_nonce_field('bulk_delete_faqs_nonce'); ?>
                <input type="hidden" name="action" value="bulk_delete_faqs">

                <table class="widefat">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select_all"></th>
                            <th>ID</th>
                            <th>Question</th>
                            <th>Answer</th>
                            <th>Show on Home</th>
                            <th>Position</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faqs as $faq): ?>
                            <tr>
                                <td><input type="checkbox" name="faq_ids[]" value="<?php echo esc_attr($faq['id']); ?>"></td>
                                <td><?php echo esc_html($faq['id']); ?></td>
                                <td><?php echo esc_html($faq['question']); ?></td>
                                <td><?php echo esc_html(wp_trim_words($faq['answer'], 10)); ?></td>
                                <td><?php echo $faq['show_on_home'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo esc_html($faq['position']); ?></td>
                                <td>
                                    <a class="button" href="<?php echo admin_url('admin.php?page=chest-ai-faqs-add&edit=' . $faq['id']); ?>">Edit</a>
                                    <a href="#" class="button delete-button" data-id="<?php echo esc_attr($faq['id']); ?>">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p><input type="submit" class="button button-danger" value="Delete Selected"></p>
            </form>

            <?php
            $total_pages = ceil($total / $per_page);
            if ($total_pages > 1): ?>
                <div class="tablenav-pages">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a class="button <?php echo ($i == $paged) ? 'button-primary' : ''; ?>" href="<?php echo esc_url(add_query_arg(['paged' => $i], $base_url)); ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

            <script>
                document.getElementById('select_all').addEventListener('click', function(event) {
                    const checkboxes = document.querySelectorAll('input[name="faq_ids[]"]');
                    checkboxes.forEach(checkbox => checkbox.checked = event.target.checked);
                });

                document.querySelectorAll('.delete-button').forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        const faqId = this.getAttribute('data-id');
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "This FAQ will be deleted!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Yes, delete it!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = "<?php echo admin_url('admin-post.php?action=delete_faq&id='); ?>" + faqId + "&_wpnonce=<?php echo wp_create_nonce('delete_faq_nonce'); ?>";
                            }
                        });
                    });
                });

                const bulkDeleteForm = document.getElementById('bulk-delete-form');
                bulkDeleteForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "Selected FAQs will be deleted!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete selected!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            bulkDeleteForm.submit();
                        }
                    });
                });
            </script>
        </div>
    <?php
    }

    public function save_faq()
    {
        if (!current_user_can('manage_options') || !check_admin_referer('save_faq_nonce')) {
            wp_die('Unauthorized.');
        }

        $question = sanitize_text_field($_POST['question']);
        $answer = sanitize_textarea_field($_POST['answer']);
        $show_on_home = isset($_POST['show_on_home']) ? 1 : 0;
        $position = sanitize_text_field($_POST['position']); // Get the selected position

        // Insert FAQ with position (will be blank if not specified)
        $this->db->insert_faq($question, $answer, $show_on_home, $position);

        wp_redirect(admin_url('admin.php?page=chest-ai-faqs-manage&success=1'));
        exit;
    }

    public function update_faq()
    {
        if (!current_user_can('manage_options') || !check_admin_referer('save_faq_nonce')) {
            wp_die('Unauthorized.');
        }

        $id = intval($_POST['id']);
        $question = sanitize_text_field($_POST['question']);
        $answer = sanitize_textarea_field($_POST['answer']);
        $show_on_home = isset($_POST['show_on_home']) ? 1 : 0;
        $position = sanitize_text_field($_POST['position']); // Get the selected position

        // Update FAQ with position (may be blank if not specified)
        $this->db->update_faq($id, $question, $answer, $show_on_home, $position);

        wp_redirect(admin_url('admin.php?page=chest-ai-faqs-manage&success=1'));
        exit;
    }


    public function delete_faq()
    {
        if (!current_user_can('manage_options') || !check_admin_referer('delete_faq_nonce')) {
            wp_die('Unauthorized.');
        }

        $id = intval($_GET['id']);
        $this->db->delete_faq($id);

        wp_redirect(admin_url('admin.php?page=chest-ai-faqs-manage'));
        exit;
    }

    public function bulk_delete_faqs()
    {
        if (!current_user_can('manage_options') || !check_admin_referer('bulk_delete_faqs_nonce')) {
            wp_die('Unauthorized.');
        }

        if (!empty($_POST['faq_ids']) && is_array($_POST['faq_ids'])) {
            foreach ($_POST['faq_ids'] as $id) {
                $this->db->delete_faq(intval($id));
            }
        }

        wp_redirect(admin_url('admin.php?page=chest-ai-faqs-manage'));
        exit;
    }


    public function export_faqs_page()
    {
    ?>
        <div class="wrap">
            <h1>Export FAQs</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('export_faqs_nonce'); ?>
                <input type="hidden" name="action" value="export_faqs">
                <p><input type="submit" class="button button-primary" value="Download CSV"></p>
            </form>
        </div>
    <?php
    }

    public function export_faqs()
    {
        if (!current_user_can('manage_options') || !check_admin_referer('export_faqs_nonce')) {
            wp_die('Unauthorized');
        }

        $faqs = $this->db->get_all_faqs(); // Get all FAQs

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="faqs-export-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // CSV headers
        fputcsv($output, ['ID', 'Question', 'Answer', 'Show on Home', 'Position']);

        foreach ($faqs as $faq) {
            fputcsv($output, [
                $faq['id'],
                $faq['question'],
                $faq['answer'],
                $faq['show_on_home'],
                $faq['position']
            ]);
        }

        fclose($output);
        exit;
    }


    public function import_faqs_page()
    {
    ?>
        <div class="wrap">
            <h1>Import FAQs</h1>
            <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('import_faqs_nonce'); ?>
                <input type="hidden" name="action" value="import_faqs">
                <table class="form-table">
                    <tr>
                        <th><label for="csv_file">CSV File</label></th>
                        <td><input type="file" name="csv_file" id="csv_file" accept=".csv" required></td>
                    </tr>
                </table>
                <p><input type="submit" class="button button-primary" value="Import CSV"></p>
            </form>
        </div>
<?php
    }


    public function import_faqs()
{
    if (!current_user_can('manage_options') || !check_admin_referer('import_faqs_nonce')) {
        wp_die('Unauthorized');
    }

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        wp_die('Upload failed.');
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');

    if (!$handle) {
        wp_die('Cannot open uploaded file.');
    }

    // Skip header line
    fgetcsv($handle);

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) >= 5) {
            $question = sanitize_text_field($row[1]);
            $answer = sanitize_textarea_field($row[2]);
            $show_on_home = intval($row[3]);
            $position = sanitize_text_field($row[4]);

            // Check if the FAQ already exists based on the question
            $existing_faq = $this->db->get_faq_by_question($question);

            if (!$existing_faq) {
                // Insert FAQ if it doesn't exist
                $this->db->insert_faq($question, $answer, $show_on_home, $position);
            }
        }
    }

    fclose($handle);

    wp_redirect(admin_url('admin.php?page=chest-ai-faqs-manage&success=1'));
    exit;
}
}
// This class handles the admin side of the plugin, including displaying the FAQ management page, adding/editing FAQs, and handling form submissions.
// It uses the ChestAIFaqs_DB class to interact with the database.