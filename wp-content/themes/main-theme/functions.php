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
            $output .= '<a href="' . esc_url(home_url('/single-recipe/?recipe_id=' . $recipe->id)) . '">';
            $output .= '<div class="recipe-card">';
            if ($recipe->image) {
                $output .= '<img class="recipe-image" src="' . esc_url($recipe->image) . '" alt="' . esc_attr($recipe->title) . '">';
            }
            $output .= '<p class="recipe-title">' . esc_html($recipe->title) . '</p>';
            $output .= '<p class="recipe-description">' . esc_html($recipe->short_description) . '</p>';
            $output .= '<div class="recipe-meta">';
            $output .= '<p class="recipe-category">' . esc_html($recipe->category_day) . '</p>';
            $output .= '<p class="recipe-time">' . esc_html($recipe->cooking_time) . ' мин.</p>';
            $output .= '</div>';
            $output .= '</div></a>';
        }
        $output .= '</div>';
    } else {
        $output = '<p>Нет рецептов для отображения.</p>';
    }

    return $output;
}
add_shortcode('random_recipes_db', 'random_recipes_from_db');

function main_theme_enqueue_styles() {
    wp_enqueue_style('main-theme-style', get_stylesheet_directory_uri() . '/style.css');
    wp_enqueue_style('main-theme-fonts-style', get_stylesheet_directory_uri() . '/fonts.css');
    if (is_page('single-recipe')) {
        wp_enqueue_style('single-recipe-style', get_template_directory_uri() . '/single-recipe-page.css');
    }
}
add_action('wp_enqueue_scripts', 'main_theme_enqueue_styles');

function single_recipe_template($template) {
    if (is_page('single-recipe') && isset($_GET['recipe_id'])) {
        $new_template = locate_template('single-recipe.php');
        if ('' != $new_template) {
            return $new_template;
        }
    }
    return $template;
}
add_filter('template_include', 'single_recipe_template');

?>
