<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Chips_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Atomic_Dependency_Manager;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Array_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

class PMPro_Elementor_Content_Restriction extends PMPro_Elementor {
	private $no_access_messages = array();

	/**
	 * Cached list of supported atomic container element types, resolved once per request.
	 *
	 * @var array|null
	 */
	private $atomic_container_types = null;

	protected function content_restriction() {
		// Setup controls
		$this->register_controls();
		add_filter( 'elementor/atomic-widgets/props-schema', array( $this, 'add_atomic_props_schema' ) );
		add_filter( 'elementor/atomic-widgets/controls', array( $this, 'add_atomic_controls' ), 10, 2 );

		// Filter elementor render_content hook
		add_action( 'elementor/widget/render_content', array( $this, 'pmpro_elementor_render_content' ), 10, 2 );
		// Run our should_render checks late (priority 9999) so they can see decisions made by other
		// should_render filters (e.g. display-condition plugins). This lets pmpro_elementor_should_render()
		// stand down — and discard any buffered no-access message — when something else has already
		// hidden the element.
		add_action( 'elementor/frontend/section/should_render', array( $this, 'pmpro_elementor_should_render' ), 9999, 2 );
		add_action( 'elementor/frontend/container/should_render', array( $this, 'pmpro_elementor_should_render' ), 9999, 2 );
		add_action( 'elementor/frontend/before_render', array( $this, 'pmpro_elementor_before_render' ), 0 );
		add_action( 'elementor/frontend/after_render', array( $this, 'pmpro_elementor_after_render' ), PHP_INT_MAX );

		// Register the should_render filter for each supported Elementor v4 (atomic) container type.
		// The same list gates which elements display the restriction controls (see add_atomic_controls()),
		// keeping the editor UI and the frontend enforcement in sync. Note this list is read once here at
		// setup time, so the pmpro_elementor_atomic_element_types filter must be added early (by the time
		// this runs on plugins_loaded) for a custom type to get both its controls and its enforcement.
		// Atomic widgets are handled separately via the render_content filter above, so they are not listed here.
		foreach ( $this->get_atomic_container_types() as $element_type ) {
			add_filter( 'elementor/frontend/' . $element_type . '/should_render', array( $this, 'pmpro_elementor_should_render' ), 9999, 2 );
		}

		// Migrate settings from old restriction method (pmpro_require_membership) to new method (pmpro_enable).
		add_action( 'wp', array( $this, 'migrate_settings' ) ); // Migrate on frontend (also runs when editing but too late).
		add_action( 'elementor/editor/init', array( $this, 'migrate_settings' ) ); // Migrate on editor load.

	}

	// Register controls to sections and widgets
	protected function register_controls() {
		foreach( $this->locations as $where ) {
            add_action('elementor/element/'.$where['element'].'/'.$this->section_name.'/before_section_end', array( $this, 'add_controls' ), 10, 2 );
		}
	}

	/**
	 * Get the Elementor v4 (atomic) container element types that support PMPro content restriction.
	 *
	 * This single list drives both the frontend should_render enforcement (see content_restriction())
	 * and the editor restriction controls (see add_atomic_controls()), keeping the two in sync so we
	 * never display a restriction toggle for a container that wouldn't actually be enforced.
	 *
	 * Structural sub-elements (individual tabs, tab menus, tab content areas, form success/error
	 * messages, etc.) are intentionally excluded: hiding them in isolation breaks their parent widget.
	 * Atomic widgets are handled via the render_content filter and are not included here.
	 *
	 * @since TBD
	 *
	 * @return array Array of atomic container element type names.
	 */
	private function get_atomic_container_types() {
		// Resolve once and cache for the rest of the request. The first call happens at setup time in
		// content_restriction() (where the should_render hooks are registered), and add_atomic_controls()
		// reuses that same snapshot. This guarantees the editor UI and the frontend enforcement always
		// use an identical list, even if the pmpro_elementor_atomic_element_types filter is changed later.
		if ( null === $this->atomic_container_types ) {
			$this->atomic_container_types = apply_filters(
				'pmpro_elementor_atomic_element_types',
				array( 'e-div-block', 'e-flexbox', 'e-tabs', 'e-form' )
			);
		}

		return $this->atomic_container_types;
	}

	/**
	 * Whether the Elementor v4 (atomic widgets) APIs this integration relies on are available.
	 *
	 * Both add_atomic_props_schema() (which registers the settings) and add_atomic_controls() (which
	 * renders the editor UI for those settings) are gated on this single check so that the prop-type
	 * classes and the control classes are always verified together. If only one set were present we
	 * could end up binding controls to props that were never registered.
	 *
	 * @since TBD
	 *
	 * @return bool True if all required Elementor v4 classes exist.
	 */
	private function atomic_widgets_available() {
		return class_exists( Boolean_Prop_Type::class )
			&& class_exists( String_Prop_Type::class )
			&& class_exists( String_Array_Prop_Type::class )
			&& class_exists( Atomic_Dependency_Manager::class )
			&& class_exists( Section::class )
			&& class_exists( Switch_Control::class )
			&& class_exists( Select_Control::class )
			&& class_exists( Chips_Control::class );
	}

	/**
	 * Build the pmpro_apply_block_visibility() parameters from an element's resolved settings.
	 *
	 * Centralizes the normalization so the should_render, before_render, and render_content paths
	 * always agree (they previously each built this array by hand and could drift). Two intentional
	 * rules live here:
	 *
	 * - Membership levels are only forwarded for the "specific" audience. This prevents a stale
	 *   pmpro_levels value (e.g. chosen under "Specific Membership Levels" and then left behind after
	 *   switching the audience back to "All Members", where the field is hidden but the value is kept)
	 *   from being treated as a legacy specific-level restriction by pmpro_apply_block_visibility().
	 * - The "no access" message only applies when showing content to members, never when hiding
	 *   content from them, so show_noaccess is forced off whenever the rule is inverted.
	 *
	 * @since TBD
	 *
	 * @param array $settings The element's resolved settings (atomic get_atomic_settings() or classic get_active_settings()).
	 * @return array Parameters for pmpro_apply_block_visibility().
	 */
	private function build_visibility_params( $settings ) {
		$segment = ! empty( $settings['pmpro_segment'] ) ? $settings['pmpro_segment'] : 'all';
		$invert_restrictions = ! empty( $settings['pmpro_invert_restrictions'] ) ? filter_var( $settings['pmpro_invert_restrictions'], FILTER_VALIDATE_BOOLEAN ) : false;
		$show_noaccess = ! empty( $settings['pmpro_show_noaccess'] ) ? filter_var( $settings['pmpro_show_noaccess'], FILTER_VALIDATE_BOOLEAN ) : false;

		return array(
			'segment'             => $segment,
			'levels'              => ( 'specific' === $segment && ! empty( $settings['pmpro_levels'] ) ) ? array_map( 'strval', (array) $settings['pmpro_levels'] ) : array(),
			'invert_restrictions' => $invert_restrictions,
			'show_noaccess'       => $show_noaccess && ! $invert_restrictions,
		);
	}

	/**
	 * Add PMPro props to Elementor v4 atomic element schemas.
	 *
	 * @since TBD
	 *
	 * @param array $schema The atomic element props schema.
	 * @return array
	 */
	public function add_atomic_props_schema( $schema ) {
		if ( ! $this->atomic_widgets_available() ) {
			return $schema;
		}

		if ( isset( $schema['pmpro_enable'] ) ) {
			return $schema;
		}

		$enabled_dependency = Atomic_Dependency_Manager::make()
			->where(
				array(
					'operator' => 'eq',
					'path'     => array( 'pmpro_enable' ),
					'value'    => true,
					'effect'   => 'hide',
				)
			)
			->get();

		$specific_levels_dependency = Atomic_Dependency_Manager::make( Atomic_Dependency_Manager::RELATION_AND )
			->where(
				array(
					'operator' => 'eq',
					'path'     => array( 'pmpro_enable' ),
					'value'    => true,
					'effect'   => 'hide',
				)
			)
			->where(
				array(
					'operator' => 'eq',
					'path'     => array( 'pmpro_segment' ),
					'value'    => 'specific',
					'effect'   => 'hide',
				)
			)
			->get();

		$no_access_dependency = Atomic_Dependency_Manager::make( Atomic_Dependency_Manager::RELATION_AND )
			->where(
				array(
					'operator' => 'eq',
					'path'     => array( 'pmpro_enable' ),
					'value'    => true,
					'effect'   => 'hide',
				)
			)
			->where(
				array(
					'operator' => 'eq',
					'path'     => array( 'pmpro_invert_restrictions' ),
					'value'    => '0',
					'effect'   => 'hide',
				)
			)
			->get();

		$schema['pmpro_enable'] = Boolean_Prop_Type::make()
			->default( false );
		$schema['pmpro_invert_restrictions'] = String_Prop_Type::make()
			->enum( array( '0', '1' ) )
			->default( '0' )
			->set_dependencies( $enabled_dependency );
		$schema['pmpro_segment'] = String_Prop_Type::make()
			->enum( array( 'all', 'specific', 'logged_in' ) )
			->default( 'all' )
			->set_dependencies( $enabled_dependency );
		$schema['pmpro_levels'] = String_Array_Prop_Type::make()
			->default( array() )
			->set_dependencies( $specific_levels_dependency );
		$schema['pmpro_show_noaccess'] = Boolean_Prop_Type::make()
			->default( false )
			->set_dependencies( $no_access_dependency );

		return $schema;
	}

	/**
	 * Add PMPro controls to Elementor v4 atomic elements.
	 *
	 * @since TBD
	 *
	 * @param array  $controls The atomic controls.
	 * @param object $element  The atomic element.
	 * @return array
	 */
	public function add_atomic_controls( $controls, $element ) {
		if ( ! $this->atomic_widgets_available() ) {
			return $controls;
		}

		// Only offer the restriction controls on elements we can actually enforce: atomic widgets
		// (handled via the render_content filter) and the supported atomic container types (handled
		// via the should_render filters registered in content_restriction()). This prevents showing a
		// restriction toggle on structural sub-elements where it wouldn't be enforced or would break
		// the parent widget.
		$element_type = method_exists( $element, 'get_type' ) ? $element->get_type() : '';
		if ( 'widget' !== $element_type && ! in_array( $element_type, $this->get_atomic_container_types(), true ) ) {
			return $controls;
		}

		$level_options = array();
		foreach ( pmpro_elementor_get_all_levels() as $level_id => $level_name ) {
			$level_options[] = array(
				'value' => (string) $level_id,
				'label' => $level_name,
			);
		}

		$items = array(
			Switch_Control::bind_to( 'pmpro_enable' )
				->set_label( esc_html__( 'Enable Paid Memberships Pro module visibility?', 'paid-memberships-pro' ) ),
			Select_Control::bind_to( 'pmpro_invert_restrictions' )
				->set_label( esc_html__( 'Visibility rule', 'paid-memberships-pro' ) )
				->set_options(
					array(
						array(
							'value' => '0',
							'label' => esc_html__( 'Show content to...', 'paid-memberships-pro' ),
						),
						array(
							'value' => '1',
							'label' => esc_html__( 'Hide content from...', 'paid-memberships-pro' ),
						),
					)
				),
			Select_Control::bind_to( 'pmpro_segment' )
				->set_label( esc_html__( 'Audience', 'paid-memberships-pro' ) )
				->set_options(
					array(
						array(
							'value' => 'all',
							'label' => esc_html__( 'All Members', 'paid-memberships-pro' ),
						),
						array(
							'value' => 'specific',
							'label' => esc_html__( 'Specific Membership Levels', 'paid-memberships-pro' ),
						),
						array(
							'value' => 'logged_in',
							'label' => esc_html__( 'Logged-In Users', 'paid-memberships-pro' ),
						),
					)
				),
			Chips_Control::bind_to( 'pmpro_levels' )
				->set_label( __( 'Membership Levels', 'paid-memberships-pro' ) )
				->set_options( $level_options ),
		);

		$items[] = Switch_Control::bind_to( 'pmpro_show_noaccess' )
			->set_label( esc_html__( 'Show no access message', 'paid-memberships-pro' ) );

		$controls[] = Section::make()
			->set_label( __( 'Paid Memberships Pro', 'paid-memberships-pro' ) )
			->set_id( $this->section_name )
			->set_items( $items );

		return $controls;
	}

	// Define controls
	public function add_controls( $element, $args ) {
        $element->add_control(
			'pmpro_enable', array(
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label' => esc_html__( 'Enable Paid Memberships Pro module visibility?', 'paid-memberships-pro' ),
				'default' => 'no',
            )
		);

        $element->add_control(
			'pmpro_invert_restrictions', array(
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => array(                
					'0' => esc_html__( 'Show content to...', 'paid-memberships-pro' ),
					'1' => esc_html__( 'Hide content from...', 'paid-memberships-pro' ),                    
                ),
                'label_block' => 'true',
				'default' => '0',
                'condition' => [
                    'pmpro_enable' => 'yes',
                ],
            )
		);

        $element->add_control(
			'pmpro_segment', array(
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => array(                
					'all' => esc_html__( 'All Members', 'paid-memberships-pro' ),
					'specific' => esc_html__( 'Specific Membership Levels', 'paid-memberships-pro' ),
                    'logged_in' => esc_html__( 'Logged-In Users', 'paid-memberships-pro' ),
                ),
                'label_block' => 'true',
				'default' => 'all',
                'condition' => [
                    'pmpro_enable' => 'yes',
                ],
            )
		);
        
        $element->add_control(
            'pmpro_levels', array(
                'type'        => Controls_Manager::SELECT2,
                'options'     => pmpro_elementor_get_all_levels(),
                'multiple'    => 'true',
				'label' => __( 'Membership Levels', 'paid-memberships-pro' ),
                'condition' => [
                    'pmpro_segment' => 'specific',    
                    'pmpro_enable' => 'yes',                
                ],
            )
        );

		// Only add this option to Widgets as we can replace the contents in widgets, not sections.
		if ( 'widget' === $element->get_type() ) {
			$element->add_control(
				'pmpro_show_noaccess', array(
					'label' => esc_html__( 'Show no access message', 'paid-memberships-pro' ),
					'type' => \Elementor\Controls_Manager::SWITCHER,
					'return_value' => 'yes',
					'default' => 'no',
                    'condition' => [
                        'pmpro_enable' => 'yes',
                        'pmpro_invert_restrictions' => '0',
                    ],
				)
			);
		}

	}

	/**
	 * Replace a restricted atomic container's output with its "no access" message.
	 *
	 * Atomic container elements (div blocks, flexbox, tabs, forms) cannot have their rendered content
	 * filtered the way widgets can, so we buffer the container's output here and swap it for the no
	 * access message in pmpro_elementor_after_render(). Note that Elementor renders the element (and
	 * its children) before this output is discarded, so the restricted content is built on the server
	 * and then thrown away rather than skipped entirely; it is never sent to the browser, but the work
	 * (and any assets the inner widgets enqueue) still happens.
	 *
	 * Widgets are handled separately via pmpro_elementor_render_content(), so they are skipped here.
	 *
	 * @since TBD
	 *
	 * @param object $element The Elementor element being rendered.
	 */
	public function pmpro_elementor_before_render( $element ) {
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() || 'widget' === $element->get_type() ) {
			return;
		}

		$element_settings = method_exists( $element, 'get_atomic_settings' ) ? $element->get_atomic_settings() : $element->get_active_settings();

		// If the block is not being restricted, there is nothing to replace.
		if ( empty( $element_settings['pmpro_enable'] ) || 'no' === $element_settings['pmpro_enable'] ) {
			return;
		}

		$apply_block_visibility_params = $this->build_visibility_params( $element_settings );

		// This path only renders the "no access" message. When the message is off (or the rule is
		// inverted, in which case build_visibility_params() forces it off) there is nothing to do here;
		// hiding is handled by pmpro_elementor_should_render().
		if ( empty( $apply_block_visibility_params['show_noaccess'] ) ) {
			return;
		}

		$placeholder = 'pmpro-elementor-access-probe';
		$result = pmpro_apply_block_visibility( $apply_block_visibility_params, $placeholder );
		if ( ! empty( $result ) && $result !== $placeholder ) {
			$this->no_access_messages[ $element->get_id() ] = $result;
			ob_start();
		}
	}

	/**
	 * Output the buffered "no access" message in place of a restricted atomic container's content.
	 *
	 * @since TBD
	 *
	 * @param object $element The Elementor element being rendered.
	 */
	public function pmpro_elementor_after_render( $element ) {
		if ( empty( $this->no_access_messages[ $element->get_id() ] ) ) {
			return;
		}

		// Discard the element's real (restricted) output that was buffered in pmpro_elementor_before_render().
		ob_get_clean();

		// The no access message comes from pmpro_get_no_access_message() and is intentionally output
		// as-is: it is admin-controlled HTML (links and markup), not user input, and escaping it would
		// strip that markup. This matches how PMPro outputs the no access message elsewhere (e.g. the
		// core blocks integration in includes/blocks.php).
		echo $this->no_access_messages[ $element->get_id() ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		unset( $this->no_access_messages[ $element->get_id() ] );
	}

	/**
	 * Filter sections to render content or not.
	 * If user doesn't have access, hide the section.
	 * @return boolean whether to show or hide section.
	 * @since 2.3
	 */
	public function pmpro_elementor_should_render( $should_render, $element ) {

		// Don't hide content in editor mode.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return $should_render;
		}        
        
		// If something else has already decided not to render this element — another should_render
		// filter (e.g. a display-condition plugin) or Elementor's own empty-content check — respect that.
		// Discard any no-access message we buffered in pmpro_elementor_before_render() so it is not echoed
		// in place of an element that is supposed to be gone, and let the buffer close balanced. We run
		// late (priority 9999) so other plugins' decisions are visible here; note this is a strong
		// mitigation rather than a guarantee, since a filter hooked even later could still win.
		if ( false === $should_render ) {
			$element_id = $element->get_id();
			if ( isset( $this->no_access_messages[ $element_id ] ) ) {
				ob_get_clean();
				unset( $this->no_access_messages[ $element_id ] );
			}
			return $should_render;
		}

        $element_settings = method_exists( $element, 'get_atomic_settings' ) ? $element->get_atomic_settings() : $element->get_active_settings();

        // If the block is not being restricted, then the user should be able to view it.
        if ( empty( $element_settings['pmpro_enable'] ) || 'no' === $element_settings['pmpro_enable'] ) {
            return true;
        }

        $apply_block_visibility_params = $this->build_visibility_params( $element_settings );
        $should_render = ! empty( pmpro_apply_block_visibility( $apply_block_visibility_params, 'sample content' ) );
		
		return apply_filters_deprecated( 'pmpro_elementor_section_access', array( $should_render, $element ), '3.5' );
	}

	/**
	 * Filter individual content for members.
	 * @return string Returns the content set from Elementor.
	 * @since 2.0
	 */
	public function pmpro_elementor_render_content( $content, $widget ){

        // Don't hide content in editor mode.
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {            
            return $content;
        }
        
        // We can only use the no access message on a widget
        if ( 'widget' !== $widget->get_type() ) {
            return $content;
        }
        
        $widget_settings = method_exists( $widget, 'get_atomic_settings' ) ? $widget->get_atomic_settings() : $widget->get_active_settings();

        // If the block is not being restricted, bail.
        if ( empty( $widget_settings['pmpro_enable'] ) || 'no' === $widget_settings['pmpro_enable'] ) {
            return $content;
        }

        // Use the pmpro_apply_block_visibility() method to generate output.
        $apply_block_visibility_params = $this->build_visibility_params( $widget_settings );
        return pmpro_apply_block_visibility( $apply_block_visibility_params, $content );

	}

	/**
	 * Migrate settings from old restriction method (pmpro_require_membership) to new method (pmpro_enable).
	 *
	 * @since 3.5
	 */
	public function migrate_settings() {
		// Get the post being viewed.
		$post_id = get_the_ID();
		if ( empty( $post_id ) ) {
			return;
		}

		// Get the _elementor_data for the post.
		$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
		if ( empty( $elementor_data ) ) {
			return;
		}

		// Decode the _elementor_data if needed.
		if ( is_string( $elementor_data ) ) {
			$elementor_data = json_decode( $elementor_data, true );
		}

		// If the data is still empty after decoding, bail.
		if ( empty( $elementor_data ) || ! is_array( $elementor_data ) ) {
			return;
		}

		$migrated_data = false;
		foreach ( $elementor_data as $key => &$container ) {
			// Migrate the settings for this container.
			$migrated_settings = $this->migrate_settings_helper( $container );
			if ( ! empty( $migrated_settings ) ) {
				$elementor_data[ $key ]['settings'] = $migrated_settings['settings'];
				$elementor_data[ $key ]['elements'] = $migrated_settings['elements'] ?? array();
				// Track that we made changes.
				$migrated_data = true;
			}
		}

		// Only save if something actually changed.
		if ( ! empty( $migrated_data ) ) {
			update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $elementor_data ) ) );
		}
	}

	/**
	 * Helper method for migrating settings from old restriction method (pmpro_require_membership) to new method (pmpro_enable).
	 *
	 * @since 3.5
	 *
	 * @param array $container An elementor container possibly containing $settings and $elements arrays to migrate.
	 * @return array|null Returns the updated settings array if changes were made, or null if no changes were needed.
	 */
	private function migrate_settings_helper( $container ) {
		// Track whether we made any changes for child elements.
		$migrated_child_elements = false;

		// Loop through the elements and call this method recursively.
		if ( ! empty( $container['elements'] ) && is_array( $container['elements'] ) ) {
			foreach ( $container['elements'] as &$element ) {
				$migrated_settings = $this->migrate_settings_helper( $element );
				if ( ! empty( $migrated_settings ) ) {
					$element['settings'] = $migrated_settings['settings'];
					$element['elements'] = $migrated_settings['elements'] ?? array();
					$migrated_child_elements = true;
				}
			}
			unset( $element );
		}

		// Migrate the the settings for this container.
		// If pmpro_require_membership is not set, bail.
		if ( ! isset( $container['settings']['pmpro_require_membership'] ) || empty( $container['settings']['pmpro_require_membership'] ) ) {
			return $migrated_child_elements ? $container : null;
		}

		// Let's convert the settings to the new format now.
		$container['settings']['pmpro_enable'] = 'yes';
		$container['settings']['pmpro_invert_restrictions'] = '0'; // 0 = Show content to members, 1 = Hide content from members.
		$container['settings']['pmpro_segment'] = 'specific'; // Elementor would always be "specific" during upgrade.
		$container['settings']['pmpro_levels'] = $container['settings']['pmpro_require_membership'];
		$container['settings']['pmpro_show_noaccess'] = ! empty( $container['settings']['pmpro_no_access_message'] ) ? $container['settings']['pmpro_no_access_message'] : 'no';

		// Remove the old pmpro_require_membership settings to clean up.
		unset( $container['settings']['pmpro_require_membership'] );
		unset( $container['settings']['pmpro_no_access_message'] );

		return $container;
	}

	/**
	 * Figure out if the user has access to restricted content.
	 * @return bool True or false based if the user has access to the content or not.
	 * @since 2.3
     * @deprecated 3.5
	 */
	public function pmpro_elementor_has_access( $element ) {
        _deprecated_function( __METHOD__, '3.5' );

		$element_settings = $element->get_active_settings();

        // If the block is not being restricted, then the user has access.
        if ( empty( $element_settings['pmpro_enable'] ) || 'no' === $element_settings['pmpro_enable'] ) {
            return true;
        }

        // If pmpro_apply_block_visibility returns content, then we want the user to see it.
        $apply_block_visibility_params = array(
            'segment' => $element_settings['pmpro_segment'],
            'levels' => $element_settings['pmpro_levels'],
            'invert_restrictions' => $element_settings['pmpro_invert_restrictions'],
            'show_noaccess' => $element_settings['pmpro_show_noaccess'],
        );
        $access = ! empty( pmpro_apply_block_visibility( $apply_block_visibility_params, 'sample content' ) );
        
		return apply_filters( 'pmpro_elementor_has_access', $access, $element, $element_settings['pmpro_levels'] );
	}

}
new PMPro_Elementor_Content_Restriction;
