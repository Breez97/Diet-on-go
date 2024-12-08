<?php

function all_recipes() {
    $category_days = array();
    $categories = array();
    $args = array(
        'post_type' => 'recipe',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    $query = new WP_Query($args);
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $category_day = get_field('category_day');
            $category = get_field('category');
            if ($category_day && !in_array($category_day, $category_days)) {
                $category_days[] = $category_day;
            }
            if ($category && !in_array($category, $categories)) {
                $categories[] = $category;
            }
        }
        wp_reset_postdata();
    }
    sort($category_days);
    sort($categories);
    $output = '<div class="recipes-container">';
    $output .= '<div class="recipes-filter-section">';
    $output .= '<input type="text" id="recipe-search" placeholder="Поиск рецептов...">';
    $output .= '<div class="filter-row">';
    $output .= '<select id="recipe-category-day-filter">';
    $output .= '<option value="">Все дни</option>';
    foreach ($category_days as $day) {
        $output .= '<option value="' . esc_attr($day) . '">' . esc_html($day) . '</option>';
    }
    $output .= '</select>';
    $output .= '<select id="recipe-category-filter">';
    $output .= '<option value="">Все категории</option>';
    foreach ($categories as $cat) {
        $output .= '<option value="' . esc_attr($cat) . '">' . esc_html($cat) . '</option>';
    }
    $output .= '</select>';
    $output .= '<select id="recipe-cooking-time-filter">';
    $output .= '<option value="">Любое время готовки</option>';
    $output .= '<option value="0-15">До 15 минут</option>';
    $output .= '<option value="15-30">15-30 минут</option>';
    $output .= '<option value="30+">Более 30 минут</option>';
    $output .= '</select></div></div>';
    $output .= '<div id="recipes-grid" class="recipe-cards-container">';
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $title = get_the_title();
            $short_description = get_field('short_description');
            $image = get_field('image');
            $cooking_time = get_field('cooking_time');
            $category_day = get_field('category_day');
            
            $output .= '<div class="recipe-card" 
                data-category="' . esc_attr($category_day) . '" 
                data-title="' . esc_attr(strtolower($title)) . '">';
            $output .= '<a href="' . get_permalink() . '">';
            if ($image) {
                $output .= '<img class="recipe-image" src="' . esc_url($image) . '" alt="' . esc_attr($title) . '">';
            }
            $output .= '<p class="recipe-title">' . esc_html($title) . '</p>';
            $output .= '<p class="recipe-description">' . esc_html($short_description) . '</p>';
            $output .= '<div class="recipe-meta">';
            $output .= '<p class="recipe-category">' . esc_html($category_day) . '</p>';
            $output .= '<p class="recipe-time">' . esc_html($cooking_time) . ' мин.</p>';
            $output .= '</div></a></div>';
        }
        wp_reset_postdata();
    } else {
        $output .= '<p>Нет рецептов для отображения</p>';
    }
    $output .= '</div></div>';
    wp_localize_script('recipes-filter', 'recipeFilterParams', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
    return $output;
}
add_shortcode('all_recipes', 'all_recipes');

function filter_recipes() {
    $search_term = sanitize_text_field($_POST['search_term']);
    $category_day_filter = sanitize_text_field($_POST['category_day_filter']);
    $category_filter = sanitize_text_field($_POST['category_filter']);
    $cooking_time_filter = sanitize_text_field($_POST['cooking_time_filter']);
    $args = array(
        'post_type' => 'recipe',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        's' => $search_term,
        'meta_query' => array('relation' => 'AND')
    );
    if (!empty($category_day_filter)) {
        $args['meta_query'][] = array(
            'key' => 'category_day',
            'value' => $category_day_filter,
            'compare' => '='
        );
    }
    if (!empty($category_filter)) {
        $args['meta_query'][] = array(
            'key' => 'category',
            'value' => $category_filter,
            'compare' => '='
        );
    }
    if (!empty($cooking_time_filter)) {
        $args['meta_query'][] = array(
            'key' => 'cooking_time',
            'value' => '',
            'compare' => '!=',
            'type' => 'NUMERIC'
        );
    }
    $query = new WP_Query($args);
    $response = '';
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $title = get_the_title();
            $short_description = get_field('short_description');
            $image = get_field('image');
            $cooking_time = get_field('cooking_time');
            $category_day = get_field('category_day');
            $include_recipe = true;
            if (!empty($cooking_time_filter)) {
                $cooking_time_num = intval($cooking_time);
                switch ($cooking_time_filter) {
                    case '0-15':
                        $include_recipe = $cooking_time_num <= 15;
                        break;
                    case '15-30':
                        $include_recipe = $cooking_time_num >= 15 && $cooking_time_num <= 30;
                        break;
                    case '30+':
                        $include_recipe = $cooking_time_num > 30;
                        break;
                }
            }
            if ($include_recipe) {
                $response .= '<div class="recipe-card" 
                    data-category="' . esc_attr($category_day) . '"
                    data-title="' . esc_attr(strtolower($title)) . '">';
                $response .= '<a href="' . get_permalink() . '">';
                if ($image) {
                    $response .= '<img class="recipe-image" src="' . esc_url($image) . '" alt="' . esc_attr($title) . '">';
                }
                $response .= '<p class="recipe-title">' . esc_html($title) . '</p>';
                $response .= '<p class="recipe-description">' . esc_html($short_description) . '</p>';
                $response .= '<div class="recipe-meta">';
                $response .= '<p class="recipe-category">' . esc_html($category_day) . '</p>';
                $response .= '<p class="recipe-time">' . esc_html($cooking_time) . ' мин.</p>';
                $response .= '</div></a></div>';
            }
        }
        wp_reset_postdata();
    } else {
        $response = '<p>Нет рецептов, соответствующих фильтру.</p>';
    }
    echo $response;
    wp_die();
}
add_action('wp_ajax_filter_recipes', 'filter_recipes');
add_action('wp_ajax_nopriv_filter_recipes', 'filter_recipes');

?>