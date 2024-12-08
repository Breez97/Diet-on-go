<?php

function get_similar_recipes($atts) {
    $atts = shortcode_atts(array(
        'current_title' => '',
        'category' => '',
        'cooking_time' => '',
        'category_day' => ''
    ), $atts);
    $current_title = sanitize_text_field($atts['current_title']);
    $category = sanitize_text_field($atts['category']);
    $cooking_time = sanitize_text_field($atts['cooking_time']);
    $category_day = sanitize_text_field($atts['category_day']);
    $current_recipe = get_page_by_title($current_title, OBJECT, 'recipe');
    $current_recipe_id = $current_recipe ? $current_recipe->ID : 0;
    $args = array(
        'post_type' => 'recipe',
        'posts_per_page' => 3,
        'post__not_in' => $current_recipe_id ? array($current_recipe_id) : array(),
        'meta_query' => array('relation' => 'OR')
    );
    if (!empty($category)) {
        $args['meta_query'][] = array(
            'key' => 'category',
            'value' => $category,
            'compare' => '='
        );
    }
    if (!empty($cooking_time)) {
        $args['meta_query'][] = array(
            'key' => 'cooking_time',
            'value' => $cooking_time,
            'compare' => '<=',
            'type' => 'NUMERIC'
        );
    }
    if (!empty($category_day)) {
        $args['meta_query'][] = array(
            'key' => 'category_day',
            'value' => $category_day,
            'compare' => '='
        );
    }
    $similar_recipes = new WP_Query($args);
    $recipes = $similar_recipes->posts;
    if (count($recipes) < 3) {
        $args['posts_per_page'] = 3 - count($recipes);
        $args['meta_query'] = ''; 
        $additional_recipes = new WP_Query($args);
        $recipes = array_merge($recipes, $additional_recipes->posts);
    }
    return render_recipe_cards($recipes);
}

function render_recipe_cards($recipes) {
    if (empty($recipes)) {
        return '<p>Похожие рецепты не найдены</p>';
    }
    $output = '<div class="recipe-cards-container">';
    foreach ($recipes as $recipe) {
        $title = get_the_title($recipe);
        $short_description = get_field('short_description', $recipe->ID);
        $image = get_field('image', $recipe->ID);
        $cooking_time = get_field('cooking_time', $recipe->ID);
        $category_day = get_field('category_day', $recipe->ID);

        $output .= '<div class="recipe-card">';
        $output .= '<a href="' . esc_url(get_permalink($recipe)) . '">';
        if ($image) {
            $output .= '<img class="recipe-image" src="' . esc_url($image) . '" alt="' . esc_attr($title) . '">';
        }
        $output .= '<p class="recipe-title">' . esc_html($title) . '</p>';
        $output .= '<p class="recipe-description">' . esc_html($short_description) . '</p>';
        $output .= '<div class="recipe-meta">';
        $output .= '<p class="recipe-category">' . esc_html($category_day) . '</p>';
        $output .= '<p class="recipe-time">' . esc_html($cooking_time) . ' мин.</p>';
        $output .= '</div>';
        $output .= '</a></div>';
    }
    $output .= '</div>';
    return $output;
}

add_shortcode('get_similar_recipes', 'get_similar_recipes');


?>