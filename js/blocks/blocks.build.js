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
/* harmony import */ var _membership_block_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./membership/block.js */ "./blocks/membership/block.js");
/**
 * Import internationalization
 */

/**
 * Import registerBlockType blocks
 */
// import './checkout-button/block.js';
// import './account-page/block.js';
// import './account-membership-section/block.js';
// import './account-profile-section/block.js';
// import './account-invoices-section/block.js';
// import './account-links-section/block.js';
// import './billing-page/block.js';
// import './cancel-page/block.js';
// import './checkout-page/block.js';
// import './confirmation-page/block.js';
// import './invoice-page/block.js';
// import './levels-page/block.js';



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
 * Block dependencies
 */
//  import './editor.css';
//  import classnames from 'classnames';

/**
 * Internal block libraries
 */
var __ = wp.i18n.__;
var _wp$blocks = wp.blocks,
    registerBlockType = _wp$blocks.registerBlockType,
    AlignmentToolbar = _wp$blocks.AlignmentToolbar,
    BlockControls = _wp$blocks.BlockControls,
    BlockAlignmentToolbar = _wp$blocks.BlockAlignmentToolbar;
var _wp$components = wp.components,
    PanelBody = _wp$components.PanelBody,
    PanelRow = _wp$components.PanelRow,
    TextControl = _wp$components.TextControl,
    SelectControl = _wp$components.SelectControl;
var _wp$editor = wp.editor,
    RichText = _wp$editor.RichText,
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
        className = props.className,
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
      label: __('Select levels to show content to:'),
      value: levels,
      onChange: function onChange(levels) {
        setAttributes({
          levels: levels
        });
      },
      options: all_levels
    }))), isSelected && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: className
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
      class: "pmpro-membership-title"
    }, "Require Membership"), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(PanelBody, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(SelectControl, {
      multiple: true,
      label: __('Select levels to show content to:'),
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
      className: className
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("span", {
      class: "pmpro-membership-title"
    }, "Require Membership: ", levels), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(InnerBlocks, {
      templateLock: false
    }))];
  },
  save: function save(props) {
    var _props$attributes2 = props.attributes,
        levels = _props$attributes2.levels,
        uid = _props$attributes2.uid,
        className = props.className,
        isSelected = props.isSelected;
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", {
      className: className
    }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(InnerBlocks.Content, null));
  }
}));

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