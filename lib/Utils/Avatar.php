<?php

namespace SGI\LtrAv\Utils;

/**
 * 
 * @param mixed        $id_or_email The avatar to retrieve. Accepts a user_id, Gravatar MD5 hash,
 *                                  user email, WP_User object, WP_Post object, or WP_Comment object.
 * @return null|string $letter      Letter for the avatar
 */
function getLetter($user_or_id) : ?string
{

    return null;

}

/**
 * Function which checks if the user has a gravatar set.
 * 
 * @param  mixed $id_or_email The Gravatar to retrieve. Accepts a user ID, Gravatar MD5 hash,
 *                            user email, WP_User object, WP_Post object, or WP_Comment object.
 * @param  array $args        Optional. Arguments to return instead of the default arguments.
 * @return array              Along with the arguments passed in `$args`, this will contain a couple of extra arguments.    
 * 
 * @since 3.5
 */
function validateGravatar($id_or_email, ?array $args = null) : bool
{

    $check  = false;
    $cached = LtrAv()->cacheActive();
    $theuid = processUserIdentifier($id_or_email);

    if ($cached) :

        $check = wp_cache_get("ltrav_{$theuid['hash']}",'sgi_ltrav');

    endif;

    if ($check !== false) :
        return ($check == 200) ? true : false;
    endif;

    if (!array_key_exists('size',$args)) :
        $args['size'] = 200;
    endif;

    $url_args = [
        's' => $args['size'],
        'd' => '404',
        'f' => $args['force_default'] ? 'y' : false,
        'r' => $args['rating'],
    ];

    $url = sprintf( 'http://2.gravatar.com/avatar/%s', $theuid['hash'] );

    if ( is_ssl() ) :
        $url = 'https://secure.gravatar.com/avatar/' . $theuid['hash'];
    endif;

    $url = add_query_arg(
        rawurlencode_deep( array_filter( $url_args ) ),
        set_url_scheme( $url, $args['scheme'] )
    );

    $response = wp_remote_head($url);
        
    if( is_wp_error($response) ) :
        $check = 404;
    else :
        $check = $response['response']['code'];
    endif;

    return ($check == 200) ? true : false;

}

function processUserIdentifier($id_or_email) : ?array
{

    //Set the default data
    $data = [
        'name'  => null,
        'email' => null,
        'hash'  => null,
    ];

    if ($id_or_email instanceof \WP_Comment) : //

        if ( !empty($id_or_email->user_id) ) :

            $user = get_user_by( 'id', (int) $id_or_email->user_id );

            $data['name']  = $user->display_name;
            $data['email'] = get_userdata($user->ID)->user_email;
            $data['hash']  = md5( strtolower( trim($data['email']) ) );

            return $data;

        endif;

        $data['name']  = $id_or_email->comment_author;
        $data['email'] = $id_or_email->comment_author_email;
        $data['hash']  = md5( strtolower( trim($data['email']) ) );

        return $data;

    elseif ( is_numeric($id_or_email) ) :

            $user = get_user_by( 'id', (int) $id_or_email );

            $data['name']  = $user->display_name;
            $data['email'] = get_userdata($user->ID)->user_email;
            $data['hash']  = md5( strtolower( trim($data['email']) ) );

            return $data;

    elseif ( is_string($id_or_email) && is_email($id_or_email) ) :

        $data['email'] = $id_or_email;
        $data['hash']  = md5( strtolower( trim($data['email']) ) );

        return $data;

    elseif ($id_or_email instanceof \WP_User) :

        $user = get_user_by( 'id', (int) $id_or_email );

        $data['name']  = $user->display_name;
        $data['email'] = get_userdata($user->ID)->user_email;
        $data['hash']  = md5( strtolower( trim($data['email']) ) );

        return $data;

    elseif ($id_or_email instanceof \WP_Post) :

        $user = get_user_by( 'id', (int) $id_or_email->post_author );

        $data['name']  = $user->display_name;
        $data['email'] = get_userdata($user->ID)->user_email;
        $data['hash']  = md5( strtolower( trim($data['email']) ) );

        return $data;

    endif;

    return null;
    
}