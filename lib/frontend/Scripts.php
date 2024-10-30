<?php

namespace SGI\LtrAv\Frontend;

use function SGI\LtrAv\Utils\generateCSS;

class Scripts
{

    /**
     * @var Array - Plugin options
     * @since 1.0
     */
    private $opts;

    /**
     * @var bool - Defines if we're loading styles
     * @since 1.1
     */
    private $load_css;

    /**
     * @var bool - Flag which defines buddypress usage
     * @since 2.5
     */
    private $with_bp;

    public function __construct()
    {

        if (is_admin())
            return;

        $this->with_bp = LtrAv()->bpActive();
        $this->opts    = LtrAv()->getOpts();

        /**
         * @since 1.1
         * @param boolean - boolean flag which determines if we should load inline styles
         */
        $this->load_css = apply_filters('sgi/ltrav/load_styles', true);

        //add styles
        add_action('wp_head',[&$this,'add_inline_styles'],20);
        add_action('wp_enqueue_scripts', [&$this,'add_gfont_css'],20);

    }

    public function add_inline_styles()
    {

        if (!$this->load_css)
            return;

        global $post;

        if (!is_singular())
            return;

        $css = generateCSS($this->opts['style'],$this->opts['font'],$this->with_bp);

        printf("<style type=\"text/css\" id=\"sgi-ltrav-style\">\n%s\n</style>", $css);

    }

    public function add_gfont_css()
    {

        if (!$this->opts['font']['load_gfont'])
            return;

        if (!is_singular())
            return;
        
        if (!$this->load_css)
            return;

        $font_name = str_replace(' ', '+', $this->opts['font']['font_name']);
        $font_style = $this->opts['font']['gfont_style'];

        if ($font_style == 'regular') :
            $font_style = '400';
        endif;

        if ($font_style == 'italic') :
            $font_style = '400italic';
        endif;

        wp_enqueue_style('sgi-letter-avatar-gfont', "//fonts.googleapis.com/css?family=${font_name}:${font_style}&subset=latin-ext", false, null );
    }

}