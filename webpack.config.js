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
  /*
  "sidebar/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "sidebar",
    "index.js"
  ),
  "settings/admin": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "settings",
    "index.js"
  ),
  "customer-portal/index": path.resolve(
    process.cwd(),
    "blocks",
    "src",
    "customer-portal",
    "index.js"
  ),
  */
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
    ],
  })
);

module.exports = config;
