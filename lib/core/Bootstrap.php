<?php

namespace SGI\LtrAv\Core;

use \SGI\LtrAv\{
    Admin    as Admin,
    Frontend as Frontend
};

use const \SGI\LtrAv\{
    FILE,
    PATH,
    DOMAIN
};

use function SGI\LtrAv\Utils\getDefaultConfig;

class Bootstrap
{

    /**
     * @var null|Bootstrap Class instance
     * @since 3.5
     */
    private static ?Bootstrap $instance = null;

    /**
     * @var array Plugin options
     * @since 3.5
     */
    private array $opts;

    /**
     * @var bool WP User Avatar activation check
     * @since 3.5
     */
    private bool $wpua_active;

    /**
     * @var bool WP User Avatar activation check
     * @since 3.5
     */
    private bool $bp_active;

    /**
     * @var bool Flag which determines if we're using cache for gravatar checks
     * @since 3.5
     */
    private bool $use_cache;

	private function __construct()
	{

        $this->opts = get_option(
            'sgi_ltrav_opts',
            getDefaultConfig()
        );

        add_action('wp_loaded', [&$this, 'loadTextDomain']);
        add_action('plugins_loaded', [&$this, 'checkConflicts'], 100);

		if (is_admin()) :
			add_action('plugins_loaded', [&$this, 'load_admin']);
        endif;
        
        add_action('init', [&$this, 'loadFrontend'], 500);

    }

    /**
     * Returns the class instance
     *
     * @return Bootstrap Class instance
     * @since 3.5
     */
    public static function getInstance()
    {

        if (self::$instance === null) :
            self::$instance = new Bootstrap();
        endif;

        return self::$instance;

    }

    /**
     * Retrieves Plugin options
     *
     * @return array Plugin options array
     * @since 3.5
     */
    public function getOpts() : array
    {
        return $this->opts;
    }

    /**
     * Retrieve caching option
     * 
     * @return boolean Flag which determines if we're using cache
     * @since 3.5
     */
    public function cacheActive() : bool
    {
        return $this->use_cache;
    }

    /**
     * Retrieves WP User Avatar activation check
     *
     * @return bool True if active, false if not
     * @since 3.5
     */
    public function wpuaActive() : bool
    {
        return $this->wpua_active;
    }

    /**
     * Retrieves BuddyPress activation check
     *
     * @return bool True if active, false if not
     * @since 3.5
     */
    public function bpActive() : bool
    {
        return $this->bp_active;
    }
    
    public function checkConflicts()
    {

        $this->bp_active   = class_exists('BuddyPress');
        $this->wpua_active = function_exists('has_wp_user_avatar');

        /**
         * @since 2.6
         * @param boolean - flag which determines if we should use caching for avatar checks
         */
        $this->use_cache = apply_filters('sgi/ltrav/use_cache', false);

    }

	public function loadTextDomain()
	{

		$domain_path = basename(dirname(FILE)).'/languages';

		load_plugin_textdomain(
			DOMAIN,
			false,
			$domain_path
		);

	}

	public function load_admin()
	{

		new Admin\Core();
		new Admin\Scripts();
		new Admin\Settings();

	}

	public function loadFrontend()
	{

        new Frontend\Scripts();
        new Frontend\Engine();

	}

}