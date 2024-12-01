<?php

function random_recipes_from_db() {
    global $wpdb;
    $table_recipes = $wpdb->prefix . 'recipes';
    $table_recipe_info = $wpdb->prefix . 'recipe_info';
    $query = "
        SELECT r.id, r.title, r.short_description, r.image, r.category, r.category_day, r.cooking_time
        FROM $table_recipes r
        ORDER BY RAND()
        LIMIT 3
    ";
    $recipes = $wpdb->get_results($query);
    if ($recipes) {
        $output = '<div class="recipe-cards-container">';
        foreach ($recipes as $recipe) {
            $recipe_info_query = $wpdb->prepare(
                "SELECT ingredients, instructions, calories, protein, carbs, fat FROM $table_recipe_info WHERE recipe_id = %d",
                $recipe->id
            );
            $recipe_info = $wpdb->get_row($recipe_info_query);
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
    } else {
        $output = '<p>Нет рецептов для отображения.</p>';
    }
    return $output;
}
add_shortcode('random_recipes_db', 'random_recipes_from_db');

?>