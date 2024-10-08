<?php
/**
 * Theme Options, Color Schemes and Fonts utilities
 *
 * @package WordPress
 * @subpackage HEAVEN11
 * @since HEAVEN11 1.0
 */

// -----------------------------------------------------------------
// -- Create and manage Theme Options
// -----------------------------------------------------------------

// Theme init priorities:
// 2 - create Theme Options
if ( ! function_exists( 'heaven11_options_theme_setup2' ) ) {
	add_action( 'after_setup_theme', 'heaven11_options_theme_setup2', 2 );
	function heaven11_options_theme_setup2() {
		heaven11_create_theme_options();
	}
}

// Step 1: Load default settings and previously saved mods
if ( ! function_exists( 'heaven11_options_theme_setup5' ) ) {
	add_action( 'after_setup_theme', 'heaven11_options_theme_setup5', 5 );
	function heaven11_options_theme_setup5() {
		heaven11_storage_set( 'options_reloaded', false );
		heaven11_load_theme_options();
	}
}

// Step 2: Load current theme customization mods
if ( is_customize_preview() ) {
	if ( ! function_exists( 'heaven11_load_custom_options' ) ) {
		add_action( 'wp_loaded', 'heaven11_load_custom_options' );
		function heaven11_load_custom_options() {
			if ( ! heaven11_storage_get( 'options_reloaded' ) ) {
				heaven11_storage_set( 'options_reloaded', true );
				heaven11_load_theme_options();
			}
		}
	}
}



// Load current values for each customizable option
if ( ! function_exists( 'heaven11_load_theme_options' ) ) {
	function heaven11_load_theme_options() {
		$options = heaven11_storage_get( 'options' );
		$reset   = (int) get_theme_mod( 'reset_options', 0 );
		foreach ( $options as $k => $v ) {
			if ( isset( $v['std'] ) ) {
				$value = heaven11_get_theme_option_std( $k, $v['std'] );
				if ( ! $reset ) {
					if ( isset( $_GET[ $k ] ) ) {
						$value = wp_kses_data( wp_unslash( $_GET[ $k ] ) );
					} else {
						$default_value = -987654321;
						$tmp           = get_theme_mod( $k, $default_value );
						if ( $tmp != $default_value ) {
							$value = $tmp;
						}
					}
				}
				heaven11_storage_set_array2( 'options', $k, 'val', $value );
				if ( $reset ) {
					remove_theme_mod( $k );
				}
			}
		}
		if ( $reset ) {
			// Unset reset flag
			set_theme_mod( 'reset_options', 0 );
			// Regenerate CSS with default colors and fonts
			heaven11_customizer_save_css();
		} else {
			do_action( 'heaven11_action_load_options' );
		}
	}
}

// Override options with stored page/post meta
if ( ! function_exists( 'heaven11_override_theme_options' ) ) {
	add_action( 'wp', 'heaven11_override_theme_options', 1 );
	function heaven11_override_theme_options( $query = null ) {
		if ( is_page_template( 'blog.php' ) ) {
			heaven11_storage_set( 'blog_archive', true );
			heaven11_storage_set( 'blog_template', get_the_ID() );
		}
		heaven11_storage_set( 'blog_mode', heaven11_detect_blog_mode() );
		if ( is_singular() ) {
			heaven11_storage_set( 'options_meta', get_post_meta( get_the_ID(), 'heaven11_options', true ) );
		}
		do_action( 'heaven11_action_override_theme_options' );
	}
}

// Override options with stored page meta on 'Blog posts' pages
if ( ! function_exists( 'heaven11_blog_override_theme_options' ) ) {
	add_action( 'heaven11_action_override_theme_options', 'heaven11_blog_override_theme_options' );
	function heaven11_blog_override_theme_options() {
		global $wp_query;
		if ( is_home() && ! is_front_page() && ! empty( $wp_query->is_posts_page ) ) {
			$id = get_option( 'page_for_posts' );
			if ( (int) $id > 0 ) {
				heaven11_storage_set( 'options_meta', get_post_meta( $id, 'heaven11_options', true ) );
			}
		}
	}
}


// Return 'std' value of the option, processed by special function (if specified)
if ( ! function_exists( 'heaven11_get_theme_option_std' ) ) {
	function heaven11_get_theme_option_std( $opt_name, $opt_std ) {
		if ( ! is_array( $opt_std ) && strpos( $opt_std, '$heaven11_' ) !== false ) {
			$func = substr( $opt_std, 1 );
			if ( function_exists( $func ) ) {
				$opt_std = $func( $opt_name );
			}
		}
		return $opt_std;
	}
}


// Return customizable option value
if ( ! function_exists( 'heaven11_get_theme_option' ) ) {
	function heaven11_get_theme_option( $name, $defa = '', $strict_mode = false, $post_id = 0 ) {
		$rez            = $defa;
		$from_post_meta = false;

		if ( $post_id > 0 ) {
			if ( ! heaven11_storage_isset( 'post_options_meta', $post_id ) ) {
				heaven11_storage_set_array( 'post_options_meta', $post_id, get_post_meta( $post_id, 'heaven11_options', true ) );
			}
			if ( heaven11_storage_isset( 'post_options_meta', $post_id, $name ) ) {
				$tmp = heaven11_storage_get_array( 'post_options_meta', $post_id, $name );
				if ( ! heaven11_is_inherit( $tmp ) ) {
					$rez            = $tmp;
					$from_post_meta = true;
				}
			}
		}

		if ( ! $from_post_meta && heaven11_storage_isset( 'options' ) ) {

			$blog_mode = heaven11_storage_get( 'blog_mode' );
			if ( ! heaven11_storage_isset( 'options', $name ) && ( empty( $blog_mode ) || ! heaven11_storage_isset( 'options', $name . '_' . $blog_mode ) ) ) {
				$rez = '_not_exists_';
				$tmp = $rez;
				if ( function_exists( 'trx_addons_get_option' ) ) {
					$rez = trx_addons_get_option( $name, $tmp, false );
				}
				if ( $rez === $tmp ) {
					if ( $strict_mode ) {
						// Translators: Add option's name to the output
						echo '<pre>' . esc_html( sprintf( __( 'Undefined option "%s" called from:', 'heaven11' ), $name ) );
						if ( function_exists( 'dcs' ) ) {
							dcs();
						}
						echo '</pre>';
						wp_die();
					} else {
						$rez = $defa;
					}
				}
			} else {

				$blog_mode_parent = apply_filters( 
										'heaven11_filter_blog_mode_parent',
										'post' == $blog_mode
											? 'blog'
											: str_replace( '_single', '', $blog_mode )
									);

				// Override option from GET or POST for current blog mode
				if ( ! empty( $blog_mode ) && isset( $_REQUEST[ $name . '_' . $blog_mode ] ) ) {
					$rez = wp_kses_data( wp_unslash( $_REQUEST[ $name . '_' . $blog_mode ] ) );

					// Override option from GET
				} elseif ( isset( $_REQUEST[ $name ] ) ) {
					$rez = wp_kses_data( wp_unslash( $_REQUEST[ $name ] ) );

					// Override option from current page settings (if exists)
				} elseif ( heaven11_storage_isset( 'options_meta', $name ) && ! heaven11_is_inherit( heaven11_storage_get_array( 'options_meta', $name ) ) ) {
					$rez = heaven11_storage_get_array( 'options_meta', $name );

					// Override option from current blog mode settings: 'front', 'search', 'page', 'post', 'blog', etc. (if exists)
				} elseif ( ! empty( $blog_mode ) && heaven11_storage_isset( 'options', $name . '_' . $blog_mode, 'val' ) && ! heaven11_is_inherit( heaven11_storage_get_array( 'options', $name . '_' . $blog_mode, 'val' ) ) ) {
					$rez = heaven11_storage_get_array( 'options', $name . '_' . $blog_mode, 'val' );

					// Override option for 'post' from 'blog' settings (if exists)
					// Also used for override 'xxx_single' on the 'xxx'
					// (for example, instead 'sidebar_courses_single' return option for 'sidebar_courses')
				} elseif ( ! empty( $blog_mode_parent ) && $blog_mode != $blog_mode_parent && heaven11_storage_isset( 'options', $name . '_' . $blog_mode_parent, 'val' ) && ! heaven11_is_inherit( heaven11_storage_get_array( 'options', $name . '_' . $blog_mode_parent, 'val' ) ) ) {
					$rez = heaven11_storage_get_array( 'options', $name . '_' . $blog_mode_parent, 'val' );

					// Get saved option value
				} elseif ( heaven11_storage_isset( 'options', $name, 'val' ) ) {
					$rez = heaven11_storage_get_array( 'options', $name, 'val' );

					// Get ThemeREX Addons option value
				} elseif ( function_exists( 'trx_addons_get_option' ) ) {
					$rez = trx_addons_get_option( $name, $defa, false );

				}
			}
		}
		return $rez;
	}
}


// Check if customizable option exists
if ( ! function_exists( 'heaven11_check_theme_option' ) ) {
	function heaven11_check_theme_option( $name ) {
		return heaven11_storage_isset( 'options', $name );
	}
}


// Return customizable option value, stored in the posts meta
if ( ! function_exists( 'heaven11_get_theme_option_from_meta' ) ) {
	function heaven11_get_theme_option_from_meta( $name, $defa = '' ) {
		$rez = $defa;
		if ( heaven11_storage_isset( 'options_meta' ) ) {
			if ( heaven11_storage_isset( 'options_meta', $name ) ) {
				$rez = heaven11_storage_get_array( 'options_meta', $name );
			} else {
				$rez = 'inherit';
			}
		}
		return $rez;
	}
}


// Get dependencies list from the Theme Options
if ( ! function_exists( 'heaven11_get_theme_dependencies' ) ) {
	function heaven11_get_theme_dependencies() {
		$options = heaven11_storage_get( 'options' );
		$depends = array();
		foreach ( $options as $k => $v ) {
			if ( isset( $v['dependency'] ) ) {
				$depends[ $k ] = $v['dependency'];
			}
		}
		return $depends;
	}
}



//------------------------------------------------
// Save options
//------------------------------------------------
if ( ! function_exists( 'heaven11_options_save' ) ) {
	add_action( 'after_setup_theme', 'heaven11_options_save', 4 );
	function heaven11_options_save() {

		if ( ! isset( $_REQUEST['page'] ) || 'theme_options' != $_REQUEST['page'] || '' == heaven11_get_value_gp( 'heaven11_nonce' ) ) {
			return;
		}

		// verify nonce
		if ( ! wp_verify_nonce( heaven11_get_value_gp( 'heaven11_nonce' ), admin_url() ) ) {
			heaven11_add_admin_message( esc_html__( 'Bad security code! Options are not saved!', 'heaven11' ), 'error', true );
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			heaven11_add_admin_message( esc_html__( 'Manage options is denied for the current user! Options are not saved!', 'heaven11' ), 'error', true );
			return;
		}

		// Save options
		heaven11_options_update( null, 'heaven11_options_field_' );

		// Return result
		heaven11_add_admin_message( esc_html__( 'Options are saved', 'heaven11' ) );
		wp_redirect( get_admin_url( null, 'admin.php?page=theme_options' ) );
		exit();
	}
}


// Update theme options from specified source
// (_POST or any other options storage)
if ( ! function_exists( 'heaven11_options_update' ) ) {
	function heaven11_options_update( $from = null, $from_prefix = '' ) {
		$options           = heaven11_storage_get( 'options' );
		$external_storages = array();
		$values            = null === $from ? get_theme_mods() : $from;
		foreach ( $options as $k => $v ) {
			// Skip non-data options - sections, info, etc.
			if ( ! isset( $v['std'] ) ) {
				continue;
			}
			// Get new value
			$value = null;
			if ( null === $from ) {
				$from_name = "{$from_prefix}{$k}";
				if ( isset( $_POST[ $from_name ] ) ) {
					$value = heaven11_get_value_gp( $from_name );
					// Individual options processing
					if ( 'custom_logo' == $k ) {
						if ( ! empty( $value ) && 0 == (int) $value ) {
							$value = attachment_url_to_postid( heaven11_clear_thumb_size( $value ) );
							if ( empty( $value ) ) {
								$value = null === $from ? get_theme_mod( $k ) : $values[$k];
							}
						}
					}
					// Save to the result array
					if ( ! empty( $v['type'] ) && 'hidden' != $v['type'] && ( empty( $v['hidden'] ) || ! $v['hidden'] ) && heaven11_get_theme_option_std( $k, $v['std'] ) != $value ) {
						$values[ $k ] = $value;
					} elseif ( isset( $values[ $k ] ) ) {
						unset( $values[ $k ] );
						$value = null;
					}
				}
			} else {
				$value = isset( $values[ $k ] )
								? $values[ $k ]
								: heaven11_get_theme_option_std( $k, $v['std'] );
			}
			// External plugin's options
			if ( $value !== null && ! empty( $v['options_storage'] ) ) {
				if ( ! isset( $external_storages[ $v['options_storage'] ] ) ) {
					$external_storages[ $v['options_storage'] ] = array();
				}
				$external_storages[ $v['options_storage'] ][ $k ] = $value;
			}
		}

		// Update options in the external storages
		foreach ( $external_storages as $storage_name => $storage_values ) {
			$storage = get_option( $storage_name, false );
			if ( is_array( $storage ) ) {
				foreach ( $storage_values as $k => $v ) {
					$storage[ $k ] = $v;
				}
				update_option( $storage_name, apply_filters( 'heaven11_filter_options_save', $storage, $storage_name ) );
			}
		}

		// Update Theme Mods (internal Theme Options)
		$stylesheet_slug = get_option( 'stylesheet' );
		$values          = apply_filters( 'heaven11_filter_options_save', $values, 'theme_mods' );

		update_option( "theme_mods_{$stylesheet_slug}", $values );

		do_action( 'heaven11_action_just_save_options', $values );

		// Store new schemes colors
		if ( ! empty( $values['scheme_storage'] ) ) {
			$schemes = heaven11_unserialize( $values['scheme_storage'] );
			if ( is_array( $schemes ) && count( $schemes ) > 0 ) {
				heaven11_storage_set( 'schemes', $schemes );
			}
		}

		// Store new fonts parameters
		$fonts = heaven11_get_theme_fonts();
		foreach ( $fonts as $tag => $v ) {
			foreach ( $v as $css_prop => $css_value ) {
				if ( in_array( $css_prop, array( 'title', 'description' ) ) ) {
					continue;
				}
				if ( isset( $values[ "{$tag}_{$css_prop}" ] ) ) {
					$fonts[ $tag ][ $css_prop ] = $values[ "{$tag}_{$css_prop}" ];
				}
			}
		}
		heaven11_storage_set( 'theme_fonts', $fonts );

		// Update ThemeOptions save timestamp
		$stylesheet_time = time();
		update_option( "heaven11_options_timestamp_{$stylesheet_slug}", $stylesheet_time );

		// Sinchronize theme options between child and parent themes
		if ( heaven11_get_theme_setting( 'duplicate_options' ) == 'both' ) {
			$theme_slug = get_option( 'template' );
			if ( $theme_slug != $stylesheet_slug ) {
				heaven11_customizer_duplicate_theme_options( $stylesheet_slug, $theme_slug, $stylesheet_time );
			}
		}

		// Apply action - moved to the delayed state (see below) to load all enabled modules and apply changes after
		// Attention! Don't remove comment the line below!
		// Not need here: do_action('heaven11_action_save_options');
		update_option( 'heaven11_action', 'heaven11_action_save_options' );
	}
}


//-------------------------------------------------------
//-- Delayed action from previous session
//-- (after save options)
//-- to save new CSS, etc.
//-------------------------------------------------------
if ( ! function_exists( 'heaven11_do_delayed_action' ) ) {
	add_action( 'after_setup_theme', 'heaven11_do_delayed_action' );
	function heaven11_do_delayed_action() {
		$action = get_option( 'heaven11_action' );
		if ( '' != $action ) {
			do_action( $action );
			update_option( 'heaven11_action', '' );
		}
	}
}



// -----------------------------------------------------------------
// -- Theme Settings utilities
// -----------------------------------------------------------------

// Return internal theme setting value
if ( ! function_exists( 'heaven11_get_theme_setting' ) ) {
	function heaven11_get_theme_setting( $name ) {
		if ( ! heaven11_storage_isset( 'settings', $name ) ) {
			// Translators: Add setting's name to the output
			echo '<pre>' . esc_html( sprintf( __( 'Undefined setting "%s" called from:', 'heaven11' ), $name ) );
			if ( function_exists( 'dcs' ) ) {
				dcs();
			}
			echo '</pre>';
			wp_die();
		} else {
			return heaven11_storage_get_array( 'settings', $name );
		}
	}
}

// Set theme setting
if ( ! function_exists( 'heaven11_set_theme_setting' ) ) {
	function heaven11_set_theme_setting( $option_name, $value ) {
		if ( heaven11_storage_isset( 'settings', $option_name ) ) {
			heaven11_storage_set_array( 'settings', $option_name, $value );
		}
	}
}



// -----------------------------------------------------------------
// -- Color Schemes utilities
// -----------------------------------------------------------------

// Load saved values to the color schemes
if ( ! function_exists( 'heaven11_load_schemes' ) ) {
	add_action( 'heaven11_action_load_options', 'heaven11_load_schemes' );
	function heaven11_load_schemes() {
		$schemes = heaven11_storage_get( 'schemes' );
		$storage = heaven11_unserialize( heaven11_get_theme_option( 'scheme_storage' ) );
		if ( is_array( $storage ) && count( $storage ) > 0 ) {
			// New way - use all color schemes (built-in and created by user)
			heaven11_storage_set( 'schemes', heaven11_check_scheme_colors( $storage, $schemes ) );
		}
	}
}
			
// Compare schemes and return correct colors set
if ( ! function_exists( 'heaven11_check_scheme_colors' ) ) {
	function heaven11_check_scheme_colors( $storage, $schemes ) {
		// Remove old colors
		foreach ( $storage as $k => $v ) {
			if ( isset( $schemes[ $k ] ) ) {
				foreach ( $v['colors'] as $k1 => $v1 ) {
					if ( ! isset( $schemes[ $k ]['colors'][ $k1 ] ) ) {
						unset( $storage[ $k ]['colors'][ $k1 ] );
					}
				}
			}
		}
		// Add new colors
		foreach ( $schemes as $k => $v ) {
			foreach ( $v['colors'] as $k1 => $v1 ) {
				if ( ! isset( $storage[ $k ]['colors'][ $k1 ] ) ) {
					$storage[ $k ]['colors'][ $k1 ] = $v1;
				}
			}
		}
		return $storage;
	}
}

// Return specified color from current (or specified) color scheme
if ( ! function_exists( 'heaven11_get_scheme_color' ) ) {
	function heaven11_get_scheme_color( $color_name, $scheme = '' ) {
		if ( empty( $scheme ) ) {
			$scheme = heaven11_get_theme_option( 'color_scheme' );
		}
		if ( empty( $scheme ) || heaven11_storage_empty( 'schemes', $scheme ) ) {
			$scheme = 'default';
		}
		$colors = heaven11_storage_get_array( 'schemes', $scheme, 'colors' );
		return $colors[ $color_name ];
	}
}

// Return colors from current color scheme
if ( ! function_exists( 'heaven11_get_scheme_colors' ) ) {
	function heaven11_get_scheme_colors( $scheme = '' ) {
		if ( empty( $scheme ) ) {
			$scheme = heaven11_get_theme_option( 'color_scheme' );
		}
		if ( empty( $scheme ) || heaven11_storage_empty( 'schemes', $scheme ) ) {
			$scheme = 'default';
		}
		return heaven11_storage_get_array( 'schemes', $scheme, 'colors' );
	}
}

// Return colors from all schemes
if ( ! function_exists( 'heaven11_get_scheme_storage' ) ) {
	function heaven11_get_scheme_storage( $scheme = '' ) {
		return serialize( heaven11_storage_get( 'schemes' ) );
	}
}

// Return theme fonts parameter's default value
if ( ! function_exists( 'heaven11_get_scheme_color_option' ) ) {
	function heaven11_get_scheme_color_option( $option_name ) {
		$parts = explode( '_', $option_name, 2 );
		return heaven11_get_scheme_color( $parts[1] );
	}
}

// Return schemes list
if ( ! function_exists( 'heaven11_get_list_schemes' ) ) {
	function heaven11_get_list_schemes( $prepend_inherit = false ) {
		$list    = array();
		$schemes = heaven11_storage_get( 'schemes' );
		if ( is_array( $schemes ) && count( $schemes ) > 0 ) {
			foreach ( $schemes as $slug => $scheme ) {
				$list[ $slug ] = $scheme['title'];
			}
		}
		return $prepend_inherit ? heaven11_array_merge( array( 'inherit' => esc_html__( 'Inherit', 'heaven11' ) ), $list ) : $list;
	}
}

// Return all schemes, sorted by usage in the parameters 'xxx_scheme' on the current page
if ( ! function_exists( 'heaven11_get_sorted_schemes' ) ) {
	function heaven11_get_sorted_schemes() {
		$params  = heaven11_storage_get( 'schemes_sorted' );
		$schemes = heaven11_storage_get( 'schemes' );
		$rez     = array();
		if ( is_array( $schemes ) ) {
			foreach ( $params as $p ) {
				if ( ! heaven11_check_theme_option( $p ) ) {
					continue;
				}
				$s = heaven11_get_theme_option( $p );
				if ( ! empty( $s ) && ! heaven11_is_inherit( $s ) && isset( $schemes[ $s ] ) ) {
					$rez[ $s ] = $schemes[ $s ];
					unset( $schemes[ $s ] );
				}
			}
			if ( count( $schemes ) > 0 ) {
				$rez = array_merge( $rez, $schemes );
			}
		}
		return $rez;
	}
}


// -----------------------------------------------------------------
// -- Theme Fonts utilities
// -----------------------------------------------------------------

// Load saved values into fonts list
if ( ! function_exists( 'heaven11_load_fonts' ) ) {
	add_action( 'heaven11_action_load_options', 'heaven11_load_fonts' );
	function heaven11_load_fonts() {
		// Fonts to load when theme starts
		$load_fonts = array();
		for ( $i = 1; $i <= heaven11_get_theme_setting( 'max_load_fonts' ); $i++ ) {
			$name = heaven11_get_theme_option( "load_fonts-{$i}-name" );
			if ( '' != $name ) {
				$load_fonts[] = array(
					'name'   => $name,
					'family' => heaven11_get_theme_option( "load_fonts-{$i}-family" ),
					'styles' => heaven11_get_theme_option( "load_fonts-{$i}-styles" ),
				);
			}
		}
		heaven11_storage_set( 'load_fonts', $load_fonts );
		heaven11_storage_set( 'load_fonts_subset', heaven11_get_theme_option( 'load_fonts_subset' ) );

		// Font parameters of the main theme's elements
		$fonts = heaven11_get_theme_fonts();
		foreach ( $fonts as $tag => $v ) {
			foreach ( $v as $css_prop => $css_value ) {
				if ( in_array( $css_prop, array( 'title', 'description' ) ) ) {
					continue;
				}
				$fonts[ $tag ][ $css_prop ] = heaven11_get_theme_option( "{$tag}_{$css_prop}" );
			}
		}
		heaven11_storage_set( 'theme_fonts', $fonts );
	}
}

// Return slug of the loaded font
if ( ! function_exists( 'heaven11_get_load_fonts_slug' ) ) {
	function heaven11_get_load_fonts_slug( $name ) {
		return str_replace( ' ', '-', $name );
	}
}

// Return load fonts parameter's default value
if ( ! function_exists( 'heaven11_get_load_fonts_option' ) ) {
	function heaven11_get_load_fonts_option( $option_name ) {
		$rez        = '';
		$parts      = explode( '-', $option_name );
		$load_fonts = heaven11_storage_get( 'load_fonts' );
		if ( 'load_fonts' == $parts[0] && count( $load_fonts ) > $parts[1] - 1 && isset( $load_fonts[ $parts[1] - 1 ][ $parts[2] ] ) ) {
			$rez = $load_fonts[ $parts[1] - 1 ][ $parts[2] ];
		}
		return $rez;
	}
}

// Return load fonts subset's default value
if ( ! function_exists( 'heaven11_get_load_fonts_subset' ) ) {
	function heaven11_get_load_fonts_subset( $option_name ) {
		return heaven11_storage_get( 'load_fonts_subset' );
	}
}

// Return load fonts list
if ( ! function_exists( 'heaven11_get_list_load_fonts' ) ) {
	function heaven11_get_list_load_fonts( $prepend_inherit = false ) {
		$list       = array();
		$load_fonts = heaven11_storage_get( 'load_fonts' );
		if ( is_array( $load_fonts ) && count( $load_fonts ) > 0 ) {
			foreach ( $load_fonts as $font ) {
				$list[ '"' . trim( $font['name'] ) . '"' . ( ! empty( $font['family'] ) ? ',' . trim( $font['family'] ) : '' ) ] = $font['name'];
			}
		}
		return $prepend_inherit ? heaven11_array_merge( array( 'inherit' => esc_html__( 'Inherit', 'heaven11' ) ), $list ) : $list;
	}
}

// Return font settings of the theme specific elements
if ( ! function_exists( 'heaven11_get_theme_fonts' ) ) {
	function heaven11_get_theme_fonts() {
		return heaven11_storage_get( 'theme_fonts' );
	}
}

// Return theme fonts parameter's default value
if ( ! function_exists( 'heaven11_get_theme_fonts_option' ) ) {
	function heaven11_get_theme_fonts_option( $option_name ) {
		$rez         = '';
		$parts       = explode( '_', $option_name );
		$theme_fonts = heaven11_storage_get( 'theme_fonts' );
		if ( ! empty( $theme_fonts[ $parts[0] ][ $parts[1] ] ) ) {
			$rez = $theme_fonts[ $parts[0] ][ $parts[1] ];
		}
		return $rez;
	}
}

// Update loaded fonts list in the each tag's parameter (p, h1..h6,...) after the 'load_fonts' options are loaded
if ( ! function_exists( 'heaven11_update_list_load_fonts' ) ) {
	add_action( 'heaven11_action_load_options', 'heaven11_update_list_load_fonts', 11 );
	function heaven11_update_list_load_fonts() {
		$theme_fonts = heaven11_get_theme_fonts();
		$load_fonts  = heaven11_get_list_load_fonts( true );
		foreach ( $theme_fonts as $tag => $v ) {
			heaven11_storage_set_array2( 'options', $tag . '_font-family', 'options', $load_fonts );
		}
	}
}



// -----------------------------------------------------------------
// -- Other options utilities
// -----------------------------------------------------------------

// Return all vars from Theme Options with option 'customizer'
if ( ! function_exists( 'heaven11_get_theme_vars' ) ) {
	function heaven11_get_theme_vars() {
		$options = heaven11_storage_get( 'options' );
		$vars    = array();
		foreach ( $options as $k => $v ) {
			if ( ! empty( $v['customizer'] ) ) {
				$vars[ $v['customizer'] ] = heaven11_get_theme_option( $k );
			}
		}
		return $vars;
	}
}

// Return current theme-specific border radius for form's fields and buttons
if ( ! function_exists( 'heaven11_get_border_radius' ) ) {
	function heaven11_get_border_radius() {
		$rad = str_replace( ' ', '', heaven11_get_theme_option( 'border_radius' ) );
		if ( empty( $rad ) ) {
			$rad = 0;
		}
		return heaven11_prepare_css_value( $rad );
	}
}




// -----------------------------------------------------------------
// -- Theme Options page
// -----------------------------------------------------------------

if ( ! function_exists( 'heaven11_options_init_page_builder' ) ) {
	add_action( 'after_setup_theme', 'heaven11_options_init_page_builder' );
	function heaven11_options_init_page_builder() {
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', 'heaven11_options_add_scripts' );
		}
	}
}

// Load required styles and scripts for admin mode
if ( ! function_exists( 'heaven11_options_add_scripts' ) ) {
	
	function heaven11_options_add_scripts() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		if ( ! empty( $screen->id ) && false !== strpos($screen->id, '_page_theme_options') ) {
			wp_enqueue_style( 'heaven11-icons', heaven11_get_file_url( 'css/font-icons/css/fontello-embedded.css' ), array(), null );
			wp_enqueue_style( 'wp-color-picker', false, array(), null );
			wp_enqueue_script( 'wp-color-picker', false, array( 'jquery' ), null, true );
			wp_enqueue_script( 'jquery-ui-tabs', false, array( 'jquery', 'jquery-ui-core' ), null, true );
			wp_enqueue_script( 'jquery-ui-accordion', false, array( 'jquery', 'jquery-ui-core' ), null, true );
			wp_enqueue_script( 'heaven11-options', heaven11_get_file_url( 'theme-options/theme-options.js' ), array( 'jquery' ), null, true );
			wp_enqueue_script( 'heaven11-colorpicker-colors', heaven11_get_file_url( 'js/colorpicker/colors.js' ), array( 'jquery' ), null, true );
			wp_enqueue_script( 'heaven11-colorpicker', heaven11_get_file_url( 'js/colorpicker/jqColorPicker.js' ), array( 'jquery' ), null, true );
			wp_localize_script( 'heaven11-options', 'heaven11_dependencies', heaven11_get_theme_dependencies() );
			wp_localize_script( 'heaven11-options', 'heaven11_color_schemes', heaven11_storage_get( 'schemes' ) );
			wp_localize_script( 'heaven11-options', 'heaven11_simple_schemes', heaven11_storage_get( 'schemes_simple' ) );
			wp_localize_script( 'heaven11-options', 'heaven11_sorted_schemes', heaven11_storage_get( 'schemes_sorted' ) );
			wp_localize_script( 'heaven11-options', 'heaven11_theme_fonts', heaven11_storage_get( 'theme_fonts' ) );
			wp_localize_script( 'heaven11-options', 'heaven11_theme_vars', heaven11_get_theme_vars() );
			wp_localize_script(
				'heaven11-options', 'heaven11_options_vars', apply_filters(
					'heaven11_filter_options_vars', array(
						'max_load_fonts' => heaven11_get_theme_setting( 'max_load_fonts' ),
					)
				)
			);
		}
	}
}

// Add Theme Options item in the Appearance menu
if ( ! function_exists( 'heaven11_options_add_theme_panel_page' ) ) {
	add_action( 'trx_addons_filter_add_theme_panel_pages', 'heaven11_options_add_theme_panel_page' );
	function heaven11_options_add_theme_panel_page($list) {
		if ( ! HEAVEN11_THEME_FREE ) {
			$list[] = array(
				esc_html__( 'Theme Options', 'heaven11' ),
				esc_html__( 'Theme Options', 'heaven11' ),
				'manage_options',
				'theme_options',
				'heaven11_options_page_builder',
				'dashicons-admin-generic'
			);
		}
		return $list;
	}
}


// Build options page
if ( ! function_exists( 'heaven11_options_page_builder' ) ) {
	function heaven11_options_page_builder() {
		?>
		<div class="heaven11_options">
			<h2 class="heaven11_options_title"><?php esc_html_e( 'Theme Options', 'heaven11' ); ?></h2>
			<?php heaven11_show_admin_messages(); ?>
			<div class="heaven11_options_info notice notice-info notice-large">
				<p><b>
					<?php esc_html_e( 'Attention!', 'heaven11' ); ?>
				</b></p>
				<p>
					<?php echo esc_html__( 'Some of these options can be overridden in the following sections (Blog, Plugins settings, etc.) or in the settings of individual pages.', 'heaven11' )
						. '<br>'
						. esc_html__( 'If you changed such parameter and nothing happened on the page, this option may be overridden in the corresponding section or in the Page Options of this page.', 'heaven11' );
					?>
				</p>
				<p><span class="heaven11_options_asterisk"></span>
					<i>
						<?php esc_html_e( 'These options are marked with an asterisk (*) in the title.', 'heaven11' ); ?>
					</i>
				</p>
			</div>
			<form id="heaven11_options_form" action="#" method="post" enctype="multipart/form-data">
				<input type="hidden" name="heaven11_nonce" value="<?php echo esc_attr( wp_create_nonce( admin_url() ) ); ?>" />
				<?php heaven11_options_show_fields(); ?>
				<div class="heaven11_options_buttons">
					<input type="button" class="heaven11_options_button_submit" value="<?php  esc_attr_e( 'Save Options', 'heaven11' ); ?>">
				</div>
			</form>
		</div>
		<?php
	}
}


// Display all option's fields
if ( ! function_exists( 'heaven11_options_show_fields' ) ) {
	function heaven11_options_show_fields( $options = false ) {
		if ( empty( $options ) ) {
			$options = heaven11_storage_get( 'options' );
		}
		$tabs_titles  = array();
		$tabs_content = array();
		$last_panel   = '';
		$last_section = '';
		$last_group   = '';
		foreach ( $options as $k => $v ) {
			if ( 'panel' == $v['type'] || ( 'section' == $v['type'] && empty( $last_panel ) ) ) {
				// New tab
				if ( ! isset( $tabs_titles[ $k ] ) ) {
					$tabs_titles[ $k ]  = $v['title'];
					$tabs_content[ $k ] = '';
				}
				if ( ! empty( $last_group ) ) {
					$tabs_content[ $last_section ] .= '</div></div>';
					$last_group                     = '';
				}
				$last_section = $k;
				if ( 'panel' == $v['type'] ) {
					$last_panel = $k;
				}
			} elseif ( 'group' == $v['type'] || ( 'section' == $v['type'] && ! empty( $last_panel ) ) ) {
				// New group
				if ( empty( $last_group ) ) {
					$tabs_content[ $last_section ] = ( ! isset( $tabs_content[ $last_section ] ) ? '' : $tabs_content[ $last_section ] )
													. '<div class="heaven11_accordion heaven11_options_groups">';
				} else {
					$tabs_content[ $last_section ] .= '</div>';
				}
				$tabs_content[ $last_section ] .= '<h4 class="heaven11_accordion_title heaven11_options_group_title">' . esc_html( $v['title'] ) . '</h4>'
												. '<div class="heaven11_accordion_content heaven11_options_group_content">';
				$last_group                     = $k;
			} elseif ( in_array( $v['type'], array( 'group_end', 'section_end', 'panel_end' ) ) ) {
				// End panel, section or group
				if ( ! empty( $last_group ) && ( 'section_end' != $v['type'] || empty( $last_panel ) ) ) {
					$tabs_content[ $last_section ] .= '</div></div>';
					$last_group                     = '';
				}
				if ( 'panel_end' == $v['type'] ) {
					$last_panel = '';
				}
			} else {
				// Field's layout
				$tabs_content[ $last_section ] = ( ! isset( $tabs_content[ $last_section ] ) ? '' : $tabs_content[ $last_section ] )
												. heaven11_options_show_field( $k, $v );
			}
		}
		if ( ! empty( $last_group ) ) {
			$tabs_content[ $last_section ] .= '</div></div>';
		}

		if ( count( $tabs_content ) > 0 ) {
			// Remove empty sections
			foreach ( $tabs_content as $k => $v ) {
				if ( empty( $v ) ) {
					unset( $tabs_titles[ $k ] );
					unset( $tabs_content[ $k ] );
				}
			}
			?>
			<div id="heaven11_options_tabs" class="heaven11_tabs <?php echo count( $tabs_titles ) > 1 ? 'with_tabs' : 'no_tabs'; ?>">
				<?php
				if ( count( $tabs_titles ) > 1 ) {
					?>
					<ul>
						<?php
						$cnt = 0;
						foreach ( $tabs_titles as $k => $v ) {
							$cnt++;
							echo '<li><a href="#heaven11_options_section_' . esc_attr( $cnt ) . '">' . esc_html( $v ) . '</a></li>';
						}
						?>
					</ul>
					<?php
				}
				$cnt = 0;
				foreach ( $tabs_content as $k => $v ) {
					$cnt++;
					?>
					<div id="heaven11_options_section_<?php echo esc_attr( $cnt ); ?>" class="heaven11_tabs_section heaven11_options_section">
						<?php heaven11_show_layout( $v ); ?>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}
	}
}


// Display single option's field
if ( ! function_exists( 'heaven11_options_show_field' ) ) {
	function heaven11_options_show_field( $name, $field, $post_type = '' ) {

		$inherit_allow = ! empty( $post_type );
		$inherit_state = ! empty( $post_type ) && isset( $field['val'] ) && heaven11_is_inherit( $field['val'] );

		$field_data_present = 'info' != $field['type'] || ! empty( $field['override']['desc'] ) || ! empty( $field['desc'] );

		if ( ( 'hidden' == $field['type'] && $inherit_allow )         // Hidden field in the post meta (not in the root Theme Options)
			|| ( ! empty( $field['hidden'] ) && ! $inherit_allow )    // Field only for post meta in the root Theme Options
		) {
			return '';
		}

		if ( 'hidden' == $field['type'] ) {
			$output = isset( $field['val'] )
							? '<input type="hidden" name="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' value="' . esc_attr( $field['val'] ) . '"'
								. ' />'
							: '';
		} else {
			$output = ( ! empty( $field['class'] ) && strpos( $field['class'], 'heaven11_new_row' ) !== false
						? '<div class="heaven11_new_row_before"></div>'
						: '' )
						. '<div class="heaven11_options_item heaven11_options_item_' . esc_attr( $field['type'] )
									. ( $inherit_allow ? ' heaven11_options_inherit_' . ( $inherit_state ? 'on' : 'off' ) : '' )
									. ( ! empty( $field['class'] ) ? ' ' . esc_attr( $field['class'] ) : '' )
									. '">'
							. '<h4 class="heaven11_options_item_title">'
								. esc_html( $field['title'] )
								. ( ! empty( $field['override'] )
										? ' <span class="heaven11_options_asterisk"></span>'
										: '' )
								. ( $inherit_allow
										? '<span class="heaven11_options_inherit_lock" id="heaven11_options_inherit_' . esc_attr( $name ) . '"></span>'
										: '' )
							. '</h4>'
							. ( $field_data_present
								? '<div class="heaven11_options_item_data">'
									. '<div class="heaven11_options_item_field" data-param="' . esc_attr( $name ) . '"'
										. ( ! empty( $field['linked'] ) ? ' data-linked="' . esc_attr( $field['linked'] ) . '"' : '' )
										. '>'
								: '' );
			if ( 'checkbox' == $field['type'] ) {
				// Type 'checkbox'
				$output .= '<label class="heaven11_options_item_label">'
							// Hack to always send checkbox value even it not checked
							. '<input type="hidden" name="heaven11_options_field_' . esc_attr( $name ) . '" value="' . esc_attr( heaven11_is_inherit( $field['val'] ) ? '' : $field['val'] ) . '" />'
							. '<input type="checkbox" name="heaven11_options_field_' . esc_attr( $name ) . '_chk" value="1"'
									. ( 1 == $field['val'] ? ' checked="checked"' : '' )
									. ' />'
							. esc_html( $field['title'] )
						. '</label>';
			} elseif ( in_array( $field['type'], array( 'switch', 'radio' ) ) ) {
				// Type 'switch' (2 choises) or 'radio' (3+ choises)
				$field['options'] = apply_filters( 'heaven11_filter_options_get_list_choises', $field['options'], $name );
				$first            = true;
				foreach ( $field['options'] as $k => $v ) {
					$output .= '<label class="heaven11_options_item_label">'
								. '<input type="radio" name="heaven11_options_field_' . esc_attr( $name ) . '"'
										. ' value="' . esc_attr( $k ) . '"'
										. ( ( '#' . $field['val'] ) == ( '#' . $k ) || ( $first && ! isset( $field['options'][ $field['val'] ] ) ) ? ' checked="checked"' : '' )
										. ' />'
								. esc_html( $v )
							. '</label>';
					$first   = false;
				}
			} elseif ( in_array( $field['type'], array( 'text', 'time', 'date' ) ) ) {
				// Type 'text' or 'time' or 'date'
				$output .= '<input type="text" name="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' value="' . esc_attr( heaven11_is_inherit( $field['val'] ) ? '' : $field['val'] ) . '"'
								. ' />';
			} elseif ( 'textarea' == $field['type'] ) {
				// Type 'textarea'
				$output .= '<textarea name="heaven11_options_field_' . esc_attr( $name ) . '">'
								. esc_html( heaven11_is_inherit( $field['val'] ) ? '' : $field['val'] )
							. '</textarea>';
			} elseif ( 'text_editor' == $field['type'] ) {
				// Type 'text_editor'
				$output .= '<input type="hidden" id="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' name="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' value="' . esc_textarea( heaven11_is_inherit( $field['val'] ) ? '' : $field['val'] ) . '"'
								. ' />'
							. heaven11_show_custom_field(
								'heaven11_options_field_' . esc_attr( $name ) . '_tinymce',
								$field,
								heaven11_is_inherit( $field['val'] ) ? '' : $field['val']
							);
			} elseif ( 'select' == $field['type'] ) {
				// Type 'select'
				$field['options'] = apply_filters( 'heaven11_filter_options_get_list_choises', $field['options'], $name );
				$output          .= '<select size="1" name="heaven11_options_field_' . esc_attr( $name ) . '">';
				foreach ( $field['options'] as $k => $v ) {
					$output .= '<option value="' . esc_attr( $k ) . '"' . ( ( '#' . $field['val'] ) == ( '#' . $k ) ? ' selected="selected"' : '' ) . '>' . esc_html( $v ) . '</option>';
				}
				$output .= '</select>';
			} elseif ( in_array( $field['type'], array( 'image', 'media', 'video', 'audio' ) ) ) {
				// Type 'image', 'media', 'video' or 'audio'
				if ( (int) $field['val'] > 0 ) {
					$image        = wp_get_attachment_image_src( $field['val'], 'full' );
					$field['val'] = $image[0];
				}
				$output .= ( ! empty( $field['multiple'] )
							? '<input type="hidden" id="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' name="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' value="' . esc_attr( heaven11_is_inherit( $field['val'] ) ? '' : $field['val'] ) . '"'
								. ' />'
							: '<input type="text" id="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' name="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' value="' . esc_attr( heaven11_is_inherit( $field['val'] ) ? '' : $field['val'] ) . '"'
								. ' />' )
						. heaven11_show_custom_field(
							'heaven11_options_field_' . esc_attr( $name ) . '_button',
							array(
								'type'            => 'mediamanager',
								'multiple'        => ! empty( $field['multiple'] ),
								'data_type'       => $field['type'],
								'linked_field_id' => 'heaven11_options_field_' . esc_attr( $name ),
							),
							heaven11_is_inherit( $field['val'] ) ? '' : $field['val']
						);
			} elseif ( 'color' == $field['type'] ) {
				// Type 'color'
				$output .= '<input type="text" id="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' class="heaven11_color_selector"'
								. ' name="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' value="' . esc_attr( $field['val'] ) . '"'
								. ' />';
			} elseif ( 'icon' == $field['type'] ) {
				// Type 'icon'
				$output .= '<input type="text" id="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' name="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' value="' . esc_attr( heaven11_is_inherit( $field['val'] ) ? '' : $field['val'] ) . '"'
								. ' />'
							. heaven11_show_custom_field(
								'heaven11_options_field_' . esc_attr( $name ) . '_button',
								array(
									'type'   => 'icons',
									'button' => true,
									'icons'  => true,
								),
								heaven11_is_inherit( $field['val'] ) ? '' : $field['val']
							);
			} elseif ( 'checklist' == $field['type'] ) {
				// Type 'checklist'
				$output .= '<input type="hidden" id="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' name="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' value="' . esc_attr( heaven11_is_inherit( $field['val'] ) ? '' : $field['val'] ) . '"'
								. ' />'
							. heaven11_show_custom_field(
								'heaven11_options_field_' . esc_attr( $name ) . '_list',
								$field,
								heaven11_is_inherit( $field['val'] ) ? '' : $field['val']
							);
			} elseif ( 'scheme_editor' == $field['type'] ) {
				// Type 'scheme_editor'
				$storage = heaven11_check_scheme_colors( heaven11_unserialize( $field['val'] ), heaven11_storage_get( 'schemes' ) );
				$field['val'] = serialize( $storage );
				$output .= '<input type="hidden" id="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' name="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' value="' . esc_attr( heaven11_is_inherit( $field['val'] ) ? '' : $field['val'] ) . '"'
								. ' />'
							. heaven11_show_custom_field(
								'heaven11_options_field_' . esc_attr( $name ) . '_scheme',
								$field,
								$storage
							);
			} elseif ( in_array( $field['type'], array( 'slider', 'range' ) ) ) {
				// Type 'slider' || 'range'
				$field['show_value'] = ! isset( $field['show_value'] ) || $field['show_value'];
				$output             .= '<input type="' . ( ! $field['show_value'] ? 'hidden' : 'text' ) . '" id="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' name="heaven11_options_field_' . esc_attr( $name ) . '"'
								. ' value="' . esc_attr( heaven11_is_inherit( $field['val'] ) ? '' : $field['val'] ) . '"'
								. ( $field['show_value'] ? ' class="heaven11_range_slider_value"' : '' )
								. ' />'
							. heaven11_show_custom_field(
								'heaven11_options_field_' . esc_attr( $name ) . '_slider',
								$field,
								heaven11_is_inherit( $field['val'] ) ? '' : $field['val']
							);
			}

			$output .= ( $inherit_allow
							? '<div class="heaven11_options_inherit_cover' . ( ! $inherit_state ? ' heaven11_hidden' : '' ) . '">'
								. '<span class="heaven11_options_inherit_label">' . esc_html__( 'Inherit', 'heaven11' ) . '</span>'
								. '<input type="hidden" name="heaven11_options_inherit_' . esc_attr( $name ) . '"'
										. ' value="' . esc_attr( $inherit_state ? 'inherit' : '' ) . '"'
										. ' />'
								. '</div>'
							: '' )
						. ( $field_data_present ? '</div>' : '' )
						. ( ! empty( $field['override']['desc'] ) || ! empty( $field['desc'] )
							? '<div class="heaven11_options_item_description">'
								. ( ! empty( $field['override']['desc'] )   // param 'desc' already processed with wp_kses()!
										? $field['override']['desc']
										: $field['desc'] )
								. '</div>'
							: '' )
					. ( $field_data_present ? '</div>' : '' )
				. '</div>';
		}
		return $output;
	}
}


// Show theme specific fields
function heaven11_show_custom_field( $id, $field, $value ) {
	$output = '';

	switch ( $field['type'] ) {

		case 'mediamanager':
			wp_enqueue_media();
			$title   = empty( $field['data_type'] ) || 'image' == $field['data_type']
							? esc_html__( 'Choose Image', 'heaven11' )
							: esc_html__( 'Choose Media', 'heaven11' );
			$output .= '<input type="button"'
							. ' id="' . esc_attr( $id ) . '"'
							. ' class="button mediamanager heaven11_media_selector"'
							. '	data-param="' . esc_attr( $id ) . '"'
							. '	data-choose="' . esc_attr( ! empty( $field['multiple'] ) ? esc_html__( 'Choose Images', 'heaven11' ) : $title ) . '"'
							. ' data-update="' . esc_attr( ! empty( $field['multiple'] ) ? esc_html__( 'Add to Gallery', 'heaven11' ) : $title ) . '"'
							. '	data-multiple="' . esc_attr( ! empty( $field['multiple'] ) ? '1' : '0' ) . '"'
							. '	data-type="' . esc_attr( ! empty( $field['data_type'] ) ? $field['data_type'] : 'image' ) . '"'
							. '	data-linked-field="' . esc_attr( $field['linked_field_id'] ) . '"'
							. ' value="'
								. ( ! empty( $field['multiple'] )
										? ( empty( $field['data_type'] ) || 'image' == $field['data_type']
											? esc_html__( 'Add Images', 'heaven11' )
											: esc_html__( 'Add Files', 'heaven11' )
											)
										: esc_html( $title )
									)
								. '"'
							. '>';
			$output .= '<span class="heaven11_options_field_preview">';
			$images  = explode( '|', $value );
			if ( is_array( $images ) ) {
				foreach ( $images as $img ) {
					$output .= $img && ! heaven11_is_inherit( $img )
							? '<span>'
									. ( in_array( heaven11_get_file_ext( $img ), array( 'gif', 'jpg', 'jpeg', 'png' ) )
											? '<img src="' . esc_url( $img ) . '" alt="' . esc_attr__( 'Selected image', 'heaven11' ) . '">'
											: '<a href="' . esc_attr( $img ) . '">' . esc_html( basename( $img ) ) . '</a>'
										)
								. '</span>'
							: '';
				}
			}
			$output .= '</span>';
			break;

		case 'icons':
			$icons_type = ! empty( $field['style'] )
							? $field['style']
							: heaven11_get_theme_setting( 'icons_type' );
			if ( empty( $field['return'] ) ) {
				$field['return'] = 'full';
			}
			$heaven11_icons = heaven11_get_list_icons( $icons_type );
			if ( is_array( $heaven11_icons ) ) {
				if ( ! empty( $field['button'] ) ) {
					$output .= '<span id="' . esc_attr( $id ) . '"'
									. ' class="heaven11_list_icons_selector'
											. ( 'icons' == $icons_type && ! empty( $value ) ? ' ' . esc_attr( $value ) : '' )
											. '"'
									. ' title="' . esc_attr__( 'Select icon', 'heaven11' ) . '"'
									. ' data-style="' . esc_attr( $icons_type ) . '"'
									. ( in_array( $icons_type, array( 'images', 'svg' ) ) && ! empty( $value )
										? ' style="background-image: url(' . esc_url( 'slug' == $field['return'] ? $heaven11_icons[ $value ] : $value ) . ');"'
										: ''
										)
								. '></span>';
				}
				if ( ! empty( $field['icons'] ) ) {
					$output .= '<div class="heaven11_list_icons">'
								. '<input type="text" class="heaven11_list_icons_search" placeholder="' . esc_attr__( 'Search icon ...', 'heaven11' ) . '">';
					foreach ( $heaven11_icons as $slug => $icon ) {
						$output .= '<span class="' . esc_attr( 'icons' == $icons_type ? $icon : $slug )
								. ( ( 'full' == $field['return'] ? $icon : $slug ) == $value ? ' heaven11_list_active' : '' )
								. '"'
								. ' title="' . esc_attr( $slug ) . '"'
								. ' data-icon="' . esc_attr( 'full' == $field['return'] ? $icon : $slug ) . '"'
								. ( in_array( $icons_type, array( 'images', 'svg' ) ) ? ' style="background-image: url(' . esc_url( $icon ) . ');"' : '' )
								. '></span>';
					}
					$output .= '</div>';
				}
			}
			break;

		case 'checklist':
			if ( ! empty( $field['sortable'] ) ) {
				wp_enqueue_script( 'jquery-ui-sortable', false, array( 'jquery', 'jquery-ui-core' ), null, true );
			}
			$output .= '<div class="heaven11_checklist heaven11_checklist_' . esc_attr( $field['dir'] )
						. ( ! empty( $field['sortable'] ) ? ' heaven11_sortable' : '' )
						. '">';
			if ( ! is_array( $value ) ) {
				if ( ! empty( $value ) && ! heaven11_is_inherit( $value ) ) {
					parse_str( str_replace( '|', '&', $value ), $value );
				} else {
					$value = array();
				}
			}
			// Sort options by values order
			if ( ! empty( $field['sortable'] ) && is_array( $value ) ) {
				$field['options'] = heaven11_array_merge( $value, $field['options'] );
			}
			foreach ( $field['options'] as $k => $v ) {
				$output .= '<label class="heaven11_checklist_item_label'
								. ( ! empty( $field['sortable'] ) ? ' heaven11_sortable_item' : '' )
								. '">'
							. '<input type="checkbox" value="1" data-name="' . $k . '"'
								. ( isset( $value[ $k ] ) && 1 == (int) $value[ $k ] ? ' checked="checked"' : '' )
								. ' />'
							. ( substr( $v, 0, 4 ) == 'http' ? '<img src="' . esc_url( $v ) . '">' : esc_html( $v ) )
						. '</label>';
			}
			$output .= '</div>';
			break;

		case 'slider':
		case 'range':
			wp_enqueue_script( 'jquery-ui-slider', false, array( 'jquery', 'jquery-ui-core' ), null, true );
			$is_range   = 'range' == $field['type'];
			$field_min  = ! empty( $field['min'] ) ? $field['min'] : 0;
			$field_max  = ! empty( $field['max'] ) ? $field['max'] : 100;
			$field_step = ! empty( $field['step'] ) ? $field['step'] : 1;
			$field_val  = ! empty( $value )
							? ( $value . ( $is_range && strpos( $value, ',' ) === false ? ',' . $field_max : '' ) )
							: ( $is_range ? $field_min . ',' . $field_max : $field_min );
			$output    .= '<div id="' . esc_attr( $id ) . '"'
							. ' class="heaven11_range_slider"'
							. ' data-range="' . esc_attr( $is_range ? 'true' : 'min' ) . '"'
							. ' data-min="' . esc_attr( $field_min ) . '"'
							. ' data-max="' . esc_attr( $field_max ) . '"'
							. ' data-step="' . esc_attr( $field_step ) . '"'
							. '>'
							. '<span class="heaven11_range_slider_label heaven11_range_slider_label_min">'
								. esc_html( $field_min )
							. '</span>'
							. '<span class="heaven11_range_slider_label heaven11_range_slider_label_max">'
								. esc_html( $field_max )
							. '</span>';
			$values     = explode( ',', $field_val );
			for ( $i = 0; $i < count( $values ); $i++ ) {
				$output .= '<span class="heaven11_range_slider_label heaven11_range_slider_label_cur">'
								. esc_html( $values[ $i ] )
							. '</span>';
			}
			$output .= '</div>';
			break;

		case 'text_editor':
			if ( function_exists( 'wp_enqueue_editor' ) ) {
				wp_enqueue_editor();
			}
			ob_start();
			wp_editor(
				$value, $id, array(
					'default_editor' => 'tmce',
					'wpautop'        => isset( $field['wpautop'] ) ? $field['wpautop'] : false,
					'teeny'          => isset( $field['teeny'] ) ? $field['teeny'] : false,
					'textarea_rows'  => isset( $field['rows'] ) && $field['rows'] > 1 ? $field['rows'] : 10,
					'editor_height'  => 16 * ( isset( $field['rows'] ) && $field['rows'] > 1 ? (int) $field['rows'] : 10 ),
					'tinymce'        => array(
						'resize'             => false,
						'wp_autoresize_on'   => false,
						'add_unload_trigger' => false,
					),
				)
			);
			$editor_html = ob_get_contents();
			ob_end_clean();
			$output .= '<div class="heaven11_text_editor">' . $editor_html . '</div>';
			break;

		case 'scheme_editor':
			if ( ! is_array( $value ) ) {
				break;
			}
			if ( empty( $field['colorpicker'] ) ) {
				$field['colorpicker'] = 'internal';
			}
			$output .= '<div class="heaven11_scheme_editor">';
			// Select scheme
			$output .= '<div class="heaven11_scheme_editor_scheme">'
							. '<select class="heaven11_scheme_editor_selector">';
			foreach ( $value as $scheme => $v ) {
				$output .= '<option value="' . esc_attr( $scheme ) . '">' . esc_html( $v['title'] ) . '</option>';
			}
			$output .= '</select>';
			// Scheme controls
			$output .= '<span class="heaven11_scheme_editor_controls">'
							. '<span class="heaven11_scheme_editor_control heaven11_scheme_editor_control_reset" title="' . esc_attr__( 'Reload scheme', 'heaven11' ) . '"></span>'
							. '<span class="heaven11_scheme_editor_control heaven11_scheme_editor_control_copy" title="' . esc_attr__( 'Duplicate scheme', 'heaven11' ) . '"></span>'
							. '<span class="heaven11_scheme_editor_control heaven11_scheme_editor_control_delete" title="' . esc_attr__( 'Delete scheme', 'heaven11' ) . '"></span>'
						. '</span>'
					. '</div>';
			// Select type
			$output .= '<div class="heaven11_scheme_editor_type">'
							. '<div class="heaven11_scheme_editor_row">'
								. '<span class="heaven11_scheme_editor_row_cell">'
									. esc_html__( 'Editor type', 'heaven11' )
								. '</span>'
								. '<span class="heaven11_scheme_editor_row_cell heaven11_scheme_editor_row_cell_span">'
									. '<label>'
										. '<input name="heaven11_scheme_editor_type" type="radio" value="simple" checked="checked"> '
										. esc_html__( 'Simple', 'heaven11' )
									. '</label>'
									. '<label>'
										. '<input name="heaven11_scheme_editor_type" type="radio" value="advanced"> '
										. esc_html__( 'Advanced', 'heaven11' )
									. '</label>'
								. '</span>'
							. '</div>'
						. '</div>';
			// Colors
			$groups  = heaven11_storage_get( 'scheme_color_groups' );
			$colors  = heaven11_storage_get( 'scheme_color_names' );
			$output .= '<div class="heaven11_scheme_editor_colors">';
			foreach ( $value as $scheme => $v ) {
				$output .= '<div class="heaven11_scheme_editor_header">'
								. '<span class="heaven11_scheme_editor_header_cell"></span>';
				foreach ( $groups as $group_name => $group_data ) {
					$output .= '<span class="heaven11_scheme_editor_header_cell" title="' . esc_attr( $group_data['description'] ) . '">'
								. esc_html( $group_data['title'] )
								. '</span>';
				}
				$output .= '</div>';
				foreach ( $colors as $color_name => $color_data ) {
					$output .= '<div class="heaven11_scheme_editor_row">'
								. '<span class="heaven11_scheme_editor_row_cell" title="' . esc_attr( $color_data['description'] ) . '">'
								. esc_html( $color_data['title'] )
								. '</span>';
					foreach ( $groups as $group_name => $group_data ) {
						$slug    = 'main' == $group_name
									? $color_name
									: str_replace( 'text_', '', "{$group_name}_{$color_name}" );
						$output .= '<span class="heaven11_scheme_editor_row_cell">'
									. ( isset( $v['colors'][ $slug ] )
										? "<input type=\"text\" name=\"{$slug}\" class=\"" . ( 'tiny' == $field['colorpicker'] ? 'tinyColorPicker' : 'iColorPicker' ) . '" value="' . esc_attr( $v['colors'][ $slug ] ) . '">'
										: ''
										)
									. '</span>';
					}
					$output .= '</div>';
				}
				break;
			}
			$output .= '</div>'
					. '</div>';
			break;
	}
	return apply_filters( 'heaven11_filter_show_custom_field', $output, $id, $field, $value );
}


// Refresh data in the linked field
// according the main field value
if ( ! function_exists( 'heaven11_refresh_linked_data' ) ) {
	function heaven11_refresh_linked_data( $value, $linked_name ) {
		if ( 'parent_cat' == $linked_name ) {
			$tax   = heaven11_get_post_type_taxonomy( $value );
			$terms = ! empty( $tax ) ? heaven11_get_list_terms( false, $tax ) : array();
			$terms = heaven11_array_merge( array( 0 => esc_html__( '- Select category -', 'heaven11' ) ), $terms );
			heaven11_storage_set_array2( 'options', $linked_name, 'options', $terms );
		}
	}
}


// AJAX: Refresh data in the linked fields
if ( ! function_exists( 'heaven11_callback_get_linked_data' ) ) {
	add_action( 'wp_ajax_heaven11_get_linked_data', 'heaven11_callback_get_linked_data' );
	function heaven11_callback_get_linked_data() {
		if ( ! wp_verify_nonce( heaven11_get_value_gp( 'nonce' ), admin_url( 'admin-ajax.php' ) ) ) {
			wp_die();
		}
		$chg_name  = wp_kses_data( wp_unslash( $_REQUEST['chg_name'] ) );
		$chg_value = wp_kses_data( wp_unslash( $_REQUEST['chg_value'] ) );
		$response  = array( 'error' => '' );
		if ( 'post_type' == $chg_name ) {
			$tax              = heaven11_get_post_type_taxonomy( $chg_value );
			$terms            = ! empty( $tax ) ? heaven11_get_list_terms( false, $tax ) : array();
			$response['list'] = heaven11_array_merge( array( 0 => esc_html__( '- Select category -', 'heaven11' ) ), $terms );
		}
		echo json_encode( $response );
		wp_die();
	}
}
