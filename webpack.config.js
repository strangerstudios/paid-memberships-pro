/**
 * `@wordpress/scripts` path-based name multi-block Webpack configuration.
 * @see https://wordpress.stackexchange.com/questions/390282
 */

// Native Depedencies.
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
  "single-level/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "single-level",
    "index.js"
  ),
  "single-level-checkout/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "single-level-checkout",
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
