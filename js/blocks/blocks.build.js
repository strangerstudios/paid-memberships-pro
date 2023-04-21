/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./blocks/account-invoices-section/block.js":
/*!**************************************************!*\
  !*** ./blocks/account-invoices-section/block.js ***!
  \**************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
const {
  __
} = wp.i18n;
const {
  registerBlockType
} = wp.blocks;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/account-invoices-section', {
  title: __('PMPro Page: Account Invoices', 'paid-memberships-pro'),
  description: __('Dynamic page section that displays a list of the last 5 membership invoices for the active member.', 'paid-memberships-pro'),
  category: 'pmpro-pages',
  icon: {
    background: '#FFFFFF',
    foreground: '#1A688B',
    src: 'archive'
  },
  keywords: [__('account', 'paid-memberships-pro'), __('member', 'paid-memberships-pro'), __('order', 'paid-memberships-pro'), __('paid memberships pro', 'paid-memberships-pro'), __('pmpro', 'paid-memberships-pro'), __('purchases', 'paid-memberships-pro'), __('receipt', 'paid-memberships-pro'), __('user', 'paid-memberships-pro')],
  supports: {},
  attributes: {},

  edit() {
    return [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "pmpro-block-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-title"
    }, __('Paid Memberships Pro', 'paid-memberships-pro')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-subtitle"
    }, " ", __('Membership Account: Invoices', 'paid-memberships-pro')))];
  },

  save() {
    return null;
  }

}));

/***/ }),

/***/ "./blocks/account-links-section/block.js":
/*!***********************************************!*\
  !*** ./blocks/account-links-section/block.js ***!
  \***********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
const {
  __
} = wp.i18n;
const {
  registerBlockType
} = wp.blocks;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/account-links-section', {
  title: __('PMPro Page: Account Links', 'paid-memberships-pro'),
  description: __('Dynamic page section that displays custom links available for the active member only. This block is only visible if other Add Ons or custom code have added links.', 'paid-memberships-pro'),
  category: 'pmpro-pages',
  icon: {
    background: '#FFFFFF',
    foreground: '#1A688B',
    src: 'external'
  },
  keywords: [__('access', 'paid-memberships-pro'), __('account', 'paid-memberships-pro'), __('link', 'paid-memberships-pro'), __('member', 'paid-memberships-pro'), __('paid memberships pro', 'paid-memberships-pro'), __('pmpro', 'paid-memberships-pro'), __('quick link', 'paid-memberships-pro'), __('user', 'paid-memberships-pro')],
  supports: {},
  attributes: {},

  edit() {
    return [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "pmpro-block-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-title"
    }, __('Paid Memberships Pro', 'paid-memberships-pro')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-subtitle"
    }, __('Membership Account: Member Links', 'paid-memberships-pro')))];
  },

  save() {
    return null;
  }

}));

/***/ }),

/***/ "./blocks/account-membership-section/block.js":
/*!****************************************************!*\
  !*** ./blocks/account-membership-section/block.js ***!
  \****************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
const {
  __
} = wp.i18n;
const {
  registerBlockType
} = wp.blocks;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/account-membership-section', {
  title: __('PMPro Page: Account Memberships', 'paid-memberships-pro'),
  description: __('Dynamic page section to display the member\'s active membership information with links to view all membership options, update billing information, and change or cancel membership.', 'paid-memberships-pro'),
  category: 'pmpro-pages',
  icon: {
    background: '#FFFFFF',
    foreground: '#1A688B',
    src: 'groups'
  },
  keywords: [__('active', 'paid-memberships-pro'), __('member', 'paid-memberships-pro'), __('paid memberships pro', 'paid-memberships-pro'), __('pmpro', 'paid-memberships-pro'), __('purchases', 'paid-memberships-pro'), __('user', 'paid-memberships-pro')],
  supports: {},
  attributes: {},

  edit() {
    return [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "pmpro-block-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-title"
    }, __('Paid Memberships Pro', 'paid-memberships-pro')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-subtitle"
    }, __('Membership Account: My Memberships', 'paid-memberships-pro')))];
  },

  save() {
    return null;
  }

}));

/***/ }),

/***/ "./blocks/account-page/block.js":
/*!**************************************!*\
  !*** ./blocks/account-page/block.js ***!
  \**************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _inspector__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./inspector */ "./blocks/account-page/inspector.js");



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

const {
  __
} = wp.i18n;
const {
  registerBlockType
} = wp.blocks;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/account-page', {
  title: __('PMPro Page: Account (Full)', 'paid-memberships-pro'),
  description: __('Dynamic page section to display the selected sections of the Membership Account page including Memberships, Profile, Invoices, and Member Links. These sections can also be added via separate blocks.', 'paid-memberships-pro'),
  category: 'pmpro-pages',
  icon: {
    background: '#FFFFFF',
    foreground: '#1A688B',
    src: 'admin-users'
  },
  keywords: [__('account', 'paid-memberships-pro'), __('billing', 'paid-memberships-pro'), __('invoice', 'paid-memberships-pro'), __('links', 'paid-memberships-pro'), __('member', 'paid-memberships-pro'), __('order', 'paid-memberships-pro'), __('paid memberships pro', 'paid-memberships-pro'), __('pmpro', 'paid-memberships-pro'), __('profile', 'paid-memberships-pro'), __('purchases', 'paid-memberships-pro'), __('quick link', 'paid-memberships-pro'), __('receipt', 'paid-memberships-pro'), __('user', 'paid-memberships-pro')],
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
  edit: props => {
    const {
      setAttributes,
      isSelected
    } = props;
    return [isSelected && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_inspector__WEBPACK_IMPORTED_MODULE_2__["default"], (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({
      setAttributes
    }, props)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
      className: "pmpro-block-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("span", {
      className: "pmpro-block-title"
    }, __('Paid Memberships Pro', 'paid-memberships-pro')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("span", {
      className: "pmpro-block-subtitle"
    }, __('Membership Account Page', 'paid-memberships-pro')))];
  },

  save() {
    return null;
  }

}));

/***/ }),

/***/ "./blocks/account-page/inspector.js":
/*!******************************************!*\
  !*** ./blocks/account-page/inspector.js ***!
  \******************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ Inspector; }
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Internal block libraries
 */
const {
  __
} = wp.i18n;
const {
  Component
} = wp.element;
const {
  PanelBody,
  CheckboxControl
} = wp.components;
const {
  InspectorControls
} = wp.blockEditor;
/**
 * Create an Inspector Controls wrapper Component
 */

class Inspector extends Component {
  constructor() {
    super(...arguments);
  }

  render() {
    const {
      attributes: {
        membership,
        profile,
        invoices,
        links
      },
      setAttributes
    } = this.props;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(InspectorControls, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(CheckboxControl, {
      label: __("Show 'My Memberships' Section", 'paid-memberships-pro'),
      checked: membership,
      onChange: membership => setAttributes({
        membership
      })
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(CheckboxControl, {
      label: __("Show 'Profile' Section", 'paid-memberships-pro'),
      checked: profile,
      onChange: profile => setAttributes({
        profile
      })
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(CheckboxControl, {
      label: __("Show 'Invoices' Section", 'paid-memberships-pro'),
      checked: invoices,
      onChange: invoices => setAttributes({
        invoices
      })
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(CheckboxControl, {
      label: __("Show 'Member Links' Section", 'paid-memberships-pro'),
      checked: links,
      onChange: links => setAttributes({
        links
      })
    })));
  }

}

/***/ }),

/***/ "./blocks/account-profile-section/block.js":
/*!*************************************************!*\
  !*** ./blocks/account-profile-section/block.js ***!
  \*************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
const {
  __
} = wp.i18n;
const {
  registerBlockType
} = wp.blocks;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/account-profile-section', {
  title: __('PMPro Page: Account Profile View', 'paid-memberships-pro'),
  description: __('Dynamic page section that displays the member\'s profile as read-only information with a link to edit fields or change their password.', 'paid-memberships-pro'),
  category: 'pmpro-pages',
  icon: {
    background: '#FFFFFF',
    foreground: '#1A688B',
    src: 'admin-users'
  },
  keywords: [__('fields', 'paid-memberships-pro'), __('member', 'paid-memberships-pro'), __('paid memberships pro', 'paid-memberships-pro'), __('pmpro', 'paid-memberships-pro'), __('user', 'paid-memberships-pro')],
  supports: {},
  attributes: {},

  edit() {
    return [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "pmpro-block-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-title"
    }, __('Paid Memberships Pro', 'paid-memberships-pro')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-subtitle"
    }, __('Membership Account: Profile', 'paid-memberships-pro')))];
  },

  save() {
    return null;
  }

}));

/***/ }),

/***/ "./blocks/billing-page/block.js":
/*!**************************************!*\
  !*** ./blocks/billing-page/block.js ***!
  \**************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
const {
  __
} = wp.i18n;
const {
  registerBlockType
} = wp.blocks;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/billing-page', {
  title: __('PMPro Page: Billing', 'paid-memberships-pro'),
  description: __('Dynamic page section to display the member\'s billing information. Members can update their subscription payment method from this form.', 'paid-memberships-pro'),
  category: 'pmpro-pages',
  icon: {
    background: '#FFFFFF',
    foreground: '#1A688B',
    src: 'list-view'
  },
  keywords: [__('credit card', 'paid-memberships-pro'), __('member', 'paid-memberships-pro'), __('paid memberships pro', 'paid-memberships-pro'), __('payment method', 'paid-memberships-pro'), __('pmpro', 'paid-memberships-pro')],
  supports: {},
  attributes: {},

  edit() {
    return [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "pmpro-block-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-title"
    }, __('Paid Memberships Pro', 'paid-memberships-pro')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-subtitle"
    }, __('Membership Billing Page', 'paid-memberships-pro')))];
  },

  save() {
    return null;
  }

}));

/***/ }),

/***/ "./blocks/cancel-page/block.js":
/*!*************************************!*\
  !*** ./blocks/cancel-page/block.js ***!
  \*************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
const {
  __
} = wp.i18n;
const {
  registerBlockType
} = wp.blocks;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/cancel-page', {
  title: __('PMPro Page: Cancel', 'paid-memberships-pro'),
  description: __('Dynamic page section where members can cancel their membership and active subscription if applicable.', 'paid-memberships-pro'),
  category: 'pmpro-pages',
  icon: {
    background: '#FFFFFF',
    foreground: '#1A688B',
    src: 'no'
  },
  keywords: [__('member', 'paid-memberships-pro'), __('paid memberships pro', 'paid-memberships-pro'), __('payment method', 'paid-memberships-pro'), __('pmpro', 'paid-memberships-pro'), __('terminate', 'paid-memberships-pro')],
  supports: {},
  attributes: {},

  edit() {
    return [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "pmpro-block-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-title"
    }, __('Paid Memberships Pro', 'paid-memberships-pro')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-subtitle"
    }, __('Membership Cancel Page', 'paid-memberships-pro')))];
  },

  save() {
    return null;
  }

}));

/***/ }),

/***/ "./blocks/checkout-button/block.js":
/*!*****************************************!*\
  !*** ./blocks/checkout-button/block.js ***!
  \*****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _inspector__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./inspector */ "./blocks/checkout-button/inspector.js");



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

const {
  __
} = wp.i18n;
const {
  registerBlockType
} = wp.blocks;
const {
  TextControl,
  SelectControl
} = wp.components;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/checkout-button', {
  title: __('Membership Checkout Button', 'paid-memberships-pro'),
  description: __('Inserts a button that links directly to membership checkout for the selected level.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#FFFFFF',
    foreground: '#658B24',
    src: 'migrate'
  },
  keywords: [__('buy', 'paid-memberships-pro'), __('level', 'paid-memberships-pro'), __('member', 'paid-memberships-pro'), __('paid memberships pro', 'paid-memberships-pro'), __('pmpro', 'paid-memberships-pro'), __('purchase', 'paid-memberships-pro')],
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
  edit: props => {
    const {
      attributes: {
        text,
        level,
        css_class
      },
      className,
      setAttributes,
      isSelected
    } = props;
    return [isSelected && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_inspector__WEBPACK_IMPORTED_MODULE_2__["default"], (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({
      setAttributes
    }, props)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
      className: className
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("a", {
      class: css_class
    }, text)), isSelected && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
      className: "pmpro-block-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(TextControl, {
      label: __('Button Text', 'paid-memberships-pro'),
      value: text,
      onChange: text => setAttributes({
        text
      })
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(SelectControl, {
      label: __('Membership Level', 'paid-memberships-pro'),
      value: level,
      onChange: level => setAttributes({
        level
      }),
      options: window.pmpro.all_level_values_and_labels
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(TextControl, {
      label: __('CSS Class', 'paid-memberships-pro'),
      value: css_class,
      onChange: css_class => setAttributes({
        css_class
      })
    }))];
  },

  save() {
    return null;
  }

}));

/***/ }),

/***/ "./blocks/checkout-button/inspector.js":
/*!*********************************************!*\
  !*** ./blocks/checkout-button/inspector.js ***!
  \*********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ Inspector; }
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Internal block libraries
 */
const {
  __
} = wp.i18n;
const {
  Component
} = wp.element;
const {
  PanelBody,
  TextControl,
  SelectControl
} = wp.components;
const {
  InspectorControls
} = wp.blockEditor;
/**
 * Create an Inspector Controls wrapper Component
 */

class Inspector extends Component {
  constructor() {
    super(...arguments);
  }

  render() {
    const {
      attributes: {
        text,
        level,
        css_class
      },
      setAttributes
    } = this.props;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(InspectorControls, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(TextControl, {
      label: __('Button Text', 'paid-memberships-pro'),
      help: __('Text for checkout button', 'paid-memberships-pro'),
      value: text,
      onChange: text => setAttributes({
        text
      })
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(SelectControl, {
      label: __('Level', 'paid-memberships-pro'),
      help: __('The level to link to for checkout button', 'paid-memberships-pro'),
      value: level,
      onChange: level => setAttributes({
        level
      }),
      options: window.pmpro.all_level_values_and_labels
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(TextControl, {
      label: __('CSS Class', 'paid-memberships-pro'),
      help: __('Additional styling for checkout button', 'paid-memberships-pro'),
      value: css_class,
      onChange: css_class => setAttributes({
        css_class
      })
    })));
  }

}

/***/ }),

/***/ "./blocks/checkout-page/block.js":
/*!***************************************!*\
  !*** ./blocks/checkout-page/block.js ***!
  \***************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _inspector__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./inspector */ "./blocks/checkout-page/inspector.js");



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

const {
  __
} = wp.i18n;
const {
  registerBlockType
} = wp.blocks;
const {
  SelectControl
} = wp.components;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/checkout-page', {
  title: __('Membership Checkout Form', 'paid-memberships-pro'),
  description: __('Dynamic form that allows users to complete free registration or paid checkout for the selected membership level.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#FFFFFF',
    foreground: '#658B24',
    src: 'list-view'
  },
  keywords: [__('member', 'paid-memberships-pro'), __('paid memberships pro', 'paid-memberships-pro'), __('pmpro', 'paid-memberships-pro'), __('buy', 'paid-memberships-pro'), __('purchase', 'paid-memberships-pro'), __('sell', 'paid-memberships-pro')],
  supports: {},
  attributes: {
    pmpro_default_level: {
      type: 'string',
      source: 'meta',
      meta: 'pmpro_default_level'
    }
  },
  edit: props => {
    const {
      attributes: {
        pmpro_default_level
      },
      className,
      setAttributes,
      isSelected
    } = props;
    return [isSelected && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(_inspector__WEBPACK_IMPORTED_MODULE_2__["default"], (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({
      setAttributes
    }, props)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
      className: "pmpro-block-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("span", {
      className: "pmpro-block-title"
    }, __('Paid Memberships Pro', 'paid-memberships-pro')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("span", {
      className: "pmpro-block-subtitle"
    }, __('Membership Checkout Form', 'paid-memberships-pro')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)("hr", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.createElement)(SelectControl, {
      label: __('Membership Level', 'paid-memberships-pro'),
      value: pmpro_default_level,
      onChange: pmpro_default_level => setAttributes({
        pmpro_default_level
      }),
      options: window.pmpro.all_level_values_and_labels
    }))];
  },

  save() {
    return null;
  }

}));

/***/ }),

/***/ "./blocks/checkout-page/inspector.js":
/*!*******************************************!*\
  !*** ./blocks/checkout-page/inspector.js ***!
  \*******************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ Inspector; }
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Internal block libraries
 */
const {
  __
} = wp.i18n;
const {
  Component
} = wp.element;
const {
  PanelBody,
  PanelRow,
  SelectControl
} = wp.components;
const {
  InspectorControls
} = wp.blockEditor;
/**
 * Create an Inspector Controls wrapper Component
 */

class Inspector extends Component {
  constructor() {
    super(...arguments);
  }

  render() {
    const {
      attributes: {
        pmpro_default_level
      },
      setAttributes
    } = this.props;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(InspectorControls, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(SelectControl, {
      label: __('Membership Level', 'paid-memberships-pro'),
      help: __('Choose a default level for Membership Checkout.', 'paid-memberships-pro'),
      value: pmpro_default_level,
      onChange: pmpro_default_level => setAttributes({
        pmpro_default_level
      }),
      options: [''].concat(window.pmpro.all_level_values_and_labels)
    })));
  }

}

/***/ }),

/***/ "./blocks/confirmation-page/block.js":
/*!*******************************************!*\
  !*** ./blocks/confirmation-page/block.js ***!
  \*******************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
const {
  __
} = wp.i18n;
const {
  registerBlockType
} = wp.blocks;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/confirmation-page', {
  title: __('PMPro Page: Confirmation', 'paid-memberships-pro'),
  description: __('Dynamic page section that displays a confirmation message and purchase information for the active member immediately after membership registration and checkout.', 'paid-memberships-pro'),
  category: 'pmpro-pages',
  icon: {
    background: '#FFFFFF',
    foreground: '#1A688B',
    src: 'yes'
  },
  keywords: [__('member', 'paid-memberships-pro'), __('buy', 'paid-memberships-pro'), __('paid memberships pro', 'paid-memberships-pro'), __('pmpro', 'paid-memberships-pro'), __('purchase', 'paid-memberships-pro'), __('receipt', 'paid-memberships-pro'), __('success', 'paid-memberships-pro')],
  supports: {},
  attributes: {},

  edit() {
    return [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "pmpro-block-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-title"
    }, __('Paid Memberships Pro', 'paid-memberships-pro')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-subtitle"
    }, __('Membership Confirmation Page', 'paid-memberships-pro')))];
  },

  save() {
    return null;
  }

}));

/***/ }),

/***/ "./blocks/i18n.js":
/*!************************!*\
  !*** ./blocks/i18n.js ***!
  \************************/
/***/ (function() {

wp.i18n.setLocaleData({
  '': {}
}, 'paid-memberships-pro');

/***/ }),

/***/ "./blocks/invoice-page/block.js":
/*!**************************************!*\
  !*** ./blocks/invoice-page/block.js ***!
  \**************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
const {
  __
} = wp.i18n;
const {
  registerBlockType
} = wp.blocks;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/invoice-page', {
  title: __('PMPro Page: Invoice', 'paid-memberships-pro'),
  description: __('Dynamic page section that displays a list of all invoices (purchase history) for the active member. Each invoice can be selected and viewed in full detail.', 'paid-memberships-pro'),
  category: 'pmpro-pages',
  icon: {
    background: '#FFFFFF',
    foreground: '#1A688B',
    src: 'archive'
  },
  keywords: [__('history', 'paid-memberships-pro'), __('order', 'paid-memberships-pro'), __('paid memberships pro', 'paid-memberships-pro'), __('pmpro', 'paid-memberships-pro'), __('purchases', 'paid-memberships-pro'), __('receipt', 'paid-memberships-pro')],
  supports: {},
  attributes: {},

  edit() {
    return [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "pmpro-block-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-title"
    }, __('Paid Memberships Pro', 'paid-memberships-pro')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-subtitle"
    }, __('Membership Invoices', 'paid-memberships-pro')))];
  },

  save() {
    return null;
  }

}));

/***/ }),

/***/ "./blocks/levels-page/block.js":
/*!*************************************!*\
  !*** ./blocks/levels-page/block.js ***!
  \*************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
const {
  __
} = wp.i18n;
const {
  registerBlockType
} = wp.blocks;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/levels-page', {
  title: __('Membership Levels and Pricing Table', 'paid-memberships-pro'),
  description: __('Dynamic page section that displays a list of membership levels and pricing, linked to membership checkout. To reorder the display, navigate to Memberships > Settings > Levels.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#FFFFFF',
    foreground: '#658B24',
    src: 'list-view'
  },
  keywords: [__('level', 'paid-memberships-pro'), __('paid memberships pro', 'paid-memberships-pro'), __('pmpro', 'paid-memberships-pro'), __('price', 'paid-memberships-pro'), __('pricing table', 'paid-memberships-pro')],
  supports: {},
  attributes: {},

  edit() {
    return [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "pmpro-block-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-title"
    }, __('Paid Memberships Pro', 'paid-memberships-pro')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-subtitle"
    }, __('Membership Levels List', 'paid-memberships-pro')))];
  },

  save() {
    return null;
  }

}));

/***/ }),

/***/ "./blocks/login/block.js":
/*!*******************************!*\
  !*** ./blocks/login/block.js ***!
  \*******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _inspector__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./inspector */ "./blocks/login/inspector.js");


/**
 * Block: PMPro Login Form
 *
 * Add a login form to any page or post.
 *
 */

/**
 * Block dependencies
 */

/**
 * Internal block libraries
 */

const {
  __
} = wp.i18n;
const {
  registerBlockType
} = wp.blocks;
const {
  Fragment
} = wp.element;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/login-form', {
  title: __('Login Form', 'paid-memberships-pro'),
  description: __('Dynamic form that allows users to log in or recover a lost password. Logged in users can see a welcome message with the selected custom menu.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#FFFFFF',
    foreground: '#658B24',
    src: 'unlock'
  },
  keywords: [__('log in', 'paid-memberships-pro'), __('lost password', 'paid-memberships-pro'), __('paid memberships pro', 'paid-memberships-pro'), __('password reset', 'paid-memberships-pro'), __('pmpro', 'paid-memberships-pro')],
  supports: {},
  edit: props => {
    return [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_inspector__WEBPACK_IMPORTED_MODULE_1__["default"], props), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "pmpro-block-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-title"
    }, __("Paid Memberships Pro", "paid-memberships-pro")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-subtitle"
    }, __("Log in Form", "paid-memberships-pro"))))];
  },

  save() {
    return null;
  }

}));

/***/ }),

/***/ "./blocks/login/inspector.js":
/*!***********************************!*\
  !*** ./blocks/login/inspector.js ***!
  \***********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ Inspector; }
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Internal block libraries
 */
const {
  __
} = wp.i18n;
const {
  Component
} = wp.element;
const {
  PanelBody,
  SelectControl,
  ToggleControl
} = wp.components;
const {
  InspectorControls
} = wp.blockEditor;
/**
 * Create an Inspector Controls wrapper Component
 */

class Inspector extends Component {
  constructor() {
    super(...arguments);
  }

  render() {
    const {
      attributes,
      setAttributes
    } = this.props;
    const {
      display_if_logged_in,
      show_menu,
      show_logout_link,
      location
    } = attributes;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(InspectorControls, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(ToggleControl, {
      label: __("Display 'Welcome' content when logged in.", "paid-memberships-pro"),
      checked: display_if_logged_in,
      onChange: value => {
        this.props.setAttributes({
          display_if_logged_in: value
        });
      }
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(ToggleControl, {
      label: __("Display the 'Log In Widget' menu.", "paid-memberships-pro"),
      help: __("Assign the menu under Appearance > Menus.", "paid-memberships-pro"),
      checked: show_menu,
      onChange: value => {
        this.props.setAttributes({
          show_menu: value
        });
      }
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(ToggleControl, {
      label: __("Display a 'Log Out' link.", "paid-memberships-pro"),
      checked: show_logout_link,
      onChange: value => {
        this.props.setAttributes({
          show_logout_link: value
        });
      }
    })));
  }

}

/***/ }),

/***/ "./blocks/member-profile-edit/block.js":
/*!*********************************************!*\
  !*** ./blocks/member-profile-edit/block.js ***!
  \*********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Block: PMPro Member Profile Edit
 *
 *
 */

/**
 * Internal block libraries
 */
const {
  __
} = wp.i18n;
const {
  registerBlockType
} = wp.blocks;
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/member-profile-edit', {
  title: __('PMPro Page: Account Profile Edit', 'paid-memberships-pro'),
  description: __('Dynamic form that allows the current logged in member to edit their default user profile information and any custom user profile fields.', 'paid-memberships-pro'),
  category: 'pmpro-pages',
  icon: {
    background: '#FFFFFF',
    foreground: '#1A688B',
    src: 'admin-users'
  },
  keywords: [__('custom field', 'paid-memberships-pro'), __('fields', 'paid-memberships-pro'), __('paid memberships pro', 'paid-memberships-pro'), __('pmpro', 'paid-memberships-pro'), __('user fields', 'paid-memberships-pro')],
  edit: props => {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "pmpro-block-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-title"
    }, __("Paid Memberships Pro", "paid-memberships-pro")), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-subtitle"
    }, __("Member Profile Edit", "paid-memberships-pro")));
  },

  save() {
    return null;
  }

}));

/***/ }),

/***/ "./blocks/membership/block.js":
/*!************************************!*\
  !*** ./blocks/membership/block.js ***!
  \************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
const {
  __
} = wp.i18n;
const {
  registerBlockType
} = wp.blocks;
const {
  PanelBody,
  CheckboxControl,
  SelectControl
} = wp.components;
const {
  InspectorControls,
  InnerBlocks
} = wp.blockEditor;
const all_levels = [{
  value: 0,
  label: "Non-Members"
}].concat(pmpro.all_level_values_and_labels);
/**
 * Register block
 */

/* harmony default export */ __webpack_exports__["default"] = (registerBlockType('pmpro/membership', {
  title: __('Membership Required Block', 'paid-memberships-pro'),
  description: __('Nest blocks within this wrapper to control the inner block visibility by membership level or for non-members only.', 'paid-memberships-pro'),
  category: 'pmpro',
  icon: {
    background: '#FFFFFF',
    foreground: '#1A688B',
    src: 'visibility'
  },
  keywords: [__('block visibility', 'paid-memberships-pro'), __('conditional', 'paid-memberships-pro'), __('content', 'paid-memberships-pro'), __('hide', 'paid-memberships-pro'), __('hidden', 'paid-memberships-pro'), __('paid memberships pro', 'paid-memberships-pro'), __('pmpro', 'paid-memberships-pro'), __('private', 'paid-memberships-pro'), __('restrict', 'paid-memberships-pro')],
  attributes: {
    levels: {
      type: 'array',
      default: []
    },
    uid: {
      type: 'string',
      default: ''
    },
    show_noaccess: {
      type: 'string',
      default: '0'
    }
  },
  edit: props => {
    const {
      attributes: {
        levels,
        uid,
        show_noaccess
      },
      setAttributes,
      isSelected
    } = props;

    if (uid == '') {
      var rand = Math.random() + "";
      setAttributes({
        uid: rand
      });
    } // Build an array of checkboxes for each level.


    var checkboxes = all_levels.map(function (level) {
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

      return [(0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(CheckboxControl, {
        label: level.label,
        checked: levels.some(levelID => levelID == level.value),
        onChange: setLevelsAttribute
      })];
    });
    return [isSelected && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(InspectorControls, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, __('Which membership levels can view this block?', 'paid-memberships-pro'))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      class: "pmpro-block-inspector-scrollable"
    }, checkboxes), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("hr", null), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, __('What should users without access see?', 'paid-memberships-pro'))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(SelectControl, {
      value: show_noaccess,
      help: __("Modify the 'no access' message on the Memberships > Advanced Settings page.", "paid-memberships-pro"),
      options: [{
        label: __("Show nothing", 'paid-memberships-pro'),
        value: '0'
      }, {
        label: __("Show the 'no access' message", 'paid-memberships-pro'),
        value: '1'
      }],
      onChange: show_noaccess => setAttributes({
        show_noaccess
      })
    }))), isSelected && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "pmpro-block-require-membership-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-title"
    }, __('Membership Required', 'paid-memberships-pro')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      class: "pmpro-block-inspector-scrollable"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, null, checkboxes)), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(InnerBlocks, {
      templateLock: false
    })), !isSelected && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "pmpro-block-require-membership-element"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "pmpro-block-title"
    }, __('Membership Required', 'paid-memberships-pro')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(InnerBlocks, {
      templateLock: false
    }))];
  },
  save: props => {
    const {
      className
    } = props;
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: className
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(InnerBlocks.Content, null));
  }
}));

/***/ }),

/***/ "./blocks/sidebar/index.js":
/*!*********************************!*\
  !*** ./blocks/sidebar/index.js ***!
  \*********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);


/**
 * Require Membership sidebar panel.
 */



function pmproCustomStore() {
  return {
    name: 'pmpro/require-membership',
    instantiate: () => {
      const listeners = new Set();
      const storeData = {
        restrictedLevels: []
      };

      function storeChanged() {
        for (const listener of listeners) {
          listener();
        }
      }

      function subscribe(listener) {
        listeners.add(listener);
        return () => listeners.delete(listener);
      }

      const selectors = {
        getRestrictedLevels() {
          return storeData['restrictedLevels'];
        }

      };
      const actions = {
        setRestrictedLevels(restrictedLevels) {
          storeData['restrictedLevels'] = restrictedLevels;
          storeChanged();
        },

        fetchRestrictedLevels() {
          _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
            path: 'pmpro/v1/post_restrictions/?post_id=' + pmpro_blocks.post_id
          }).then(data => {
            // Set the restricted levels to the membership_id values.
            actions.setRestrictedLevels(data.map(item => item.membership_id));
            storeChanged();
          }).catch(error => {
            console.error(error);
          });
        },

        saveRestrictedLevels() {
          _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
            path: 'pmpro/v1/post_restrictions/',
            method: 'POST',
            data: {
              post_id: pmpro_blocks.post_id,
              level_ids: storeData['restrictedLevels']
            }
          });
        }

      };
      actions.fetchRestrictedLevels();
      return {
        getSelectors: () => selectors,
        getActions: () => actions,
        subscribe
      };
    }
  };
}

(0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.register)(pmproCustomStore());

(function (wp) {
  const {
    __
  } = wp.i18n;
  const {
    registerPlugin
  } = wp.plugins;
  const {
    PluginDocumentSettingPanel
  } = wp.editPost;
  const {
    Component
  } = wp.element;
  const {
    Spinner,
    CheckboxControl
  } = wp.components;
  const {
    withSelect,
    withDispatch,
    dispatch
  } = wp.data;
  const {
    compose
  } = wp.compose;
  const RequireMembershipControl = compose(withDispatch(function (dispatch, props) {
    return {
      setRestrictedLevelsValue: function (value) {
        dispatch('pmpro/require-membership').setRestrictedLevels(value); // Add another action to update a fake meta value to force the save button to enable.

        dispatch('core/editor').editPost({
          meta: {
            pmpro_force_save_enable: '1'
          }
        });
      }
    };
  }), withSelect(function (select, props) {
    return {
      restrictedLevels: select('pmpro/require-membership').getRestrictedLevels()
    };
  }))(function (props) {
    const level_checkboxes = props.levels.map(level => {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(CheckboxControl, {
        key: level.id,
        label: level.name,
        checked: props.restrictedLevels.includes(level.id),
        onChange: () => {
          let newValue = [...props.restrictedLevels];

          if (newValue.includes(level.id)) {
            newValue = newValue.filter(item => item !== level.id);
          } else {
            newValue.push(level.id);
          }

          props.setRestrictedLevelsValue(newValue);
        }
      });
    });
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("fragment", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, __('Select levels to restrict by', 'paid-memberships-pro')), level_checkboxes.length > 6 ? (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "pmpro-scrollable-div"
    }, level_checkboxes) : level_checkboxes);
  }); // Whenever a post is saved, call the saveRestrictedLevels action.

  wp.data.subscribe(function () {
    if (wp.data.select('core/editor').isSavingPost()) {
      dispatch('pmpro/require-membership').saveRestrictedLevels();
    }
  });

  class PMProSidebar extends Component {
    constructor(props) {
      super(props);
      this.state = {
        levelList: [],
        loadingLevels: true
      };
    }

    componentDidMount() {
      this.fetchlevels();
    }

    fetchlevels() {
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
        path: 'pmpro/v1/membership_levels'
      }).then(data => {
        // If data is an object, convert to associative array
        if (typeof data === 'object') {
          data = Object.keys(data).map(function (key) {
            return data[key];
          });
        }

        this.setState({
          levelList: data,
          loadingLevels: false
        });
      }).catch(error => {
        this.setState({
          levelList: error,
          loadingLevels: false
        });
      });
    }

    render() {
      var sidebar_content = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(Spinner, null);

      if (!this.state.loadingLevels) {
        if (!Array.isArray(this.state.levelList)) {
          sidebar_content = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, __('Error retrieving membership levels.', 'restrict-with-stripe') + ' ' + this.state.levelList);
        } else if (this.state.levelList.length === 0) {
          sidebar_content = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, __('No levels found. Please create a level to restrict content.', 'paid-memberships-pro'));
        } else {
          sidebar_content = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(RequireMembershipControl, {
            label: __('Membership Levels', 'paid-memberships-pro'),
            levels: this.state.levelList
          }));
        }
      }

      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(PluginDocumentSettingPanel, {
        name: "pmpro-sidebar-panel",
        title: __('Require Membership', 'paid-memberships-pro')
      }, sidebar_content);
    }

  }

  registerPlugin('pmpro-sidebar', {
    icon: 'lock',
    render: PMProSidebar
  });
})(window.wp);

/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ (function(module) {

"use strict";
module.exports = window["wp"]["apiFetch"];

/***/ }),

/***/ "@wordpress/data":
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
/***/ (function(module) {

"use strict";
module.exports = window["wp"]["data"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ (function(module) {

"use strict";
module.exports = window["wp"]["element"];

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/extends.js":
/*!************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/extends.js ***!
  \************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ _extends; }
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
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
!function() {
"use strict";
/*!**************************!*\
  !*** ./blocks/blocks.js ***!
  \**************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _i18n_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./i18n.js */ "./blocks/i18n.js");
/* harmony import */ var _i18n_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_i18n_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _checkout_button_block_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./checkout-button/block.js */ "./blocks/checkout-button/block.js");
/* harmony import */ var _account_page_block_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./account-page/block.js */ "./blocks/account-page/block.js");
/* harmony import */ var _account_membership_section_block_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./account-membership-section/block.js */ "./blocks/account-membership-section/block.js");
/* harmony import */ var _account_profile_section_block_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./account-profile-section/block.js */ "./blocks/account-profile-section/block.js");
/* harmony import */ var _account_invoices_section_block_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./account-invoices-section/block.js */ "./blocks/account-invoices-section/block.js");
/* harmony import */ var _account_links_section_block_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./account-links-section/block.js */ "./blocks/account-links-section/block.js");
/* harmony import */ var _billing_page_block_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./billing-page/block.js */ "./blocks/billing-page/block.js");
/* harmony import */ var _cancel_page_block_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./cancel-page/block.js */ "./blocks/cancel-page/block.js");
/* harmony import */ var _checkout_page_block_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./checkout-page/block.js */ "./blocks/checkout-page/block.js");
/* harmony import */ var _confirmation_page_block_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./confirmation-page/block.js */ "./blocks/confirmation-page/block.js");
/* harmony import */ var _invoice_page_block_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./invoice-page/block.js */ "./blocks/invoice-page/block.js");
/* harmony import */ var _levels_page_block_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./levels-page/block.js */ "./blocks/levels-page/block.js");
/* harmony import */ var _membership_block_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./membership/block.js */ "./blocks/membership/block.js");
/* harmony import */ var _member_profile_edit_block_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./member-profile-edit/block.js */ "./blocks/member-profile-edit/block.js");
/* harmony import */ var _login_block_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./login/block.js */ "./blocks/login/block.js");
/* harmony import */ var _sidebar_index_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./sidebar/index.js */ "./blocks/sidebar/index.js");


/**
 * Import internationalization
 */

/**
 * Import registerBlockType blocks
 */


















(function () {
  const PMProSVG = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    version: "1.1",
    id: "Layer_1",
    x: "0px",
    y: "0px",
    viewBox: "0 0 18 18"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    d: "M17.99,4.53c-0.35,0.12-0.7,0.26-1.06,0.4c-0.35,0.14-0.7,0.3-1.05,0.46c-0.35,0.16-0.69,0.33-1.03,0.51 c-0.34,0.18-0.68,0.37-1.02,0.56c-0.15,0.09-0.31,0.18-0.46,0.27c-0.15,0.09-0.3,0.19-0.45,0.28c-0.15,0.1-0.3,0.19-0.45,0.29 c-0.15,0.1-0.3,0.2-0.44,0.3c-0.08,0.05-0.16,0.11-0.23,0.16c-0.08,0.05-0.16,0.11-0.23,0.17c-0.08,0.06-0.15,0.11-0.23,0.17 c-0.08,0.06-0.15,0.11-0.23,0.17c-0.07,0.05-0.13,0.1-0.2,0.15c-0.07,0.05-0.13,0.1-0.2,0.15c-0.07,0.05-0.13,0.1-0.2,0.15 c-0.07,0.05-0.13,0.1-0.2,0.16c-0.04,0.03-0.09,0.07-0.13,0.1c-0.04,0.03-0.09,0.07-0.13,0.1C10,9.13,9.95,9.17,9.91,9.2 C9.87,9.24,9.83,9.27,9.79,9.31C9.77,9.32,9.75,9.33,9.74,9.35C9.72,9.36,9.71,9.37,9.69,9.39C9.67,9.4,9.66,9.42,9.64,9.43 C9.63,9.44,9.61,9.46,9.59,9.47C9.54,9.52,9.49,9.56,9.43,9.61C9.38,9.65,9.33,9.7,9.27,9.74C9.22,9.79,9.17,9.84,9.11,9.88 c-0.05,0.05-0.11,0.09-0.16,0.14c-0.27,0.24-0.54,0.49-0.81,0.75c-0.26,0.25-0.53,0.51-0.78,0.78c-0.26,0.26-0.51,0.53-0.76,0.81 c-0.25,0.27-0.49,0.55-0.73,0.84c-0.03,0.04-0.07,0.08-0.1,0.12c-0.03,0.04-0.07,0.08-0.1,0.12c-0.03,0.04-0.07,0.08-0.1,0.12 c-0.03,0.04-0.07,0.08-0.1,0.12c-0.03,0.04-0.07,0.08-0.1,0.12c-0.03,0.04-0.06,0.08-0.1,0.12c-0.03,0.04-0.06,0.08-0.1,0.12 c-0.03,0.04-0.06,0.08-0.1,0.12c0,0.01-0.01,0.01-0.01,0.02c0,0.01-0.01,0.01-0.01,0.02c0,0.01-0.01,0.01-0.01,0.02 c0,0.01-0.01,0.01-0.01,0.02c-0.03,0.03-0.05,0.07-0.08,0.1c-0.03,0.03-0.05,0.07-0.08,0.1c-0.03,0.03-0.05,0.07-0.08,0.11 c-0.03,0.03-0.05,0.07-0.08,0.11c-0.03,0.04-0.06,0.08-0.09,0.12c-0.03,0.04-0.06,0.08-0.09,0.12C4.5,14.96,4.47,15,4.44,15.05 c-0.03,0.04-0.06,0.08-0.09,0.13c0,0-0.01,0.01-0.01,0.01c0,0-0.01,0.01-0.01,0.01c0,0-0.01,0.01-0.01,0.01c0,0-0.01,0.01-0.01,0.01 c-0.15,0.22-0.31,0.44-0.46,0.67c-0.15,0.22-0.3,0.45-0.44,0.68c-0.14,0.23-0.29,0.46-0.43,0.7C2.85,17.52,2.71,17.76,2.58,18 c-0.08-0.19-0.16-0.38-0.23-0.56c-0.07-0.18-0.14-0.35-0.21-0.51c-0.07-0.16-0.13-0.32-0.19-0.47c-0.06-0.15-0.12-0.3-0.18-0.45 l-0.01,0.01l0.01-0.03c-0.01-0.03-0.02-0.05-0.03-0.08c-0.01-0.02-0.02-0.05-0.03-0.07c-0.01-0.02-0.02-0.05-0.03-0.07 c-0.01-0.02-0.02-0.05-0.03-0.07c0-0.01-0.01-0.02-0.01-0.02c0-0.01-0.01-0.02-0.01-0.02c0-0.01-0.01-0.02-0.01-0.02 c0-0.01-0.01-0.02-0.01-0.02c-0.01-0.02-0.01-0.04-0.02-0.05c-0.01-0.02-0.01-0.04-0.02-0.05c-0.01-0.02-0.01-0.04-0.02-0.05 c-0.01-0.02-0.01-0.04-0.02-0.05c-0.01-0.03-0.02-0.05-0.03-0.07c-0.01-0.02-0.02-0.05-0.03-0.07c-0.01-0.02-0.02-0.05-0.03-0.07 c-0.01-0.02-0.02-0.05-0.03-0.07c-0.01-0.02-0.02-0.05-0.03-0.07c-0.01-0.02-0.02-0.05-0.03-0.07c-0.01-0.02-0.02-0.05-0.03-0.07 c-0.01-0.02-0.02-0.05-0.03-0.07c-0.02-0.05-0.04-0.1-0.06-0.16c-0.02-0.05-0.04-0.1-0.06-0.16c-0.02-0.05-0.04-0.11-0.06-0.16 c-0.02-0.05-0.04-0.11-0.06-0.16c-0.08-0.23-0.16-0.47-0.25-0.72c-0.08-0.25-0.17-0.5-0.26-0.77c-0.09-0.27-0.18-0.55-0.27-0.84 c-0.09-0.29-0.19-0.6-0.29-0.93c0.05,0.07,0.1,0.15,0.15,0.22c0.05,0.07,0.1,0.14,0.14,0.2c0.05,0.07,0.09,0.13,0.14,0.19 c0.04,0.06,0.09,0.12,0.13,0.18c0.09,0.13,0.18,0.24,0.27,0.35c0.09,0.11,0.17,0.21,0.24,0.3c0.08,0.09,0.15,0.18,0.23,0.27 c0.07,0.09,0.15,0.17,0.22,0.25c0.02,0.02,0.03,0.04,0.05,0.06c0.02,0.02,0.03,0.04,0.05,0.06c0.02,0.02,0.03,0.04,0.05,0.06 c0.02,0.02,0.03,0.04,0.05,0.06c0.07,0.07,0.13,0.14,0.2,0.22c0.07,0.08,0.14,0.16,0.22,0.24c0.08,0.08,0.16,0.17,0.24,0.27 c0.09,0.1,0.18,0.2,0.27,0.31c0.01,0.01,0.02,0.02,0.03,0.03c0.01,0.01,0.02,0.02,0.03,0.03c0.01,0.01,0.02,0.02,0.03,0.04 c0.01,0.01,0.02,0.02,0.03,0.04c0.02-0.02,0.04-0.05,0.06-0.07c0.02-0.02,0.04-0.05,0.06-0.07c0.02-0.02,0.04-0.05,0.06-0.07 C2.96,14.03,2.98,14,3,13.98c0.03-0.03,0.05-0.06,0.08-0.09c0.03-0.03,0.05-0.06,0.08-0.09c0.03-0.03,0.05-0.06,0.08-0.09 c0.03-0.03,0.05-0.06,0.08-0.09c0.28-0.33,0.58-0.65,0.88-0.97c0.31-0.32,0.63-0.62,0.95-0.92c0.33-0.3,0.67-0.6,1.02-0.88 c0.35-0.29,0.72-0.57,1.09-0.84c0.06-0.04,0.11-0.08,0.17-0.12C7.49,9.83,7.55,9.79,7.6,9.75c0.06-0.04,0.11-0.08,0.17-0.12 c0.06-0.04,0.12-0.08,0.17-0.12C7.97,9.5,7.98,9.49,8,9.48c0.02-0.01,0.03-0.02,0.05-0.03C8.06,9.43,8.08,9.42,8.1,9.41 C8.11,9.4,8.13,9.38,8.14,9.37c0.05-0.03,0.1-0.06,0.14-0.1c0.05-0.03,0.1-0.06,0.14-0.1c0.05-0.03,0.1-0.06,0.14-0.1 c0.05-0.03,0.1-0.06,0.15-0.09C8.79,8.94,8.87,8.9,8.94,8.85C9.01,8.8,9.09,8.76,9.16,8.71c0.07-0.05,0.15-0.09,0.22-0.14 c0.07-0.05,0.15-0.09,0.22-0.14c0.09-0.05,0.17-0.11,0.26-0.16c0.09-0.05,0.17-0.1,0.26-0.16c0.09-0.05,0.18-0.1,0.27-0.15 c0.09-0.05,0.18-0.1,0.27-0.15c0.25-0.14,0.51-0.28,0.76-0.42c0.26-0.14,0.52-0.27,0.78-0.41c0.26-0.13,0.53-0.27,0.79-0.4 c0.27-0.13,0.54-0.26,0.81-0.38c0.01,0,0.02-0.01,0.03-0.01c0.01,0,0.02-0.01,0.03-0.01c0.01,0,0.02-0.01,0.03-0.01 c0.01,0,0.02-0.01,0.03-0.01c0.33-0.15,0.67-0.3,1-0.44c0.34-0.15,0.68-0.29,1.02-0.42c0.34-0.14,0.69-0.27,1.03-0.4 C17.31,4.77,17.65,4.64,17.99,4.53z M15.73,9.59l0.65,4.56l-10.4-0.05c-0.02,0.02-0.04,0.04-0.05,0.07 c-0.02,0.02-0.04,0.04-0.05,0.07c-0.02,0.02-0.04,0.04-0.05,0.07c-0.02,0.02-0.04,0.04-0.05,0.07c-0.02,0.02-0.03,0.04-0.05,0.06 c-0.02,0.02-0.03,0.04-0.05,0.06c-0.02,0.02-0.03,0.04-0.05,0.06c-0.02,0.02-0.03,0.04-0.05,0.06l11.23,0.2l-0.78-5.24L15.73,9.59z M6.75,13.2c-0.04,0.04-0.08,0.09-0.11,0.13c-0.04,0.04-0.08,0.09-0.11,0.13c-0.04,0.04-0.07,0.09-0.11,0.13l9.22-0.07L15.04,9.1 l-0.07-0.53l-0.39,0.04l0.55,4.3l-8.27,0.17C6.83,13.12,6.79,13.16,6.75,13.2z M13.78,7.66l-0.59,0.08 c-0.06,0.04-0.12,0.08-0.18,0.12c-0.06,0.04-0.12,0.08-0.18,0.12c-0.06,0.04-0.12,0.08-0.18,0.12c-0.06,0.04-0.12,0.08-0.18,0.12 c-0.08,0.05-0.16,0.11-0.24,0.16c-0.08,0.06-0.16,0.11-0.24,0.17c-0.08,0.06-0.16,0.11-0.24,0.17c-0.08,0.06-0.16,0.11-0.24,0.17 c-0.07,0.05-0.14,0.1-0.21,0.15c-0.07,0.05-0.14,0.1-0.21,0.15c-0.07,0.05-0.14,0.1-0.2,0.16c-0.07,0.05-0.14,0.11-0.2,0.16 c-0.04,0.03-0.09,0.07-0.13,0.1c-0.04,0.03-0.09,0.07-0.13,0.1c-0.04,0.04-0.09,0.07-0.13,0.11c-0.04,0.04-0.09,0.07-0.13,0.11 c-0.02,0.01-0.03,0.03-0.05,0.04c-0.02,0.01-0.03,0.03-0.05,0.04c-0.02,0.01-0.03,0.03-0.05,0.04c-0.02,0.01-0.03,0.03-0.05,0.04 c-0.06,0.05-0.11,0.09-0.16,0.14c-0.05,0.05-0.11,0.09-0.16,0.14c-0.05,0.05-0.11,0.09-0.16,0.14c-0.05,0.05-0.11,0.09-0.16,0.14 c-0.17,0.15-0.34,0.3-0.51,0.46c-0.17,0.16-0.33,0.31-0.5,0.47c-0.16,0.16-0.33,0.32-0.49,0.48c-0.16,0.16-0.32,0.33-0.48,0.49 l6.98-0.23l-0.48-4.16L13.78,7.66z M13.32,5.73c-0.06,0.03-0.11,0.05-0.17,0.08c-0.06,0.03-0.12,0.06-0.17,0.09 c-0.03,0.01-0.06,0.03-0.08,0.04c0,0,0,0,0,0c-0.02-0.01-0.04-0.03-0.06-0.04c-0.06-0.04-0.13-0.07-0.21-0.09 c-0.07-0.02-0.15-0.04-0.23-0.04c-0.08,0-0.16,0-0.24,0.01l-0.14,0.02c0.07-0.04,0.13-0.08,0.18-0.14c0.05-0.05,0.1-0.11,0.14-0.18 c0.04-0.06,0.06-0.13,0.08-0.2c0.02-0.07,0.02-0.15,0.01-0.22c-0.01-0.1-0.04-0.18-0.08-0.26c-0.05-0.08-0.11-0.14-0.18-0.19 c-0.07-0.05-0.16-0.08-0.25-0.1c-0.09-0.02-0.19-0.02-0.29,0c-0.1,0.02-0.19,0.06-0.27,0.11c-0.08,0.05-0.15,0.11-0.21,0.19 C11.08,4.9,11.03,4.98,11,5.07c-0.03,0.09-0.04,0.18-0.03,0.27c0.01,0.07,0.02,0.14,0.05,0.2c0.03,0.06,0.06,0.12,0.11,0.17 c0.05,0.05,0.1,0.09,0.16,0.12c0.06,0.03,0.13,0.06,0.2,0.07l-0.17,0.03C11.18,5.96,11.06,6,10.94,6.07 c-0.11,0.07-0.21,0.15-0.29,0.25c-0.08,0.1-0.14,0.21-0.19,0.33c-0.04,0.12-0.06,0.25-0.05,0.38l0.02,0.33 c-0.09,0.05-0.17,0.1-0.26,0.16c-0.02,0-0.05,0-0.07,0c0.02-0.01,0.04-0.02,0.06-0.03c-0.06-0.06-0.13-0.11-0.21-0.16 c-0.07-0.04-0.15-0.08-0.24-0.1C9.63,7.2,9.54,7.18,9.45,7.18c-0.09-0.01-0.18,0-0.27,0.01L9.01,7.21c0.08-0.05,0.16-0.1,0.23-0.17 C9.3,6.97,9.36,6.9,9.41,6.81C9.46,6.73,9.5,6.64,9.52,6.55c0.02-0.09,0.03-0.19,0.03-0.29C9.54,6.13,9.51,6.02,9.46,5.92 c-0.05-0.1-0.12-0.18-0.21-0.25C9.17,5.6,9.07,5.56,8.96,5.53c-0.11-0.02-0.22-0.03-0.34,0C8.5,5.55,8.39,5.6,8.29,5.66 C8.19,5.72,8.1,5.81,8.02,5.9C7.95,5.99,7.89,6.1,7.85,6.21C7.81,6.32,7.79,6.44,7.79,6.56c0,0.09,0.02,0.18,0.05,0.26 c0.03,0.08,0.07,0.16,0.12,0.22c0.05,0.07,0.11,0.12,0.18,0.17c0.07,0.04,0.15,0.08,0.23,0.1l-0.2,0.03 C8.01,7.37,7.85,7.42,7.72,7.51C7.58,7.59,7.46,7.7,7.35,7.82C7.25,7.95,7.17,8.1,7.11,8.25c-0.06,0.16-0.09,0.33-0.08,0.5 l0.01,0.74C6.98,9.53,6.93,9.58,6.88,9.62C6.81,9.49,6.74,9.38,6.65,9.28c-0.1-0.11-0.21-0.2-0.33-0.27 C6.2,8.94,6.07,8.89,5.93,8.87C5.8,8.84,5.66,8.83,5.51,8.85L5.3,8.88c0.1-0.06,0.2-0.13,0.29-0.22c0.09-0.09,0.16-0.19,0.23-0.3 c0.06-0.11,0.12-0.23,0.15-0.35C6,7.88,6.02,7.75,6.02,7.62c0-0.17-0.03-0.32-0.08-0.46C5.88,7.03,5.8,6.91,5.71,6.82 C5.61,6.73,5.5,6.67,5.37,6.63c-0.12-0.04-0.26-0.04-0.4-0.02c0,0,0,0,0,0c0,0,0,0,0,0c0,0,0,0,0,0c0,0,0,0,0,0 c-0.14,0.03-0.28,0.08-0.4,0.16c-0.12,0.08-0.23,0.18-0.33,0.3C4.14,7.2,4.07,7.33,4.01,7.48c-0.06,0.15-0.09,0.3-0.1,0.46 c0,0.12,0.01,0.24,0.03,0.35c0.03,0.11,0.07,0.21,0.12,0.3c0.05,0.09,0.12,0.17,0.2,0.23c0.08,0.06,0.17,0.11,0.27,0.14L4.3,9 C4.1,9.03,3.92,9.09,3.75,9.2C3.58,9.3,3.43,9.44,3.3,9.6c-0.13,0.16-0.24,0.35-0.32,0.56c-0.08,0.21-0.13,0.43-0.14,0.67 l-0.12,2.26l-0.53-0.6l0.49-6.3C2.68,6.09,2.71,6,2.74,5.91c0.04-0.09,0.08-0.17,0.14-0.24c0.06-0.07,0.12-0.14,0.2-0.19 C3.15,5.44,3.23,5.4,3.32,5.38l0.71-0.17l0-0.02l0.18-0.04l0.06-1.19C4.3,3.56,4.39,3.15,4.55,2.77c0.16-0.38,0.37-0.75,0.64-1.08 C5.45,1.35,5.76,1.05,6.11,0.8c0.35-0.26,0.74-0.47,1.16-0.61C7.7,0.05,8.12-0.01,8.51,0c0.4,0.02,0.77,0.12,1.1,0.29 c0.33,0.18,0.62,0.43,0.83,0.75c0.21,0.33,0.35,0.73,0.38,1.19l0.1,1.36l0.3-0.07l0,0.02l0.89-0.21c0.13-0.03,0.25-0.03,0.36-0.02 c0.12,0.02,0.22,0.05,0.32,0.11c0.09,0.05,0.17,0.13,0.23,0.21c0.06,0.09,0.1,0.19,0.11,0.31L13.32,5.73z M9.46,3.96L9.4,2.61 C9.39,2.33,9.31,2.09,9.19,1.88C9.07,1.68,8.91,1.51,8.71,1.4C8.52,1.28,8.29,1.21,8.05,1.19C7.81,1.17,7.55,1.2,7.28,1.28 C7.01,1.37,6.76,1.49,6.53,1.65c-0.22,0.16-0.43,0.35-0.6,0.57C5.77,2.43,5.63,2.67,5.53,2.91c-0.1,0.25-0.16,0.5-0.17,0.76 L5.33,4.91L9.46,3.96z"
  }));
  wp.blocks.updateCategory('pmpro', {
    icon: PMProSVG
  });
  wp.blocks.updateCategory('pmpro-pages', {
    icon: PMProSVG
  });
})();
}();
/******/ })()
;
//# sourceMappingURL=blocks.build.js.map