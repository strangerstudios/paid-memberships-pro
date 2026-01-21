<?php
/**
 * Add restriction options to Elementor Widgets For Paid Memberships Pro.
 * @since 2.2.6
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

class PMPro_Elementor {
    private static $_instance = null;

    public $locations = array(
        array(
            'element' => 'common',
            'action'  => '_section_style',
        ),
        array(
            'element' => 'section',
            'action'  => 'section_advanced',
		),
		array(
			'element' => 'container',
			'action'  => 'section_layout'
		)
    );
    public $section_name = 'pmpro_elementor_section';

	/**
	 * Register new section for PMPro Required Membership Levels.
	 */
	public function __construct() {

		require_once( __DIR__ . '/class-pmpro-elementor-content-restriction.php' );

		// Register our custom widgets.
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'init' ) );

		// Create a new category for our widgets.
		add_action( 'elementor/elements/categories_registered', array( $this, 'add_widget_categories' ) );

		// Register new section to display restriction controls
		$this->register_sections();
		$this->content_restriction();

    }

    /**
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @return PMPro_Elementor An instance of the class.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();

        return self::$_instance;
    }

	/**
	 * Initialize the widgets.
	 */
	public function init() {
		require_once __DIR__ . '/widgets/class-pmpro-elementor-widget-base.php';
		require_once __DIR__ . '/widgets/class-pmpro-elementor-widget-account.php';
		require_once __DIR__ . '/widgets/class-pmpro-elementor-widget-billing.php';
		require_once __DIR__ . '/widgets/class-pmpro-elementor-widget-cancel.php';
		require_once __DIR__ . '/widgets/class-pmpro-elementor-widget-checkout.php';
		require_once __DIR__ . '/widgets/class-pmpro-elementor-widget-confirmation.php';
		require_once __DIR__ . '/widgets/class-pmpro-elementor-widget-order.php';
		require_once __DIR__ . '/widgets/class-pmpro-elementor-widget-levels.php';
		require_once __DIR__ . '/widgets/class-pmpro-elementor-widget-login.php';
		require_once __DIR__ . '/widgets/class-pmpro-elementor-widget-member-profile-edit.php';

		\Elementor\Plugin::instance()->widgets_manager->register( new PMPro_Elementor_Widget_Account() );
		\Elementor\Plugin::instance()->widgets_manager->register( new PMPro_Elementor_Widget_Billing() );
		\Elementor\Plugin::instance()->widgets_manager->register( new PMPro_Elementor_Widget_Cancel() );
		\Elementor\Plugin::instance()->widgets_manager->register( new PMPro_Elementor_Widget_Checkout() );
		\Elementor\Plugin::instance()->widgets_manager->register( new PMPro_Elementor_Widget_Confirmation() );
		\Elementor\Plugin::instance()->widgets_manager->register( new PMPro_Elementor_Widget_Order() );
		\Elementor\Plugin::instance()->widgets_manager->register( new PMPro_Elementor_Widget_Levels() );
		\Elementor\Plugin::instance()->widgets_manager->register( new PMPro_Elementor_Widget_Login() );
		\Elementor\Plugin::instance()->widgets_manager->register( new PMPro_Elementor_Widget_Member_Profile_Edit() );
	}

    /**
     * Add a new category for our widgets.
     */
    public function add_widget_categories( $elements_manager ) {
        $elements_manager->add_category(
            'paid-memberships-pro',
            [
                'title' => __( 'Paid Memberships Pro', 'paid-memberships-pro' ),
                'icon'  => 'dashicons-before admin-users',
            ]
        );
    }

    private function register_sections() {
        foreach( $this->locations as $where ) {
            add_action( 'elementor/element/'.$where['element'].'/'.$where['action'].'/after_section_end', array( $this, 'add_section' ), 10, 2 );
        }
    }

    public function add_section( $element, $args ) {
        $exists = \Elementor\Plugin::instance()->controls_manager->get_control_from_stack( $element->get_unique_name(), $this->section_name );

        if( !is_wp_error( $exists ) )
            return false;

        $element->start_controls_section(
            $this->section_name, array(
                'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
                'label' => __( 'Paid Memberships Pro', 'paid-memberships-pro' ),
            )
        );

        $element->end_controls_section();
    }

    protected function content_restriction(){}
}

// Instantiate Plugin Class
PMPro_Elementor::instance();
