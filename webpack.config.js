const path = require('path');
const defaultConfig = require("./node_modules/@wordpress/scripts/config/webpack.config");
const ExtractTextPlugin = require("extract-text-webpack-plugin");

const blocksCSSPlugin = new ExtractTextPlugin( {
	filename: './css/blocks.style.css',
  } );

const editBlocksCSSPlugin = new ExtractTextPlugin( {
	filename: './css/blocks.editor.css',
  } );

  // Configuration for the ExtractTextPlugin.
const extractConfig = {
	use: [
	  { loader: 'raw-loader' },
	  {
		loader: 'postcss-loader',
		options: {
		  plugins: [ require( 'autoprefixer' ) ],
		},
	  }
	],
  };

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
        use: blocksCSSPlugin.extract( extractConfig ),
		},
		{
        test: /editor\.s?css$/,
        use: editBlocksCSSPlugin.extract( extractConfig ),
      	},
	]
	},
	plugins: [
		blocksCSSPlugin,
		editBlocksCSSPlugin,
	  ],
};