/**
 * `@wordpress/scripts` path-based name multi-block Webpack configuration.
 * @see https://wordpress.stackexchange.com/questions/390282
 */

// Native Dependencies.
const path = require("path");

// Third-Party Dependencies.
const CopyPlugin = require("copy-webpack-plugin");
const config = require("@wordpress/scripts/config/webpack.config.js");

config.entry = {
  "account-invoices-section/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "account-invoices-section",
    "index.js"
  ),
  "account-profile-section/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "account-profile-section",
    "index.js"
  ),  
  "account-membership-section/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "account-membership-section",
    "index.js"
  ),
  "account-links-section/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "account-links-section",
    "index.js"
  ),
  "account-page/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "account-page",
    "index.js"
  ),
  "billing-page/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "billing-page",
    "index.js"
  ),
  "cancel-page/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "cancel-page",
    "index.js"
  ),
  "checkout-button/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "checkout-button",
    "index.js"
  ),
  "checkout-page/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "checkout-page",
    "index.js",
  ),
  "confirmation-page/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "confirmation-page",
    "index.js"
  ),
  "invoice-page/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "invoice-page",
    "index.js"
  ),
  "levels-page/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "levels-page",
    "index.js"
  ),
  "member-profile-edit/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "member-profile-edit",
    "index.js"
  ),
  "login/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "login",
    "index.js"
  ),
  "membership/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "membership",
    "index.js"
  ),
   "single-level/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "single-level",
    "index.js"
  ),
  "single-level-name/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "single-level-name",
    "index.js"
  ),
  "single-level-expiration/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "single-level-expiration",
    "index.js"
  ),
  "single-level-description/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "single-level-description",
    "index.js"
  ),
  "single-level-price/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "single-level-price",
    "index.js"
  ),
  "sidebar/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "sidebar",
    "index.js"
  ),
  "component-content-visibility/index": path.resolve(
	process.cwd(),
	"blocks",
	"src",
	"component-content-visibility",
	"index.js"
  )
};
config.output = {
  filename: "[name].js",
  path: path.resolve(process.cwd(), "blocks", "build"),
};

// Add a CopyPlugin to copy over block.json files.
config.plugins.push(
  new CopyPlugin({
    patterns: [
      {
        context: "blocks/src",
        from: `*/block.json`,
        noErrorOnMissing: true,
      },
      {
        context: "blocks/src",
        from: `*/render.php`,
        noErrorOnMissing: true,
      },
    ],
  })
);

module.exports = config;
