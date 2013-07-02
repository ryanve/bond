<?php 
/*
Plugin Name: Bond
Plugin URI: http://github.com/ryanve/bond
Description: Manage many-to-many relationships.
Version: 0.1.0-2
Author: Ryan Van Etten
Author URI: http://ryanve.com
License: MIT
*/

add_action('init', function() {
    $cpt = 'bond';
    $is_admin = is_admin();
    register_post_type($cpt, apply_filters("@$cpt:cpt:ui", array(
        'public' => current_user_can('delete_posts')
      , 'has_archive' => false
      , 'taxonomies' => get_taxonomies()
      , 'capability_type' => 'page'
      , 'hierarchical' => true
      , 'supports' => explode('|', 'title|editor|author|thumbnail|excerpt|custom-fields|page-attributes')
      , 'exclude_from_search' => true
      , 'publicly_queryable' => false
      , 'labels' => array(
            'all_items' => __('All')
          , 'edit_item' => __('Edit')
          , 'view_item' => __('View')
          , 'update_item' => __('Update')
          , 'add_new_item' => __('Add')
          , 'new_item_name' => __('Name')
          , 'search_items' => __('Search')
          , 'popular_items' => __('Popular')
          , 'separate_items_with_commas' => __('Separate with commas')
          , 'add_or_remove_items' => __('Add or remove')
          , 'choose_from_most_used' => __('Most used')
          , 'not_found' => __('Not found')
          , 'parent_item_colon' => __('Parent:')
          , 'singular_name' => 'Bond +'
          , 'name' => 'Bonds +', 
        )
    )));
    
    /* register_post_type('_' . $cpt, apply_filters("@$cpt:cpt:ui", array(
        'public' => current_user_can('delete_posts')
      , 'has_archive' => false
      , 'taxonomies' => get_taxonomies()
      , 'capability_type' => 'page'
      , 'hierarchical' => true
      , 'supports' => explode('|', 'title|editor|author|thumbnail|excerpt|custom-fields|page-attributes')
      , 'exclude_from_search' => true
      , 'publicly_queryable' => false
      , 'labels' => array(
            'all_items' => __('All')
          , 'edit_item' => __('Edit')
          , 'view_item' => __('View')
          , 'update_item' => __('Update')
          , 'add_new_item' => __('Add')
          , 'new_item_name' => __('Name')
          , 'search_items' => __('Search')
          , 'popular_items' => __('Popular')
          , 'separate_items_with_commas' => __('Separate with commas')
          , 'add_or_remove_items' => __('Add or remove')
          , 'choose_from_most_used' => __('Most used')
          , 'not_found' => __('Not found')
          , 'parent_item_colon' => __('Parent:')
          , 'singular_name' => '- Bond'
          , 'name' => '- Bonds', 
        )
    ))); */

    /* register_taxonomy($cpt, array($cpt), apply_filters("@$cpt:tax:ui", array(
        'public' => $is_admin && current_user_can('delete_others_posts')
      , 'hierarchical' => true
      , 'rewrite' => array('slug' => '_' . $cpt)
      , 'labels' => array(
            'all_items' => __('All')
          , 'popular_items' => __('Popular')
          , 'edit_item' => __('Edit')
          , 'view_item' => __('View')
          , 'update_item' => __('Update')
          , 'search_items' => __('Search')
          , 'add_new_item' => __('Add')
          , 'new_item_name' => __('Name')
          , 'add_or_remove_items' => __('Add or remove')
          , 'choose_from_most_used' => __('Most used')
          , 'not_found' => __('Not found')
          , 'separate_items_with_commas' => 'Separate with commas.'
          , 'name' => 'Bonds -'
          , 'singular_name' => 'Bond -'
        ))
    )); */
    
    /* $is_admin or add_action('pre_get_posts', function(&$main) use ($cpt) {
        $main->is_main_query() && empty($main->is_singular) && $main->set('post_type', array_diff(
            (array) $main->get('post_type'), array($cpt)
        ));
    }, 100); */

    $is_admin or add_action('wp', function() use ($cpt) {
        #print_r(get_queried_object());
        $bool = (bool) (
            ! is_singular()
            and is_object($query = get_queried_object())
            and !empty($query->taxonomy)
            and !empty($query->term_id)
            and ($post = get_posts(
                apply_filters("@$cpt:get_posts", array(
                    'posts_per_page'  => 1
                  , 'post_type' => $cpt
               // , 'taxonomy' => $query->taxonomy
               // , 'terms' => $query->term_id
               // , 'meta_key' => "$cpt-term"
                  , 'taxonomy' => $cpt
                  , 'terms' => 'term-' . $query->term_id
                  , 'field' => 'id'
                  , 'order' => 'DESC'
                  , 'orderby' => 'post_date'
                  , 'post_status' => 'publish'
                  , 'suppress_filters' => true
                ), $query)))
            and is_object($post = array_shift($post))
            // and in_array('term-' . $query->term_id, preg_split('#\s+#', get_post_meta($post->ID, "$cpt-term", true)))
            and add_filter('get_term', function($term, $tax) use ($cpt, $post) {
                return apply_filters("@$cpt:term", $term, $post);
            }, 1, 2)
        );

        add_filter('body_class', function($list) use ($bool) {
            $class = array('bonded', 'unbonded');
            $list[] = $bool ? array_shift($class) : array_pop($class);
            return array_diff(array_unique($list), $class);
        });
    });
    
    call_user_func(function($route) use ($cpt) {
        $r = apply_filters("@$cpt:route", $route);
        $r and add_filter("@$cpt:term", is_scalar($r) || is_callable($r) ? $r : function() use ($route, $r) {
            return call_user_func($route, func_get_arg(0), func_get_arg(1), $r);
        }, 10, 2);
    }, function($term, $post, $map = null) {
        null === $map and $map = array('description' => 'post_content');
        foreach ($map as $k => $v)
            empty($post->{$v}) or $term->{$k} = $post->{$v};
        return $term;
    });
}, 1);

#end