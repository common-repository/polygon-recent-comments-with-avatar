<?php
/*
  Plugin Name: Polygon recent comments with avatar
  Plugin URI: https://polyxgo.vn
  Description: Recent comments with image avatar, support for Gravatar, date, and user.
  Version: 1.0.4
  Author: PolyXGO
  Author URI: https://polyxgo.vn
  License: GPLv2
  Requires at least: 4.1
  Tested up to: 6.5.3
 */

require_once(ABSPATH . WPINC . '/default-widgets.php');

define('PXG_RECENTCOMMENTS_VERSION', '1.0.3');

function POLYGON_Recent_Comments()
{
    register_widget("POLYGON_Widget_Recent_Comments");
}
add_action("widgets_init", "POLYGON_Recent_Comments");

class POLYGON_Widget_Recent_Comments extends WP_Widget
{
    function __construct()
    {
        parent::__construct(
            'Polygon_widget',
            __('Poly recent comments', 'POLYGON_Widget_Recent_Comments'),
            array('description' => __('The widget supports displaying comments with author information, avatar, link, and more.', 'POLYGON_Widget_Recent_Comments'),)
        );

        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    function enqueue_assets()
    {
        wp_enqueue_style('poly-recent-comments-styles', plugin_dir_url(__FILE__) . 'assets/css/styles.css', null, PXG_RECENTCOMMENTS_VERSION);
        wp_enqueue_script('poly-recent-comments-scripts', plugin_dir_url(__FILE__) . 'assets/js/scripts.js', array('jquery'), PXG_RECENTCOMMENTS_VERSION, true);
    }
    function admin_enqueue_assets($hook_suffix)
    {
        if ($hook_suffix === 'widgets.php') {
            wp_enqueue_style('poly-recent-comments-styles', plugin_dir_url(__FILE__) . 'assets/css/styles.css', null, PXG_RECENTCOMMENTS_VERSION);
            wp_enqueue_style('poly-recent-comments-admin-scripts', plugin_dir_url(__FILE__) . 'assets/css/admin-styles.css', null, PXG_RECENTCOMMENTS_VERSION);
            wp_enqueue_script('poly-recent-comments-admin-head-scripts', plugin_dir_url(__FILE__) . 'assets/js/head.js', array('jquery'), PXG_RECENTCOMMENTS_VERSION);
            wp_enqueue_script('poly-recent-comments-admin-scripts', plugin_dir_url(__FILE__) . 'assets/js/admin-scripts.js', array('jquery'), PXG_RECENTCOMMENTS_VERSION, true);
        }
    }

    function widget($args, $instance)
    {
        global $comments, $comment;

        $cache = wp_cache_get('widget_polygon_recent_comments', 'widget');

        if (!is_array($cache))
            $cache = array();

        if (!isset($args['widget_id']))
            $args['widget_id'] = $this->id;

        if (isset($cache[$args['widget_id']])) {
            echo $cache[$args['widget_id']];
            return;
        }
        extract($args, EXTR_SKIP);
        $output = '';
        $title = apply_filters('widget_title', empty($instance['title']) ? __('Poly recent comments', 'POLYGON_Widget_Recent_Comments') : $instance['title'], $instance, $this->id_base);
        $num_comments = (isset($instance['num_comments'])) ? $instance['num_comments'] : 5;
        $avatar_size = (isset($instance['avatar_size'])) ? $instance['avatar_size'] : 88;
        $num_comments_show_scroll = (isset($instance['num_comments_show_scroll'])) ? $instance['num_comments_show_scroll'] : 5;
        $num_split = (isset($instance['num_split'])) ? $instance['num_split'] : 123;
        $show_date = (isset($instance['show_date']) && !empty($instance['show_date'])) ? $instance['show_date'] : '';
        $show_comment_link = (isset($instance['show_comment_link']) && !empty($instance['show_comment_link'])) ? $instance['show_comment_link'] : '';
        $show_avatar = (isset($instance['show_avatar']) && !empty($instance['show_avatar'])) ? $instance['show_avatar'] : '';
        $show_scroll = (isset($instance['show_scroll']) && !empty($instance['show_scroll'])) ? $instance['show_scroll'] : '';
        $no_show_scroll = (isset($instance['no_show_scroll']) && !empty($instance['no_show_scroll'])) ? $instance['no_show_scroll'] : '';
        $separate_info = (isset($instance['separate_info']) && !empty($instance['separate_info'])) ? $instance['separate_info'] : '';
        $show_gravatar = (isset($instance['show_gravatar']) && !empty($instance['show_gravatar'])) ? $instance['show_gravatar'] : '';
        $avatar_layout = (isset($instance['avatar_layout'])) ? $instance['avatar_layout'] : 'square';
        $avatar_alignment = (isset($instance['avatar_alignment'])) ? $instance['avatar_alignment'] : 'palignleft';
        $height_show = (isset($instance['height_show'])) ? $instance['height_show'] : 200;
        $tag_and_styles_separator = (isset($instance['tag_and_styles_separator'])) ? $instance['tag_and_styles_separator'] : '<br/>';
        $date_format = (isset($instance['date_format']) && !empty($instance['date_format'])) ? $instance['date_format'] : '';
        $time_format = (isset($instance['time_format']) && !empty($instance['time_format'])) ? $instance['time_format'] : '';

        $comments = get_comments(apply_filters('widget_comments_args', array('number' => $num_comments, 'status' => 'approve', 'post_status' => 'publish', 'type' => 'comment')));

        if ($no_show_scroll == 'on' && $num_comments_show_scroll >= count($comments)) {
            $show_scroll = '';
        }
        if (!empty($comments)) {
            $output .= $before_widget;
            if ($title)
                $output .= $before_title . $title . $after_title;

            $output .= '<ul id="recentcomments" style="' . (($show_scroll == 'on') ? "max-height:" . $height_show . 'px' : '') . '">';

            if ($comments) {
                $post_ids = array_unique(wp_list_pluck($comments, 'comment_post_ID'));
                _prime_post_caches($post_ids, strpos(get_option('permalink_structure'), '%category%'), false);
                $pseparator = ($separate_info == 'on') ? $tag_and_styles_separator : '';

                $d = $date_format;
                $t = $time_format;

                $default_date_format = get_option('date_format');
                $default_time_format = get_option('time_format');

                $d = !empty($d) ? $d : $default_date_format;
                $t = !empty($t) ? $t : $default_time_format;

                foreach ((array) $comments as $key => $comment) {

                    // Roles to display label
                    $roles = [];
                    $user_id = $comment->user_id;
                    if ($user_id) {
                        $user = get_userdata($user_id);
                        if ($user) {
                            $roles = $user->roles;
                        }
                    }
                    $is_admin = in_array('administrator', $roles);
                    // Roles to display label

                    $post_title_current = get_the_title($comment->comment_post_ID);
                    $comment_link = ($show_comment_link == 'on') ? get_comment_author_link() : get_comment_author();

                    $email = $comment->comment_author_email;
                    //Display Gravatar;
                    $avatar_url = '';
                    $gravatar_hash = $this->get_gravatar_hash($email);
                    if ($show_gravatar == 'on') {
                        $avatar_url = $this->get_gravatar_url($email, $avatar_size);
                    } else {
                        $avatar_url = $this->get_wp_avatar_url($email, $avatar_size);
                    }
                    $cus_comment_date = get_comment_date($d, $comment->comment_ID) . __(' at ') . get_comment_date($t, $comment->comment_ID);
                    $output .= '<li class="recentcomments">';
                    if (strpos($avatar_alignment, 'rightleft') !== false || strpos($avatar_alignment, 'leftright') !== false) {
                        $cur_avatar_align = $avatar_alignment . '-' . ($key % 2);
                    } else {
                        $cur_avatar_align = $avatar_alignment;
                    }
                    $output .= ($show_avatar == 'on') ? '<div class="' . $cur_avatar_align . '"><img data-is-admin="' . ($is_admin ? 'true' : 'false') . '" data-gravatar-size="' . $avatar_size . '" data-show-gravatar="' . $show_gravatar . '" data-gravatar-hash="' . $gravatar_hash . '" src="' . $avatar_url . '" width="' . $avatar_size . '" height="' . $avatar_size . '" class="' . $avatar_layout . '" style="float: left; margin-right: 10px;  background-color: rgb(255, 255, 255); padding: 3px; border: 1px solid rgb(214, 214, 214); width: ' . $avatar_size . 'px; height: ' . $avatar_size . 'px;" /></div>' : '';
                    $output .= '<b>' . $comment_link . '</b> ' . __('on') . ' <a href="' . esc_url(get_comment_link($comment->comment_ID)) . '">' . $post_title_current . '</a> ' . $pseparator;
                    $output .= $pseparator . $this->truncate_string($comment->comment_content, $num_split);

                    if ($show_date == 'on') {
                        $output .= ' <span class="date">(' . $cus_comment_date . ')</span>';
                    }
                    $output .= '</li>';
                }
            }
            $output .= '</ul>';
            $output .= $after_widget;
            echo $output;
            $cache[$args['widget_id']] = $output;
            wp_cache_set('widget_polygon_recent_comments', $cache, 'widget');
        }
    }

    public function form($instance)
    {

        $user_id = get_current_user_id();
        $email = '';
        if ($user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                $email = $user->email;
            }
        }
        //Display Gravatar;
        $avatar_url = $this->get_gravatar_url($email);
        if (!empty($avatar_url)) {
            $avatar_url = $this->get_wp_avatar_url($email);
        }

        $title = (!empty($instance['title'])) ? $instance['title'] : __('Polygon recent comments', 'POLYGON_Widget_Recent_Comments');
        $num_comments = (!empty($instance['num_comments'])) ? $instance['num_comments'] : 0;
        $avatar_size = (!empty($instance['avatar_size'])) ? $instance['avatar_size'] : 88;
        $num_comments_show_scroll = (!empty($instance['num_comments_show_scroll'])) ? $instance['num_comments_show_scroll'] : 5;
        $num_split = (!empty($instance['num_split'])) ? $instance['num_split'] : 121;
        $avatar_layout = (isset($instance['avatar_layout'])) ? ((!empty($instance['avatar_layout'])) ? $instance['avatar_layout'] : 'square') : 'square';
        $avatar_alignment = (isset($instance['avatar_alignment'])) ? ((!empty($instance['avatar_alignment'])) ? $instance['avatar_alignment'] : 'palignleft') : 'palignleft';
        $show_date = (!empty($instance['show_date'])) ? $instance['show_date'] : '';
        $date_format = (!empty($instance['date_format'])) ? $instance['date_format'] : '';
        $time_format = (!empty($instance['time_format'])) ? $instance['time_format'] : '';
        $height_show = (isset($instance['height_show'])) ? ((!empty($instance['height_show'])) ? $instance['height_show'] : 200) : 200;
        $tag_and_styles_separator = (isset($instance['tag_and_styles_separator'])) ? ((!empty($instance['tag_and_styles_separator'])) ? $instance['tag_and_styles_separator'] : "<br/>") : "<br/>";
        $show_avatar = (!empty($instance['show_avatar'])) ? $instance['show_avatar'] : '';
        $separate_info = (!empty($instance['separate_info'])) ? $instance['separate_info'] : '';
        $show_scroll = (!empty($instance['show_scroll'])) ? $instance['show_scroll'] : '';
        $no_show_scroll = (!empty($instance['no_show_scroll'])) ? $instance['no_show_scroll'] : '';
        $show_comment_link = (!empty($instance['show_comment_link'])) ? $instance['show_comment_link'] : '';
        $show_gravatar = (!empty($instance['show_gravatar'])) ? $instance['show_gravatar'] : '';
?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Header title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
            <label for="<?php echo $this->get_field_id('num_comments'); ?>"><?php _e('Comment limit (<=50):'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('num_comments'); ?>" name="<?php echo $this->get_field_name('num_comments'); ?>" type="text" value="<?php echo esc_attr($num_comments); ?>" />
            <label for="<?php echo $this->get_field_id('num_split'); ?>"><?php _e('Character limit (comment length):'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('num_split'); ?>" name="<?php echo $this->get_field_name('num_split'); ?>" type="text" value="<?php echo esc_attr($num_split); ?>" />
            <hr />

            <input class="widefat" id="<?php echo $this->get_field_id('show_date'); ?>" name="<?php echo $this->get_field_name('show_date'); ?>" type="checkbox" <?php checked($show_date, 'on'); ?> />
            <label for="<?php echo $this->get_field_id('show_date'); ?>"><?php _e('Show date?'); ?></label> <br />
            <label for="<?php echo $this->get_field_id('date_format'); ?>"><?php _e('Date format:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('date_format'); ?>" name="<?php echo $this->get_field_name('date_format'); ?>" type="text" value="<?php echo esc_attr($date_format); ?>" />
            <br /><?php echo _e('Ex: d/m/Y. Default as per <a href="' . home_url('/wp-admin/options-general.php') . '" target="_blank">system configuration</a>.') ?>

            <label for="<?php echo $this->get_field_id('time_format'); ?>"><?php _e('Time format:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('time_format'); ?>" name="<?php echo $this->get_field_name('time_format'); ?>" type="text" value="<?php echo esc_attr($time_format); ?>" />
            <br /><?php echo _e('Ex: g:i A. Default as per <a href="' . home_url('/wp-admin/options-general.php') . '" target="_blank">system configuration</a>.') ?>
            <hr />

            <input class="widefat" id="<?php echo $this->get_field_id('show_comment_link'); ?>" name="<?php echo $this->get_field_name('show_comment_link'); ?>" type="checkbox" <?php checked($show_comment_link, 'on'); ?> />
            <label for="<?php echo $this->get_field_id('show_comment_link'); ?>"><?php _e('Enable author link?'); ?></label> <br />
            <hr />

            <input class="widefat" id="<?php echo $this->get_field_id('separate_info'); ?>" name="<?php echo $this->get_field_name('separate_info'); ?>" type="checkbox" <?php checked($separate_info, 'on'); ?> />
            <label for="<?php echo $this->get_field_id('separate_info'); ?>"><?php _e('Separate info?'); ?></label> <br />
            <label for="<?php echo $this->get_field_id('tag_and_styles_separator'); ?>"><?php _e('HTML tag and style for separator (default is &lt;br/&gt;)'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('tag_and_styles_separator'); ?>" name="<?php echo $this->get_field_name('tag_and_styles_separator'); ?>" type="text" value="<?php echo esc_attr($tag_and_styles_separator); ?>" />
            <hr />

            <input class="widefat" id="<?php echo $this->get_field_id('show_avatar'); ?>" name="<?php echo $this->get_field_name('show_avatar'); ?>" type="checkbox" <?php checked($show_avatar, 'on'); ?> />
            <label for="<?php echo $this->get_field_id('show_avatar'); ?>"><?php _e('Show avatar?'); ?></label>
            <br />
            <input class="widefat" disabled id="<?php echo $this->get_field_id('show_gravatar'); ?>" name="<?php echo $this->get_field_name('show_gravatar'); ?>" type="checkbox" <?php checked($show_gravatar, 'on'); ?> />
            <label for="<?php echo $this->get_field_id('show_gravatar'); ?>"><?php _e('Show gravatar?'); ?></label>
            <br />
            <label for="<?php echo $this->get_field_id('avatar_size'); ?>"><?php _e('Avatar size (in pixels)'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('avatar_size'); ?>" name="<?php echo $this->get_field_name('avatar_size'); ?>" type="text" value="<?php echo esc_attr($avatar_size); ?>" /><br />

            <label for="<?php echo $this->get_field_id('avatar_layout'); ?>"><?php _e('Avatar display'); ?></label>
            <input class="hidden poly-avatar_layout" id="<?php echo $this->get_field_id('avatar_layout'); ?>" name="<?php echo $this->get_field_name('avatar_layout'); ?>" type="text" value="<?php echo esc_attr($avatar_layout); ?>" />
        <div class="poly-avatar-layout-thumb-container" name="<?php echo $this->get_field_name('avatar_layout'); ?>">
            <div data-value="square" class="poly-avatar-layout-item <?php echo ($avatar_layout == 'square') ? 'active' : '' ?>"><img src="<?php echo $avatar_url ?>" class="square poly-avatar-layout-thumb"></div>
            <div data-value="circle" class="poly-avatar-layout-item <?php echo ($avatar_layout == 'circle') ? 'active' : '' ?>"><img src="<?php echo $avatar_url ?>" class="circle poly-avatar-layout-thumb"></div>
            <div data-value="eclip1" class="poly-avatar-layout-item <?php echo ($avatar_layout == 'eclip1') ? 'active' : '' ?>"><img src="<?php echo $avatar_url ?>" class="eclip1 poly-avatar-layout-thumb"></div>
            <div data-value="eclip2" class="poly-avatar-layout-item <?php echo ($avatar_layout == 'eclip2') ? 'active' : '' ?>"><img src="<?php echo $avatar_url ?>" class="eclip2 poly-avatar-layout-thumb"></div>
            <div data-value="eclip3" class="poly-avatar-layout-item <?php echo ($avatar_layout == 'eclip3') ? 'active' : '' ?>"><img src="<?php echo $avatar_url ?>" class="eclip3 poly-avatar-layout-thumb"></div>
            <div data-value="eclip4" class="poly-avatar-layout-item <?php echo ($avatar_layout == 'eclip4') ? 'active' : '' ?>"><img src="<?php echo $avatar_url ?>" class="eclip4 poly-avatar-layout-thumb"></div>
        </div>
        <br />
        <label for="<?php echo $this->get_field_id('avatar_alignment'); ?>"><?php _e('Avatar alignment:'); ?></label>
        <select name="<?php echo $this->get_field_name('avatar_alignment'); ?>" id="<?php echo $this->get_field_id('avatar_alignment'); ?>">
            <option value='palignleft' <?php echo ($avatar_alignment == 'palignleft') ? 'selected' : '' ?>>Left</option>
            <option value='palignright' <?php echo ($avatar_alignment == 'palignright') ? 'selected' : '' ?>>Right</option>
            <option value='palignleftright' <?php echo ($avatar_alignment == 'palignleftright') ? 'selected' : '' ?>>Zigzag left->right</option>
            <option value='palignrightleft' <?php echo ($avatar_alignment == 'palignrightleft') ? 'selected' : '' ?>>Zigzag right->left</option>
        </select>
        <hr />
        <input class="widefat" id="<?php echo $this->get_field_id('show_scroll'); ?>" name="<?php echo $this->get_field_name('show_scroll'); ?>" type="checkbox" <?php checked($show_scroll, 'on'); ?> />
        <label for="<?php echo $this->get_field_id('show_scroll'); ?>"><?php _e('Enable scroll bar?'); ?></label><br />

        <input class="widefat" id="<?php echo $this->get_field_id('no_show_scroll'); ?>" name="<?php echo $this->get_field_name('no_show_scroll'); ?>" type="checkbox" <?php checked($no_show_scroll, 'on'); ?> />
        <label for="<?php echo $this->get_field_id('no_show_scroll'); ?>"><?php _e('Do not show scroll bar if there are '); ?></label>
        <input class="widefat" style="display:inline-block;width: 50px" id="<?php echo $this->get_field_id('num_comments_show_scroll'); ?>" name="<?php echo $this->get_field_name('num_comments_show_scroll'); ?>" type="text" value="<?php echo esc_attr($num_comments_show_scroll); ?>" /> <?php echo _e('comments') ?>
        <br />
        <label for="<?php echo $this->get_field_id('height_show'); ?>"><?php _e('Comment display frame height (in pixels):'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('height_show'); ?>" name="<?php echo $this->get_field_name('height_show'); ?>" type="text" value="<?php echo esc_attr($height_show); ?>" />
        <br /><?php echo _e('(Do not display scroll bar if input is 1000)') ?>
        </p>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $num_comments = $this->remove_non_numeric($new_instance['num_comments'], 12);
        $instance['num_comments'] = ($num_comments  <= 50) ? $num_comments : 12;
        $num_split = $this->remove_non_numeric($new_instance['num_split'], 123);
        $instance['num_split'] = ($num_split  <= 250) ? $num_split : 123;
        $instance['show_date'] = strip_tags($new_instance['show_date']);
        $instance['show_comment_link'] = strip_tags($new_instance['show_comment_link']);
        $instance['show_avatar'] = strip_tags($new_instance['show_avatar']);
        $instance['show_gravatar'] = strip_tags($new_instance['show_gravatar']);
        $instance['separate_info'] = strip_tags($new_instance['separate_info']);
        $instance['tag_and_styles_separator'] = (!empty($new_instance['tag_and_styles_separator'])) ? $new_instance['tag_and_styles_separator'] : '<br/>';
        $instance['show_scroll'] = strip_tags($new_instance['show_scroll']);
        $instance['no_show_scroll'] = strip_tags($new_instance['no_show_scroll']);
        $instance['date_format'] = strip_tags($new_instance['date_format']);
        $instance['time_format'] = strip_tags($new_instance['time_format']);
        $instance['avatar_layout'] =  $new_instance['avatar_layout'];
        $instance['avatar_alignment'] =  $new_instance['avatar_alignment'];
        $num_comments_show_scroll = $this->remove_non_numeric($new_instance['num_comments_show_scroll'], 5);
        $instance['num_comments_show_scroll'] = ($num_comments_show_scroll  <= 200) ? $num_comments_show_scroll : 5;
        $instance['avatar_size'] = (!empty($new_instance['avatar_size'])) ? strip_tags($new_instance['avatar_size']) : '88';
        $height_show = $this->remove_non_numeric($new_instance['height_show'], 200);
        $instance['height_show'] = ($height_show  <= 1000) ? $height_show : 200;
        return $instance;
    }

    /**
     * Generate a Gravatar hash from an email address.
     * 
     * This function takes an email address and generates a hash that can be 
     * used to retrieve the Gravatar associated with that email. It uses the MD5 
     * hashing algorithm after converting the email to lowercase and trimming any 
     * leading or trailing whitespace.
     * 
     * @param string $email The email address to generate the hash for.
     * 
     * @return string The MD5 hash of the email address.
     */
    function get_gravatar_hash($email)
    {
        return md5(strtolower(trim($email)));
    }

    /**
     * Get the URL of a Gravatar for a given email address and size.
     * 
     * This function constructs the URL to the Gravatar image associated with 
     * the specified email address. It uses the Gravatar hash generated by the 
     * get_gravatar_hash function and includes the specified size parameter.
     * 
     * @param string $email The email address to get the Gravatar for.
     * @param int $size The size of the Gravatar image in pixels.
     * 
     * @return string The URL of the Gravatar image.
     */
    function get_gravatar_url($email, $size = '404')
    {
        return 'http://www.gravatar.com/avatar/' . $this->get_gravatar_hash($email) . '?s=' . $size;
    }

    /**
     * Get the URL of a WordPress avatar for a given email address.
     * 
     * This function retrieves the URL of a WordPress avatar based on the provided 
     * email address. If a user with the specified email exists, it gets the avatar 
     * URL for that user's ID. If no user is found, it attempts to get the avatar 
     * URL directly using the email.
     * 
     * @param string $email The email address of the user.
     * @param int $size (optional) The size of the avatar in pixels (default is 80).
     * 
     * @return string The URL of the user's avatar.
     */
    function get_wp_avatar_url($email, $size = 80)
    {
        $user = get_user_by('email', $email);
        $avatar_url = '';
        if ($user) {
            $avatar_url = get_avatar_url($user->ID, ['size' => $size]);
        } else {
            $avatar_url = get_avatar_url($email, ['size' => $size]);
        }
        return $avatar_url;
    }

    /**
     * Remove all non-numeric characters from a string.
     * If the value is null or empty, set it to 0.
     *
     * @param string|null $input The input string.
     * @return int The cleaned numeric value.
     */
    function remove_non_numeric($input, $default = 0)
    {
        if (is_null($input) || $input === '') {
            return 0;
        }
        $numeric_value = preg_replace('/\D/', '', $input);
        if ($numeric_value === '') {
            return $default;
        }
        return (int)$numeric_value;
    }

    /**
     * Truncate a string to a specified length without cutting off words.
     * 
     * This function will truncate a string to a specified length and add an 
     * optional ellipsis (or other string) to indicate that the string has 
     * been truncated. If the truncation point falls within a word, the function 
     * will backtrack to the previous space to ensure that the word is not cut off.
     * 
     * @param string $string The input string to be truncated.
     * @param int $length The maximum length of the truncated string.
     * @param string $etc (optional) The string to append to the truncated string (default is '...').
     * 
     * @return string The truncated string, with the optional appended string.
     */
    function truncate_string($string, $length, $etc = '...')
    {
        $string = strip_tags($string);
        if (mb_strlen($string) <= $length) {
            return $string;
        }
        $last_space = mb_strrpos(mb_substr($string, 0, $length), ' ');
        if ($last_space !== false) {
            return mb_substr($string, 0, $last_space) . $etc;
        }
        return mb_substr($string, 0, $length) . $etc;
    }
}
