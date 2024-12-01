<?php

function get_similar_recipes_from_db() {
    if (isset($_GET['recipe_id'])) {
        $recipe_id = intval($_GET['recipe_id']);
    } else {
        return 'ID рецепта не указан.';
    }
    global $wpdb;
    $table_recipes = $wpdb->prefix . 'recipes';
    $table_recipe_info = $wpdb->prefix . 'recipe_info';
    $current_recipe = $wpdb->get_row($wpdb->prepare(
        "SELECT category, category_day FROM $table_recipes WHERE id = %d",
        $recipe_id
    ));
    if (!$current_recipe) {
        return 'Рецепт не найден.';
    }
    $category = $current_recipe->category;
    $category_day = $current_recipe->category_day;
    $query = $wpdb->prepare(
        "SELECT id, title, short_description, image, category, category_day, cooking_time 
         FROM $table_recipes
         WHERE (category = %s OR category_day = %s) AND id != %d
         ORDER BY RAND()
         LIMIT 3",
        $category, $category_day, $recipe_id
    );
    $similar_recipes = $wpdb->get_results($query);
    $count = count($similar_recipes);
    if ($count < 3) {
        $additional_query = $wpdb->prepare(
            "SELECT id, title, short_description, image, category, category_day, cooking_time 
             FROM $table_recipes
             WHERE id != %d AND id NOT IN (" . implode(',', wp_list_pluck($similar_recipes, 'id')) . ")
             ORDER BY RAND()
             LIMIT %d",
            $recipe_id, 3 - $count
        );
        $additional_recipes = $wpdb->get_results($additional_query);
        $similar_recipes = array_merge($similar_recipes, $additional_recipes);
    }
    return render_recipe_cards_with_details($similar_recipes, $wpdb, $table_recipe_info);
}

function render_recipe_cards_with_details($recipes, $wpdb, $table_recipe_info) {
    if (empty($recipes)) {
        return '<p>Похожие рецепты не найдены.</p>';
    }
    $output = '<div class="recipe-cards-container">';
    foreach ($recipes as $recipe) {
        $recipe_info = $wpdb->get_row($wpdb->prepare(
            "SELECT ingredients, instructions, calories, protein, carbs, fat 
             FROM $table_recipe_info WHERE recipe_id = %d",
            $recipe->id
        ));
        $output .= '<div class="recipe-card">';
        $output .= '<a href="' . esc_url(home_url('/single-recipe/?recipe_id=' . $recipe->id)) . '">';
        if ($recipe->image) {
            $output .= '<img class="recipe-image" src="' . esc_url($recipe->image) . '" alt="' . esc_attr($recipe->title) . '">';
        }
        $output .= '<p class="recipe-title">' . esc_html($recipe->title) . '</p>';
        $output .= '<p class="recipe-description">' . esc_html($recipe->short_description) . '</p>';
        $output .= '<div class="recipe-meta">';
        $output .= '<p class="recipe-category">' . esc_html($recipe->category_day) . '</p>';
        $output .= '<p class="recipe-time">' . esc_html($recipe->cooking_time) . ' мин.</p>';
        $output .= '</div>';

        $output .= '</a></div>';
    }
    $output .= '</div>';
    return $output;
}
add_shortcode('get_similar_recipes', 'get_similar_recipes_from_db');

?>