<?php

function get_recipe_field($field, $table = 'recipes') {
    if (isset($_GET['recipe_id'])) {
        $recipe_id = intval($_GET['recipe_id']);
    } else {
        return 'ID рецепта не указан';
    }
    global $wpdb;
    if ($table === 'recipe_info') {
        $query = $wpdb->prepare("
            SELECT $field 
            FROM {$wpdb->prefix}recipe_info WHERE recipe_id = %d", $recipe_id
        );
    } else {
        $query = $wpdb->prepare("SELECT $field FROM {$wpdb->prefix}recipes WHERE id = %d", $recipe_id);
    }
    $result = $wpdb->get_var($query);
    if ($result) {
        return nl2br(esc_html($result));
    }
    return ucfirst(str_replace('_', ' ', $field)) . ' не найдено';
}

function get_recipe_title($atts) {
    return get_recipe_field('title');
}
add_shortcode('get_recipe_title', 'get_recipe_title');

function get_recipe_description($atts) {
    return get_recipe_field('description', 'recipe_info');
}
add_shortcode('get_recipe_description', 'get_recipe_description');

function get_recipe_ingredients($atts) {
    return get_recipe_field('ingredients', 'recipe_info');
}
add_shortcode('get_recipe_ingredients', 'get_recipe_ingredients');

function get_recipe_instructions($atts) {
    return get_recipe_field('instructions', 'recipe_info');
}
add_shortcode('get_recipe_instructions', 'get_recipe_instructions');

function get_recipe_category($atts) {
    if (isset($_GET['recipe_id'])) {
        $recipe_id = intval($_GET['recipe_id']);
    } else {
        return 'ID рецепта не указан';
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'recipes';
    $query = $wpdb->prepare("SELECT category, category_day FROM $table_name WHERE id = %d", $recipe_id);
    $recipe_category = $wpdb->get_row($query);
    if ($recipe_category) {
        $category_text = esc_html($recipe_category->category) . ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ' . esc_html($recipe_category->category_day);
        return $category_text;
    }
    return 'Информация не найдена';
}
add_shortcode('get_recipe_category', 'get_recipe_category');

function get_recipe_info($atts) {
    if (isset($_GET['recipe_id'])) {
        $recipe_id = intval($_GET['recipe_id']);
    } else {
        return 'ID рецепта не указан';
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'recipe_info';
    $query = $wpdb->prepare("
        SELECT calories, protein, fat, carbs 
        FROM $table_name 
        WHERE recipe_id = %d", 
        $recipe_id
    );
    $recipe_info = $wpdb->get_row($query);
    if ($recipe_info) {
        $info_text = 'Калории: ' . esc_html($recipe_info->calories) . ' | ';
        $info_text .= 'Белки: ' . esc_html($recipe_info->protein) . ' г | ';
        $info_text .= 'Жиры: ' . esc_html($recipe_info->fat) . ' г | ';
        $info_text .= 'Углеводы: ' . esc_html($recipe_info->carbs) . ' г';
        return $info_text;
    }
    return 'Информация не найдена';
}
add_shortcode('get_recipe_info', 'get_recipe_info');

function get_recipe_image($atts) {
    if (isset($_GET['recipe_id'])) {
        $recipe_id = intval($_GET['recipe_id']);
    } else {
        return 'ID рецепта не указан';
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'recipes';
    $image_url = $wpdb->get_var($wpdb->prepare("SELECT image FROM $table_name WHERE id = %d", $recipe_id));
    if ($image_url) {
        return '<img src="' . esc_url($image_url) . '" alt="Рецепт" style="border-radius: 12px; border: 2px solid #655552;" />';
    }
    return 'Изображение не найдено';
}
add_shortcode('get_recipe_image', 'get_recipe_image');

?>