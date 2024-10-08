<?php
/**
 * The template's part to display the property's agent or author info and contact form
 *
 * @package WordPress
 * @subpackage ThemeREX Addons
 * @since v1.6.22
 */

$trx_addons_args = get_query_var('trx_addons_args_properties_agent');
$trx_addons_meta = $trx_addons_args['meta'];
$trx_addons_agent = trx_addons_properties_get_agent_data($trx_addons_meta);

// Agent's photo (avatar)
if (!empty($trx_addons_agent['image_id']) || !empty($trx_addons_agent['image'])) {
	?><div class="properties_page_agent_avatar"<?php trx_addons_seo_snippets('image'); ?>><?php
		if (!empty($trx_addons_agent['image_id'])) {
			$trx_addons_agent['image'] = trx_addons_get_attachment_url($trx_addons_agent['image_id'], trx_addons_get_thumb_size('agent'));
		}
		if (!empty($trx_addons_agent['image'])) {
			$attr = trx_addons_getimagesize($trx_addons_agent['image']);
			?><img src="<?php echo esc_url($trx_addons_agent['image']); ?>" alt="<?php esc_attr_e('Property agent', 'heaven11'); ?>"<?php
				if (!empty($attr[3])) echo ' '.trim($attr[3]);
			?>><?php
		}
	?></div><?php
}

// Agent's info
?><div class="properties_page_agent_info"><?php
	?><h6 class="properties_page_agent_info_name"<?php trx_addons_seo_snippets('name'); ?>><?php
		echo esc_html($trx_addons_agent['name']);
	?></h6><?php
	if (!empty($trx_addons_agent['position'])) {
		?><div class="properties_page_agent_info_position"><?php echo esc_html($trx_addons_agent['position']); ?></div><?php
	}
	if (!empty($trx_addons_agent['description'])) {
		?><div class="properties_page_agent_info_description"<?php trx_addons_seo_snippets('description'); ?>><?php
			echo wp_kses(($trx_addons_agent['description']), 'heaven11_kses_content');
		?></div><?php
	}
	if (!empty($trx_addons_agent['phone_mobile']) || !empty($trx_addons_agent['phone_office']) || !empty($trx_addons_agent['phone_fax'])) {
		?><div class="properties_page_agent_info_phones"><?php
			if (!empty($trx_addons_agent['phone_mobile'])) {
				?><a href="<?php echo esc_attr(trx_addons_get_phone_link($trx_addons_agent['phone_mobile'])); ?>" class="properties_page_agent_info_phones_mobile"><?php echo esc_html($trx_addons_agent['phone_mobile']); ?></a> <?php
			}
			if (!empty($trx_addons_agent['phone_office'])) {
				?><a href="<?php echo esc_attr(trx_addons_get_phone_link($trx_addons_agent['phone_office'])); ?>" class="properties_page_agent_info_phones_office"><?php echo esc_html($trx_addons_agent['phone_office']); ?></a> <?php
			}
			if (!empty($trx_addons_agent['phone_fax'])) {
				?><a href="<?php echo esc_attr(trx_addons_get_phone_link($trx_addons_agent['phone_fax'])); ?>" class="properties_page_agent_info_phones_fax"><?php echo esc_html($trx_addons_agent['phone_fax']); ?></a><?php
			}
		?></div><?php
	}
	if (!empty($trx_addons_agent['address'])) {
		?><div class="properties_page_agent_info_address"><?php
			echo esc_html($trx_addons_agent['address']);
		?></div><?php
	}
    if (!empty($trx_addons_agent['posts_link']))
	    echo '<a class="agent_view_all" href="'.esc_url($trx_addons_agent['posts_link']).'">'.esc_attr__('View all my offers', 'heaven11').'</a>';
	$trx_addons_socials = trx_addons_properties_get_agent_socials($trx_addons_agent);
	if (count($trx_addons_socials) > 0) {
		?><div class="properties_page_agent_info_profiles socials_wrap"><?php
			trx_addons_show_layout(trx_addons_get_socials_links_custom($trx_addons_socials));
		?></div><?php
	}
?></div><?php

// Contact form
trx_addons_get_template_part(TRX_ADDONS_PLUGIN_CPT . 'properties/tpl.properties.parts.form.php',
								'trx_addons_args_properties_form',
								array(
									'meta' => $trx_addons_meta,
									'agent' => $trx_addons_agent
								)
							);
