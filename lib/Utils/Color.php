<?php

/**
 * Function that deterimines the font color for the letter avatar based on the background color
 * 
 * @param  string $hexcolor Hex value for the background color
 * @return string           Color of the letter for the avatar
 * 
 * @author Sibin Grasic
 * @since 1.0
 * @link https://en.wikipedia.org/wiki/YIQ
 * @link https://24ways.org/2010/calculating-color-contrast/
 * 
 */
function getYIQContrast(string $hexcolor) : string
{
    $hexcolor = ltrim($hexcolor,'#');

    $r = hexdec(substr($hexcolor,0,2));
    $g = hexdec(substr($hexcolor,2,2));
    $b = hexdec(substr($hexcolor,4,2));

    $yiq = (($r*299)+($g*587)+($b*114))/1000;

    return ($yiq >= 128) ? '#000' : '#fff';
}

/**
 * Converts Hue-Saturation-Luminosity to RGB
 * 
 * @param  float  $H Hue
 * @param  float  $S Saturation
 * @param  float  $L Luminosity
 * @return string    RGB color
 * 
 * @since 2.1
 */
function hsl2rgb(float $H, float $S, float $L = 1) : string
{

    $H *= 6;
    $h = intval($H);
    $H -= $h;

    $L *= 255;

    $m = $L*(1 - $S);

    $x = $L*(1 - $S*(1-$H));
    $y = $L*(1 - $S*$H);

    $a = [
        [$L, $x, $m],
        [$y, $L, $m],
        [$m, $L, $x],
        [$m, $y, $L],
        [$x, $m, $L],
        [$L, $m, $y],
    ];

    $a = $a[$h];

    return sprintf("#%02X%02X%02X", $a[0], $a[1], $a[2]);

}

/**
 * Function which generates pretty random color for avatar background
 * 
 * @param  string  $user_uid    User UID
 * @param  array   $used_colors Array of already used colors
 * @return string  $bg_color    Hex color for avatar background
 * 
 * @since 2.1
 */
function generatePrettyRandomColor($user_uid = false, $used_colors)
{

    $user_uid = ($user_uid) ? $user_uid : uniqid();

    $hue = unpack('L', hash('adler32', strtolower($user_uid), true))[1];

    do {

        $bg_color = hsl2rgb($hue/0xFFFFFFFF, (mt_rand() / mt_getrandmax()), 1);

    } while (in_array($bg_color, $used_colors, true));

    return $bg_color;

}

/**
 * function which generated random color.
 * Deprecated in favor of generatePrettyRandomColor
 * 
 * @param  array $used_colors Colors previously used for avatar
 * @return string             Random Hex Color
 * 
 * @deprecated 3.5
 * @see generatePrettyRandomColor
 * @since 2.0
 */
function generateRandomColor(?array &$used_colors) : string
{

    $bg_color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));

    while (in_array($bg_color, $used_colors)) :
        $bg_color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    endwhile;

    $used_colors[] = $bg_color;

    return $bg_color;

}