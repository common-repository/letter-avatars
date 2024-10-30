<?php

namespace SGI\LtrAv\Utils;

use const SGI\LtraV\PATH;



/**
 * Fetches the default Letter Avatars config
 * 
 * @return array Array of default plugin options
 * 
 * @since 3.5
 * @author Rados Perovic <rados.perovic@oblak.studio>
 */
function getDefaultConfig()
{

    return [
        'use_gravatar' => true,
        'style'        => [
            'case'         => 'uppercase',
            'shape'        => 'round',
            'rand_color'   => true,
            'lock_color'   => true,
            'color'        => '#FFF',
            'bg_color'     => '#000',
        ],
        'font'         => [
            'load_gfont'   => false,
            'use_css'      => false,
            'font_name'    => 'Roboto',
            'gfont_style'  => '',
            'auto_size'    => true,
            'font_size'    => '14',
        ]
    ];

}

/**
 * Function that generates inline style for the plugin
 * 
 * @param  array   $style_opts Style options
 * @param  array   $font_opts  Font options
 * @return string              Compiled CSS for the plugin
 * 
 * @author Sibin Grasic
 * @since 1.0
 */
function generateCSS($style_opts,$font_opts)
{

    $css_template = file_get_contents(PATH . '/templates/inline-css.css');

    $gfont_style = $font_opts['gfont_style'];
    $gfont_style = ($gfont_style == 'regular') ? '400' : $gfont_style;
    $gfont_style = ($gfont_style == 'italic') ? '400italic' : $gfont_style;

    if (strlen($gfont_style) > 3) :

        $weight = substr($gfont_style, 0, 3);
        $style = substr($gfont_style, 3);

    else :

        $weight = $gfont_style;
        $style  = 'regular';

    endif;

    // Set CSS variables
    $css_vars = [];

    if (!$style_opts['rand_color']) :
        $css_vars[] = "--ltrav-bg: {$style_opts['bg_color']};";
        $css_vars[] = "--ltrav-color: {$style_opts['color']};";
    endif;

    if ($style_opts['shape'] == 'round') :
        $css_vars[] = '--ltrav-border-radius: 50%;';
    endif;

    if ($font_opts['use_css'] && $font_opts['font_name'] != '') :
        $css_vars[] = "--ltrav-font-family: {$font_opts['font_name']};";
    endif;

    if (!$font_opts['auto_size']) :
        $css_vars[] = "--ltrav-font-size: {$font_opts['font_size']}px;";
    endif;

    $css_vars[] = "--ltrav-font-weight: {$weight};";
    $css_vars[] = "--ltrav-font-style: {$style};";
    $css_vars[] = "--ltrav-text-transform: {$style_opts['case']};";

    $css = strtr($css_template, [
        '/*avatar-styles*/    ' => implode("\n\t", $css_vars)
    ]);

    return $css;
}

/**
 * Function that generates select boxes for google fonts.
 * We generate a select box for all the google fonts, with custom data-var variable that lists available styles for the font
 * 
 * @param  string  $selected_font  Selected font for the letter avatar
 * @param  string  $selected_style Selected font style for the letter avatar
 * @param  bool    $load_gfont     Boolean that defines if we're loading google font
 * @param  bool    $use_css        Boolean that defines if we're printing google font CSS
 * @return string                  HTML for Google Font select
 */
function googleFontSelect($selected_font, $selected_style, $load_gfont, $use_css)
{

    $font_list = fetchGoogleFonts($load_gfont, $use_css);
    $html = '';

    if (!$font_list && !$load_gfont && !$use_css)
        return sprintf(
            '<strong>%s</strong>
             <select style="display:none" name="sgi_ltrav_opts[font][font_name]"><option></option></select>',
            __('Google font list will be loaded when you check either of the above options','letter-avatars')
        );

    if (!$font_list) 
        return sprintf(
            '<strong>%s</strong>',
            __('Something went wrong, unable to fetch font list','letter-avatars')
        );
    

    $sel_font_array = null;

    $html .= '<select id="ltrav-gfont-select" name="sgi_ltrav_opts[font][font_name]"><option></option>';

    foreach ($font_list['items'] as $font ):

        if ($selected_font == $font['family']) :
            $sel_font_array = $font;
        endif;

        $html .= sprintf (
            '<option value="%s" data-var="%s" %s>
                %s
            </option>',
            $font['family'],
            implode(',',$font['variants']),
            selected( $selected_font, $font['family'], false ),
            $font['family']

        );

    endforeach;

    $html .= '/<select>';

    $html .= '<select id="ltrav-gfont-style" name="sgi_ltrav_opts[font][gfont_style]>';

    if (is_array($sel_font_array)) :
        foreach ($sel_font_array['variants'] as $variant) :

            $html .= sprintf(
                '<option value="%s" %s>%s</option>',
                $variant,
                selected($selected_style,$variant,false),
                $variant
            );

        endforeach;
    endif;

    $html .= '</select><br>';

    return $html;

}

/**
 * Function that gets the complete google fonts list.
 * We first check if we have the font list in a transient. If not it will be fetched from google.
 * 
 * @param bool   $load_gfont Boolean that defines if we're loading google font
 * @param bool   $use_css    Boolean that defines if we're printing google font CSS
 * @return array             JSON decoded font list from google server
 * 
 * @author Sibin Grasic
 * @since 1.0
 */
function fetchGoogleFonts(bool $load_gfont, bool $use_css) : array
{

    if (!$load_gfont && !$use_css)
        return [];

    $url = 'https://www.googleapis.com/webfonts/v1/webfonts?key=AIzaSyC2XWzS33ZIlkC17s5GEX31ltIjOffyP5o';

    $font_list = get_transient('sgi_ltrav_gfonts');

    if ($font_list !== false) :

        return json_decode($font_list, true);

    endif;

    $font_list = wp_remote_get($url);

    if (is_wp_error( $font_list )) :

        return false;

    endif;

    $font_list = $font_list['body'];

    set_transient('sgi_ltrav_gfonts', $font_list, (60*60*24));

    return json_decode($font_list,true);

}