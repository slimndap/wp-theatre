<?php get_header(); ?>

<?php 
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post(); 
?>
			<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>	
				<h2><?php the_title();?></h2>
				<?php echo get_the_post_thumbnail($post->ID, 'medium');?>
				<?php the_content();?>
			</div>
<?php
		} // end while
	} // end if
?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
