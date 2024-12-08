<?php

/*
Plugin Name: Articles Post Type
Description: Плагин для создания собственного типа записи "Статьи"
Version: 1.0
Author: Shamrov Ilya
*/

if (!defined('ABSPATH')) {
    exit;
}

class CustomArticles {

    public function __construct() {
        add_action('init', [$this, 'register_custom_post_type']);
        add_action('add_meta_boxes', [$this, 'add_custom_meta_boxes']);
        add_action('save_post', [$this, 'save_custom_meta_boxes_data']);
        add_action('init', [$this, 'register_custom_meta']);
        add_shortcode('articles_list', [$this, 'articles_shortcode']);
        add_shortcode('random_articles', [$this, 'random_articles_shortcode']);
        register_activation_hook(__FILE__, [$this, 'plugin_activation']);
        register_deactivation_hook(__FILE__, [$this, 'plugin_deactivation']);
    }

    public function register_custom_post_type() {
        $labels = [
            'name' => 'Статьи',
            'singular_name' => 'Статья',
            'add_new' => 'Добавить новую',
            'add_new_item' => 'Добавить новую статью',
            'edit_item'  => 'Редактировать статью',
            'new_item' => 'Новая статья',
            'view_item' => 'Просмотреть статью',
            'search_items' => 'Искать статьи',
            'not_found' => 'Статьи не найдены',
            'not_found_in_trash' => 'В корзине статьи не найдены',
            'all_items' => 'Все статьи',
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_position' => 5,
            'menu_icon' => 'dashicons-media-document',
            'rewrite' => ['slug' => 'articles'],
        ];

        register_post_type('articles', $args);
    }

    public function register_custom_meta() {
        register_post_meta('articles', '_short_description', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
        ]);
        register_post_meta('articles', '_reading_time', [
            'show_in_rest' => true,
            'single' => true,
            'type' => 'integer',
        ]);
    }

    public function add_custom_meta_boxes() {
        add_meta_box(
            'short_description',
            'Краткое описание',
            [$this, 'render_short_description_meta_box'],
            'articles',
            'normal',
            'high'
        );

        add_meta_box(
            'reading_time',
            'Время чтения (в минутах)',
            [$this, 'render_reading_time_meta_box'],
            'articles',
            'side',
            'high'
        );
    }

    public function render_short_description_meta_box($post) {
        wp_nonce_field('articles_meta_box_nonce', 'articles_meta_box_nonce');
        $value = get_post_meta($post->ID, '_short_description', true);
        echo '<textarea style="width:100%;" id="short_description" name="short_description" rows="4">' . esc_textarea($value) . '</textarea>';
    }

    public function render_reading_time_meta_box($post) {
        wp_nonce_field('articles_meta_box_nonce', 'articles_meta_box_nonce');
        $value = get_post_meta($post->ID, '_reading_time', true);
        echo '<input type="number" id="reading_time" name="reading_time" value="' . esc_attr($value) . '" style="width:100%;" min="1" />';
    }

    public function save_custom_meta_boxes_data($post_id) {
        if (!isset($_POST['articles_meta_box_nonce']) || !wp_verify_nonce($_POST['articles_meta_box_nonce'], 'articles_meta_box_nonce')) {
            return;
        }
        if (isset($_POST['short_description'])) {
            update_post_meta($post_id, '_short_description', sanitize_textarea_field($_POST['short_description']));
        }
        if (isset($_POST['reading_time'])) {
            update_post_meta($post_id, '_reading_time', sanitize_text_field($_POST['reading_time']));
        }
    }

    public function plugin_activation() {
        $this->register_custom_post_type();
        flush_rewrite_rules();
    }

    public function plugin_deactivation() {
        flush_rewrite_rules();
    }

    public function articles_shortcode($atts) {
        ob_start();
        $args = [
            'post_type' => 'articles',
            'posts_per_page' => -1,
        ];
        $articles_query = new WP_Query($args);
        if ($articles_query->have_posts()) : ?>
            <div class="articles-list">
                <?php while ($articles_query->have_posts()) : $articles_query->the_post(); 
                    $short_description = get_post_meta(get_the_ID(), '_short_description', true);
                    $reading_time = get_post_meta(get_the_ID(), '_reading_time', true);
                    $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                ?>
                    <div class="article-item" id="post-<?php the_ID(); ?>" <?php post_class('article-card'); ?>>
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php the_title(); ?>" class="article-image" />
                        <div class="article-details">
                            <h2 class="article-title"><?php the_title(); ?></h2>
                            <p class="article-description"><?php echo esc_html($short_description); ?></p>
                            <div class="article-meta">
                                <span class="article-time">Время чтения: <?php echo esc_html($reading_time); ?> мин</span>
                                <a href="<?php the_permalink(); ?>" class="read-more">Читать подробнее</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else : 
            echo '<p>Статьи не найдены.</p>';
        endif;
        wp_reset_postdata();
        return ob_get_clean();
    }

    public function random_articles_shortcode($atts) {
        ob_start();
        $args = [
            'post_type' => 'articles',
            'posts_per_page' => 3,
            'orderby' => 'rand',
        ];
        $random_articles_query = new WP_Query($args);
        if ($random_articles_query->have_posts()) : ?>
            <div class="articles-list">
                <?php while ($random_articles_query->have_posts()) : $random_articles_query->the_post(); 
                    $short_description = get_post_meta(get_the_ID(), '_short_description', true);
                    $reading_time = get_post_meta(get_the_ID(), '_reading_time', true);
                    $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                ?>
                    <div class="article-item" id="post-<?php the_ID(); ?>" <?php post_class('article-card'); ?>>
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php the_title(); ?>" class="article-image" />
                        <div class="article-details">
                            <h2 class="article-title"><?php the_title(); ?></h2>
                            <p class="article-description"><?php echo esc_html($short_description); ?></p>
                            <div class="article-meta">
                                <span class="article-time">Время чтения: <?php echo esc_html($reading_time); ?> мин</span>
                                <a href="<?php the_permalink(); ?>" class="read-more">Читать подробнее</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else : 
            echo '<p>Статьи не найдены.</p>';
        endif;
        wp_reset_postdata();
        return ob_get_clean();
    }
}

if (class_exists('CustomArticles')) {
    $obj = new CustomArticles();
}
?>
