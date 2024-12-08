<?php

function random_recipes() {
    $args = array(
        'post_type' => 'recipe',
        'posts_per_page' => 3,
        'orderby' => 'rand',
        'post_status' => 'publish'
    );
    $query = new WP_Query($args);
    if ($query->have_posts()) {
        $output = '<div class="recipe-cards-container">';
        while ($query->have_posts()) {
            $query->the_post();
            $title = get_the_title();
            $short_description = get_field('short_description');
            $image = get_field('image');
            $cooking_time = get_field('cooking_time');
            $category = get_field('category');
            $category_day = get_field('category_day');
            $output .= '<div class="recipe-card">';
            $output .= '<a href="' . get_permalink() . '">';
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
        wp_reset_postdata();
    } else {
        $output = '<p>Нет рецептов для отображения.</p>';
    }
    return $output;
}
add_shortcode('random_recipes', 'random_recipes');

?>