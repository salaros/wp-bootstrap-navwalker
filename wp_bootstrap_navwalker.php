<?php
/**
 * A custom WordPress nav walker class to implement the Bootstrap 3 navigation style
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 2.0 of GPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @author     Edward McIntyre <edward@edwardmcintyre.com>
 * @author     Zhmayev Yaroslav <salaros@salaros.com>
 *
 * Class Name: Wp_Bootstrap_Navwalker
 * GitHub URI: https://github.com/salaros/wp-bootstrap-navwalker
 * Description: A custom WordPress nav walker class to implement the Bootstrap 3 navigation style in a custom theme using the WordPress built in menu manager.
 * Version: 2.1.0
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace Salaros\Wordpress;

class Wp_Bootstrap_Navwalker extends Walker_Nav_Menu {

    /**
     * @see Walker::start_lvl()
     * @since 3.0.0
     *
     * @param string $output Passed by reference. Used to append additional content.
     * @param int $depth Depth of page. Used for padding.
     */
    public function start_lvl( &$output, $depth = 0, $args = array() ) {
        $indent = str_repeat( "\t", $depth );
        $output .= "\n{$indent}<ul role=\"menu\" class=\"dropdown-menu\">\n";
    }

    /**
     * @see Walker::start_el()
     * @since 3.0.0
     *
     * @param string $output Passed by reference. Used to append additional content.
     * @param object $item Menu item data object.
     * @param int $depth Depth of menu item. Used for padding.
     * @param int $current_page Menu item ID.
     * @param object $args
     */
    public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
        $indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

        /**
         * Dividers, Headers or Disabled
         * =============================
         * Determine whether the item is a Divider, Header, Disabled or regular
         * menu item. To prevent errors we use the strcasecmp() function to so a
         * comparison that is not case sensitive. The strcasecmp() function returns
         * a 0 if the strings are equal.
         */
        if ( strcasecmp( $item->attr_title, 'divider' ) == 0 && $depth === 1 ) {
            $output .= $indent . '<li role="presentation" class="divider">';
        } else if ( strcasecmp( $item->title, 'divider') == 0 && $depth === 1 ) {
            $output .= $indent . '<li role="presentation" class="divider">';
        } else if ( strcasecmp( $item->attr_title, 'dropdown-header') == 0 && $depth === 1 ) {
            $output .= $indent . '<li role="presentation" class="dropdown-header">' . esc_attr( $item->title );
        } else if ( strcasecmp($item->attr_title, 'disabled' ) == 0 ) {
            $output .= $indent . '<li role="presentation" class="disabled"><a href="#">' . esc_attr( $item->title ) . '</a>';
        } else {

            $class_names = $value = '';

            $classes = empty( $item->classes ) ? array() : (array) $item->classes;
            $classes[] = 'menu-item-' . $item->ID;

            $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );

            if ( $args->has_children ) {
                $class_names .= ' dropdown';
            }

            if ( in_array( 'current-menu-item', $classes ) || in_array( 'current-category-ancestor', $classes ) ) {
                $class_names .= ' active';
            }

            $class_names = $class_names ? sprintf( ' class="%s"', esc_attr( $class_names ) ) : '';

            $id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
            $id = $id ? sprintf( ' id="%s"', esc_attr( $id ) ) : '';

            $output .= $indent . '<li' . $id . $value . $class_names .'>';

            $atts = array();
            $atts['title']  = ! empty( $item->title )    ? $item->title    : '';
            $atts['target'] = ! empty( $item->target )    ? $item->target    : '';
            $atts['rel']    = ! empty( $item->xfn )        ? $item->xfn    : '';

            // If item has_children add atts to a.
            if ( $args->has_children && $depth === 0 ) {
                $atts['href']           = '#';
                $atts['data-toggle']    = 'dropdown';
                $atts['class']            = 'dropdown-toggle';
                $atts['aria-haspopup']    = 'true';
            } else {
                $atts['href'] = ! empty( $item->url ) ? $item->url : '';
            }

            $atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args );

            $attributes = '';
            foreach ( $atts as $attr => $value ) {
                if ( empty( $value ) ) continue;
                $value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                $attributes .= " {$attr}=\"{$value}\"";
            }

            $item_output = $args->before;

            /*
             * Glyphicons
             * ===========
             * Since the the menu item is NOT a Divider or Header we check the see
             * if there is a value in the attr_title property. If the attr_title
             * property is NOT null we apply it as the class name for the glyphicon.
             */
            $item_output .= ( empty( $item->attr_title ) )
                ? "<a {$attributes}>"
                : sprintf( '<a%s><span class="glyphicon %s"></span>&nbsp;', $attributes, esc_attr( $item->attr_title ) );

            $item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
            $item_output .= ( $args->has_children && 0 === $depth )
                ? ' <span class="caret"></span></a>'
                : '</a>';
            $item_output .= $args->after;

            $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
        }
    }

    /**
     * Menu Fallback
     * =============
     * If this function is assigned to the wp_nav_menu's fallback_cb variable
     * and a manu has not been assigned to the theme location in the WordPress
     * menu manager the function with display nothing to a non-logged in user,
     * and will add a link to the WordPress menu manager if logged in as an admin.
     *
     * @param array $args passed from the wp_nav_menu function.
     *
     */
    public static function fallback( $args ) {
        if ( current_user_can( 'manage_options' ) ) {
            return;
        }

        extract( $args );

        $fb_output = null;

        if ( $container ) {
            $fb_output = "<{$container}";

            if ( $container_id )
                $fb_output .= " id=\"{$container_id}\"";

            if ( $container_class )
                $fb_output .= " class=\"{$container_class}\"";

            $fb_output .= '>';
        }

        // Create ul element
        $fb_output .= '<ul';
        if ( $menu_id )
            $fb_output .= " id=\"{$menu_id}\"";

        if ( $menu_class )
            $fb_output .= " class=\"{$menu_class}\"";
        $fb_output .= '>';
        $nav_menus_url = admin_url( 'nav-menus.php' );
        $fb_output .= "<li><a href=\"{$nav_menus_url}\">Add a menu</a></li>";
        $fb_output .= '</ul>';

        if ( $container )
            $fb_output .= "</{$container}>";

        echo $fb_output;
    }
}
