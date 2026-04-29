/**
 * PMPro Divi 5 Visual Builder — Membership Level Condition
 *
 * Registers a custom "Membership Level" condition type in the Divi 5 Display
 * Conditions panel.  The server-side evaluation is handled in divi.php.
 *
 * Hooks used (all via wp.hooks / window.vendor.wp.hooks):
 *   divi.fieldLibrary.conditionalDisplay.conditionsStore    — adds condition to dropdown
 *   divi.fieldLibrary.conditionalDisplay.initialCustomItemEdit — default state on add
 *   divi.fieldLibrary.conditionalDisplay.customSettingsComponent — settings panel UI
 *   divi.fieldLibrary.conditionalDisplay.customTitle        — summary label in list
 */
( function () {
	'use strict';

	// Guard: require Divi 5 hooks and React.
	var wpHooks = window.vendor && window.vendor.wp && window.vendor.wp.hooks;
	var React   = window.vendor && window.vendor.React;

	if ( ! wpHooks || ! wpHooks.addFilter || ! React ) {
		return;
	}

	var createElement = React.createElement;
	var Fragment      = React.Fragment;

	var __        = ( window.vendor && window.vendor.wp && window.vendor.wp.i18n && window.vendor.wp.i18n.__ ) || function ( s ) { return s; };
	var addFilter = wpHooks.addFilter;

	// window.divi.modal.FieldWrapper provides the labelled row wrapper used by
	// all other condition settings panels.
	var FieldWrapper = window.divi && window.divi.modal && window.divi.modal.FieldWrapper;

	// Membership levels localized from PHP via wp_localize_script.
	var allLevels = ( window.pmproDivi && window.pmproDivi.levels ) || [];

	// Register our custom category "PMPro" and condition type "Content Visibility" in the dropdown.
	addFilter(
		'divi.fieldLibrary.conditionalDisplay.conditionCategories',
		'pmpro/divi/condition-categories',
		function ( categories ) {
			categories.pmpro = {
				label:   __( 'PMPro', 'paid-memberships-pro' ),
				options: {},
			};
			return categories;
		}
	);

	addFilter(
		'divi.fieldLibrary.conditionalDisplay.conditionsStore',
		'pmpro/divi/conditions-store',
		function ( conditions ) {
			return conditions.concat( [ {
				name:     'pmproMembershipLevel',
				label:    __( 'Content Visibility', 'paid-memberships-pro' ),
				category: 'pmpro',
			} ] );
		}
	);

	// Add defaults to the content visibility Divi 5 condition.
	addFilter(
		'divi.fieldLibrary.conditionalDisplay.initialCustomItemEdit',
		'pmpro/divi/initial-item',
		function ( initial, conditionName, id, operator ) {
			if ( 'pmproMembershipLevel' !== conditionName ) {
				return initial;
			}
			return {
				id:               id,
				conditionName:    'pmproMembershipLevel',
				conditionSettings: {
					displayRule:         'hasMembership',
					segment:             'all',
					levelIds:            '',
					showNoAccessMessage: 'off',
					enableCondition:     'on',
				},
				operator: operator,
			};
		}
	);

	// Show summary/title when condition is collapsed.
	addFilter(
		'divi.fieldLibrary.conditionalDisplay.customTitle',
		'pmpro/divi/custom-title',
		function ( title, condition ) {
			if ( ! condition || 'pmproMembershipLevel' !== condition.conditionName ) {
				return title;
			}
			var s        = condition.conditionSettings || {};
			var ruleText = s.displayRule === 'doesNotHaveMembership'
				? __( 'Hide from', 'paid-memberships-pro' )
				: __( 'Show to', 'paid-memberships-pro' );
			var segment  = s.segment || 'all';
			var segmentText;
			if ( segment === 'logged_in' ) {
				segmentText = __( 'Logged-In Users', 'paid-memberships-pro' );
			} else if ( segment === 'specific' && s.levelIds ) {
				segmentText = __( 'Levels', 'paid-memberships-pro' ) + ' ' + s.levelIds;
			} else {
				segmentText = __( 'All Members', 'paid-memberships-pro' );
			}
			return 'PMPro — ' + ruleText + ' ' + segmentText;
		}
	);

	/**
	 * PMPro membership level condition settings component.
	 *
	 * @param {Object} props
	 * @param {Object} props.condition   - Full condition object (id, conditionName, conditionSettings, operator).
	 * @param {Function} props.onChange  - setState-style setter: fn( prev => newState ).
	 */
	function PMProConditionSettings( props ) {
		var condition = props.condition;
		var onChange  = props.onChange;
		var settings  = ( condition && condition.conditionSettings ) || {};

		// Levels localized from PHP.
		var levels = allLevels;

		/**
		 * Merge changes into conditionSettings and call the parent setter.
		 *
		 * @param {Object} changes
		 */
		function update( changes ) {
			onChange( function ( prev ) {
				return Object.assign( {}, prev, {
					conditionSettings: Object.assign( {}, prev.conditionSettings, changes ),
				} );
			} );
		}

		var displayRuleOptions = [
			{ value: 'hasMembership',          label: __( 'Show', 'paid-memberships-pro' ) },
			{ value: 'doesNotHaveMembership',   label: __( 'Hide', 'paid-memberships-pro' ) },
		];

		var segmentOptions = [
			{ value: 'all',      label: __( 'All Members', 'paid-memberships-pro' ) },
			{ value: 'specific', label: __( 'Specific Membership Levels', 'paid-memberships-pro' ) },
			{ value: 'logged_in', label: __( 'Logged-In Users', 'paid-memberships-pro' ) },
		];

		var yesNoOptions = [
			{ value: 'off', label: __( 'No - Hide this content if the user does not have access', 'paid-memberships-pro' ) },
			{ value: 'on',  label: __( "Yes - Show the 'no access' message if the user does not have access", 'paid-memberships-pro' ) },
		];

function renderSelect( name, value, options, onChangeFn ) {
			return createElement(
				'select',
				{
					name:      name,
					value:     value,
					className: 'et-vb-field-input et-vb-field-input-select',
					onChange:  function ( e ) { onChangeFn( e.target.value ); },
					style:     { width: '100%' },
				},
				options.map( function ( opt ) {
					return createElement( 'option', { key: opt.value, value: opt.value }, opt.label );
				} )
			);
		}

		// Build the levels checkboxes (or a text fallback if no levels loaded).
		function renderLevelSelect() {
			if ( ! levels.length ) {
				// Fallback: plain text input for manual IDs.
				return createElement(
					Fragment,
					null,
					createElement( 'input', {
						type:        'text',
						className:   'et-vb-field-input et-vb-field-input-text',
						placeholder: __( 'e.g. 1,2,3', 'paid-memberships-pro' ),
						value:       settings.levelIds || '',
						style:       { width: '100%' },
						onChange:    function ( e ) { update( { levelIds: e.target.value } ); },
					} ),
					createElement(
						'p',
						{ className: 'et-vb-field-description', style: { marginTop: '4px' } },
						__( 'Enter comma-separated membership level IDs.', 'paid-memberships-pro' )
					)
				);
			}

			var selectedSet = new Set(
				( settings.levelIds || '' ).split( ',' ).map( function ( id ) { return id.trim(); } ).filter( Boolean )
			);

			function toggleLevel( value ) {
				var newSet = new Set( selectedSet );
				if ( newSet.has( value ) ) {
					newSet.delete( value );
				} else {
					newSet.add( value );
				}
				update( { levelIds: Array.from( newSet ).join( ',' ) } );
			}

			function selectAllLevels() {
				update( { levelIds: levels.map( function ( l ) { return l.value; } ).join( ',' ) } );
			}

			function selectNone() {
				update( { levelIds: '' } );
			}

			return createElement(
				Fragment,
				null,
				// Select All | None links
				createElement(
					'p',
					{ style: { margin: '0 0 8px' } },
					__( 'Select', 'paid-memberships-pro' ),
					' ',
					createElement( 'a', {
						href:    '#',
						onClick: function ( e ) { e.preventDefault(); selectAllLevels(); },
					}, __( 'All', 'paid-memberships-pro' ) ),
					' | ',
					createElement( 'a', {
						href:    '#',
						onClick: function ( e ) { e.preventDefault(); selectNone(); },
					}, __( 'None', 'paid-memberships-pro' ) )
				),
				// Scrollable checkbox list
				createElement(
					'div',
					{ className: 'pmpro-divi-vb-scrollable', style: { height: '170px', overflow: 'auto', padding: '8px' } },
					levels.map( function ( level ) {
						var isChecked = selectedSet.has( level.value );
						return createElement(
							'label',
							{
								key:   level.value,
								style: { display: 'flex', alignItems: 'center', gap: '6px', marginBottom: '4px', cursor: 'pointer' },
							},
							createElement( 'input', {
								type:     'checkbox',
								checked:  isChecked,
								onChange: function () { toggleLevel( level.value ); },
							} ),
							level.label
						);
					} )
				)
			);
		}

		function wrapField( label, content ) {
			if ( FieldWrapper ) {
				return createElement( FieldWrapper, { label: label }, content );
			}
			// Plain fallback if FieldWrapper isn't available.
			return createElement(
				'div',
				{ className: 'et-vb-option-container', style: { marginBottom: '8px' } },
				createElement( 'label', { className: 'et-vb-option-label', style: { display: 'block', marginBottom: '4px', fontWeight: 600 } }, label ),
				content
			);
		}

		var currentSegment    = settings.segment || 'all';
		var currentRule       = settings.displayRule || 'hasMembership';

		return createElement(
			Fragment,
			null,

			// Display Rule (Show / Hide)
			wrapField(
				__( 'Display Rule', 'paid-memberships-pro' ),
				renderSelect(
					'pmpro-display-rule',
					currentRule,
					displayRuleOptions,
					function ( val ) { update( { displayRule: val } ); }
				)
			),

			// Segment (All Members / Specific Levels / Logged-In Users)
			wrapField(
				currentRule === 'doesNotHaveMembership'
					? __( 'Hide content from:', 'paid-memberships-pro' )
					: __( 'Show content to:', 'paid-memberships-pro' ),
				renderSelect(
					'pmpro-segment',
					currentSegment,
					segmentOptions,
					function ( val ) { update( { segment: val, levelIds: '' } ); }
				)
			),

			// Membership Level(s) — only when segment is 'specific'
			currentSegment === 'specific' ? wrapField(
				__( 'Membership Levels', 'paid-memberships-pro' ),
				renderLevelSelect()
			) : null,

			// Show No Access Message — only in "show" mode
			currentRule === 'hasMembership' ? wrapField(
				__( 'Show No Access Message', 'paid-memberships-pro' ),
				createElement(
					Fragment,
					null,
					renderSelect(
						'pmpro-show-no-access',
						settings.showNoAccessMessage || 'off',
						yesNoOptions,
						function ( val ) { update( { showNoAccessMessage: val } ); }
					),
					createElement(
						'p',
						{ className: 'et-vb-field-description', style: { marginTop: '4px' } },
						__( "Modify the 'no access' message on the Memberships > Advanced Settings page.", 'paid-memberships-pro' )
					)
				)
			) : null
		);
	}

	addFilter(
		'divi.fieldLibrary.conditionalDisplay.customSettingsComponent',
		'pmpro/divi/settings-component',
		function ( component, condition, onChange ) {
			if ( ! condition || 'pmproMembershipLevel' !== condition.conditionName ) {
				return component;
			}
			return createElement( PMProConditionSettings, { condition: condition, onChange: onChange } );
		}
	);

}() );
