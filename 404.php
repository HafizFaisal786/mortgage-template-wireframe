<?php
/**
 * The template to display the 404 page
 *
 * @package WordPress
 * @subpackage HEAVEN11
 * @since HEAVEN11 1.0
 */

get_header();

get_template_part( apply_filters( 'heaven11_filter_get_template_part', 'content', '404' ), '404' );

get_footer();
