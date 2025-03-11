<?php
/**
 * The template for displaying employer story archive
 *
 * @package EmployerStories
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

get_header();

// Default to 3 columns for the archive page
$columns = 3;
$image_size = Employer_Stories::get_column_image_size($columns);
$column_class = 'large-' . (12 / $columns) . ' medium-6 small-12';
?>

    <main id="main" class="<?php echo esc_attr( flatsome_main_classes() ); ?>">
        <div class="es-section-wrapper es-employer-story-header">
			<?php echo do_shortcode('[block id="archive-employer-story-header"]'); ?>
        </div>
        <div class="row es-archive-employer-stories">
            <div class="large-12 col">
				<?php if ( have_posts() ) : ?>
                    <div class="es-employer-stories-grid row">
						<?php while ( have_posts() ) : the_post(); ?>
                            <div class="es-employer-story-item col <?php echo esc_attr($column_class); ?>">
                                <article id="employer-story-<?php the_ID(); ?>" <?php post_class('es-employer-story-card'); ?>>
                                    <a href="<?php the_permalink(); ?>" class="es-employer-story-link">
										<?php if ( has_post_thumbnail() ) : ?>
                                            <div class="es-employer-story-thumbnail">
												<?php the_post_thumbnail($image_size, array('class' => 'es-card-image')); ?>
                                            </div>
										<?php else : ?>
                                            <div class="es-employer-story-thumbnail es-no-image">
                                                <div class="es-placeholder"><?php _e('No Image', 'employer-stories'); ?></div>
                                            </div>
										<?php endif; ?>
                                    </a>
                                </article>
                            </div>
						<?php endwhile; ?>
                    </div>

                    <div class="es-pagination">
						<?php
						echo paginate_links(array(
							'prev_text' => '&larr; ' . __('Previous', 'employer-stories'),
							'next_text' => __('Next', 'employer-stories') . ' &rarr;',
						));
						?>
                    </div>

				<?php else : ?>
                    <div class="es-no-employer-stories">
                        <p><?php _e('No employer stories found.', 'employer-stories'); ?></p>
                    </div>
				<?php endif; ?>

            </div>
        </div>
    </main>

<?php get_footer(); ?>