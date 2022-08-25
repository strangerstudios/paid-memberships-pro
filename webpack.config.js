const path = require('path');
const defaultConfig = require("./node_modules/@wordpress/scripts/config/webpack.config");

module.exports = {
  ...defaultConfig,
	entry: {
		index: path.resolve( __dirname, 'blocks', 'blocks.js' ),
	},
	output: {
		path: path.resolve( __dirname, 'js/blocks' ),
		filename: 'blocks.build.js'
	}
};