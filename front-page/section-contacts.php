<div class="front_page_section front_page_section_contacts<?php
	$heaven11_scheme = heaven11_get_theme_option( 'front_page_contacts_scheme' );
	if ( ! heaven11_is_inherit( $heaven11_scheme ) ) {
		echo ' scheme_' . esc_attr( $heaven11_scheme );
	}
	echo ' front_page_section_paddings_' . esc_attr( heaven11_get_theme_option( 'front_page_contacts_paddings' ) );
?>"
		<?php
		$heaven11_css      = '';
		$heaven11_bg_image = heaven11_get_theme_option( 'front_page_contacts_bg_image' );
		if ( ! empty( $heaven11_bg_image ) ) {
			$heaven11_css .= 'background-image: url(' . esc_url( heaven11_get_attachment_url( $heaven11_bg_image ) ) . ');';
		}
		if ( ! empty( $heaven11_css ) ) {
			echo ' style="' . esc_attr( $heaven11_css ) . '"';
		}
		?>
>
<?php
	// Add anchor
	$heaven11_anchor_icon = heaven11_get_theme_option( 'front_page_contacts_anchor_icon' );
	$heaven11_anchor_text = heaven11_get_theme_option( 'front_page_contacts_anchor_text' );
if ( ( ! empty( $heaven11_anchor_icon ) || ! empty( $heaven11_anchor_text ) ) && shortcode_exists( 'trx_sc_anchor' ) ) {
	echo do_shortcode(
		'[trx_sc_anchor id="front_page_section_contacts"'
									. ( ! empty( $heaven11_anchor_icon ) ? ' icon="' . esc_attr( $heaven11_anchor_icon ) . '"' : '' )
									. ( ! empty( $heaven11_anchor_text ) ? ' title="' . esc_attr( $heaven11_anchor_text ) . '"' : '' )
									. ']'
	);
}
?>
	<div class="front_page_section_inner front_page_section_contacts_inner
	<?php
	if ( heaven11_get_theme_option( 'front_page_contacts_fullheight' ) ) {
		echo ' heaven11-full-height sc_layouts_flex sc_layouts_columns_middle';
	}
	?>
			"
			<?php
			$heaven11_css      = '';
			$heaven11_bg_mask  = heaven11_get_theme_option( 'front_page_contacts_bg_mask' );
			$heaven11_bg_color_type = heaven11_get_theme_option( 'front_page_contacts_bg_color_type' );
			if ( 'custom' == $heaven11_bg_color_type ) {
				$heaven11_bg_color = heaven11_get_theme_option( 'front_page_contacts_bg_color' );
			} elseif ( 'scheme_bg_color' == $heaven11_bg_color_type ) {
				$heaven11_bg_color = heaven11_get_scheme_color( 'bg_color', $heaven11_scheme );
			} else {
				$heaven11_bg_color = '';
			}
			if ( ! empty( $heaven11_bg_color ) && $heaven11_bg_mask > 0 ) {
				$heaven11_css .= 'background-color: ' . esc_attr(
					1 == $heaven11_bg_mask ? $heaven11_bg_color : heaven11_hex2rgba( $heaven11_bg_color, $heaven11_bg_mask )
				) . ';';
			}
			if ( ! empty( $heaven11_css ) ) {
				echo ' style="' . esc_attr( $heaven11_css ) . '"';
			}
			?>
	>
		<div class="front_page_section_content_wrap front_page_section_contacts_content_wrap content_wrap">
			<?php

			// Title and description
			$heaven11_caption     = heaven11_get_theme_option( 'front_page_contacts_caption' );
			$heaven11_description = heaven11_get_theme_option( 'front_page_contacts_description' );
			if ( ! empty( $heaven11_caption ) || ! empty( $heaven11_description ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) {
				// Caption
				if ( ! empty( $heaven11_caption ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) {
					?>
					<h2 class="front_page_section_caption front_page_section_contacts_caption front_page_block_<?php echo ! empty( $heaven11_caption ) ? 'filled' : 'empty'; ?>">
					<?php
						echo wp_kses(( $heaven11_caption ),'heaven11_kses_content' );
					?>
					</h2>
					<?php
				}

				// Description
				if ( ! empty( $heaven11_description ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) {
					?>
					<div class="front_page_section_description front_page_section_contacts_description front_page_block_<?php echo ! empty( $heaven11_description ) ? 'filled' : 'empty'; ?>">
					<?php
						echo wp_kses( wpautop( $heaven11_description ), 'heaven11_kses_content' )
					?>
					</div>
					<?php
				}
			}

			// Content (text)
			$heaven11_content = heaven11_get_theme_option( 'front_page_contacts_content' );
			$heaven11_layout  = heaven11_get_theme_option( 'front_page_contacts_layout' );
			if ( 'columns' == $heaven11_layout && ( ! empty( $heaven11_content ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) ) {
				?>
				<div class="front_page_section_columns front_page_section_contacts_columns columns_wrap">
					<div class="column-1_3">
				<?php
			}

			if ( ( ! empty( $heaven11_content ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) ) {
				?>
				<div class="front_page_section_content front_page_section_contacts_content front_page_block_<?php echo ! empty( $heaven11_content ) ? 'filled' : 'empty'; ?>">
				<?php
					echo wp_kses(( $heaven11_content ), 'heaven11_kses_content');
				?>
				</div>
				<?php
			}

			if ( 'columns' == $heaven11_layout && ( ! empty( $heaven11_content ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) ) {
				?>
				</div><div class="column-2_3">
				<?php
			}

			// Shortcode output
			$heaven11_sc = heaven11_get_theme_option( 'front_page_contacts_shortcode' );
			if ( ! empty( $heaven11_sc ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) {
				?>
				<div class="front_page_section_output front_page_section_contacts_output front_page_block_<?php echo ! empty( $heaven11_sc ) ? 'filled' : 'empty'; ?>">
				<?php
					heaven11_show_layout( do_shortcode( $heaven11_sc ) );
				?>
				</div>
				<?php
			}

			if ( 'columns' == $heaven11_layout && ( ! empty( $heaven11_content ) || ( current_user_can( 'edit_theme_options' ) && is_customize_preview() ) ) ) {
				?>
				</div></div>
				<?php
			}
			?>

		</div>
	</div>
</div>
