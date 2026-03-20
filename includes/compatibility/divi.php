<?php

class PMProDivi {

	function __construct() {

		$is_d5 = function_exists( 'et_builder_d5_enabled' ) && et_builder_d5_enabled();

		if ( $is_d5 ) {

			// Divi 5: use the native Display Conditions system for hide/show logic.
			// The condition name 'pmproMembershipLevel' is evaluated server-side here.
			add_filter( 'divi_module_options_conditions_is_custom_condition_true', array( __CLASS__, 'd5_condition_check' ), 10, 4 );

			// Divi 5: intercept module wrapper output to inject the no-access message.
			// This runs before the displayable check so we can replace the content rather
			// than return an empty string when showNoAccessMessage is enabled.
			add_filter( 'divi_module_wrapper_render', array( __CLASS__, 'd5_no_access_message' ), 1, 2 );

			// Divi 5: enqueue the VB conditions UI script and register the membership
			// levels REST endpoint used by that script.
			add_action( 'divi_visual_builder_assets_before_enqueue_scripts', array( __CLASS__, 'd5_enqueue_vb_script' ) );
			add_action( 'rest_api_init', array( __CLASS__, 'd5_register_rest_routes' ) );

			// Divi 5: D4 → D5 migration. Mark our D4 attrs as legacy so they don't
			// get preserved as unknownAttributes (which would keep the module as a
			// shortcode-module fallback). Then convert them to native D5 conditions.
			add_filter( 'divi.conversion.legacyAttributeNames', array( __CLASS__, 'd5_legacy_attribute_names' ) );
			add_filter( 'divi.conversion.postConvertAttrs', array( __CLASS__, 'd5_migrate_d4_attrs' ), 10, 4 );

		}

		// Always register the D4 hooks for two reasons:
		//   1. When Divi 4 is the active builder (! $is_d5).
		//   2. When Divi 5 is active but a page contains migrated D4 content — Divi 5 wraps
		//      rows/sections that have unknown third-party attributes (like paid-memberships-pro)
		//      in a `divi/shortcode-module` fallback block which bootstraps the full D4 shortcode
		//      engine and fires et_pb_module_content. Without these hooks that legacy content
		//      would lose its membership restriction silently.
		if ( empty( $_GET['page'] ) || 'et_divi_role_editor' !== $_GET['page'] ) {
			// UI settings hooks are only useful in Divi 4 mode.
			if ( ! $is_d5 ) {
				add_filter( 'et_builder_get_parent_modules', array( __CLASS__, 'toggle' ) );
				add_filter( 'et_pb_all_fields_unprocessed_et_pb_row', array( __CLASS__, 'row_settings' ) );
				add_filter( 'et_pb_all_fields_unprocessed_et_pb_section', array( __CLASS__, 'row_settings' ) );
			}

			// Content restriction must run in both modes.
			add_filter( 'et_pb_module_content', array( __CLASS__, 'restrict_content' ), 10, 4 );
		}

		add_action( 'pmpro_element_class', array( __CLASS__, 'pmpro_element_class' ), 10, 2 );
	}

	// -------------------------------------------------------------------------
	// Divi 5 methods
	// -------------------------------------------------------------------------

	/**
	 * Evaluate the custom 'pmproMembershipLevel' condition for the Divi 5
	 * Display Conditions system.
	 *
	 * Expected conditionSettings keys:
	 *   levelIds           string  Comma-separated membership level IDs, e.g. "1,2,3".
	 *   displayRule        string  'hasMembership' (default) or 'doesNotHaveMembership'.
	 *   showNoAccessMessage string 'on' | 'off'. When 'on', this filter returns true so
	 *                              the module still renders and d5_no_access_message() can
	 *                              swap the output for the no-access notice.
	 *
	 * Hooked into divi_module_options_conditions_is_custom_condition_true.
	 *
	 * @since TBD
	 *
	 * @param bool|null $is_condition_true  Null if not yet handled, bool if handled.
	 * @param string    $condition_name     The condition identifier.
	 * @param array     $condition_settings The condition configuration.
	 * @param string    $condition_id       The unique condition instance ID.
	 *
	 * @return bool|null
	 */
	public static function d5_condition_check( $is_condition_true, $condition_name, $condition_settings, $condition_id ) {

		if ( 'pmproMembershipLevel' !== $condition_name ) {
			return $is_condition_true;
		}

		$level_ids   = isset( $condition_settings['levelIds'] ) ? trim( $condition_settings['levelIds'] ) : '';
		$display_rule = isset( $condition_settings['displayRule'] ) ? $condition_settings['displayRule'] : 'hasMembership';
		$show_message = isset( $condition_settings['showNoAccessMessage'] ) ? $condition_settings['showNoAccessMessage'] : 'off';

		if ( empty( $level_ids ) || '0' === $level_ids ) {
			return $is_condition_true;
		}

		$levels = array_filter( array_map( 'trim', explode( ',', $level_ids ) ) );

		if ( empty( $levels ) ) {
			return $is_condition_true;
		}

		$has_level     = pmpro_hasMembershipLevel( $levels );
		$should_display = ( 'hasMembership' === $display_rule ) ? $has_level : ! $has_level;

		// When a no-access message is requested we must not return false here — if we did
		// Divi would skip the render callback entirely and d5_no_access_message() would
		// never get a chance to swap the output.  Return true so the module renders, then
		// let d5_no_access_message() replace the HTML with the notice.
		if ( ! $should_display && 'on' === $show_message ) {
			return true;
		}

		return $should_display;
	}

	/**
	 * Replace a restricted module's rendered output with the PMPro no-access message.
	 *
	 * This only fires when a 'pmproMembershipLevel' condition is present with
	 * showNoAccessMessage = 'on' and the current user lacks the required level.
	 *
	 * Applies to divi/row and divi/section only.
	 *
	 * Hooked into divi_module_wrapper_render at priority 1 so it runs before any
	 * other modifications to the wrapper output.
	 *
	 * @since TBD
	 *
	 * @param string $output The rendered module HTML.
	 * @param array  $args   Filter arguments including 'name' and 'attrs'.
	 *
	 * @return string
	 */
	public static function d5_no_access_message( $output, $args ) {

		$name = isset( $args['name'] ) ? $args['name'] : '';

		if ( ! in_array( $name, array( 'divi/row', 'divi/section' ), true ) ) {
			return $output;
		}

		$conditions = isset( $args['attrs']['module']['decoration']['conditions']['desktop']['value'] )
			? $args['attrs']['module']['decoration']['conditions']['desktop']['value']
			: array();

		if ( empty( $conditions ) ) {
			return $output;
		}

		foreach ( $conditions as $condition ) {

			if ( 'pmproMembershipLevel' !== ( isset( $condition['conditionName'] ) ? $condition['conditionName'] : '' ) ) {
				continue;
			}

			$settings     = isset( $condition['conditionSettings'] ) ? $condition['conditionSettings'] : array();
			$show_message = isset( $settings['showNoAccessMessage'] ) ? $settings['showNoAccessMessage'] : 'off';

			if ( 'on' !== $show_message ) {
				continue;
			}

			$level_ids    = isset( $settings['levelIds'] ) ? trim( $settings['levelIds'] ) : '';
			$display_rule = isset( $settings['displayRule'] ) ? $settings['displayRule'] : 'hasMembership';

			if ( empty( $level_ids ) || '0' === $level_ids ) {
				continue;
			}

			$levels = array_filter( array_map( 'trim', explode( ',', $level_ids ) ) );

			if ( empty( $levels ) ) {
				continue;
			}

			$has_level      = pmpro_hasMembershipLevel( $levels );
			$should_display = ( 'hasMembership' === $display_rule ) ? $has_level : ! $has_level;

			if ( ! $should_display ) {
				return pmpro_get_no_access_message( null, $levels );
			}
		}

		return $output;
	}

	/**
	 * Enqueue the Visual Builder JavaScript that registers the PMPro condition
	 * type in the Divi 5 conditions panel.
	 *
	 * Hooked into divi_visual_builder_assets_before_enqueue_scripts.
	 *
	 * @since TBD
	 */
	public static function d5_enqueue_vb_script() {
		wp_enqueue_script(
			'pmpro-divi-vb',
			plugins_url( 'js/pmpro-divi-5.js', PMPRO_BASE_FILE ),
			array( 'divi-hooks', 'divi-vendor-react', 'divi-vendor-wp-hooks', 'divi-vendor-wp-i18n', 'divi-field-library', 'divi-modal' ),
			PMPRO_VERSION,
			true
		);
	}

	/**
	 * Register the REST endpoint that returns available PMPro membership levels.
	 *
	 * Endpoint: GET /divi/v1/pmpro/membership-levels
	 * Response: [ { label: 'Gold', value: '1' }, … ]
	 *
	 * Hooked into rest_api_init.
	 *
	 * @since TBD
	 */
	public static function d5_register_rest_routes() {
		register_rest_route(
			'divi/v1',
			'/pmpro/membership-levels',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'd5_rest_membership_levels' ),
				'permission_callback' => array( __CLASS__, 'd5_rest_permission' ),
			)
		);
	}

	/**
	 * REST callback: return all active PMPro membership levels.
	 *
	 * @since TBD
	 *
	 * @return \WP_REST_Response
	 */
	public static function d5_rest_membership_levels() {
		$data   = array();
		$levels = pmpro_getAllLevels();

		if ( ! empty( $levels ) ) {
			foreach ( $levels as $level ) {
				$data[] = array(
					'label' => esc_html( $level->name ),
					'value' => (string) $level->id,
				);
			}
		}

		return rest_ensure_response( $data );
	}

	/**
	 * REST permission callback: only VB-capable users may fetch levels.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public static function d5_rest_permission() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * D4 → D5 migration: mark PMPro D4 attributes as legacy so they are not
	 * preserved as unknownAttributes during conversion.
	 *
	 * Without this, Divi 5 sees 'paid-memberships-pro' as an unrecognised
	 * attribute and wraps the whole row/section in a shortcode-module fallback,
	 * making it uneditable in the D5 Visual Builder.
	 *
	 * Hooked into divi.conversion.legacyAttributeNames.
	 *
	 * @since TBD
	 *
	 * @param array $attrs Existing list of legacy attribute names.
	 *
	 * @return array
	 */
	public static function d5_legacy_attribute_names( $attrs ) {
		$attrs[] = 'paid-memberships-pro';
		$attrs[] = 'pmpro_show_no_access_message';
		return $attrs;
	}

	/**
	 * D4 → D5 migration: convert PMPro D4 row/section attributes to native
	 * D5 Display Conditions format.
	 *
	 * Reads the old 'paid-memberships-pro' and 'pmpro_show_no_access_message'
	 * D4 attributes and injects an equivalent pmproMembershipLevel condition
	 * into the converted D5 attribute tree.
	 *
	 * Hooked into divi.conversion.postConvertAttrs at priority 10.
	 *
	 * @since TBD
	 *
	 * @param array  $converted  The converted D5 attribute array.
	 * @param string $module     Module name, e.g. 'divi/row' or 'divi/section'.
	 * @param array  $attrs      Original D4 attribute array.
	 * @param bool   $is_preset  Whether this is a preset conversion.
	 *
	 * @return array
	 */
	public static function d5_migrate_d4_attrs( $converted, $module, $attrs, $is_preset ) {

		if ( ! in_array( $module, array( 'divi/row', 'divi/section' ), true ) ) {
			return $converted;
		}

		$level_ids = isset( $attrs['paid-memberships-pro'] ) ? trim( $attrs['paid-memberships-pro'] ) : '';

		if ( empty( $level_ids ) || '0' === $level_ids ) {
			return $converted;
		}

		$show_message = isset( $attrs['pmpro_show_no_access_message'] ) ? $attrs['pmpro_show_no_access_message'] : 'off';

		$condition = array(
			'id'                => wp_generate_uuid4(),
			'conditionName'     => 'pmproMembershipLevel',
			'conditionSettings' => array(
				'levelIds'            => $level_ids,
				'displayRule'         => 'hasMembership',
				'showNoAccessMessage' => $show_message,
				'enableCondition'     => 'on',
			),
			'operator'          => 'OR',
		);

		// Merge with any existing D5 conditions rather than overwriting them.
		$existing = isset( $converted['module']['decoration']['conditions']['desktop']['value'] )
			? $converted['module']['decoration']['conditions']['desktop']['value']
			: array();

		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		$existing[] = $condition;

		$converted['module']['decoration']['conditions']['desktop']['value'] = $existing;

		return $converted;
	}

	// -------------------------------------------------------------------------
	// Divi 4 methods (unchanged)
	// -------------------------------------------------------------------------

	public static function toggle( $modules ) {

		if ( isset( $modules['et_pb_row'] ) && is_object( $modules['et_pb_row'] ) ) {
			$modules['et_pb_row']->settings_modal_toggles['custom_css']['toggles']['paid-memberships-pro'] = __( 'Paid Memberships Pro', 'paid-memberships-pro' );
		}

		if ( isset( $modules['et_pb_section'] ) && is_object( $modules['et_pb_section'] ) ) {
			$modules['et_pb_section']->settings_modal_toggles['custom_css']['toggles']['paid-memberships-pro'] = __( 'Paid Memberships Pro', 'paid-memberships-pro' );
		}

		return $modules;
	}

	public static function row_settings( $settings ) {

		$settings['paid-memberships-pro'] = array(
			'tab_slug'        => 'custom_css',
			'label'           => __( 'Restrict Row by Level', 'paid-memberships-pro' ),
			'description'     => __( 'Enter comma-separated level IDs.', 'paid-memberships-pro' ),
			'type'            => 'text',
			'default'         => '',
			'option_category' => 'configuration',
			'toggle_slug'     => 'paid-memberships-pro',
		);

		$settings['pmpro_show_no_access_message'] = array(
			'tab_slug'    => 'custom_css',
			'label'       => __( 'Show no access message', 'paid-memberships-pro' ),
			'description' => __( 'Displays a no access message to non-members.', 'paid-memberships-pro' ),
			'type'        => 'yes_no_button',
			'options'     => array(
				'off' => __( 'No', 'paid-memberships-pro' ),
				'on'  => __( 'Yes', 'paid-memberships-pro' ),
			),
			'toggle_slug' => 'paid-memberships-pro',
		);

		return $settings;
	}

	public static function restrict_content( $output, $props, $attrs, $slug ) {

		if ( et_fb_is_enabled() ) {
			return $output;
		}

		if ( ! isset( $props['paid-memberships-pro'] ) ) {
			return $output;
		}

		$level = $props['paid-memberships-pro'];

		if ( empty( trim( $level ) ) || trim( $level ) === '0' ) {
			return $output;
		}

		if ( strpos( $level, ',' ) ) {
			$levels = explode( ',', $level );
		} else {
			$levels = array( $level );
		}

		if ( pmpro_hasMembershipLevel( $levels ) ) {
			return $output;
		} else {
			if ( ! empty( $props['pmpro_show_no_access_message'] ) && 'on' === $props['pmpro_show_no_access_message'] ) {
				return pmpro_get_no_access_message( null, $levels );
			} else {
				return '';
			}
		}
	}

	// -------------------------------------------------------------------------
	// Shared
	// -------------------------------------------------------------------------

	/**
	 * Filter the element classes added to the no_access messages for improved
	 * appearance in Divi (both v4 and v5).
	 *
	 * Hooked into pmpro_element_class.
	 *
	 * @since 2.8.2
	 */
	public static function pmpro_element_class( $class, $element ) {
		if ( in_array( 'pmpro_content_message', $class ) ) {
			$class[] = 'et_pb_row';
		}
		return $class;
	}
}
new PMProDivi();
