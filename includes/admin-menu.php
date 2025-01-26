<?php
function fmf_register_admin_menu() {
    add_menu_page(
        'Ingredients Manager',   
        'Ingredients',          
        'manage_options',        
        'fmf-ingredients',       
        'fmf_render_admin_page', 
        'dashicons-carrot',    
        6                        
    );
}
add_action('admin_menu', 'fmf_register_admin_menu');

function fmf_render_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ingredients';
    $ingredient_to_edit = null;

    // Handle Add/Edit Ingredient Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['fmf_add_ingredient']) || isset($_POST['fmf_update_ingredient'])) {
            $ingredient_name = sanitize_text_field($_POST['ingredient_name']);
            $attributes = $_POST['attributes'];
            $values = $_POST['values'];
        
            // Prepare nutrition info as JSON
            $nutrition_info = [];
            foreach ($attributes as $key => $attribute) {
                $nutrition_info[sanitize_text_field($attribute)] = sanitize_text_field($values[$key]);
            }
        
            $ingredient_name = sanitize_text_field($_POST['ingredient_name']);
            $existing_name_check = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE ingredient_name = %s AND id != %d", 
                    $ingredient_name, 
                    isset($_POST['ingredient_id']) ? intval($_POST['ingredient_id']) : 0 // Make sure this is excluded if editing
                )
            );

            // If another ingredient with the same name exists (excluding the current one being updated), show an error
            if ($existing_name_check > 0) {
                echo '<div class="error"><p>Ingredient name already exists. Please choose a different name.</p></div>';
            } else {
                // Proceed with the update or insert
                if (!empty($_POST['ingredient_id'])) {
                    // Update existing ingredient
                    $wpdb->update(
                        $table_name,
                        [
                            'ingredient_name' => $ingredient_name,
                            'nutrition_info'  => wp_json_encode($nutrition_info),
                        ],
                        ['id' => intval($_POST['ingredient_id'])]
                    );
                } else {
                    // Insert new ingredient
                    $wpdb->insert(
                        $table_name,
                        [
                            'ingredient_name' => $ingredient_name,
                            'nutrition_info'  => wp_json_encode($nutrition_info),
                        ]
                    );
                }
            }
        }

        // Handle Ingredient Deletion
        if (isset($_POST['fmf_delete_ingredient'])) {
            $wpdb->delete($table_name, ['id' => intval($_POST['ingredient_id'])]);
        }

        // Handle Edit Ingredient Request
        if (isset($_POST['fmf_edit_ingredient'])) {
            $ingredient_id = intval($_POST['ingredient_id']);
            $ingredient_to_edit = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $ingredient_id");
        }
    }

    // Fetch all ingredients for the table
    $ingredients = $wpdb->get_results("SELECT * FROM $table_name");

    // Include the admin page template
    include plugin_dir_path(__FILE__) . '../templates/admin-page.php';
}
