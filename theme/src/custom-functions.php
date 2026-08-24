<?php

/**
 * Placeholder content for ACF block inserter previews and empty editor previews.
 */

function timberland_field_is_empty( $value ) {
	if ( $value === null || $value === false || $value === '' ) {
		return true;
	}

	if ( is_array( $value ) ) {
		return count( array_filter( $value, 'timberland_field_is_empty' ) ) === 0;
	}

	return false;
}

function timberland_get_block_field_group( $block_name ) {
	if ( ! function_exists( 'acf_get_field_groups' ) ) {
		return null;
	}

	foreach ( acf_get_field_groups() as $group ) {
		if ( empty( $group['location'] ) ) {
			continue;
		}

		foreach ( $group['location'] as $rules ) {
			foreach ( $rules as $rule ) {
				if ( ( $rule['param'] ?? '' ) === 'block' && ( $rule['value'] ?? '' ) === $block_name ) {
					return $group;
				}
			}
		}
	}

	return null;
}

function timberland_placeholder_text_for_label( $label ) {
	$label = strtolower( (string) $label );

	if ( str_contains( $label, 'heading' ) || str_contains( $label, 'title' ) ) {
		return 'Sample heading';
	}

	if ( str_contains( $label, 'eyebrow' ) ) {
		return 'Eyebrow';
	}

	if ( str_contains( $label, 'tagline' ) ) {
		return 'Improving experiences through experimentation.';
	}

	if ( str_contains( $label, 'capability' ) || str_contains( $label, 'tag' ) ) {
		return 'A/B Testing';
	}

	if ( str_contains( $label, 'label' ) ) {
		return 'Label';
	}

	if ( str_contains( $label, 'name' ) ) {
		return 'Jane Smith';
	}

	if ( str_contains( $label, 'role' ) || str_contains( $label, 'company' ) ) {
		return 'Company name';
	}

	if ( str_contains( $label, 'date' ) || str_contains( $label, 'year' ) ) {
		return '2020–2024';
	}

	if ( str_contains( $label, 'value' ) || str_contains( $label, 'stat' ) ) {
		return '120+';
	}

	if ( str_contains( $label, 'email' ) ) {
		return 'hello@example.com';
	}

	if ( str_contains( $label, 'phone' ) ) {
		return '+1 555 0100';
	}

	return 'Lorem ipsum';
}

function timberland_placeholder_for_field( $field ) {
	$type  = $field['type'] ?? 'text';
	$label = $field['label'] ?? '';

	switch ( $type ) {
		case 'text':
			return timberland_placeholder_text_for_label( $label );

		case 'textarea':
			return 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.';

		case 'wysiwyg':
			return '<h3>Service Overview:</h3><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><h3>What\'s Included:</h3><h4>Discovery &amp; Strategy:</h4><ul><li>Stakeholder interviews</li><li>User research</li><li>Technical audit</li></ul>';

		case 'email':
			return 'hello@example.com';

		case 'url':
			return '#';

		case 'link':
			return array(
				'url'    => '#',
				'title'  => timberland_placeholder_text_for_label( $label ),
				'target' => '',
			);

		case 'number':
		case 'range':
			return 42;

		case 'true_false':
			return 0;

		case 'select':
		case 'radio':
			if ( ! empty( $field['choices'] ) ) {
				$choices = array_keys( $field['choices'] );
				return $choices[0];
			}
			return '';

		case 'repeater':
			$row_count = max( (int) ( $field['min'] ?? 0 ), 2 );
			$row_count = min( $row_count, 3 );
			$rows      = array();

			for ( $i = 0; $i < $row_count; $i++ ) {
				$row = array();
				foreach ( (array) ( $field['sub_fields'] ?? array() ) as $sub_field ) {
					$row[ $sub_field['name'] ] = timberland_placeholder_for_field( $sub_field );
				}
				$rows[] = $row;
			}

			return $rows;

		case 'group':
			$group = array();
			foreach ( (array) ( $field['sub_fields'] ?? array() ) as $sub_field ) {
				$group[ $sub_field['name'] ] = timberland_placeholder_for_field( $sub_field );
			}
			return $group;

		case 'image':
		case 'file':
		case 'gallery':
		case 'post_object':
		case 'relationship':
		case 'taxonomy':
		case 'user':
			return null;

		default:
			return timberland_placeholder_text_for_label( $label );
	}
}

function timberland_apply_field_placeholders( array $fields, array $definitions ) {
	foreach ( $definitions as $field ) {
		$name = $field['name'] ?? '';

		if ( $name === '' ) {
			continue;
		}

		if ( ! timberland_field_is_empty( $fields[ $name ] ?? null ) ) {
			if ( ( $field['type'] ?? '' ) === 'repeater' && is_array( $fields[ $name ] ) ) {
				foreach ( $fields[ $name ] as $index => $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					foreach ( (array) ( $field['sub_fields'] ?? array() ) as $sub_field ) {
						$sub_name = $sub_field['name'] ?? '';
						if ( $sub_name !== '' && timberland_field_is_empty( $row[ $sub_name ] ?? null ) ) {
							$fields[ $name ][ $index ][ $sub_name ] = timberland_placeholder_for_field( $sub_field );
						}
					}
				}
			}
			continue;
		}

		$fields[ $name ] = timberland_placeholder_for_field( $field );
	}

	return $fields;
}

function timberland_get_block_fields_with_placeholders( $block, $fields, $is_preview ) {
	if ( ! $is_preview || ! function_exists( 'acf_get_fields' ) ) {
		return $fields;
	}

	$group = timberland_get_block_field_group( $block['name'] ?? '' );
	if ( ! $group ) {
		return $fields;
	}

	$definitions = acf_get_fields( $group['key'] );
	if ( ! $definitions ) {
		return $fields;
	}

	return timberland_apply_field_placeholders( (array) $fields, $definitions );
}

/**
 * Remove editor-injected class attributes from WYSIWYG HTML so theme
 * .rich-text-content styles apply consistently.
 */
function timberland_strip_wp_classes( $html ) {
	if ( ! is_string( $html ) || $html === '' ) {
		return $html;
	}

	$processor = new WP_HTML_Tag_Processor( $html );

	while ( $processor->next_tag() ) {
		$is_component_section = 'SECTION' === $processor->get_tag()
			&& null !== $processor->get_attribute( 'data-component' );

		if ( ! $is_component_section ) {
			$processor->remove_attribute( 'class' );
		}
	}

	return $processor->get_updated_html();
}
