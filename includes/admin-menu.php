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
add_action( 'admin_menu', 'fmf_register_admin_menu' );

function fmf_render_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ingredients';

    if (isset($_POST['fmf_add_ingredient'])) {
        $ingredient_name = sanitize_text_field($_POST['ingredient_name']);
        $attributes = $_POST['attributes'];
        $values = $_POST['values'];
        $nutrition_info = [];

        foreach ($attributes as $key => $attribute) {
            $nutrition_info[sanitize_text_field($attribute)] = sanitize_text_field($values[$key]);
        }

        $wpdb->insert($table_name, [
            'ingredient_name' => $ingredient_name,
            'nutrition_info' => wp_json_encode($nutrition_info),
        ]);
    }

    // Handle ingredient deletion
    if (isset($_POST['fmf_delete_ingredient'])) {
        $wpdb->delete($table_name, ['id' => intval($_POST['ingredient_id'])]);
    }

    $ingredients = $wpdb->get_results("SELECT * FROM $table_name");

    include plugin_dir_path(__FILE__) . 'templates/admin-page.php';
}
