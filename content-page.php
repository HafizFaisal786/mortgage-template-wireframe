<?php
/**
 * The default template to display the content of the single page
 *
 * @package WordPress
 * @subpackage HEAVEN11
 * @since HEAVEN11 1.0
 */

$heaven11_seo = heaven11_is_on( heaven11_get_theme_option( 'seo_snippets' ) );
?>

<article id="post-<?php the_ID(); ?>" 
									<?php
									post_class( 'post_item_single post_type_page' );
									if ( $heaven11_seo ) {
										?>
		itemscope="itemscope" 
		itemprop="mainEntityOfPage" 
		itemtype="//schema.org/<?php echo esc_attr( heaven11_get_markup_schema() ); ?>" 
		itemid="<?php echo esc_url( get_the_permalink() ); ?>"
		content="<?php the_title_attribute(); ?>"
										<?php
									}
									?>
>

	<?php
	do_action( 'heaven11_action_before_post_data' );

	// Structured data snippets
	if ( $heaven11_seo ) {
		get_template_part( apply_filters( 'heaven11_filter_get_template_part', 'templates/seo' ) );
	}

	// Now featured image used as header's background
	// Uncomment next rows (or remove false from the condition) to show featured image for the pages
	if ( false && ! heaven11_sc_layouts_showed( 'featured' ) && strpos( get_the_content(), '[trx_widget_banner]' ) === false ) {
		do_action( 'heaven11_action_before_post_featured' );
		heaven11_show_post_featured();
		do_action( 'heaven11_action_after_post_featured' );
	}

	do_action( 'heaven11_action_before_post_content' );
	?>

	<div class="post_content entry-content">
		<?php
			the_content();

			wp_link_pages(
				array(
					'before'      => '<div class="page_links"><span class="page_links_title">' . esc_html__( 'Pages:', 'heaven11' ) . '</span>',
					'after'       => '</div>',
					'link_before' => '<span>',
					'link_after'  => '</span>',
					'pagelink'    => '<span class="screen-reader-text">' . esc_html__( 'Page', 'heaven11' ) . ' </span>%',
					'separator'   => '<span class="screen-reader-text">, </span>',
				)
			);
			?>
	</div><!-- .entry-content -->

	<?php
	do_action( 'heaven11_action_after_post_content' );

	do_action( 'heaven11_action_after_post_data' );
	?>

</article>
