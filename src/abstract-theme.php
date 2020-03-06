<?php
/**
 * Theme Base Class for WordPress Themes
 *
 * @author  Seth Carstens
 * @package abtract-theme-base
 * @version 1.0.0
 * @license GPL 2.0 - please retain comments that express original build of this file by the author.
 */

/*
 * Namespace with versions as a solution to composer vs WordPress themes
 * Reference url https://wptavern.com/a-narrative-of-using-composer-in-a-wordpress-theme
 */

namespace WPAZ_Theme_Base\V_1_0;

/**
 * Class Theme_Base
 */
abstract class Abstract_Theme {
	/**
	 * Turn debugging on or off
	 *
	 * @var bool $debug
	 */
	public $debug;

	/**
	 * Used to hold an instance of the admin object related to the theme.
	 *
	 * @var null|\stdClass|Abstract_Theme $admin
	 */
	public $Abstract_Theme_version = __NAMESPACE__;

	/**
	 * Used to hold an instance of the admin object related to the theme.
	 *
	 * @var null|\stdClass|Abstract_Theme $admin
	 */
	public $admin;

	/**
	 * Use magic constant to tell abstract class current namespace as prefix for all other namespaces in the theme.
	 *
	 * @var string $autoload_class_prefix magic constant
	 */
	public static $autoload_class_prefix = __NAMESPACE__;

	/**
	 * Define the folder or folders that spl_autoload should check for custom PHP classes that need autoloaded
	 *
	 * @var array|string $autoload_dir
	 */
	public $autoload_dir = [ '/app/', '/app/admin/', '/app/admin/inc/' ];

	/**
	 * Usually the depth of your namespace prefix, defaults to 1, only applies to psr-4 autoloading type.
	 *
	 * @var string $autoload_ns_match_depth more efficient when set to 2, when using package [ns_prefix]/[ns]
	 */
	public static $autoload_ns_match_depth = 2;

	/**
	 * Autoload type can be classmap or psr-4
	 *
	 * @var string $autoload_dir classmap or psr-4 or false
	 */
	public static $autoload_type = 'psr-4';

	/**
	 * Magic constant trick that allows extended classes to pull actual server file location, copy into subclass.
	 *
	 * @var string $current_file
	 */
	protected static $current_file = __FILE__;

	/**
	 * Filename prefix standard for WordPress when the file represents a class
	 *
	 * @var string $filename_prefix typically class- is the prefix
	 */
	public static $filename_prefix = 'class-';

	/**
	 * themes class object installed directory on the server
	 *
	 * @var string $installed_dir
	 */
	public $installed_dir;

	/**
	 * theme root directory on the server
	 *
	 * @var string $theme_object_basedir
	 */
	public $theme_basedir;

	/**
	 * themes URL for access to any static files or assets like css, js, or media
	 *
	 * @var string $installed_url
	 */
	public $installed_url;

	/**
	 * Modules is a collection class that holds the modules / parts of the theme.
	 *
	 * @var \stdClass $modules
	 */
	public $modules;

	/**
	 * Related WordPress multisite network url with smarter fallbacks to guarantee a value
	 *
	 * @var string $network_url
	 */
	public $network_url;

	/**
	 * When main theme filename matches folder name this gets the value from get_theme_data()
	 *
	 * @var array $theme_data Array of meta data representing meta from main theme file
	 */
	public $theme_data = [];

	/**
	 * If theme_data is built, this represents the version number defined the the main theme file meta
	 *
	 * @var string $version
	 */
	public $version;

	/**
	 * Assumed path to main theme file. Assumes your theme folder and main theme file are the same.
	 *
	 * @var string $theme_file
	 */
	public $theme_file;

	/**
	 * The slug or name stored in array WordPress uses to associate a "short path" for each theme.
	 * Example found in site_option('active_sitewide_themes')
	 *
	 * @var string $wp_theme_slug
	 */
	public $wp_theme_slug;

	/**
	 * First thing setup by class with provided namespace for use as prefix in WordPress hooks & actions
	 *
	 * @var string
	 */
	public $wp_hook_pre = '';

	/**
	 * Construct the theme object.
	 * Note that classes that extend this class should add there construction actions into onload()
	 */
	public function __construct() {

		// Setup hook prefix used to create unique actions/filters unique to this theme.
		$this->wp_hook_pre = trim( strtolower( str_ireplace( '\\', '_', get_called_class() ) ) ) . '_';

		// Hook can be used by mu themes to modify theme behavior after theme is setup.
		do_action( $this->wp_hook_pre . '_preface', $this );

		// Configure and setup the theme class variables.
		$this->configure_defaults();

		// Define globals used by the theme including bloginfo.
		$this->defines_and_globals();

		// If enabled, register auto-loading to include any files in the $autoload_dir.
		if ( ! empty( static::$autoload_type ) ) {
			spl_autoload_register( [ $this, 'autoload' ] );
		}

		// Onload to do things during theme construction.
		$this->onload( $this );

		// Most actions go into init which loads after WordPress core sets up all the defaults.
		add_action( 'init', [ $this, 'init' ] );

		// Init for use with logged in users, see this::authenticated_init for more details.
		add_action( 'init', [ $this, 'authenticated_init' ] );

		// Hook can be used by mu themes to modify theme behavior after theme is setup.
		do_action( $this->wp_hook_pre . '_setup', $this );

	} // END public function __construct

	/**
	 * Activated the theme actions
	 *
	 * @return void
	 */
	public static function activate() {
	}

	/**
	 * Initialize the theme - for admin (back end)
	 * You would expected this to be handled on action admin_init, but it does not properly handle
	 * the use case for all logged in user actions. Always keep is_user_logged_in() wrapper within
	 * this function for proper usage.
	 *
	 * @since   0.1
	 * @return  void
	 */
	abstract public function authenticated_init();

	/**
	 * Auto-load classes on demand to reduce memory consumption. Classes must have a namespace so as to resolve
	 * performance issues around auto-loading classes unrelated to current theme.
	 *
	 * @param string $class The name of the class object.
	 */
	public function autoload( $class ) {
		$parent               = explode( '\\', get_class( $this ) );
		$class_array          = explode( '\\', $class );
		$intersect            = array_intersect_assoc( $parent, $class_array );
		$intersect_depth      = count( $intersect );
		$autoload_match_depth = static::$autoload_ns_match_depth;
		// Confirm $class is in same namespace as this autoloader.
		if ( $intersect_depth >= $autoload_match_depth ) {
			$file = $this->get_file_name_from_class( $class );
			if ( 'classmap' === static::$autoload_type && is_array( $this->autoload_dir ) ) {
				foreach ( $this->autoload_dir as $dir ) {
					$this->load_file( $this->installed_dir . $dir . $file );
				}
			} else {
				$this->load_file( $this->installed_dir . $file );
			}
		}

	}

	/**
	 * Setup themes global params.
	 */
	protected function configure_defaults() {
		$this->modules        = new \stdClass();
		$this->modules->count = 0;
		$this->installed_dir  = static::dirname( static::$current_file, 1 );
		$this->theme_basedir = static::dirname( static::$current_file, 2 );
		$assumed_theme_name  = basename( $this->theme_basedir );
		$this->theme_file    = $this->theme_basedir . '/' . $assumed_theme_name . '.php';
		$this->wp_theme_slug = $assumed_theme_name . '/' . $assumed_theme_name . '.php';

		if ( file_exists( $this->theme_file ) ) {
			$this->installed_url = get_stylesheet_directory_uri();
			// Ensure get_theme_data is available.
			require_once ABSPATH . 'wp-admin/includes/theme.php';
			$this->theme_data = get_theme_data( '', $this->theme_basedir );
			if ( is_array( $this->theme_data ) && isset( $this->theme_data['Version'] ) ) {
				$this->version = $this->theme_data['Version'];
			}
		} else {
			$this->installed_url = themes_url( '/', static::$current_file );
		}
		// Setup network url and fallback in case siteurl is not defined.
		if ( ! defined( 'WP_NETWORKURL' ) && is_multisite() ) {
			define( 'WP_NETWORKURL', network_site_url() );
		} elseif ( ! defined( 'WP_NETWORKURL' ) ) {
			define( 'WP_NETWORKURL', get_site_url() );
		}
		$this->network_url = WP_NETWORKURL;
	}

	/**
	 * Deactivated the theme actions
	 * 
	 * @param string $newname Name of the theme we activated.
	 * @param string $newtheme New theme object.
	 * 
	 * @return  void
	 */
	public static function deactivate( $newname, $newtheme ) {
	}

	/**
	 * Enforce that the theme prepare any defines or globals in a standard location.
	 *
	 * @return mixed
	 */
	abstract protected function defines_and_globals();

	/**
	 * Dirname function that mimics PHP7 dirname() enhancements so that we can enable PHP5.6 support.
	 *
	 * @param string $path Path to this specific themes directory.
	 * @param int    $count How many directories to look upwards.
	 *
	 * @return string
	 */
	public static function dirname( $path, $count = 1 ) {
		if ( $count > 1 ) {
			return dirname( static::dirname( $path, -- $count ) );
		} else {
			return dirname( $path );
		}
	}

	/**
	 * Utility function to get class name from filename if you follow this Abtract Theme's naming standards
	 *
	 * @param string $file          Absolute path to file.
	 * @param string $installed_dir Absolute path to theme folder.
	 * @param string $namespace     Namespace of calling class, if any.
	 *
	 * @return string $class_name Name of Class to load based on file path.
	 */
	public static function filepath_to_classname( $file, $installed_dir, $namespace ) {
		/**
		 * Convert path and filename into namespace and class
		 */
		$path_info        = str_ireplace( $installed_dir, '', $file );
		$path_info        = pathinfo( $path_info );
		$converted_dir    = str_replace( '/', '\\', $path_info['dirname'] );
		$converted_dir    = ucwords( $converted_dir, '_\\' );
		$filename_search  = [ static::$filename_prefix, '-' ];
		$filename_replace = [ '', '_' ];
		$class            = str_ireplace( $filename_search, $filename_replace, $path_info['filename'] );
		$class_name       = $namespace . $converted_dir . '\\' . ucwords( $class, '_' );

		return $class_name;
	}

	/**
	 * Used to get the instance of the class as an unforced singleton model
	 *
	 * @return bool|Abstract_Theme|mixed $instance
	 */
	public static function get() {
		global $wp_themes;
		$theme_name = strtolower( get_called_class() );
		if ( isset( $wp_themes ) && isset( $wp_themes->$theme_name ) ) {
			return $wp_themes->$theme_name;
		} else {
			return false;
		}
	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param  string $class Raw class name.
	 *
	 * @return string
	 */
	private function get_file_name_from_class( $class ) {
		if ( 'classmap' === static::$autoload_type ) {
			$filtered_class_name = explode( '\\', $class );
			$class_filename      = end( $filtered_class_name );
			$class_filename      = str_replace( '_', '-', $class_filename );

			return static::$filename_prefix . $class_filename . '.php';
		} else {

			return $this->psr4_get_file_name_from_class( $class );
		}
	}

	/**
	 * Initialize the theme - for public (front end)
	 * Example of building a module of the theme into init
	 * ```$this->modules->FS_Mail = new FS_Mail( $this, $this->theme_object_basedir );```
	 *
	 * @since   0.1
	 * @return  void
	 */
	abstract public function init();

	/**
	 * Initialize the theme - for public (front end)
	 *
	 * @param mixed $instance Parent instance passed through to child.
	 *
	 * @since   0.1
	 * @return  void
	 */
	abstract public function onload( $instance );

	/**
	 * Include a class file.
	 *
	 * @param  string $path Server path to file for inclusion.
	 *
	 * @return bool successful or not
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once $path;
			$success = true;
		}

		return ! ( empty( $success ) ) ? true : false;
	}

	/**
	 * Take a namespaced class name and turn it into a file name.
	 *
	 * @param  string $class The name of the class to load.
	 *
	 * @return string
	 */
	private function psr4_get_file_name_from_class( $class ) {
		$class = strtolower( $class );
		if ( stristr( $class, '\\' ) ) {

			// If the first item is == the collection name, trim it off.
			$class = str_ireplace( static::$autoload_class_prefix, '', $class );

			// Maybe fix formatting underscores to dashes and double to single slashes.
			$class     = str_replace( [ '_', '\\' ], [ '-', '/' ], $class );
			$class     = explode( '/', $class );
			$file_name = &$class[ count( $class ) - 1 ];
			$file_name = static::$filename_prefix . $file_name . '.php';
			$file_path = join( DIRECTORY_SEPARATOR, $class );

			return $file_path;
		} else {
			return static::$filename_prefix . str_replace( '_', '-', $class ) . '.php';
		}
	}

	/**
	 * Setup special hooks that don't run after themes_loaded action
	 *
	 * @param string $file File location on the server, passed in and used to build other paths.
	 */
	public static function run( $file ) {
		// Logic required for WordPress VIP themes to load during themes function file initialization.
		if ( did_action( 'themes_loaded' ) ) {
			add_action( 'init', [ get_called_class(), 'load' ], 1 );
		} else {
			add_action( 'themes_loaded', [ get_called_class(), 'load' ] );
		}
		// Installation and un-installation hooks.
		after_switch_theme( $file, [ get_called_class(), 'activate' ] );
		switch_theme( $file, [ get_called_class(), 'deactivate' ] );
	}

	/**
	 * Build and initialize the theme - on themes_loaded
	 */
	public static function load() {
		self::set();
	}

	/**
	 * Used to setup the instance of the class and place in wp_themes collection.
	 *
	 * @param bool|Abstract_Theme|mixed $instance Contains object representing the theme.
	 */
	private static function set( $instance = false ) {
		// Make sure the theme hasn't already been instantiated before.
		global $wp_themes;
		if ( ! isset( $wp_themes ) ) {
			$wp_themes = new \stdClass();
		}
		// Get the fully qualified parent class name and instantiate an instance of it.
		$called_class = get_called_class();
		$theme_name  = strtolower( $called_class );
		if ( empty( $instance ) || ! is_a( $instance, $called_class ) ) {
			$wp_themes->$theme_name = new $called_class();
		} else {
			$wp_themes->$theme_name = $instance;
		}
	}

} // END class
