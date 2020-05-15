<?php
/**
 * Multisite Network Repost
 *
 * @link              https://wordpress.org/plugins/multisite-network-repost/
 * @package           Multisite Network Repost
 * @copyright         Copyright (c) 2020, Alex Kay - Witty Finch, Inc.
 *
 * @wordpress-plugin
 * Plugin Name:       Multisite Network Repost
 * Plugin URI:        https://wordpress.org/plugins/multisite-network-repost/
 * Description:       Repost your stories to selected sites in the multisite network, preserving attachments, custom fields, categories, tags etc.
 * Version:           1.0.0
 * Author:            Alex Kay
 * Author URI:        https://wittyfinch.com
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       multisite-network-repost
 * Network:           true
 * Requires WP:       5.0
 * Requires PHP:      7.0
 *
 * Multisite Network Repost is free software:
 * You can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option)
 * any later version.
 *
 * Multisite Network Repost is distributed in the hope that
 * it will be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
 * PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WordPress. If not, see <http://www.gnu.org/licenses/>.
 */

if( ! defined('WPINC')) {
	exit('Sorry, you are not allowed to access this file directly.');
}

class Multisite_Network_Repost
{
    private $data_locked = false;
    private $post_parent;
    private $post_data = [];
    private $post_meta = [];
    private $post_terms = [];

    var $copy_taxonomies = ['category', 'post_tag'];
    var $skip_meta_keys = ['plugin_mnrp_ids', '_wp_old_slug'];


    function __construct() {
        // Not a multisite system
        if( ! is_multisite() ) return;

        add_action( 'add_meta_boxes', [$this , 'meta_boxes']);
        add_action( 'save_post', [$this , 'save_action'], 99, 2 );
    }


    private function set($name, $val)
    {
        if( ! $this->data_locked ) {
            $this->$name = $val;
        }
    }


    private function trim_len($str, $len = 28, $end = '&hellip;')
    {
        $out = $str;
        if( strlen($str) > $len ) {
            $out = substr($str, 0, $len).$end;
        }
        return $out;
    }


    private function sanitize_ids($var)
    {
        if( !is_array($var) ) {
            $var = explode(',', trim($var));
        }
        return array_unique(array_map('intval', array_filter(array_map('trim', $var))));
    }


    function meta_boxes() {
        if( get_sites(['count'=>true]) > 1 ) {
            add_meta_box('plugin_mnrp', 'Repost to these sites',
                [$this, 'meta_boxes_callback'], 'post', 'side');
        }
    }


    function meta_boxes_callback( $orig_post ) {
        wp_nonce_field('plugin_mnrp_post', 'plugin_mnrp_nonce');
        $current_ids = $this->sanitize_ids( get_post_meta($orig_post->ID, 'plugin_mnrp_ids', true) );
        $disabled = empty($current_ids) ? '' : 'disabled';

        $sites = get_sites([
            'archived' => '0',
            'orderby' => 'domain',
        ]);

        foreach( $sites as $site ) {
            $checked = checked(0, 1, false);
            if( !empty($current_ids) && in_array($site->blog_id, $current_ids) )
            {
                $checked = checked(1, 1, false);
            }
            echo '<div style="margin-top: 3px">
                    <span class="components-checkbox-control__input-container">
                      <input type="checkbox" name="plugin_mnrp_ids[]" value="'.$site->blog_id.'"
                        id="plugin_mnrp_'.$site->blog_id.'" '.$checked.' '.$disabled.' />
                    </span>
                    <label for="plugin_mnrp_'.$site->blog_id.'">'.$this->trim_len($site->blogname).'</label>
                 </div>';
        }
    }


    function save_action( $orig_post_id, $orig_post )
    {
        if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $orig_post_id;
        if( get_post_status($orig_post) !== 'publish' ) return $orig_post_id;
        if( isset($_POST['plugin_mnrp_nonce']) && !wp_verify_nonce($_POST['plugin_mnrp_nonce'], 'plugin_mnrp_post') ) return $orig_post_id;
        if( !isset($_POST['plugin_mnrp_ids']) ) return $orig_post_id;

        $repost_sites = $this->sanitize_ids($_POST['plugin_mnrp_ids']);
        if( empty($repost_sites) ) return $orig_post_id;

        remove_action('save_post', [$this, 'save_action']);
        update_post_meta($orig_post_id, 'plugin_mnrp_ids', $repost_sites);

        $this->set('post_data', array(
            'post_status'   => 'publish',
            'post_author'   => $orig_post->post_author,
            'post_date'     => $orig_post->post_date,
            'post_modified' => $orig_post->post_modified,
            'post_content'  => $orig_post->post_content,
            'post_title'    => $orig_post->post_title,
            'post_excerpt'  => $orig_post->post_excerpt,
            'post_name'     => $orig_post->post_name,
            'post_type'     => $orig_post->post_type,
            'comment_status'=> $orig_post->comment_status,
            'ping_status'   => $orig_post->ping_status,
        ));

        $this->set('post_parent', $GLOBALS['blog_id'].':'.$orig_post_id);
        $this->set('post_meta', get_post_meta($orig_post_id));
        foreach($this->copy_taxonomies as $tax) {
            $post_terms[$tax] = wp_get_object_terms($orig_post_id, $tax, ['fields' => 'slugs']);
        }
        $this->set('post_terms', $post_terms);
        $this->data_locked = true;

        foreach( $repost_sites as $site_id ) {
            if( $site_id == $GLOBALS['blog_id'] ) continue;
            switch_to_blog($site_id);

            if( get_posts([
                    'name' => $this->post_data['post_name'],
                    'post_type' => $this->post_data['post_type'],
                    'post_status' => 'publish'
                ])
            ) {
                restore_current_blog();
                continue;
            }

            $new_post_id = wp_insert_post($this->post_data);
            if( $new_post_id === 0 || is_wp_error($new_post_id) )
            {
                $msg = 'Can\'t duplicate the post';
                if( is_wp_error($new_post_id) ) $msg .= ': '.$new_post_id->get_error_message();
                add_action('admin_notices', function() {
                        echo sprintf('<div class="notice notice-warning is-dismissible"><p>%s</p></div>', $msg);
                    });
                continue;
            }

            foreach( $this->copy_taxonomies as $tax ) {
                wp_set_object_terms($new_post_id, $this->post_terms[$tax], $tax, false);
            }

            foreach( $this->post_meta as $k => $values ) {
                if( in_array($k, $this->skip_meta_keys) ) continue;
                if( $k === '_thumbnail_id' ) {
                    // Add a special prefix to the featured image file ID. This will be picked up by Multisite Global Media plugin
                    // if it's installed. Even if the plugin is missing there's no point in NOT doing this since the ID
                    // is useless otherwise, and the image will not match.

                    // ID 777 becomes 200000777 where 2 is the original site ID
                    add_post_meta($new_post_id, $k, strtok($this->post_parent, ':').'00000'.$v[0]);
                }
                else {
                    foreach ($values as $v) {
                        add_post_meta($new_post_id, $k, $v);
                    }
                }
            }
            update_post_meta($new_post_id, 'plugin_mnrp_parent', $this->post_parent);
            restore_current_blog();
        }
    }
}

new Multisite_Network_Repost();