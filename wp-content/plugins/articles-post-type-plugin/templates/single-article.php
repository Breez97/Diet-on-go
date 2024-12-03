<?php get_header(); ?>

<div id="primary" class="content-area">
    <div id="main" class="site-main">
        <?php while (have_posts()) : the_post(); ?>
            <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="article-header">
                    <h1 class="article-title"><?php the_title(); ?></h1>
                    <?php
                    $reading_time = get_post_meta(get_the_ID(), '_reading_time', true);
                    if ($reading_time) {
                        echo '<div class="reading-time">Время чтения: ' . esc_html($reading_time) . ' мин</div>';
                    }
                    ?>
                </div>
                <div class="article-content">
                    <?php if (has_post_thumbnail()): ?>
                        <div class="article-featured-image">
                            <?php the_post_thumbnail('large', ['class' => 'article-image-single']); ?>
                        </div>
                    <?php endif; ?>
                    <?php the_content(); ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php get_footer(); ?>
