<?php
function fmf_create_ingredients_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ingredients';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        ingredient_name varchar(255) NOT NULL,
        nutrition_info LONGTEXT NOT NULL
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}