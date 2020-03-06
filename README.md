# Abtract Theme Base

Used as a base class to help standardize the way we build WordPress themes.

CircleCI Build: [![CircleCI](https://circleci.com/gh/WordPress-Phoenix/abstract-theme-base.svg?style=svg)](https://circleci.com/gh/WordPress-Phoenix/abstract-theme-base)

## Table of Contents

*   [Installation](#installation)
*   [Usage](#usage)

## Installation

You can use this library to start a new theme from scratch, or you can enhance your existing themes with this library. Once you have read over the installation instructions it should make sense which direction to go.

### Composer style (recommended)

1.  Confirm that composer is installed in your development environment using `which composer`. If CLI does not print any path, you need to [install Composer](https://getcomposer.org/download/).
2.  Set CLI working directory to wp-content/themes/{your-theme-name}
3.  Install Abstract_Theme class via composer command line like
    ```bash
    composer require wordpress-phoenix/abstract-theme-base
    ```
4.  Look at sample code below to see how to include this library in your theme.

### Manual Installation

1.  Download the most updated copy of this repository from `https://api.github.com/repos/WordPress-Phoenix/abstract-theme-base/zipball`
2.  Extract the zip file, and copy the PHP file into your theme project.
3.  Include the file in your theme.

## Usage

### Why should you use this library when building your theme?
By building your theme using OOP principals, and extending this Theme_Base class object, you will be able to quickly and efficiently build your theme, allowing it to be simple to start, but giving it the ability to grow complex without changing its architecture.

Immediate features include:

*   Built in SPL Autoload for your includes folder, should you follow WordPress codex naming standards for class files.
*   Template class provides you all the best practices for standard theme initialization
*   Minimizes code needed / maintenance of your main theme file.
*   Assists developers new to WordPress theme development in file / folder architecture.
*   By starting all your themes with the same architecture, we create a standard that is better for the dev community.

### Simplest example of the main theme functions file, and required theme class file

`custom-my-theme.php`:

```php
<?php
// Avoid direct calls to this file, because now WP core and framework has been used
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

$current_dir = trailingslashit( dirname( __FILE__ ) );

/**
 * 3RD PARTY DEPENDENCIES
 * (manually include_once dependencies installed via composer for safety)
 */
if ( ! class_exists( 'WPAZ_Theme_Base\\V_2_6\\Abstract_Plugin' ) ) {
	include_once $current_dir . 'class-abstract-theme.php';
}

// Maybe load vendor director from theme, if it exists.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	include_once( __DIR__ . '/vendor/autoload.php' );
}

/**
 * Load in the main theme application and initialize it.
 */
include_once $current_dir . 'app/class-theme.php';
Mynamespace\Custom_Theme\Theme::run( __FILE__ );
```

`app/class-theme.php`:

```php
<?php

namespace Custom\My_theme;

use WPAZ_Theme_Base\V_2_6\Abstract_Theme;

/**
 * Class App
 */
class Theme extends Abstract_Theme {

	public static $autoload_class_prefix = __NAMESPACE__;

	// Set to 2 when you use 2 namespaces in the main app file
	public static $autoload_ns_match_depth = 2;

	public function onload( $instance ) {
		// Nothing yet
	}

	public function init() {
		do_action( static::class . '_before_init' );

		// Do theme stuff usually looks something like
		// $subclass = new OptionalAppSubfolder\Custom_Class_Subclass();
		// $subclass->custom_theme_function();

		do_action( static::class . '_after_init' );
	}

	public function authenticated_init() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Ready for wp-admin - but not required
		//$this->admin = new Admin\App( $this );
	}

	protected function defines_and_globals() {
		// None yet.
	}

}
```
