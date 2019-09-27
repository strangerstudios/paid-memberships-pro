const path = require('path');
const defaultConfig = require("./node_modules/@wordpress/scripts/config/webpack.config");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

const blocksCSSPlugin = new MiniCssExtractPlugin( {
	filename: './css/blocks.style.css',
  } );

const editBlocksCSSPlugin = new MiniCssExtractPlugin( {
	filename: './css/blocks.editor.css',

  } );

module.exports = {
  ...defaultConfig,
	entry: {
		index: path.resolve( __dirname, 'blocks', 'blocks.js' ),
	},
	output: {
		path: path.resolve( __dirname, 'js/blocks' ),
		filename: 'blocks.build.js'
	},
	module: {
		...defaultConfig.module,
		rules: [
		...defaultConfig.module.rules,
		{
        test: /style\.s?css$/,
        use: [MiniCssExtractPlugin.loader, 'css-loader'],
		},
		{
        test: /editor\.s?css$/,
		use: [MiniCssExtractPlugin.loader, 'css-loader'],
      	},
	]
	},
	plugins: [
		blocksCSSPlugin,
		editBlocksCSSPlugin
	  ],
};