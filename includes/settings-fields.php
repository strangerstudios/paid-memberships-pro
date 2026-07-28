<?php
/**
 * Render PMPro admin settings UI from declarative field arrays.
 *
 * These helpers cover the repeated section, table, row, and control markup used across PMPro admin
 * screens. They are intentionally render-only: each page or gateway still loads, sanitizes, and
 * saves its own values because settings are stored in different places throughout the plugin.
 *
 * Helper-assembled markup is escaped here. String HTML content is passed through wp_kses_post().
 * Callable HTML entries and callback fields are explicit escape boundaries: they are useful for
 * custom markup, but the callable must escape anything it echoes.
 *
 * Pick the smallest layer that fits:
 *   - pmpro_build_settings_input()        - one control, with no <tr> wrapper.
 *   - pmpro_build_settings_field()        - one <tr> in a form-table.
 *   - pmpro_build_settings_fields()       - a list of field rows, with tables opened/closed around
 *                                           runs of fields.
 *   - pmpro_build_settings_section()      - a collapsible .pmpro_section containing a fields list.
 *   - pmpro_build_settings_section_open() - start a section for custom body markup; pair with
 *     pmpro_build_settings_section_close().
 *
 * @since TBD
 *
 * @package Paid_Memberships_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render a single settings field as a `form-table` row (`<tr>`).
 *
 * The field value defaults to `get_option( 'pmpro_' . $name )` for most named controls. Pass an
 * explicit `value` whenever the current value is not stored in that option, or when rendering a
 * checklist (checklists need an explicit array of selected values).
 *
 * @since TBD
 *
 * @param array $field {
 *     Field definition.
 *
 *     @type string          $name            Input name; also used as the id for controls that render one input.
 *                                            Required for normal controls. Optional for html, callback, and composite
 *                                            rows. When set on an html or callback row, the row label points its
 *                                            `for` at this name, so the rendered content must emit an element with
 *                                            that id (e.g. wp_dropdown_pages() does).
 *     @type string          $label           Row label text.
 *     @type string          $type            Field type. Supported values: text, number, email, url, tel, password,
 *                                            currency, color, select, radio, textarea, checkbox, checklist, editor,
 *                                            composite, html, and callback. Default text.
 *     @type mixed           $value           Current value. Most named controls default to
 *                                            get_option( 'pmpro_' . $name ); checklist fields need an explicit
 *                                            array of selected values.
 *     @type string          $description     Help text shown below standard inputs, run through wp_kses_post().
 *                                            html and callback fields are responsible for their own descriptions.
 *     @type string          $class           Input CSS class override. Text-like inputs default to "regular-text",
 *                                            number inputs to "small-text", textareas to "large-text", currency
 *                                            inputs to "regular-text", and color inputs to "pmpro_color_picker".
 *                                            Selects have no default class.
 *     @type bool            $required        Text-like fields only. Adds the HTML required attribute.
 *     @type array           $attrs           Text-like and textarea fields only. Extra HTML attributes as
 *                                            attribute => value. Keys are sanitized, values escaped.
 *     @type string          $row_class       Optional class attribute for the row's <tr>.
 *     @type array           $depends         Optional visibility conditions toggled by pmpro-admin.js. Each condition
 *                                            needs an `id` for the referenced input and either `checked` or `value`.
 *                                            `value` may be a scalar or an array of accepted values. Add `current`
 *                                            to every condition to let PHP derive the initial row visibility before
 *                                            JS runs; `current` is removed from the emitted JSON. When a `value`
 *                                            condition references a checkbox or radio, the JS compares against the
 *                                            input's value attribute while checked and '' while unchecked, so pass
 *                                            `current` with those same semantics. Conditions may reference text,
 *                                            textarea, and select controls as well; those update as the user types.
 *                                            Note that `radio` fields rendered by this helper are not given ids, so a
 *                                            condition cannot target one — give the radio group a hand-rolled input
 *                                            with an id if a row needs to depend on it. The emitted data-pmpro-depends
 *                                            attribute can also be hand-placed on any element (not just rows built
 *                                            here) and pmpro-admin.js will toggle it the same way.
 *     @type bool            $depends_or      Optional. OR the depends conditions instead of AND. Default false.
 *     @type bool            $hidden          Optional initial visibility override. When this key is present, PHP does
 *                                            not derive the initial state from `depends`; true starts hidden, false
 *                                            starts visible.
 *     @type array           $options         value => label map for select, radio, and checklist fields.
 *     @type string          $checkbox_label  Checkbox fields only. Plain-text inline label shown after the checkbox.
 *     @type string          $checkbox_value  Checkbox fields only. Submitted value for legacy options that store
 *                                            something other than "1". When omitted, the checkbox submits "1" and is
 *                                            checked for any truthy current value.
 *     @type bool            $select_all      Checklist fields only. Show delegated "Select: All | None" controls.
 *     @type int             $item_count      Checklist fields only. Item count used to decide whether the box scrolls,
 *                                            mainly for custom `items` renderers that the helper cannot count.
 *     @type callable        $items           Checklist fields only. Custom item renderer; receives $field and echoes
 *                                            escaped checkbox markup. Overrides `options`.
 *     @type array           $editor_settings Editor fields only. Settings passed to wp_editor(). Default
 *                                            array( 'textarea_rows' => 5 ).
 *     @type array           $fields          Composite fields only. Ordered inline controls and literal strings sharing
 *                                            one row. Array items are rendered with pmpro_build_settings_input(); string
 *                                            items are escaped and printed between controls.
 *     @type string|callable $content         html fields only. String content is run through wp_kses_post(); callable
 *                                            content receives $field and must escape its own output.
 *     @type callable        $callback        callback fields only. Receives $field and renders the field cell contents,
 *                                            including any description. The callback must escape its own output.
 * }
 */
function pmpro_build_settings_field( $field ) {
	// A field needs something to render: an input (name), custom content, a callback, composite
	// sub-fields, or checklist items/options.
	if ( empty( $field['name'] ) && empty( $field['callback'] ) && empty( $field['content'] ) && empty( $field['fields'] ) && empty( $field['items'] ) && empty( $field['options'] ) ) {
		return;
	}

	$name      = isset( $field['name'] ) ? $field['name'] : '';
	$type      = isset( $field['type'] ) ? $field['type'] : 'text';
	$label     = isset( $field['label'] ) ? $field['label'] : '';
	$row_class = isset( $field['row_class'] ) ? $field['row_class'] : '';

	// A composite row's label targets its first named sub-input, so the row itself needs no name.
	if ( ! $name && 'composite' === $type && ! empty( $field['fields'] ) ) {
		foreach ( (array) $field['fields'] as $sub_field ) {
			if ( is_array( $sub_field ) && ! empty( $sub_field['name'] ) ) {
				$name = $sub_field['name'];
				break;
			}
		}
	}

	// A row can declare visibility that depends on other inputs. If every condition includes the
	// referenced input's render-time value in `current`, PHP derives the initial row state to avoid
	// a flash before JS runs. The `current` key is stripped before the conditions are emitted.
	$hidden     = ! empty( $field['hidden'] );
	$conditions = array();
	if ( ! empty( $field['depends'] ) ) {
		$condition_results = array();
		$all_have_current  = true;
		foreach ( (array) $field['depends'] as $condition ) {
			if ( array_key_exists( 'current', $condition ) ) {
				if ( isset( $condition['checked'] ) ) {
					$condition_results[] = (bool) $condition['current'] === (bool) $condition['checked'];
				} else {
					$expected_value = isset( $condition['value'] ) ? $condition['value'] : '';
					if ( is_array( $expected_value ) ) {
						$condition_results[] = in_array( (string) $condition['current'], array_map( 'strval', $expected_value ), true );
					} else {
						$condition_results[] = (string) $condition['current'] === (string) $expected_value;
					}
				}
				unset( $condition['current'] );
			} else {
				$all_have_current = false;
			}
			$conditions[] = $condition;
		}
		if ( $all_have_current && ! isset( $field['hidden'] ) && $condition_results ) {
			$met    = ! empty( $field['depends_or'] ) ? in_array( true, $condition_results, true ) : ! in_array( false, $condition_results, true );
			$hidden = ! $met;
		}
	}
	if ( $hidden ) {
		$row_class = trim( $row_class . ' pmpro-hidden' );
	}
	$tr_attrs = '';
	if ( $row_class ) {
		$tr_attrs .= ' class="' . esc_attr( $row_class ) . '"';
	}
	if ( $conditions ) {
		$tr_attrs .= ' data-pmpro-depends="' . esc_attr( wp_json_encode( $conditions ) ) . '"';
		if ( ! empty( $field['depends_or'] ) ) {
			$tr_attrs .= ' data-pmpro-depends-or="1"';
		}
	}
	?>
	<tr<?php echo $tr_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- assembled from individually escaped parts above. ?>>
		<th scope="row" valign="top">
			<?php if ( $name ) { ?>
				<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			<?php } else { ?>
				<label><?php echo esc_html( $label ); ?></label>
			<?php } ?>
		</th>
		<td>
			<?php
			pmpro_build_settings_input( $field );

			// Standard inputs share row-level description handling. html and callback fields own their
			// whole cell and must render any description themselves.
			if ( ! in_array( $type, array( 'html', 'callback' ), true ) && ! empty( $field['description'] ) ) {
				echo '<p class="description">' . wp_kses_post( $field['description'] ) . '</p>';
			}
			?>
		</td>
	</tr>
	<?php
}

/**
 * Echo the control/content for a field definition, without any row chrome or standard description.
 *
 * This is the rendering atom behind pmpro_build_settings_field(). Composite fields also call it
 * for each inline sub-input so those rows reuse the same control markup as standalone rows.
 *
 * @since TBD
 *
 * @param array $field Field definition. See pmpro_build_settings_field() for the supported keys.
 */
function pmpro_build_settings_input( $field ) {
	$name = isset( $field['name'] ) ? $field['name'] : '';
	$type = isset( $field['type'] ) ? $field['type'] : 'text';

	// Only resolve the get_option() default for control types that render a value. callback, html,
	// and composite rows own their own content, and checklists read $field['value'] directly.
	if ( isset( $field['value'] ) ) {
		$value = $field['value'];
	} elseif ( $name && ! in_array( $type, array( 'callback', 'html', 'composite', 'checklist' ), true ) ) {
		$value = get_option( 'pmpro_' . $name );
	} else {
		$value = '';
	}

	switch ( $type ) {
		case 'callback':
			if ( ! empty( $field['callback'] ) && is_callable( $field['callback'] ) ) {
				// Callback fields own the full cell body, including escaping and any description.
				call_user_func( $field['callback'], $field );
			}
			break;

		case 'html':
			$content = isset( $field['content'] ) ? $field['content'] : '';
			if ( ! is_string( $content ) && is_callable( $content ) ) {
				// Callable HTML owns its own escaping. Strings are markup, not callable names.
				call_user_func( $content, $field );
			} else {
				echo wp_kses_post( $content );
			}
			break;

		case 'composite':
			// A sequence of inline controls and literal text sharing one row.
			foreach ( (array) $field['fields'] as $item ) {
				if ( is_string( $item ) ) {
					echo ' ' . esc_html( $item ) . ' ';
				} elseif ( is_array( $item ) ) {
					pmpro_build_settings_input( $item );
				}
			}
			break;

		case 'select':
			$options      = isset( $field['options'] ) ? $field['options'] : array();
			$select_class = isset( $field['class'] ) ? ' class="' . esc_attr( $field['class'] ) . '"' : '';
			echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"' . $select_class . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $select_class escaped above.
			foreach ( $options as $opt_value => $opt_label ) {
				echo '<option value="' . esc_attr( $opt_value ) . '" ' . selected( $value, $opt_value, false ) . '>' . esc_html( $opt_label ) . '</option>';
			}
			echo '</select>';
			break;

		case 'radio':
			// One radio per option, each on its own line. No ids: the th label (if any) has no
			// single input to point at.
			$options = isset( $field['options'] ) ? $field['options'] : array();
			foreach ( $options as $opt_value => $opt_label ) {
				echo '<p><label>';
				echo '<input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $opt_value ) . '"' . checked( (string) $value, (string) $opt_value, false ) . ' /> ';
				echo esc_html( $opt_label );
				echo '</label></p>';
			}
			break;

		case 'textarea':
			$input_class = isset( $field['class'] ) ? $field['class'] : 'large-text';
			echo '<textarea id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" class="' . esc_attr( $input_class ) . '"' . pmpro_build_settings_input_attrs( $field ) . '>' . esc_textarea( $value ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attrs escaped in the helper.
			break;

		case 'checkbox':
			// By default the box submits "1" and is checked for any truthy value. Legacy options
			// that store another value ("yes" etc.) declare it via checkbox_value, and the box is
			// then checked only when the value matches exactly.
			if ( isset( $field['checkbox_value'] ) ) {
				$checkbox_value = (string) $field['checkbox_value'];
				$is_checked     = (string) $value === $checkbox_value;
			} else {
				$checkbox_value = '1';
				$is_checked     = ! empty( $value );
			}
			echo '<input type="checkbox" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $checkbox_value ) . '" ' . checked( $is_checked, true, false ) . ' />';
			if ( ! empty( $field['checkbox_label'] ) ) {
				echo ' <label for="' . esc_attr( $name ) . '">' . esc_html( $field['checkbox_label'] ) . '</label>';
			}
			break;

		case 'checklist':
			// A list of checkboxes ("name[]") in an optionally scrollable box. Select All / None is
			// handled by delegated JS, so each checklist does not need its own inline script.
			$selected = isset( $field['value'] ) ? (array) $field['value'] : array();
			$selected = array_map( 'strval', $selected );
			$options  = isset( $field['options'] ) ? $field['options'] : array();

			// The box scrolls once there are more than 5 items. Custom-item checklists pass
			// item_count so this logic still applies (the renderer can't count custom items).
			$item_count = isset( $field['item_count'] ) ? (int) $field['item_count'] : count( $options );
			$box_class  = 'pmpro_checkbox_box' . ( $item_count > 5 ? ' pmpro_scrollable' : '' );

			echo '<div class="pmpro_checkbox_list">';
			if ( ! empty( $field['select_all'] ) ) {
				echo '<p>' . esc_html__( 'Select:', 'paid-memberships-pro' ) . ' <a href="javascript:void(0);" data-pmpro-check-all>' . esc_html__( 'All', 'paid-memberships-pro' ) . '</a> | <a href="javascript:void(0);" data-pmpro-check-none>' . esc_html__( 'None', 'paid-memberships-pro' ) . '</a></p>';
			}
			echo '<div class="' . esc_attr( $box_class ) . '">';
			if ( ! empty( $field['items'] ) && is_callable( $field['items'] ) ) {
				// Custom item renderers, such as hierarchical term trees, must escape their output.
				call_user_func( $field['items'], $field );
			} else {
				foreach ( $options as $opt_value => $opt_label ) {
					$item_id = $name . '_' . $opt_value;
					echo '<div class="pmpro_clickable">';
					echo '<input type="checkbox" id="' . esc_attr( $item_id ) . '" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( $opt_value ) . '" ' . checked( in_array( (string) $opt_value, $selected, true ), true, false ) . ' />';
					echo '<label for="' . esc_attr( $item_id ) . '">' . esc_html( $opt_label ) . '</label>';
					echo '</div>';
				}
			}
			echo '</div>'; // .pmpro_checkbox_box
			echo '</div>'; // .pmpro_checkbox_list
			break;

		case 'editor':
			// wp_editor() echoes the TinyMCE/Quicktags markup directly; $name is used as the editor id.
			wp_editor( (string) $value, $name, isset( $field['editor_settings'] ) ? $field['editor_settings'] : array( 'textarea_rows' => 5 ) );
			break;

		case 'currency':
			pmpro_build_settings_currency_input( $name, $value, isset( $field['class'] ) ? $field['class'] : 'regular-text' );
			break;

		default:
			// Standard single inputs. Common HTML5 input types are allowed via the type passthrough.
			$allowed_types   = array( 'text', 'number', 'email', 'url', 'tel', 'password', 'color' );
			$default_classes = array(
				'color'  => 'pmpro_color_picker',
				'number' => 'small-text',
			);
			$input_type      = in_array( $type, $allowed_types, true ) ? $type : 'text';
			$required        = ! empty( $field['required'] ) ? ' required' : '';
			$input_class     = isset( $field['class'] ) ? $field['class'] : ( $default_classes[ $type ] ?? 'regular-text' );
			printf(
				'<input type="%1$s" id="%2$s" name="%2$s" value="%3$s"%4$s%5$s%6$s />',
				esc_attr( $input_type ),
				esc_attr( $name ),
				esc_attr( $value ),
				'' !== $input_class ? ' class="' . esc_attr( $input_class ) . '"' : '', // An empty class ('' passed to suppress the default) omits the attribute.
				$required,
				pmpro_build_settings_input_attrs( $field ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized and values escaped in the helper.
			);
			break;
	}
}

/**
 * Build the extra-attribute string for a field's `attrs` map.
 *
 * Keys are sanitized and values escaped, so the returned string is safe to echo into an HTML tag.
 *
 * @since TBD
 *
 * @param array $field The field definition.
 * @return string The attribute string (leading space included), or ''.
 */
function pmpro_build_settings_input_attrs( $field ) {
	$extra_attrs = '';
	if ( ! empty( $field['attrs'] ) && is_array( $field['attrs'] ) ) {
		foreach ( $field['attrs'] as $attr_key => $attr_value ) {
			$extra_attrs .= ' ' . sanitize_key( $attr_key ) . '="' . esc_attr( $attr_value ) . '"';
		}
	}
	return $extra_attrs;
}

/**
 * Echo a currency amount input with the store's currency symbol positioned around it.
 *
 * Used by the `currency` field type and by composite rows that need a price control.
 *
 * @since TBD
 *
 * @param string $name  The input name/id.
 * @param mixed  $value The current price value (formatted for display via pmpro_filter_price_for_text_field()).
 * @param string $class Optional. Input CSS class. Default 'regular-text'.
 */
function pmpro_build_settings_currency_input( $name, $value, $class = 'regular-text' ) {
	global $pmpro_currency_symbol;

	$position = function_exists( 'pmpro_getCurrencyPosition' ) ? pmpro_getCurrencyPosition() : 'left';
	$amount   = function_exists( 'pmpro_filter_price_for_text_field' ) ? pmpro_filter_price_for_text_field( $value ) : $value;

	if ( 'left' === $position ) {
		echo wp_kses_post( $pmpro_currency_symbol ) . ' ';
	}
	printf(
		'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="%3$s" />',
		esc_attr( $name ),
		esc_attr( $amount ),
		esc_attr( $class )
	);
	if ( 'right' === $position ) {
		echo ' ' . wp_kses_post( $pmpro_currency_symbol );
	}
}

/**
 * Render an ordered list of settings fields, auto-managing the form-table boundaries.
 *
 * Most entries are field definitions (see pmpro_build_settings_field() for the keys). Three special
 * entry shapes may be mixed into the list:
 *   - array( 'type' => 'submit' )           The section's submit button, rendered outside any table
 *                                           (a <p class="submit">, not a form-table row). Optional keys:
 *                                           `label` (default 'Save Settings'), `name` (default
 *                                           'savesettings'), and `class` (default 'button button-primary').
 *                                           This is a list-level entry rather than a real field type
 *                                           because the button does not belong in a <tr>; passing it to
 *                                           pmpro_build_settings_field() directly renders nothing.
 *   - array( 'html' => ... )                Content rendered outside any table. String content is
 *                                           run through wp_kses_post(); callable content must escape
 *                                           its own output and is invoked with no arguments (unlike a
 *                                           field-level `content` callable, which receives $field).
 *                                           Use for intro copy, notices, scripts, or markup that
 *                                           manages its own table.
 *   - array( 'hook' => ..., 'args' => ... ) A do_action() extension point rendered outside any table.
 *                                           `hook` is the action name; `args` is the optional array
 *                                           passed to hooked callbacks. Callbacks receive exactly the
 *                                           args provided: when `args` is omitted they are called with
 *                                           zero arguments (unlike do_action( 'hook' ), which passes
 *                                           ''), so supply args — e.g. array( '' ) — if callbacks may
 *                                           declare a required parameter. Note that hooked callbacks
 *                                           render outside any table, so hooks whose callbacks echo
 *                                           <tr> rows need a hand-rolled table via an html entry
 *                                           instead.
 *
 * Consecutive fields are grouped into a single <table class="form-table">. A submit, html, or hook
 * entry closes the current table first, renders outside the table, and lets the next field start a
 * new table. That keeps the list flat while still supporting copy, notices, scripts, hooks, and
 * custom table blocks between normal field rows.
 *
 * @since TBD
 *
 * @param array $fields Ordered array of field definitions and submit/html/hook entries.
 */
function pmpro_build_settings_fields( $fields ) {
	$table_open = false;

	foreach ( (array) $fields as $entry ) {
		$is_submit = isset( $entry['type'] ) && 'submit' === $entry['type'];

		// A submit, html, or hook entry sits outside the table, so close any open table first.
		if ( $is_submit || ! empty( $entry['hook'] ) || array_key_exists( 'html', (array) $entry ) ) {
			if ( $table_open ) {
				echo '</tbody></table>';
				$table_open = false;
			}

			if ( $is_submit ) {
				$submit_name  = isset( $entry['name'] ) ? $entry['name'] : 'savesettings';
				$submit_class = isset( $entry['class'] ) ? $entry['class'] : 'button button-primary';
				$submit_label = isset( $entry['label'] ) ? $entry['label'] : __( 'Save Settings', 'paid-memberships-pro' );
				echo '<p class="submit"><input name="' . esc_attr( $submit_name ) . '" type="submit" class="' . esc_attr( $submit_class ) . '" value="' . esc_attr( $submit_label ) . '" /></p>';
			} elseif ( ! empty( $entry['hook'] ) ) {
				do_action_ref_array( $entry['hook'], isset( $entry['args'] ) ? (array) $entry['args'] : array() );
			} elseif ( ! is_string( $entry['html'] ) && is_callable( $entry['html'] ) ) {
				// Callable HTML owns its own escaping. Strings are markup, not callable names.
				call_user_func( $entry['html'] );
			} else {
				echo wp_kses_post( $entry['html'] );
			}
			continue;
		}

		// A field row. Open a table for this run of fields if one isn't already open.
		if ( ! $table_open ) {
			echo '<table class="form-table"><tbody>';
			$table_open = true;
		}
		pmpro_build_settings_field( $entry );
	}

	// Close the final table if one is still open.
	if ( $table_open ) {
		echo '</tbody></table>';
	}
}

/**
 * Open a collapsible `.pmpro_section` wrapper.
 *
 * Pair with pmpro_build_settings_section_close(). Use this open/close form when the body cannot be
 * expressed as a fields list, or when existing custom markup should stay inline. For normal field
 * rows, intro copy, notices, scripts, and hooks, prefer pmpro_build_settings_section().
 *
 * @since TBD
 *
 * @param array $args {
 *     Section definition.
 *
 *     @type string $id    Optional. The `id` attribute for the section wrapper.
 *     @type string $title The section heading.
 *     @type bool   $open  Optional. Whether the section is expanded by default. Default true.
 * }
 */
function pmpro_build_settings_section_open( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'id'    => '',
			'title' => '',
			'open'  => true,
		)
	);

	$open = ! empty( $args['open'] );
	?>
	<div <?php if ( ! empty( $args['id'] ) ) { echo 'id="' . esc_attr( $args['id'] ) . '" '; } ?>class="pmpro_section" data-visibility="<?php echo $open ? 'shown' : 'hidden'; ?>" data-activated="<?php echo $open ? 'true' : 'false'; ?>">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="<?php echo $open ? 'true' : 'false'; ?>">
				<span class="dashicons dashicons-arrow-<?php echo $open ? 'up' : 'down'; ?>-alt2"></span>
				<?php echo esc_html( $args['title'] ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside"<?php echo $open ? '' : ' style="display: none"'; ?>>
	<?php
}

/**
 * Close a settings section opened with pmpro_build_settings_section_open().
 *
 * @since TBD
 */
function pmpro_build_settings_section_close() {
	?>
		</div>
	</div>
	<?php
}

/**
 * Render a collapsible `.pmpro_section` around a fields list.
 *
 * This is the common all-in-one form. The fields list may include normal rows plus html and hook
 * entries between rows; see pmpro_build_settings_fields().
 *
 * @since TBD
 *
 * @param array $args {
 *     Section definition.
 *
 *     @type string $id     Optional. The `id` attribute for the section wrapper.
 *     @type string $title  The section heading.
 *     @type bool   $open   Optional. Whether the section is expanded by default. Default true.
 *     @type array  $fields Ordered field definitions and html/hook entries. See pmpro_build_settings_fields().
 * }
 */
function pmpro_build_settings_section( $args ) {
	pmpro_build_settings_section_open( $args );
	pmpro_build_settings_fields( isset( $args['fields'] ) ? $args['fields'] : array() );
	pmpro_build_settings_section_close();
}

