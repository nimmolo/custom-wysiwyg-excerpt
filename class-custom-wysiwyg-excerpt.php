<?php
/*
Plugin Name: Custom WYSIWYG Excerpt
Original Plugin Name: Excerpt Below Title
Original Plugin URI: https://github.com/Kuuak/excerpt-below-title
Description: Remove default excerpt and add WYSIWYG excerpt metabox below title
Version: 2.0.0
Author: Nimmo
Original Author: Kuuak
Original Author URI: https://profiles.wordpress.org/kuuak
License: GPLv2
*/

/* Prevent loading this file directly */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( "Custom_Wysiwyg_Excerpt" ) ) :

  class Custom_Wysiwyg_Excerpt {

    private $post_types;
    private $label;

    function __construct(){
      add_action( 'init', array( $this, 'init' ) );
      add_action( 'admin_menu', array( $this, 'remove_normal_excerpt' ) );
      add_action( 'add_meta_boxes', array( $this, 'add_custom_excerpt_meta_box' ) );
      add_action( 'edit_form_after_title', array( $this, 'add_meta_boxes_after_title' ), 1 );
      add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
    }

    // Get existing post types (can only do after init)
    public function init() {
      if ( ! is_admin() ) {
        return;
      }

      $post_type_args = array(
        'public' => true
      );
      $this->post_types = get_post_types( $post_type_args, 'names', 'and' );
      // $this->post_types = array( 'post', 'exhibition', 'publication' );

      $this->label = __( 'Excerpt' );
    }

    /**
     * Removes the regular excerpt box. We're not getting rid
     * of it, we're just moving it above the wysiwyg editor
     *
     * @return null
     */
    public function remove_normal_excerpt() {
      // error_log( print_r( $this->post_types, true ) );
      foreach( $this->post_types as $post_type ) {
        remove_meta_box( 'postexcerpt' , $post_type, 'normal' );
      }
    }

    /**
     * Add the excerpt meta box back in with a custom screen location
     *
     * @param  string $post_type
     * @return null
     */
    public function add_custom_excerpt_meta_box( $post_type ) {
      if ( in_array( $post_type, $this->post_types ) ) {
        add_meta_box(
          'custom_postexcerpt',
          $this->label,
          array( $this, 'wysiwyg_excerpt_meta_box' ), // 'post_excerpt_meta_box',
          $post_type,
          'custom_excerpt_after_title',
          'default'
        );
        add_filter( "postbox_classes_{$post_type}_custom_postexcerpt", array( $this, 'filter_metabox_styling' ) );
      }
    }

    /**
     * Add the meta box HTML
     *
     * @param
     * @return html
     */
    public function wysiwyg_excerpt_meta_box() {
      $meta_content = wpautop( get_the_excerpt(), true );

      ?><label for="excerpt"><?php echo $this->label; ?></label><?php

      wp_editor(
        $meta_content,
        'excerpt',
        array(
          'wpautop'         =>  true,
          'media_buttons'   =>  false,
          'textarea_name'   =>  'excerpt',
          // 'textarea_rows'   =>  10,
          // 'teeny'           =>  true
        )
      );
    }

    /**
     * Add a CSS class to the metabox - you can't remove "postbox".
     * https://wordpress.stackexchange.com/questions/94401/auto-close-hide-custom-metabox-set-default-state
     * https://wordpress.stackexchange.com/questions/94811/remove-default-wordpress-styling-from-metaboxes-on-edit-post-pages?rq=1
     * @param  array $classes
     * @return array $classes
     */
    public function filter_metabox_styling( $classes ) {
      // error_log( print_r( $classes, true ) );
      $classes[] = "excerpt-seamless";
      return $classes;
    }


    /**
     * You can't actually add meta boxes after the title by default in WP so
     * we're being cheeky. We've registered our own meta box position
     * `custom_excerpt_after_title` onto which we've registered our new meta
     * boxes and are now calling them in the `edit_form_after_title` hook which
     * is run after the post tile box is displayed.
     *
     * @return null
     */
    public function add_meta_boxes_after_title() {
      global $post, $wp_meta_boxes;
      # Output the `exbt_after_title` meta boxes:
      do_meta_boxes( get_current_screen(), 'custom_excerpt_after_title', $post );
    }


    public function admin_styles( $hook ) {
      // Only enqueue for blog post admin screen
      if ( 'post.php' == $hook ) {
        wp_register_style( 'custom_excerpt_admin_post_stylesheet',  get_stylesheet_directory_uri() . '/inc/custom-wysiwyg-excerpt/admin-custom-excerpt.css', false, '1.0.0' );
        wp_enqueue_style( 'custom_excerpt_admin_post_stylesheet' );
      }
    }

  }

  global $Custom_Wysiwyg_Excerpt;
  $Custom_Wysiwyg_Excerpt = new Custom_Wysiwyg_Excerpt();

endif;
