<?php
/**
 * Main template file fallback
 */

get_header();
?>

<div class="container" style="padding: 60px 20px; min-height: 50vh;">
  <?php
  if (have_posts()) :
      while (have_posts()) : the_post();
          ?>
          <article id="post-<?php the_ID(); ?>" <?php post_class(); ?> style="margin-bottom: 40px;">
            <h1 style="font-size: 2rem; margin-bottom: 16px; color: var(--primary-red);"><?php the_title(); ?></h1>
            <div class="entry-content" style="line-height: 1.8; color: #4b5563;">
              <?php the_content(); ?>
            </div>
          </article>
          <?php
      endwhile;
  else :
      include get_template_directory() . '/front-page.php';
  endif;
  ?>
</div>

<?php
get_footer();
