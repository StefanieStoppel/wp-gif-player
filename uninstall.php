<?php
/*
WP GIF Player, an easy to use GIF Player for Wordpress
Copyright (C) 2015  Stefanie Stoppel @ psmedia GmbH (http://p-s-media.de/kontakt)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
if( !defined('WP_UNINSTALL_PLUGIN') ){
    exit();
}
//Delete options
$option_name = 'set_still_as_featured';
delete_option($option_name);

//Delete _first_frame entries in wp_postmeta
global $wpdb;
$num_rows = $wpdb->delete( 'wp_postmeta', array( 'meta_key' => '_first_frame' ) );

/*
 * Replace shortcodes in a post with the according gif (as html img tag).
 */
$query = "SELECT * FROM $wpdb->posts p where p.post_type = 'attachment' AND (p.post_mime_type = 'image/gif') AND (p.post_status = 'inherit')";
$gifs = $wpdb->get_results( $query );

foreach( $gifs as $gif ) {
    // get gif parent posts
    $gif_post_ids = get_post_ancestors( $gif->ID );

    $gif_post_id = $gif_post_ids[0];

    $gif_post = get_post($gif_post_id);

    // apply_filters() changes the content to html formatting
    $content = $gif_post->post_content;

    // check if content contains shortcode
    $shortcode_start = stripos($content, "[WPGP");
    if( has_shortcode($content, "WPGP") || $shortcode_start !== false ) {

        $shortcode_end = stripos($content, "]", $shortcode_start);
                
        $shortcode = substr( $content, $shortcode_start, ($shortcode_end+1 - $shortcode_start) );

        // get image html for full size gif
        $gif_attachment_html = wp_get_attachment_image($gif->ID, "full");

        //replace shortcode with image html inside content
        $content = str_replace($shortcode, $gif_attachment_html, $content);

        //update the post
        $updated_post = array(
            'ID'            =>  $gif_post_id,
            'post_content'  =>  $content
        );
        wp_update_post($updated_post);
    } 
}