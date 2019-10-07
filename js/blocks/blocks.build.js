/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./blocks/blocks.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./blocks/account-invoices-section/block.js":
/*!**************************************************!*\
  !*** ./blocks/account-invoices-section/block.js ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Block: PMPro Membership Account: Invoices
 *
 * Displays the Membership Account > Invoices page section.
 *
 */

/**
 * Internal block libraries
 */
var __ = wp.i18n.__;
var registerBlockType = wp.blocks.registerBlockType;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/account-invoices-section', {
  title: __('Membership Account: Invoices', 'paid-memberships-pro'),
  description: __('Displays the member\'s invoices.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#2997c8',
    foreground: '#ffffff',
    src: 'archive'
  },
  keywords: [__('pmpro', 'paid-memberships-pro')],
  supports: {},
  attributes: {},
  edit: function edit() {
    return [Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "pmpro-block-element"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Paid Memberships Pro', 'paid-memberships-pro')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, " ", __('Membership Account: Invoices', 'paid-memberships-pro')))];
  },
  save: function save() {
    return null;
  }
}));

/***/ }),

/***/ "./blocks/account-links-section/block.js":
/*!***********************************************!*\
  !*** ./blocks/account-links-section/block.js ***!
  \***********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Block: PMPro Membership Account: Member Links
 *
 * Displays the Membership Account > Member Links page section.
 *
 */

/**
 * Internal block libraries
 */
var __ = wp.i18n.__;
var registerBlockType = wp.blocks.registerBlockType;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/account-links-section', {
  title: __('Membership Account: Links', 'paid-memberships-pro'),
  description: __('Displays the member\'s member links. This block is only visible if other Add Ons or custom code have added links.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#2997c8',
    foreground: '#ffffff',
    src: 'external'
  },
  keywords: [__('pmpro', 'paid-memberships-pro')],
  supports: {},
  attributes: {},
  edit: function edit() {
    return [Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "pmpro-block-element"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Paid Memberships Pro', 'paid-memberships-pro')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Membership Account: Member Links', 'paid-memberships-pro')))];
  },
  save: function save() {
    return null;
  }
}));

/***/ }),

/***/ "./blocks/account-membership-section/block.js":
/*!****************************************************!*\
  !*** ./blocks/account-membership-section/block.js ***!
  \****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Block: PMPro Membership Account: Memberships
 *
 * Displays the Membership Account > My Memberships page section.
 *
 */

/**
 * Internal block libraries
 */
var __ = wp.i18n.__;
var registerBlockType = wp.blocks.registerBlockType;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/account-membership-section', {
  title: __('Membership Account: Memberships', 'paid-memberships-pro'),
  description: __('Displays the member\'s membership information.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#2997c8',
    foreground: '#ffffff',
    src: 'groups'
  },
  keywords: [__('pmpro', 'paid-memberships-pro')],
  supports: {},
  attributes: {},
  edit: function edit() {
    return [Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "pmpro-block-element"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Paid Memberships Pro', 'paid-memberships-pro')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Membership Account: My Memberships', 'paid-memberships-pro')))];
  },
  save: function save() {
    return null;
  }
}));

/***/ }),

/***/ "./blocks/account-page/block.js":
/*!**************************************!*\
  !*** ./blocks/account-page/block.js ***!
  \**************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _inspector__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./inspector */ "./blocks/account-page/inspector.js");



function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(source, true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(source).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * Block: PMPro Membership Account
 *
 * Displays the Membership Account page.
 *
 */

/**
 * Block dependencies
 */

/**
 * Internal block libraries
 */

var __ = wp.i18n.__;
var registerBlockType = wp.blocks.registerBlockType;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/account-page', {
  title: __('Membership Account Page', 'paid-memberships-pro'),
  description: __('Displays the sections of the Membership Account page as selected below.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#2997c8',
    foreground: '#ffffff',
    src: 'admin-users'
  },
  keywords: [__('pmpro', 'paid-memberships-pro')],
  supports: {},
  attributes: {
    membership: {
      type: 'boolean',
      default: false
    },
    profile: {
      type: 'boolean',
      default: false
    },
    invoices: {
      type: 'boolean',
      default: false
    },
    links: {
      type: 'boolean',
      default: false
    }
  },
  edit: function edit(props) {
    var setAttributes = props.setAttributes,
        isSelected = props.isSelected;
    return [isSelected && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_inspector__WEBPACK_IMPORTED_MODULE_2__["default"], _objectSpread({
      setAttributes: setAttributes
    }, props)), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("div", {
      className: "pmpro-block-element"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("span", null, __('Paid Memberships Pro', 'paid-memberships-pro')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("span", null, __('Membership Account Page', 'paid-memberships-pro')))];
  },
  save: function save() {
    return null;
  }
}));

/***/ }),

/***/ "./blocks/account-page/inspector.js":
/*!******************************************!*\
  !*** ./blocks/account-page/inspector.js ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Inspector; });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);







/**
 * Internal block libraries
 */
var __ = wp.i18n.__;
var Component = wp.element.Component;
var _wp$components = wp.components,
    PanelBody = _wp$components.PanelBody,
    CheckboxControl = _wp$components.CheckboxControl;
var InspectorControls = wp.editor.InspectorControls;
/**
 * Create an Inspector Controls wrapper Component
 */

var Inspector =
/*#__PURE__*/
function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(Inspector, _Component);

  function Inspector() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, Inspector);

    return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default()(this, _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(Inspector).apply(this, arguments));
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(Inspector, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          _this$props$attribute = _this$props.attributes,
          membership = _this$props$attribute.membership,
          profile = _this$props$attribute.profile,
          invoices = _this$props$attribute.invoices,
          links = _this$props$attribute.links,
          setAttributes = _this$props.setAttributes;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(InspectorControls, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(PanelBody, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(CheckboxControl, {
        label: __("Show 'My Memberships' Section", 'paid-memberships-pro'),
        checked: membership,
        onChange: function onChange(membership) {
          return setAttributes({
            membership: membership
          });
        }
      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(PanelBody, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(CheckboxControl, {
        label: __("Show 'Profile' Section", 'paid-memberships-pro'),
        checked: profile,
        onChange: function onChange(profile) {
          return setAttributes({
            profile: profile
          });
        }
      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(PanelBody, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(CheckboxControl, {
        label: __("Show 'Invoices' Section", 'paid-memberships-pro'),
        checked: invoices,
        onChange: function onChange(invoices) {
          return setAttributes({
            invoices: invoices
          });
        }
      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(PanelBody, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(CheckboxControl, {
        label: __("Show 'Member Links' Section", 'paid-memberships-pro'),
        checked: links,
        onChange: function onChange(links) {
          return setAttributes({
            links: links
          });
        }
      })));
    }
  }]);

  return Inspector;
}(Component);



/***/ }),

/***/ "./blocks/account-profile-section/block.js":
/*!*************************************************!*\
  !*** ./blocks/account-profile-section/block.js ***!
  \*************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Block: PMPro Checkout Button
 *
 * Add a styled link to the PMPro checkout page for a
 * specific level.
 *
 */

/**
 * Internal block libraries
 */
var __ = wp.i18n.__;
var registerBlockType = wp.blocks.registerBlockType;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/account-profile-section', {
  title: __('Membership Account: Profile', 'paid-memberships-pro'),
  description: __('Displays the member\'s profile information.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#2997c8',
    foreground: '#ffffff',
    src: 'admin-users'
  },
  keywords: [__('pmpro', 'paid-memberships-pro')],
  supports: {},
  attributes: {},
  edit: function edit() {
    return [Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "pmpro-blocks-element"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Paid Memberships Pro', 'paid-memberships-pro')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Membership Account: Profile', 'paid-memberships-pro')))];
  },
  save: function save() {
    return null;
  }
}));

/***/ }),

/***/ "./blocks/billing-page/block.js":
/*!**************************************!*\
  !*** ./blocks/billing-page/block.js ***!
  \**************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Block: PMPro Membership Billing
 *
 * Displays the Membership Billing page and form.
 *
 */

/**
 * Internal block libraries
 */
var __ = wp.i18n.__;
var registerBlockType = wp.blocks.registerBlockType;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/billing-page', {
  title: __('Membership Billing Page', 'paid-memberships-pro'),
  description: __('Displays the member\'s billing information and allows them to update the payment method.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#2997c8',
    foreground: '#ffffff',
    src: 'list-view'
  },
  keywords: [__('pmpro', 'paid-memberships-pro')],
  supports: {},
  attributes: {},
  edit: function edit() {
    return [Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "pmpro-blocks-element"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Paid Memberships Pro', 'paid-memberships-pro')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Membership Billing Page', 'paid-memberships-pro')))];
  },
  save: function save() {
    return null;
  }
}));

/***/ }),

/***/ "./blocks/blocks.js":
/*!**************************!*\
  !*** ./blocks/blocks.js ***!
  \**************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _i18n_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./i18n.js */ "./blocks/i18n.js");
/* harmony import */ var _i18n_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_i18n_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _checkout_button_block_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./checkout-button/block.js */ "./blocks/checkout-button/block.js");
/* harmony import */ var _account_page_block_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./account-page/block.js */ "./blocks/account-page/block.js");
/* harmony import */ var _account_membership_section_block_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./account-membership-section/block.js */ "./blocks/account-membership-section/block.js");
/* harmony import */ var _account_profile_section_block_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./account-profile-section/block.js */ "./blocks/account-profile-section/block.js");
/* harmony import */ var _account_invoices_section_block_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./account-invoices-section/block.js */ "./blocks/account-invoices-section/block.js");
/* harmony import */ var _account_links_section_block_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./account-links-section/block.js */ "./blocks/account-links-section/block.js");
/* harmony import */ var _billing_page_block_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./billing-page/block.js */ "./blocks/billing-page/block.js");
/* harmony import */ var _cancel_page_block_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./cancel-page/block.js */ "./blocks/cancel-page/block.js");
/* harmony import */ var _checkout_page_block_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./checkout-page/block.js */ "./blocks/checkout-page/block.js");
/* harmony import */ var _confirmation_page_block_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./confirmation-page/block.js */ "./blocks/confirmation-page/block.js");
/* harmony import */ var _invoice_page_block_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./invoice-page/block.js */ "./blocks/invoice-page/block.js");
/* harmony import */ var _levels_page_block_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./levels-page/block.js */ "./blocks/levels-page/block.js");
/* harmony import */ var _membership_block_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./membership/block.js */ "./blocks/membership/block.js");
/**
 * Import internationalization
 */

/**
 * Import registerBlockType blocks
 */















/***/ }),

/***/ "./blocks/cancel-page/block.js":
/*!*************************************!*\
  !*** ./blocks/cancel-page/block.js ***!
  \*************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Block: PMPro Membership Cancel
 *
 * Displays the Membership Cancel page.
 *
 */

/**
 * Internal block libraries
 */
var __ = wp.i18n.__;
var registerBlockType = wp.blocks.registerBlockType;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/cancel-page', {
  title: __('Membership Cancel Page', 'paid-memberships-pro'),
  description: __('Generates the Membership Cancel page.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#2997c8',
    foreground: '#ffffff',
    src: 'no'
  },
  keywords: [__('pmpro', 'paid-memberships-pro')],
  supports: {},
  attributes: {},
  edit: function edit() {
    return [Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "pmpro-blocks-element"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Paid Memberships Pro', 'paid-memberships-pro')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Membership Cancel Page', 'paid-memberships-pro')))];
  },
  save: function save() {
    return null;
  }
}));

/***/ }),

/***/ "./blocks/checkout-button/block.js":
/*!*****************************************!*\
  !*** ./blocks/checkout-button/block.js ***!
  \*****************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _inspector__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./inspector */ "./blocks/checkout-button/inspector.js");



function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(source, true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(source).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * Block: PMPro Checkout Button
 *
 * Add a styled link to the PMPro checkout page for a specific level.
 *
 */

/**
 * Block dependencies
 */

/**
 * Internal block libraries
 */

var __ = wp.i18n.__;
var registerBlockType = wp.blocks.registerBlockType;
var _wp$components = wp.components,
    TextControl = _wp$components.TextControl,
    SelectControl = _wp$components.SelectControl;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/checkout-button', {
  title: __('Membership Checkout Button', 'paid-memberships-pro'),
  description: __('Displays a button-styled link to Membership Checkout for the specified level.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#2997c8',
    foreground: '#ffffff',
    src: 'migrate'
  },
  keywords: [__('pmpro', 'paid-memberships-pro'), __('buy', 'paid-memberships-pro'), __('level', 'paid-memberships-pro')],
  supports: {},
  attributes: {
    text: {
      type: 'string',
      default: 'Buy Now'
    },
    css_class: {
      type: 'string',
      default: 'pmpro_btn'
    },
    level: {
      type: 'string'
    }
  },
  edit: function edit(props) {
    var _props$attributes = props.attributes,
        text = _props$attributes.text,
        level = _props$attributes.level,
        css_class = _props$attributes.css_class,
        className = props.className,
        setAttributes = props.setAttributes,
        isSelected = props.isSelected;
    return [isSelected && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_inspector__WEBPACK_IMPORTED_MODULE_2__["default"], _objectSpread({
      setAttributes: setAttributes
    }, props)), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("div", {
      className: className
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("a", {
      class: css_class
    }, text)), isSelected && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("br", null), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(TextControl, {
      label: __('Button Text', 'paid-memberships-pro'),
      value: text,
      onChange: function onChange(text) {
        return setAttributes({
          text: text
        });
      }
    }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(SelectControl, {
      label: __('Level ID', 'paid-memberships-pro'),
      value: level,
      onChange: function onChange(level) {
        return setAttributes({
          level: level
        });
      },
      options: window.pmpro.all_level_values_and_labels
    }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(TextControl, {
      label: __('CSS Class', 'paid-memberships-pro'),
      value: css_class,
      onChange: function onChange(css_class) {
        return setAttributes({
          css_class: css_class
        });
      }
    }))];
  },
  save: function save() {
    return null;
  }
}));

/***/ }),

/***/ "./blocks/checkout-button/inspector.js":
/*!*********************************************!*\
  !*** ./blocks/checkout-button/inspector.js ***!
  \*********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Inspector; });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);







/**
 * Internal block libraries
 */
var __ = wp.i18n.__;
var Component = wp.element.Component;
var _wp$components = wp.components,
    PanelBody = _wp$components.PanelBody,
    TextControl = _wp$components.TextControl,
    SelectControl = _wp$components.SelectControl;
var InspectorControls = wp.editor.InspectorControls;
/**
 * Create an Inspector Controls wrapper Component
 */

var Inspector =
/*#__PURE__*/
function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(Inspector, _Component);

  function Inspector() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, Inspector);

    return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default()(this, _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(Inspector).apply(this, arguments));
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(Inspector, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          _this$props$attribute = _this$props.attributes,
          text = _this$props$attribute.text,
          level = _this$props$attribute.level,
          css_class = _this$props$attribute.css_class,
          setAttributes = _this$props.setAttributes;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(InspectorControls, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(PanelBody, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(TextControl, {
        label: __('Button Text', 'paid-memberships-pro'),
        help: __('Text for checkout button', 'paid-memberships-pro'),
        value: text,
        onChange: function onChange(text) {
          return setAttributes({
            text: text
          });
        }
      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(PanelBody, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(SelectControl, {
        label: __('Level ID', 'paid-memberships-pro'),
        help: __('Level id to check out', 'paid-memberships-pro'),
        value: level,
        onChange: function onChange(level) {
          return setAttributes({
            level: level
          });
        },
        options: window.pmpro.all_level_values_and_labels
      })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(PanelBody, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(TextControl, {
        label: __('CSS Class', 'paid-memberships-pro'),
        help: __('Additional Styling for Button', 'paid-memberships-pro'),
        value: css_class,
        onChange: function onChange(css_class) {
          return setAttributes({
            css_class: css_class
          });
        }
      })));
    }
  }]);

  return Inspector;
}(Component);



/***/ }),

/***/ "./blocks/checkout-page/block.js":
/*!***************************************!*\
  !*** ./blocks/checkout-page/block.js ***!
  \***************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/defineProperty.js");
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _inspector__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./inspector */ "./blocks/checkout-page/inspector.js");



function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(source, true).forEach(function (key) { _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0___default()(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(source).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * Block: PMPro Membership Checkout
 *
 * Displays the Membership Checkout form.
 *
 */

/**
 * Block dependencies
 */

/**
 * Internal block libraries
 */

var __ = wp.i18n.__;
var registerBlockType = wp.blocks.registerBlockType;
var SelectControl = wp.components.SelectControl;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/checkout-page', {
  title: __('Membership Checkout Form', 'paid-memberships-pro'),
  description: __('Displays the Membership Checkout form.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#2997c8',
    foreground: '#ffffff',
    src: 'list-view'
  },
  keywords: [__('pmpro', 'paid-memberships-pro')],
  supports: {},
  attributes: {
    pmpro_default_level: {
      type: 'string',
      source: 'meta',
      meta: 'pmpro_default_level'
    }
  },
  edit: function edit(props) {
    var pmpro_default_level = props.attributes.pmpro_default_level,
        className = props.className,
        setAttributes = props.setAttributes,
        isSelected = props.isSelected;
    return [isSelected && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(_inspector__WEBPACK_IMPORTED_MODULE_2__["default"], _objectSpread({
      setAttributes: setAttributes
    }, props)), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("div", {
      className: "pmpro-block-element"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("span", null, "Paid Memberships Pro"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])("span", null, "Membership Checkout Form"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__["createElement"])(SelectControl, {
      label: __('Membership Level', 'paid-memberships-pro'),
      value: pmpro_default_level,
      onChange: function onChange(pmpro_default_level) {
        return setAttributes({
          pmpro_default_level: pmpro_default_level
        });
      },
      options: window.pmpro.all_level_values_and_labels
    }))];
  },
  save: function save() {
    return null;
  }
}));

/***/ }),

/***/ "./blocks/checkout-page/inspector.js":
/*!*******************************************!*\
  !*** ./blocks/checkout-page/inspector.js ***!
  \*******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Inspector; });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);







/**
 * Internal block libraries
 */
var __ = wp.i18n.__;
var Component = wp.element.Component;
var _wp$components = wp.components,
    PanelBody = _wp$components.PanelBody,
    PanelRow = _wp$components.PanelRow,
    SelectControl = _wp$components.SelectControl;
var InspectorControls = wp.editor.InspectorControls;
/**
 * Create an Inspector Controls wrapper Component
 */

var Inspector =
/*#__PURE__*/
function (_Component) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(Inspector, _Component);

  function Inspector() {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, Inspector);

    return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_2___default()(this, _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_3___default()(Inspector).apply(this, arguments));
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(Inspector, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          pmpro_default_level = _this$props.attributes.pmpro_default_level,
          setAttributes = _this$props.setAttributes;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(InspectorControls, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(PanelBody, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(SelectControl, {
        label: __('Membership Level', 'paid-memberships-pro'),
        help: __('Choose a default level for Membership Checkout.', 'paid-memberships-pro'),
        value: pmpro_default_level,
        onChange: function onChange(pmpro_default_level) {
          return setAttributes({
            pmpro_default_level: pmpro_default_level
          });
        },
        options: [''].concat(window.pmpro.all_level_values_and_labels)
      })));
    }
  }]);

  return Inspector;
}(Component);



/***/ }),

/***/ "./blocks/confirmation-page/block.js":
/*!*******************************************!*\
  !*** ./blocks/confirmation-page/block.js ***!
  \*******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Block: PMPro Membership Confirmation
 *
 * Displays the Membership Confirmation template.
 *
 */

/**
 * Internal block libraries
 */
var __ = wp.i18n.__;
var registerBlockType = wp.blocks.registerBlockType;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/confirmation-page', {
  title: __('Membership Confirmation Page', 'paid-memberships-pro'),
  description: __('Displays the member\'s Membership Confirmation after Membership Checkout.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#2997c8',
    foreground: '#ffffff',
    src: 'yes'
  },
  keywords: [__('pmpro', 'paid-memberships-pro')],
  supports: {},
  attributes: {},
  edit: function edit() {
    return [Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "pmpro-block-element"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Paid Memberships Pro', 'paid-memberships-pro')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Membership Confirmation Page', 'paid-memberships-pro')))];
  },
  save: function save() {
    return null;
  }
}));

/***/ }),

/***/ "./blocks/i18n.js":
/*!************************!*\
  !*** ./blocks/i18n.js ***!
  \************************/
/*! no static exports found */
/***/ (function(module, exports) {

wp.i18n.setLocaleData({
  '': {}
}, 'paid-memberships-pro');

/***/ }),

/***/ "./blocks/invoice-page/block.js":
/*!**************************************!*\
  !*** ./blocks/invoice-page/block.js ***!
  \**************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Block: PMPro Membership Invoices
 *
 * Displays the Membership Invoices template.
 *
 */

/**
 * Internal block libraries
 */
var __ = wp.i18n.__;
var registerBlockType = wp.blocks.registerBlockType;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/invoice-page', {
  title: __('Membership Invoice Page', 'paid-memberships-pro'),
  description: __('Displays the member\'s  Membership Invoices.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#2997c8',
    foreground: '#ffffff',
    src: 'archive'
  },
  keywords: [__('pmpro', 'paid-memberships-pro')],
  supports: {},
  attributes: {},
  edit: function edit() {
    return [Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "pmpro-block-element"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Paid Memberships Pro', 'paid-memberships-pro')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Membership Invoices', 'paid-memberships-pro')))];
  },
  save: function save() {
    return null;
  }
}));

/***/ }),

/***/ "./blocks/levels-page/block.js":
/*!*************************************!*\
  !*** ./blocks/levels-page/block.js ***!
  \*************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Block: PMPro Membership Levels
 *
 * Displays the Membership Levels template.
 *
 */

/**
 * Internal block libraries
 */
var __ = wp.i18n.__;
var registerBlockType = wp.blocks.registerBlockType;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/levels-page', {
  title: __('Membership Levels List', 'paid-memberships-pro'),
  description: __('Displays a list of Membership Levels. To change the order, go to Memberships > Settings > Levels.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#2997c8',
    foreground: '#ffffff',
    src: 'list-view'
  },
  keywords: [__('pmpro', 'paid-memberships-pro')],
  supports: {},
  attributes: {},
  edit: function edit() {
    return [Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "pmpro-block-element"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Paid Memberships Pro', 'paid-memberships-pro')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", null, __('Membership Levels List', 'paid-memberships-pro')))];
  },
  save: function save() {
    return null;
  }
}));

/***/ }),

/***/ "./blocks/membership/block.js":
/*!************************************!*\
  !*** ./blocks/membership/block.js ***!
  \************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Block: PMPro Membership
 *
 *
 */

/**
 * Internal block libraries
 */
var __ = wp.i18n.__;
var registerBlockType = wp.blocks.registerBlockType;
var _wp$components = wp.components,
    PanelBody = _wp$components.PanelBody,
    SelectControl = _wp$components.SelectControl;
var _wp$editor = wp.editor,
    InspectorControls = _wp$editor.InspectorControls,
    InnerBlocks = _wp$editor.InnerBlocks;
var member_levels = pmpro.all_level_values_and_labels;
var all_levels = [{
  value: 0,
  label: "Non-Members"
}].concat({
  member_levels: member_levels
});
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/membership', {
  title: __('Require Membership Block', 'paid-memberships-pro'),
  description: __('Control the visibility of nested blocks for members or non-members.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#2997c8',
    foreground: '#ffffff',
    src: 'visibility'
  },
  keywords: [__('pmpro', 'paid-memberships-pro')],
  attributes: {
    levels: {
      type: 'array',
      default: []
    },
    uid: {
      type: 'string',
      default: ''
    }
  },
  edit: function edit(props) {
    var _props$attributes = props.attributes,
        levels = _props$attributes.levels,
        uid = _props$attributes.uid,
        setAttributes = props.setAttributes,
        isSelected = props.isSelected;

    if (uid == '') {
      var rand = Math.random() + "";
      setAttributes({
        uid: rand
      });
    }

    return [isSelected && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(InspectorControls, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(PanelBody, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(SelectControl, {
      multiple: true,
      label: __('Select levels to show content to:', 'paid-memberships-pro'),
      value: levels,
      onChange: function onChange(levels) {
        setAttributes({
          levels: levels
        });
      },
      options: all_levels
    }))), isSelected && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "pmpro-block-element"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
      class: "pmpro-membership-title"
    }, __('Require Membership', 'paid-memberships-pro')), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(PanelBody, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(SelectControl, {
      multiple: true,
      label: __('Select levels to show content to:', 'paid-memberships-pro'),
      value: levels,
      onChange: function onChange(levels) {
        setAttributes({
          levels: levels
        });
      },
      options: all_levels
    })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(InnerBlocks, {
      templateLock: false
    })), !isSelected && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: "pmpro-block-element"
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
      class: "pmpro-membership-title"
    }, __('Require Membership:', 'paid-memberships-pro'), " ", levels), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(InnerBlocks, {
      templateLock: false
    }))];
  },
  save: function save(props) {
    var className = props.className;
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: className
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(InnerBlocks.Content, null));
  }
}));

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/assertThisInitialized.js ***!
  \**********************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }

  return self;
}

module.exports = _assertThisInitialized;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/classCallCheck.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/classCallCheck.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

module.exports = _classCallCheck;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/createClass.js":
/*!************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/createClass.js ***!
  \************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  return Constructor;
}

module.exports = _createClass;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/defineProperty.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/defineProperty.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _defineProperty(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }

  return obj;
}

module.exports = _defineProperty;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/getPrototypeOf.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _getPrototypeOf(o) {
  module.exports = _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) {
    return o.__proto__ || Object.getPrototypeOf(o);
  };
  return _getPrototypeOf(o);
}

module.exports = _getPrototypeOf;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/inherits.js":
/*!*********************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/inherits.js ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var setPrototypeOf = __webpack_require__(/*! ./setPrototypeOf */ "./node_modules/@babel/runtime/helpers/setPrototypeOf.js");

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function");
  }

  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      writable: true,
      configurable: true
    }
  });
  if (superClass) setPrototypeOf(subClass, superClass);
}

module.exports = _inherits;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js":
/*!**************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js ***!
  \**************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var _typeof = __webpack_require__(/*! ../helpers/typeof */ "./node_modules/@babel/runtime/helpers/typeof.js");

var assertThisInitialized = __webpack_require__(/*! ./assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");

function _possibleConstructorReturn(self, call) {
  if (call && (_typeof(call) === "object" || typeof call === "function")) {
    return call;
  }

  return assertThisInitialized(self);
}

module.exports = _possibleConstructorReturn;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/setPrototypeOf.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/setPrototypeOf.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _setPrototypeOf(o, p) {
  module.exports = _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };

  return _setPrototypeOf(o, p);
}

module.exports = _setPrototypeOf;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/typeof.js":
/*!*******************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/typeof.js ***!
  \*******************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _typeof2(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof2 = function _typeof2(obj) { return typeof obj; }; } else { _typeof2 = function _typeof2(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof2(obj); }

function _typeof(obj) {
  if (typeof Symbol === "function" && _typeof2(Symbol.iterator) === "symbol") {
    module.exports = _typeof = function _typeof(obj) {
      return _typeof2(obj);
    };
  } else {
    module.exports = _typeof = function _typeof(obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : _typeof2(obj);
    };
  }

  return _typeof(obj);
}

module.exports = _typeof;

/***/ }),

/***/ "@wordpress/element":
/*!******************************************!*\
  !*** external {"this":["wp","element"]} ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["element"]; }());

/***/ })

/******/ });
//# sourceMappingURL=blocks.build.js.map