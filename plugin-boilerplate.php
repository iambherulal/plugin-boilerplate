<?php

/**
 * Plugin Name:       Plugin Boilerplate Generator
 * Plugin URI:       https://iambherulal.github.io
 * Description:       Generate and install your customized WordPress Plugin Boilerplate effortlessly with this advanced tool. Streamline your development process and get started with plugin creation in no time!
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Version:           1.0.0
 * Author:           Bheru Lal Gameti
 * Author URI:       https://iambherulal.github.io
 * License:          GPL-2.0-or-later
 * License URI:      https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:      plugin-boilerplate
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'PB_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'PB_BUILD_DIR', plugin_dir_url( __FILE__ ) . 'build/' );
define( 'PB_TEMP_DIR', WP_CONTENT_DIR . '/pb-temp/' );
define( 'PB_TEMP_URL', content_url() . '/pb-temp/' );
define( 'PB_VERSION', '1.0.0' );

class PluginBoilerplate {



    private $github_download_url;
    private $github_dir_path;

    public function __construct() {
        $this->github_download_url = 'https://github.com/DevinVinson/WordPress-Plugin-Boilerplate/archive/refs/heads/master.zip';
        $this->github_dir_path = 'WordPress-Plugin-Boilerplate-master/';

        // Register activation hook
        register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );

        add_action( 'admin_menu', array( $this, 'init_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );

        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'settings_link' ) );
    }

    /**
     * Activation hook callback.
     */
    public function activate_plugin() {
        // Create the pb-temp directory if it doesn't exist on plugin activation
        if ( ! file_exists( PB_TEMP_DIR ) ) {
            wp_mkdir_p( PB_TEMP_DIR );
        }
    }

    // Add settings link to plugin page
    public function settings_link( $links ) {
        $settings_link = '<a href="tools.php?page=plugin-boilerplate">Settings</a>';
        array_push( $links, $settings_link );
        return $links;
    }

    /**
     * Admin menu page.
     */
    public function init_menu() {
        add_submenu_page( 'tools.php', __( 'Plugin Boilerplate', 'plugin-boilerplate' ), __( 'Plugin Boilerplate', 'plugin-boilerplate' ), 'manage_options', 'plugin-boilerplate', array( $this, 'admin_page' ) );
    }


    /**
     * Plugin settings page.
     */
    public function admin_page() {
        require_once PB_PLUGIN_PATH . 'templates/app.php';
    }

    /**
     * Enqueue scripts and styles in admin screen.
     */
    public function admin_enqueue_scripts() {
        $screen = get_current_screen();
		if ( $screen && $screen->id === 'tools_page_plugin-boilerplate' ) {
            wp_enqueue_style( 'pb-style', PB_BUILD_DIR . 'index.css', array(), PB_VERSION, 'all' );
            wp_enqueue_script( 'pb-script', PB_BUILD_DIR . 'index.js', array( 'wp-element' ), PB_VERSION, true );
            wp_localize_script(
                'pb-script', 'pb', array(
					'nonce' => wp_create_nonce( 'wp_rest' ),
					'rest_url' => esc_url_raw( rest_url() . 'pb/v1' ),
                    'site_url' => esc_url_raw( site_url() ),
                )
            );
        }
    }

    /**
     * Plugin download and install route
     */
    public function register_rest_route() {
        register_rest_route(
            'pb/v1',
            '/create',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'create_plugin_Callback' ),
            ),
        );
        register_rest_route(
            'pb/v1',
            '/plugins',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'get_plugins_Callback' ),
            )
        );
    }

    public function create_plugin_Callback( $request ) {
        global $wp_filesystem;
        // form data
        $plugin_data = json_decode( $request->get_body(), true );

        $nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) ) : '';

		if ( isset( $nonce ) && ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return wp_send_json_error( 'Invalid Nonce', 401 );
        }

        $slug = $plugin_data['slug'];
        $name = $plugin_data['name'];
        $url = $plugin_data['url'];
        $author_name = $plugin_data['authorName'];
        $author_url = $plugin_data['authorUrl'];
        $install = $plugin_data['install'];
        $description = $plugin_data['description'];

        $temp_zip_path = PB_TEMP_DIR . 'pb-temp.zip';

                // initialize WP_Filesystem
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

        WP_Filesystem();

        // Using wp_remote_get() for remote URLs
        $response = wp_remote_get( $this->github_download_url );

        if ( is_array( $response ) && ! is_wp_error( $response ) ) {
            $body = wp_remote_retrieve_body( $response );

            // Using WP_Filesystem methods instead of file_put_contents
            $wp_filesystem->put_contents( $temp_zip_path, $body, FS_CHMOD_FILE );
        }

        // Unzip in wp-content/pb-temp
        $zip = new ZipArchive();
        $zip->open( $temp_zip_path );
        $zip->extractTo( PB_TEMP_DIR );
        $zip->close();

        // Remove the zip file
        $wp_filesystem->delete( $temp_zip_path );

        // Navigate to WordPress-Plugin-Boilerplate-master folder
        $source_folder = PB_TEMP_DIR . $this->github_dir_path;

        // Rename the existing plugin-name folder to a different name
        $old_plugin_name = 'plugin-name';

        $old_plugin_name_folder = $source_folder . $old_plugin_name;
        $new_plugin_name_folder = $source_folder . $slug;

        $wp_filesystem->move( $old_plugin_name_folder, $new_plugin_name_folder );

        // Get all the files and rename instances of "plugin-name" to "your-new-name"
        $files = $this->getFilesInDir( $new_plugin_name_folder );

        foreach ( $files as $file ) {
            // Get the new file name by replacing "plugin-name" with "your-new-name"
            $new_file_name = str_replace( $old_plugin_name, $slug, $file );

            // Rename the file
            $wp_filesystem->move( $file, $new_file_name );

            // Read the content of the file
            $content = $wp_filesystem->get_contents( $new_file_name );

            //replacement strings
            $underscores_slug = str_replace( '-', '_', $slug ); // plugin_name
            $underscores_name = str_replace( ' ', '_', $name ); // Plugin_Name
            $upper_underscores_name = strtoupper( $underscores_name ) . '_'; // PLUGIN_NAME_

            $updated_string = array(
                'plugin_name' => $underscores_slug,
                'plugin-name' => $slug,
                'Plugin_Name' => $underscores_name,
                'PLUGIN_NAME_' => $upper_underscores_name,
                'http://example.com' => $url,
            );

            // Replace strings in the content
            $content = str_replace( array_keys( $updated_string ), array_values( $updated_string ), $content );

            // Write the modified content back to the file
            $wp_filesystem->put_contents( $new_file_name, $content );
        }

        $main = $new_plugin_name_folder . '/' . $slug . '.php';

        // Update the plugin header
        $this->update_plugin_header(
            $name,
            $url,
            $description,
            $author_name,
            $author_url,
            $main,
            $wp_filesystem
        );

        // Zip the folder
        $zip = new ZipArchive();
        $zip->open( $new_plugin_name_folder . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE );
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $new_plugin_name_folder ),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ( $files as $name => $file ) {
            // Skip directories (they would be added automatically)
            if ( ! $file->isDir() ) {
                // Get real and relative path for current file
                $file_path = $file->getRealPath();
                $relative_path = substr( $file_path, strlen( $new_plugin_name_folder ) + 1 );

                // Add current file to archive
                $zip->addFile( $file_path, $relative_path );
            }
        }

        $zip->close();

        $destination_file = PB_TEMP_DIR . $slug . '.zip';

        // Move the file .zip to the pb-temp directory
        $wp_filesystem->move( $new_plugin_name_folder . '.zip', $destination_file );

        // Remove the temp folder
        $wp_filesystem->rmdir( $source_folder, true );

        // Return the download link
        $plugin_zip_link = PB_TEMP_URL . $slug . '.zip';
        $plugin_zip_path = PB_TEMP_DIR . $slug . '.zip';

        // Install the plugin
        if ( $install ) {
            $this->install_plugin_from_zip( $plugin_zip_path, $slug );
        }

        return wp_send_json_success( $plugin_zip_link );
    }

    public function get_plugins_Callback() {

        $nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) ) : '';

		if ( isset( $nonce ) && ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return wp_send_json_error( 'Invalid Nonce', 401 );
        }

        $plugin_files = $this->getZipFilesInDir( PB_TEMP_DIR );
        return wp_send_json_success( $plugin_files );
    }

    // get all files in a directory
    public function getFilesInDir( $dir ) {
        $files = array();
        $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) );

        foreach ( $iterator as $file ) {
            if ( $file->isFile() ) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    // get all zip files in a directory
    public function getZipFilesInDir( $dir ) {
        $files = array();
        $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) );

        foreach ( $iterator as $file ) {
            if ( $file->isFile() && $file->getExtension() === 'zip' ) {
                $files[] = array(
                    'name' => $file->getFilename(),
                    'link' => PB_TEMP_URL . $file->getFilename(),
                );
            }
        }

        return $files;
    }

    public function update_plugin_header( $name, $url, $description, $author_name, $author_url, $file, $wp_filesystem ) {
        // Get the plugin file content
        $main_plugin_content = $wp_filesystem->get_contents( $file );

        // Define the header fields to update
        $header_fields = array(
            'Plugin Name' => $name,
            'Plugin URI' => $url,
            'Description' => $description,
            'Author' => $author_name,
            'Author URI' => $author_url,
        );

        foreach ( $header_fields as $field => $value ) {
            $pattern = "/$field:(.*)/i";
            $replacement = "$field:       $value";
            $main_plugin_content = preg_replace( $pattern, $replacement, $main_plugin_content );
        }

        // Write the updated content back to the main plugin file
        $wp_filesystem->put_contents( $file, $main_plugin_content );
    }

    private function install_plugin_from_zip( $plugin_zip_path, $slug ) {
        // Specify the target directory for extraction (main plugins directory + subfolder)
        $target_directory = WP_PLUGIN_DIR . '/' . $slug;

        // Ensure the subfolder exists
        if ( ! file_exists( $target_directory ) ) {
            wp_mkdir_p( $target_directory );
        }

        // Open the ZIP file
        $zip = new ZipArchive();
        if ( $zip->open( $plugin_zip_path ) === true ) {
            $zip->extractTo( $target_directory );
            $zip->close();
            return true;
        } else {
            return false;
        }
    }
}

$plugin_boilerplate = new PluginBoilerplate();
