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
	var useState      = React.useState;
	var useEffect     = React.useEffect;

	var __        = ( window.vendor && window.vendor.wp && window.vendor.wp.i18n && window.vendor.wp.i18n.__ ) || function ( s ) { return s; };
	var addFilter = wpHooks.addFilter;

	// window.divi.modal.FieldWrapper provides the labelled row wrapper used by
	// all other condition settings panels.
	var FieldWrapper = window.divi && window.divi.modal && window.divi.modal.FieldWrapper;

	// REST endpoint registered by divi.php for fetching available levels.
	var LEVELS_REST_ROUTE = '/divi/v1/pmpro/membership-levels';

	// -------------------------------------------------------------------------
	// 1. Add condition to the dropdown list
	// -------------------------------------------------------------------------
	addFilter(
		'divi.fieldLibrary.conditionalDisplay.conditionsStore',
		'pmpro/divi/conditions-store',
		function ( conditions ) {
			return conditions.concat( [ {
				name:     'pmproMembershipLevel',
				label:    __( 'Membership Level', 'paid-memberships-pro' ),
				category: 'user',
			} ] );
		}
	);

	// -------------------------------------------------------------------------
	// 2. Default condition state when user selects it from the dropdown
	// -------------------------------------------------------------------------
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
					levelIds:            '',
					showNoAccessMessage: 'off',
					enableCondition:     'on',
				},
				operator: operator,
			};
		}
	);

	// -------------------------------------------------------------------------
	// 3. Summary title shown in the collapsed condition row
	// -------------------------------------------------------------------------
	addFilter(
		'divi.fieldLibrary.conditionalDisplay.customTitle',
		'pmpro/divi/custom-title',
		function ( title, condition ) {
			if ( ! condition || 'pmproMembershipLevel' !== condition.conditionName ) {
				return title;
			}
			var s        = condition.conditionSettings || {};
			var ruleText = s.displayRule === 'doesNotHaveMembership'
				? __( "Doesn't Have", 'paid-memberships-pro' )
				: __( 'Has', 'paid-memberships-pro' );
			var levelText = s.levelIds
				? 'ID: ' + s.levelIds
				: __( 'Any Level', 'paid-memberships-pro' );
			return __( 'Membership Level', 'paid-memberships-pro' ) + ' — ' + ruleText + ' ' + levelText;
		}
	);

	// -------------------------------------------------------------------------
	// 4. Settings panel React component
	// -------------------------------------------------------------------------

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

		// Levels loaded from the REST endpoint.
		var levelsState = useState( [] );
		var levels      = levelsState[ 0 ];
		var setLevels   = levelsState[ 1 ];

		var loadingState = useState( true );
		var loading      = loadingState[ 0 ];
		var setLoading   = loadingState[ 1 ];

		useEffect( function () {
			var restBase = window.wpApiSettings && window.wpApiSettings.root
				? window.wpApiSettings.root.replace( /\/$/, '' )
				: '';
			var nonce = window.wpApiSettings && window.wpApiSettings.nonce
				? window.wpApiSettings.nonce
				: '';

			fetch( restBase + LEVELS_REST_ROUTE, {
				headers: {
					'X-WP-Nonce': nonce,
				},
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( data ) {
					if ( Array.isArray( data ) ) {
						setLevels( data );
					}
					setLoading( false );
				} )
				.catch( function () {
					setLoading( false );
				} );
		}, [] );

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
			{ value: 'hasMembership',          label: __( 'Has Membership Level', 'paid-memberships-pro' ) },
			{ value: 'doesNotHaveMembership',   label: __( 'Does Not Have Membership Level', 'paid-memberships-pro' ) },
		];

		var yesNoOptions = [
			{ value: 'off', label: __( 'No', 'paid-memberships-pro' ) },
			{ value: 'on',  label: __( 'Yes', 'paid-memberships-pro' ) },
		];

		var enableOptions = [
			{ value: 'on',  label: __( 'Yes', 'paid-memberships-pro' ) },
			{ value: 'off', label: __( 'No', 'paid-memberships-pro' ) },
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

		// Build the levels multi-select (or a text fallback if no levels loaded).
		function renderLevelSelect() {
			if ( loading ) {
				return createElement( 'span', null, __( 'Loading levels…', 'paid-memberships-pro' ) );
			}

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

			// Multi-select: Ctrl/Cmd-click to pick multiple levels.
			// We store them as a comma-separated string to match the PHP side.
			var selectedSet = new Set(
				( settings.levelIds || '' ).split( ',' ).map( function ( id ) { return id.trim(); } ).filter( Boolean )
			);

			return createElement(
				Fragment,
				null,
				createElement(
					'select',
					{
						multiple:  true,
						className: 'et-vb-field-input et-vb-field-input-select',
						style:     { width: '100%', minHeight: '80px' },
						value:     Array.from( selectedSet ),
						onChange:  function ( e ) {
							var selected = Array.from( e.target.selectedOptions ).map( function ( o ) { return o.value; } );
							update( { levelIds: selected.join( ',' ) } );
						},
					},
					levels.map( function ( level ) {
						return createElement( 'option', { key: level.value, value: level.value }, level.label );
					} )
				),
				createElement(
					'p',
					{ className: 'et-vb-field-description', style: { marginTop: '4px' } },
					__( 'Hold Ctrl / Cmd to select multiple levels.', 'paid-memberships-pro' )
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

		return createElement(
			Fragment,
			null,

			// Display Rule
			wrapField(
				__( 'Display Rule', 'paid-memberships-pro' ),
				renderSelect(
					'pmpro-display-rule',
					settings.displayRule || 'hasMembership',
					displayRuleOptions,
					function ( val ) { update( { displayRule: val } ); }
				)
			),

			// Membership Level(s)
			wrapField(
				__( 'Membership Level(s)', 'paid-memberships-pro' ),
				renderLevelSelect()
			),

			// Show No Access Message
			wrapField(
				__( 'Show No Access Message', 'paid-memberships-pro' ),
				renderSelect(
					'pmpro-show-no-access',
					settings.showNoAccessMessage || 'off',
					yesNoOptions,
					function ( val ) { update( { showNoAccessMessage: val } ); }
				)
			),

			// Enable Condition
			wrapField(
				__( 'Enable Condition', 'paid-memberships-pro' ),
				renderSelect(
					'pmpro-enable-condition',
					settings.enableCondition || 'on',
					enableOptions,
					function ( val ) { update( { enableCondition: val } ); }
				)
			)
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
