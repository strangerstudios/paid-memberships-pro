/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./blocks/src/membership/membershipContentControls.js":
/*!************************************************************!*\
  !*** ./blocks/src/membership/membershipContentControls.js ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ MembershipContentControls)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__);

/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */


/**
 * WordPress dependencies
 */



function MembershipContentControls(props) {
  const {
    attributes: {
      visibilityBlockEnabled,
      invert_restrictions,
      segment,
      levels,
      show_noaccess
    },
    setAttributes
  } = props;
  const rootParentId = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.select)('core/block-editor').getBlockHierarchyRootClientId(props.clientId);
  const rootParent = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.select)('core/block-editor').getBlock(rootParentId);
  //We don't want to render the visibility controls on the inner block.
  const shouldRender = rootParent.name === props.name;

  // Helper function to handle changes to the segment attribute.
  const handleSegmentChange = newSegment => {
    // Set the segment attribute and clear the levels array.
    setAttributes({
      segment: newSegment,
      levels: []
    });
  };
  // Helper function to select/deselect all levels.
  const selectAllLevels = selectAll => {
    const allLevelValues = pmpro.all_level_values_and_labels.map(level => level.value + '');
    // If selectAll is true, set newLevels to all values. If false, set it to an empty array.
    const newLevels = selectAll ? allLevelValues : [];
    setAttributes({
      levels: newLevels
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
  return shouldRender && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__.InspectorControls, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Content Visibility', 'paid-memberships-pro'),
    initialOpen: true
  }, props.name !== 'pmpro/membership' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
    label: visibilityBlockEnabled ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Disable Content Visibility for this block', 'paid-memberships-pro') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Enable Content Visibility for this block', 'paid-memberships-pro'),
    onChange: newValue => {
      setAttributes({
        visibilityBlockEnabled: newValue ? true : false
      });
    },
    checked: visibilityBlockEnabled
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: {
      display: visibilityBlockEnabled ? 'block' : 'none'
    }
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
  })))));
}

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

/***/ "@wordpress/data":
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["data"];

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
/* harmony import */ var _membership_membershipContentControls__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../membership/membershipContentControls */ "./blocks/src/membership/membershipContentControls.js");




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
        "visibilityBlockEnabled": {
          "type": 'boolean',
          "default": false
        },
        "invert_restrictions": {
          "type": "string",
          "default": "0"
        },
        "segment": {
          "type": "string",
          "default": "all"
        },
        "levels": {
          "type": "array",
          "default": []
        },
        "show_noaccess": {
          "type": "string",
          "default": "0"
        }
      });
    }
  }
  return settings;
}
wp.hooks.addFilter('blocks.registerBlockType', 'paid-memberships-pro/core-visibility', addVisibilityAttribute);

/**
 *  Render the Content Visibility block in the inspector controls sidebar.
 *
 * @param {object} props The block props.
 * @return {WPElement} Element to render.
 */
const membershipRequiredComponent = wp.compose.createHigherOrderComponent(BlockEdit => {
  return props => {
    const {
      Fragment
    } = wp.element;
    const {
      isSelected
    } = props;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(BlockEdit, props), isSelected && props.name.startsWith('core/') && (0,_membership_membershipContentControls__WEBPACK_IMPORTED_MODULE_2__["default"])(props));
  };
}, 'membershipRequiredComponent');
wp.hooks.addFilter('editor.BlockEdit', 'paid-memberships-pro/core-visibility', membershipRequiredComponent);
})();

/******/ })()
;
//# sourceMappingURL=index.js.map