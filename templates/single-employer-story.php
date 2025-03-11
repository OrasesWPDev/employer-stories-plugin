<?php
/**
 * The template for displaying single employer story posts
 *
 * @package EmployerStories
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

get_header();
?>

    <main id="main" class="<?php echo esc_attr(flatsome_main_classes()); ?>">
        <!-- Header Block (full width) -->
        <div class="es-section-wrapper es-employer-story-header">
			<?php echo do_shortcode('[block id="single-employer-story-header"]'); ?>
        </div>

        <!-- Content area (site width) -->
        <div class="row es-single-employer-story">
            <div class="large-12 col">
                <article id="employer-story-<?php the_ID(); ?>" <?php post_class('es-employer-story'); ?>>
					<?php while (have_posts()) : the_post(); ?>

                        <!-- Header Image Section -->
						<?php if (function_exists('get_field') && $header_image = get_field('header_image')) : ?>
                            <div class="es-employer-story-header-image-wrapper">
                                <img src="<?php echo esc_url($header_image['url']); ?>"
                                     alt="<?php echo esc_attr(get_the_title()); ?>"
                                     class="es-employer-story-header-image" />
                            </div>
						<?php endif; ?>

                        <!-- Employer Stats Section -->
						<?php if (function_exists('get_field') && $employer_stats = get_field('employer_stats')) : ?>
                            <div class="es-employer-stats-section">
                                <div class="row">
                                    <!-- Employer Column -->
                                    <div class="large-3 medium-6 small-12 col es-stats-column">
                                        <div class="es-stats-item">
                                            <div class="es-stats-icon">
                                                <img src="https://ptcb2025stag.wpenginepowered.com/wp-content/uploads/2025/03/employer-icon.webp"
                                                     alt="Employer"
                                                     width="35"
                                                     height="35" />
                                            </div>
                                            <h4 class="es-stats-title">Employer</h4>
                                            <div class="es-stats-value"><?php echo esc_html($employer_stats['employer']); ?></div>
                                        </div>
                                    </div>

                                    <!-- Headquarters Column -->
                                    <div class="large-3 medium-6 small-12 col es-stats-column">
                                        <div class="es-stats-item">
                                            <div class="es-stats-icon">
                                                <img src="https://ptcb2025stag.wpenginepowered.com/wp-content/uploads/2025/03/headquarters-icon.webp"
                                                     alt="Headquarters"
                                                     width="35"
                                                     height="35" />
                                            </div>
                                            <h4 class="es-stats-title">Headquarters</h4>
                                            <div class="es-stats-value"><?php echo esc_html($employer_stats['headquarters']); ?></div>
                                        </div>
                                    </div>

                                    <!-- Practice Setting Column -->
                                    <div class="large-3 medium-6 small-12 col es-stats-column">
                                        <div class="es-stats-item">
                                            <div class="es-stats-icon">
                                                <img src="https://ptcb2025stag.wpenginepowered.com/wp-content/uploads/2025/03/practice-setting-icon.webp"
                                                     alt="Practice Setting"
                                                     width="35"
                                                     height="35" />
                                            </div>
                                            <h4 class="es-stats-title">Practice Setting</h4>
                                            <div class="es-stats-value"><?php echo esc_html($employer_stats['practice_settings']); ?></div>
                                        </div>
                                    </div>

                                    <!-- Locations Column -->
                                    <div class="large-3 medium-6 small-12 col es-stats-column">
                                        <div class="es-stats-item">
                                            <div class="es-stats-icon">
                                                <img src="https://ptcb2025stag.wpenginepowered.com/wp-content/uploads/2025/03/locations-icon.webp"
                                                     alt="Locations"
                                                     width="35"
                                                     height="35" />
                                            </div>
                                            <h4 class="es-stats-title">Locations</h4>
                                            <div class="es-stats-value"><?php echo esc_html($employer_stats['locations']); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
						<?php endif; ?>

                        <!-- First Paragraph Section with Background Color -->
						<?php if (function_exists('get_field') && $first_paragraph = get_field('first_paragraph')) : ?>
                            <div class="es-first-paragraph-section">
                                <div class="es-first-paragraph-container">
									<?php if (!empty($first_paragraph['paragraph_title'])) : ?>
                                        <div class="es-paragraph-title">
											<?php echo $first_paragraph['paragraph_title']; ?>
                                        </div>
									<?php endif; ?>

									<?php if (!empty($first_paragraph['paragraph_content'])) : ?>
                                        <div class="es-paragraph-content">
											<?php echo $first_paragraph['paragraph_content']; ?>
                                        </div>
									<?php endif; ?>
                                </div>
                            </div>
						<?php endif; ?>

                        <!-- Story Title Section -->
						<?php if (function_exists('get_field') && $story_title = get_field('story_title')) : ?>
                            <div class="es-story-title-section">
                                <div class="es-story-title-container">
									<?php echo $story_title; ?>
                                </div>
                            </div>
						<?php endif; ?>

                        <!-- Story Content Section -->
						<?php if (function_exists('get_field') && have_rows('story_content')) : ?>
                            <div class="es-story-content-section">
								<?php while (have_rows('story_content')) : the_row(); ?>
                                    <div class="es-story-content-row">
										<?php if ($paragraph_title = get_sub_field('paragraph_title')) : ?>
                                            <div class="es-story-paragraph-title">
												<?php echo $paragraph_title; ?>
                                            </div>
										<?php endif; ?>

										<?php if ($paragraph_content = get_sub_field('paragraph_content')) : ?>
                                            <div class="es-story-paragraph-content">
												<?php echo $paragraph_content; ?>
                                            </div>
										<?php endif; ?>
                                    </div>
								<?php endwhile; ?>
                            </div>
						<?php endif; ?>

                        <!-- Post Navigation -->
                        <nav class="es-employer-story-navigation">
                            <div class="es-nav-links">
                                <div class="es-nav-button es-nav-previous">
									<?php if (get_previous_post()) : ?>
										<?php previous_post_link('%link', 'See Previous'); ?>
									<?php endif; ?>
                                </div>

                                <div class="es-nav-button es-nav-all">
                                    <a href="<?php echo esc_url(home_url('/employer-stories/')); ?>">See All</a>
                                </div>

                                <div class="es-nav-button es-nav-next">
									<?php if (get_next_post()) : ?>
										<?php next_post_link('%link', 'See Next'); ?>
									<?php endif; ?>
                                </div>
                            </div>
                        </nav>

					<?php endwhile; ?>
                </article>
            </div>
        </div>
    </main>

<?php get_footer(); ?>