<?php
use Elementor\Controls_Manager;
use ElementorPro\Modules\DisplayConditions\Conditions\Base\Condition_Base;

class Membership_Level_Condition extends Condition_Base {
	public function get_group() {
		return 'pmpro';
	}

	public function check( $args ) : bool {
		if ( 'is_one_of' === $args['comparator'] ) {	
			return $this->check_is_one_of( $args['levels'] );
		}

		return parent::check();
	}

	private function check_is_one_of( $levels ) {
		if ( empty( $levels ) ) {
			return true;
		}

		foreach ( $levels as $level ) {
			if ( pmpro_hasMembershipLevel( $level ) ) {
				return true;
			}
		}

		return false;
	}

	public function get_label() {
		return 'Membership Level';
	}

	public function get_options() {
		$this->add_control(
			'comparator',
			[
				'type' => Controls_Manager::SELECT,
				'options' => [
					'is_one_of' => esc_html__( 'Is one of', 'paid-memberships-pro' ),
				],
			]
		);

		$this->add_control(
			'levels',
			[
				'type' => Controls_Manager::SELECT2,
				'options' => $this->get_level_options(),
				'multiple' => true,
				'required' => true,
			]
		);
	}

	public function get_name() {
		return 'pmpro_membership_level';
	}

	private function get_level_options(): array {
		$levels = [];

		foreach ( pmpro_getAllLevels() as $level ) {
			$levels[ esc_attr( $level->id ) ] = esc_html( $level->name );
		}

		return $levels;
	}
}
