<?

function main_theme_enqueue_styles() {
    wp_enqueue_style('main-theme-style', get_stylesheet_directory_uri() . '/style.css');
    wp_enqueue_style('main-theme-fonts-style', get_stylesheet_directory_uri() . '/fonts.css');
}
add_action('wp_enqueue_scripts', 'main_theme_enqueue_styles');

require get_stylesheet_directory() . '/includes/random_recipes.php';
require get_stylesheet_directory() . '/includes/all_recipes.php';
require get_stylesheet_directory() . '/includes/similar_recipes.php';

wp_enqueue_script('jquery');
wp_enqueue_script('recipes-filter', get_stylesheet_directory_uri() . '/js/recipes-filter.js', array('jquery'), '1.0', true);

?>