/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./blocks/src/membership/edit.js":
/*!***************************************!*\
  !*** ./blocks/src/membership/edit.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Edit)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _editor_scss__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./editor.scss */ "./blocks/src/membership/editor.scss");
/* harmony import */ var _inspectorColorsFragment__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./inspectorColorsFragment */ "./blocks/src/membership/inspectorColorsFragment.js");


/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */


/**
 * WordPress dependencies
 */


/**
 * CSS code for the Membership Excluded block that gets applied to the editor.
 */


/**
 * Render the Content Visibility block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
function Edit(props) {
  // Set up the block.
  const blockProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__.useBlockProps)({});
  const {
    attributes: {
      invert_restrictions,
      segment,
      levels
    },
    setAttributes,
    isSelected
  } = props;

  // Handle migrations from PMPro < 3.0.
  // If levels is not empty and segment is 'all', we  need to migrate.
  if (levels.length > 0 && segment == 'all') {
    // If '0' is in levels, then restrictions should be inverted.
    if (levels.includes('0')) {
      // If '0' was the only element, then the segment should be 'all'.
      if (levels.length == 1) {
        setAttributes({
          invert_restrictions: '1',
          segment: 'all',
          levels: []
        });
      } else {
        // Otherwise, the segment should be 'specific' and we need to change the levels array to
        // all level IDs that were not previously selected.
        const newLevels = pmpro.all_level_values_and_labels.map(level => level.value + '').filter(levelID => !levels.includes(levelID));
        setAttributes({
          invert_restrictions: '1',
          segment: 'specific',
          levels: newLevels
        });
      }
    } else {
      // If '0' is not in levels, then we do not need to invert subscriptions and just need to change the segment to 'specific'.
      setAttributes({
        invert_restrictions: '0',
        segment: 'specific'
      });
    }
  }
  return [isSelected && (0,_inspectorColorsFragment__WEBPACK_IMPORTED_MODULE_5__["default"])(props), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({
    className: "pmpro-block-require-membership-element"
  }, blockProps), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__.InnerBlocks, {
    templateLock: false
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("span", {
    className: "pmpro-block-note"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("span", {
    class: "dashicon dashicons dashicons-lock"
  }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('This block has content visibility settings.', 'paid-memberships-pro')))];
}

/***/ }),

/***/ "./blocks/src/membership/inspectorColorsFragment.js":
/*!**********************************************************!*\
  !*** ./blocks/src/membership/inspectorColorsFragment.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ inspectorColorsFragment)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__);

/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */


/**
 * WordPress dependencies
 */


function inspectorColorsFragment(props) {
  const {
    attributes: {
      invert_restrictions,
      segment,
      levels,
      show_noaccess
    },
    setAttributes
  } = props;

  // Helper function to handle changes to the segment attribute.
  const handleSegmentChange = newSegment => {
    // Set the segment attribute and clear the levels array.
    setAttributes({
      segment: newSegment,
      levels: []
    });
  };

  // Build an array of checkboxes for each level.
  const checkboxes = pmpro.all_level_values_and_labels.map(level => {
    function setLevelsAttribute(nowChecked) {
      if (nowChecked && !levels.some(levelID => levelID == level.value)) {
        // Add the level.
        const newLevels = levels.slice();
        newLevels.push(level.value + '');
        setAttributes({
          levels: newLevels
        });
      } else if (!nowChecked && levels.some(levelID => levelID == level.value)) {
        // Remove the level.
        const newLevels = levels.filter(levelID => levelID != level.value);
        setAttributes({
          levels: newLevels
        });
      }
    }
    return [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.CheckboxControl, {
      label: level.label,
      checked: levels.some(levelID => levelID == level.value),
      onChange: setLevelsAttribute
    })];
  });
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__.InspectorControls, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Content Visibility', 'paid-memberships-pro'),
    initialOpen: true
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.__experimentalHStack, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {
    className: "pmpro-block-require-membership-element__set-show-button",
    icon: "visibility",
    variant: invert_restrictions === '0' ? 'primary' : 'secondary',
    style: {
      flexGrow: '1',
      justifyContent: 'center'
    },
    onClick: () => setAttributes({
      invert_restrictions: '0'
    })
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Show', 'your-text-domain')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {
    className: "pmpro-block-require-membership-element__set-hide-button",
    icon: "hidden",
    variant: invert_restrictions === '1' ? 'primary' : 'secondary',
    style: {
      flexGrow: '1',
      justifyContent: 'center'
    },
    onClick: () => setAttributes({
      invert_restrictions: '1'
    })
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Hide', 'your-text-domain'))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("br", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
    value: segment,
    label: invert_restrictions === '1' ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Hide content from:', 'paid-memberships-pro') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Show content to:', 'paid-memberships-pro'),
    options: [{
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('All Members', 'paid-memberships-pro'),
      value: 'all'
    }, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Specific Membership Levels', 'paid-memberships-pro'),
      value: 'specific'
    }, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Logged-In Users', 'paid-memberships-pro'),
      value: 'logged_in'
    }],
    onChange: segment => handleSegmentChange(segment)
  }), segment == 'specific' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Membership Levels', 'paid-memberships-pro'))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Select', 'paid-memberships-pro'), " ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "#",
    onClick: event => {
      event.preventDefault();
      selectAllLevels(true);
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('All', 'paid-memberships-pro')), " | ", (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "#",
    onClick: event => {
      event.preventDefault();
      selectAllLevels(false);
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('None', 'paid-memberships-pro'))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    class: "pmpro-block-inspector-scrollable"
  }, checkboxes)), invert_restrictions == '0' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
    value: show_noaccess,
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Show No Access Message?', 'paid-memberships-pro'),
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Modify the 'no access' message on the Memberships > Advanced Settings page.", "paid-memberships-pro"),
    options: [{
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('No - Hide this block if the user does not have access', 'paid-memberships-pro'),
      value: '0'
    }, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Yes - Show the 'no access' message if the user does not have access", 'paid-memberships-pro'),
      value: '1'
    }],
    onChange: show_noaccess => setAttributes({
      show_noaccess
    })
  }))));
}

/***/ }),

/***/ "./blocks/src/membership/editor.scss":
/*!*******************************************!*\
  !*** ./blocks/src/membership/editor.scss ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ ((module) => {

module.exports = window["wp"]["blockEditor"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/extends.js":
/*!************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/extends.js ***!
  \************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _extends)
/* harmony export */ });
function _extends() {
  _extends = Object.assign ? Object.assign.bind() : function (target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i];
      for (var key in source) {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
          target[key] = source[key];
        }
      }
    }
    return target;
  };
  return _extends.apply(this, arguments);
}

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!*****************************************************************!*\
  !*** ./blocks/src/block-membership-required-component/index.js ***!
  \*****************************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _membership_edit__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../membership/edit */ "./blocks/src/membership/edit.js");
/* harmony import */ var _membership_inspectorColorsFragment__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../membership/inspectorColorsFragment */ "./blocks/src/membership/inspectorColorsFragment.js");






/**
 * Add the visibility attributes to the block settings.
 *
 * @param {object} settings  The block settings.
 * @param {string} name 	The block name.
 */
function addVisibilityAttribute(settings, name) {
  if (typeof settings.attributes !== 'undefined') {
    if (name.startsWith('core/')) {
      settings.attributes = Object.assign(settings.attributes, {
        restrictedLevels: {
          type: 'array',
          default: []
        },
        invert_restrictions: {
          type: "string",
          default: "0"
        },
        segment: {
          type: "string",
          default: "all"
        },
        levels: {
          type: "array",
          default: []
        },
        show_noaccess: {
          type: "string",
          default: "0"
        }
      });
    }
  }
  return settings;
}
wp.hooks.addFilter('blocks.registerBlockType', 'paid-memberships-pro/core-visibility', addVisibilityAttribute);

/**
 *  Add the visibility controls to the block inspector.
 * 
 * 
 */
const membershipRequiredComponent = wp.compose.createHigherOrderComponent(BlockEdit => {
  return props => {
    const {
      Fragment
    } = wp.element;
    const {
      isSelected
    } = props;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(BlockEdit, props), isSelected && props.name.startsWith('core/') && (0,_membership_inspectorColorsFragment__WEBPACK_IMPORTED_MODULE_4__["default"])(props));
  };
}, 'membershipRequiredComponent');
wp.hooks.addFilter('editor.BlockEdit', 'paid-memberships-pro/core-visibility', membershipRequiredComponent);
const toggleAllLevels = (toggle, props, levels) => {
  const {
    setAttributes
  } = props;
  toggle ? setAttributes({
    restrictedLevels: levels.map(level => level.value)
  }) : setAttributes({
    restrictedLevels: []
  });
  document.querySelectorAll('pmpro-required-memberships-wrapper input[type="checkbox"]').forEach(el => {
    el.checked = toggle;
  });
};
})();

/******/ })()
;
//# sourceMappingURL=index.js.map