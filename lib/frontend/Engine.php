<?php

namespace SGI\LtrAv\Frontend;

use const \SGI\LtrAv\PATH;

use function SGI\LtrAv\Utils\validateGravatar;
use function SGI\LtrAv\Utils\processUserIdentifier;

class Engine
{

    /**
     * @var Array Plugin options
     * @since 1.0
     */
    private $opts;

    /**
     * @var bool Flag which defines buddypress usage
     * @since 2.5
     */
    private $with_bp;

    /**
     * @var bool Flag which defines if we're using WP User Avatar
     * @since 3.0
     */
    private $with_wpua;

    /**
     * @var string Location to letter avatar template
     * @since 3.0
     */
    private $avatar_template;

    /**
     * @var array Already locked colors if user lock-in is enabled
     * @since 2.0
     */
    private $locked_colors;

    /**
     * @var array Array of used colors for letter avatars
     * @since 2.0
     */
    private $used_colors;

    public function __construct()
    {

        $this->with_wpua = LtrAv()->wpuaActive();
        $this->with_bp   = LtrAv()->bpActive();
        $this->opts      = LtrAv()->getOpts();

        $this->used_colors     = [];
        $this->locked_colors   = [];
        $this->avatar_template = file_get_contents(PATH . '/templates/letter_avatar.tpl');

        add_filter('wpua_get_avatar_filter',array(&$this,'override_wpua_avatar'),10,5);

        if ($this->with_bp) :
            add_filter('bp_core_fetch_avatar',array(&$this,'override_bp_avatar'),10,9);
        endif;

        if (!$this->with_wpua) :
            add_filter('pre_get_avatar',array(&$this,'override_avatar'),10,3);
        endif;

    }

    /**
     * Function which overrides WP User Avatar
     * 
     * @param  string     $avatar
     * @param  int|string $id_or_email
     * @param  int|string $size
     * @param  string     $default
     * @param  string     $alt
     * @return string     $avatar 
     * 
     * @since 3.0
     */
    public function override_wpua_avatar($avatar, $id_or_email, $size, $default, $alt)
    {
        if (!function_exists('has_wp_user_avatar')) :
            return $avatar;
        endif;

        if (has_wp_user_avatar($id_or_email))
            return $avatar;

        $args = [
            'height'        => $size,
            'width'         => $size,
            'size'          => $size,
            'force_default' => 'y',
            'rating'        => 'x'
        ];

        return $this->override_avatar($avatar, $id_or_email, $args);

    }

    /**
     * Function that overrides BuddyPress avatar
     * 
     * @param  string $value             Full <img> element for an avatar.
     * @param  array  $params            Array of parameters for the request.
     * @param  string $value             ID of the item requested.
     * @param  string $value             Subdirectory where the requested avatar should be found.
     * @param  string $html_css_id       ID attribute for avatar.
     * @param  string $html_width        Width attribute for avatar.
     * @param  string $html_height       Height attribute for avatar.
     * @param  string $avatar_folder_url Avatar URL path.
     * @param  string $avatar_folder_dir Avatar DIR path.
     * @return string                    Complete HTML for letter avatar, or original avatar if set
     * 
     * @since 2.5

     */
    public function override_bp_avatar($html, $params, $item_id, $avatar_dir, $html_css_id, $html_width, $html_height, $avatar_folder_url, $avatar_folder_dir)
    {
        
        $object = $params['object'];

        switch ($object) :

            case 'user' :

                if (empty($item_id) || $item_id == 0) :

                    if (is_user_logged_in()) :

                        $item_id = get_current_user_id();

                    else :

                        return $html;

                    endif;

                endif;

                $user = get_user_by('ID', $item_id);

                if ($user->first_name == '') :

                    $letter = mb_substr( $user->user_email, 0, 1 );

                else :

                    $letter = mb_substr( $user->first_name, 0, 1 );

                endif;

                if (validateGravatar($user, $params) && $this->opts['use_gravatar'])
                    return $html;

                if (strpos($html, get_option('siteurl')) !== false)
                    return $html;

                return $this->make_letter_avatar(
                    $user->data->user_email,
                    $letter,
                    array(
                        'height' => $params['height'],
                        'width'  => $params['width']
                    )
                );

            break;

            case 'group' :

                if (strpos($html, 'mystery-group') === false)
                    return $html;

                $group = groups_get_group(array(
                    'group_id' => $item_id
                ));

                $letter = mb_substr($group->name, 0, 1);

                return $this->make_letter_avatar(
                    $group->name,
                    $letter,
                    array(
                        'height' => $params['height'],
                        'width'  => $params['width']
                    )
                );

            break;

        endswitch;


        return $html;
    }

    /**
     * Main plugin function which overrides the get_avatar call
     * 
     * @param  string|null $avatar      HTML for the avatar
     * @param  mixed       $id_or_email The avatar to retrieve. Accepts a user_id, Gravatar MD5 hash,
     *                                  user email, WP_User object, WP_Post object, or WP_Comment object.
     * @param  array       $args        Default arguments for avatar display
     * @return string                   Avatar HTML
     * 
     * @since 2.5
     * @author Sibin Grasic
     */
    public function override_avatar(?string $avatar, $id_or_email, array $args) : ?string
    {

        if ($this->opts['use_gravatar']) :
            if (validateGravatar($id_or_email, $args)) :
                return $avatar;
            endif;
        endif;

        $uid = processUserIdentifier($id_or_email);

        $letter   = mb_substr($uid['name'], 0, 1);
        $user_uid = $uid['email'];

        if ( is_admin() && !wp_doing_ajax())
            return $avatar;

        $args['letter'] = $letter;

        return $this->make_letter_avatar($user_uid, $args);

    }

    /**
     * Function that creates letter avatars
     * @param string $user_uid - user e-mail
     * @since 1.0
     */
    public function make_letter_avatar($user_uid, $args)
    {

        $default_args = [
            'bg_color'  => 'auto',
            'lt_color'  => 'auto',
            'font_size' => 'inherit',
        ];

        $args = wp_parse_args($args, $default_args);

        $avatar_args = [
            '{{HEIGHT}}'      => $args['height'],
            '{{LINE_HEIGHT}}' => $args['height'],
            '{{WIDTH}}'       => $args['width'],
            '{{BG_COLOR}}'    => $args['bg_color'],
            '{{COLOR}}'       => $args['lt_color'],
            '{{FONT_SIZE}}'   => $args['font_size'],
            '{{LETTER}}'      => $args['letter']
        ];

        if($this->opts['font']['auto_size']) :
            $avatar_args['{{FONT_SIZE}}'] = round($args['height'] * 0.75,0) . 'px';
        endif;

        if (!$this->opts['style']['rand_color']) :

            $avatar_args['{{BG_COLOR}}'] = 'auto';
            $avatar_args['{{COLOR}}']    = 'auto';

            $avatar = strtr($this->avatar_template, $avatar_args);

            return $avatar;

        endif;


        if ($this->opts['style']['lock_color']) :
            
            if (!isset($this->locked_colors[$user_uid])) :

                $this->locked_colors[$user_uid] = generatePrettyRandomColor($user_uid, $this->used_colors);;
                $this->used_colors[] = $this->locked_colors[$user_uid];

            endif;

            $avatar_args['{{BG_COLOR}}'] = $this->locked_colors[$user_uid];
            $avatar_args['{{COLOR}}']    = getYIQContrast($avatar_args['{{BG_COLOR}}']);

            $avatar = strtr($this->avatar_template, $avatar_args);

            return $avatar;

        endif;

        $avatar_args['{{BG_COLOR}}'] = generatePrettyRandomColor(false, $this->used_colors);
        $avatar_args['{{COLOR}}']    = getYIQContrast($avatar_args['{{BG_COLOR}}']);

        $avatar = strtr($this->avatar_template, $avatar_args);

        return $avatar;

    }



}