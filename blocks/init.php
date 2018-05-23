<?php

defined( 'ABSPATH' ) || exit;

/* NOTE: Maybe move this into includes/blocks.php or /blocks/blocks.php */

/**
 * Dynamic Block Requires
 */
require_once( PMPRO_DIR . '/blocks/checkout-button/index.php' );

/**
 * Enqueue block editor only JavaScript and CSS
 */
function pmpro_block_editor_scripts()
{

    // Make paths variables so we don't write em twice ;)
    $blockPath = '/../js/editor.blocks.js';
    $editorStylePath = '/../css/blocks.editor.css';

    // Enqueue the bundled block JS file
    wp_enqueue_script(
        'pmpro-blocks-js',
        plugins_url( $blockPath, __FILE__ ),
        [ 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-api' ],
        filemtime( plugin_dir_path(__FILE__) . $blockPath )
    );

    // Enqueue optional editor only styles
    wp_enqueue_style(
        'pmpro-editor-css',
        plugins_url( $editorStylePath, __FILE__),
        [ 'wp-blocks' ],
        filemtime( plugin_dir_path( __FILE__ ) . $editorStylePath )
    );

}

// Hook scripts function into block editor hook
add_action( 'enqueue_block_editor_assets', 'pmpro_block_editor_scripts' );


/**
 * Enqueue front end and editor JavaScript and CSS
 */
function pmpro_block_scripts()
{
    $blockPath = '/../js/frontend.blocks.js';
    // Make paths variables so we don't write em twice ;)
    $stylePath = '/../css/blocks.style.css';

    // Enqueue the bundled block JS file
    wp_enqueue_script(
        'pmpro-blocks-frontend-js',
        plugins_url( $blockPath, __FILE__ ),
        [ 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-api' ],
        filemtime( plugin_dir_path(__FILE__) . $blockPath )
    );

    // Enqueue frontend and editor block styles
    wp_enqueue_style(
        'pmpro-blocks-css',
        plugins_url($stylePath, __FILE__),
        [ 'wp-blocks' ],
        filemtime(plugin_dir_path(__FILE__) . $stylePath )
    );

}

// Hook scripts function into block editor hook
add_action('enqueue_block_assets', 'pmpro_block_scripts');
