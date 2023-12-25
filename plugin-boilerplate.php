<?php

/**
 * Plugin Name:       Your New Name
 * Plugin URI:       http://example.com/your-new-name-uri/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Version:           0.1.0
 * Author:       Your Name or Your Company
 * Author URI:       http://example.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       plugin-boilerplate
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('PB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PB_BUILD_DIR', plugin_dir_url(__FILE__) . 'build/');
define('PB_TEMP_DIR', WP_CONTENT_DIR . '/pb-temp/');
define('PB_TEMP_URL', content_url() . '/pb-temp/');

class PluginBoilerplate
{

    private $github_download_url;
    private $github_dir_path;

    public function __construct()
    {
        $this->github_download_url = "https://github.com/DevinVinson/WordPress-Plugin-Boilerplate/archive/refs/heads/master.zip";
        $this->github_dir_path = "WordPress-Plugin-Boilerplate-master/";

        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));

        add_action('admin_menu', array($this, 'init_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('rest_api_init', array($this, 'register_rest_route'));
    }

    /**
     * Activation hook callback.
     */
    public function activate_plugin()
    {
        // Create the pb-temp directory if it doesn't exist on plugin activation
        if (!file_exists(PB_TEMP_DIR)) {
            wp_mkdir_p(PB_TEMP_DIR);
        }
    }

    /**
     * Admin menu page.
     */
    public function init_menu()
    {
        add_submenu_page('tools.php', __('Plugin Boilerplate', 'plugin-boilerplate'), __('Plugin Boilerplate', 'plugin-boilerplate'), 'manage_options', 'plugin-boilerplate', array($this, 'admin_page'));
    }


    /**
     * Plugin settings page.
     */
    public function admin_page()
    {
        require_once PB_PLUGIN_PATH . 'templates/app.php';
    }

    /**
     * Enqueue scripts and styles in admin screen.
     */
    public function admin_enqueue_scripts()
    {
        if (isset($_GET['page']) && $_GET['page'] === 'plugin-boilerplate') {
            wp_enqueue_style('pb-style', PB_BUILD_DIR . 'index.css');
            wp_enqueue_script('pb-script', PB_BUILD_DIR . 'index.js', array('wp-element'), '1.0.0', true);
        }
    }

    /**
     * plugin download and install route
     */
    public function register_rest_route()
    {
        register_rest_route('pb/v1', '/create', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_plugin_Callback'),
        ));
        register_rest_route('pb/v1', '/plugins', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_plugins_Callback'),
        ));
    }

    public function create_plugin_Callback($request)
    {
        // form data
        $plugin_data = json_decode($request->get_body(), true);
        $slug = $plugin_data['slug'];
        $name = $plugin_data['name'];
        $url = $plugin_data['url'];
        $authorName = $plugin_data['authorName'];
        $authorUrl = $plugin_data['authorUrl'];
        $install = $plugin_data['install'];
        $description = $plugin_data['description'];

        $temp_zip_path = PB_TEMP_DIR . 'pb-temp.zip';

        file_put_contents($temp_zip_path, file_get_contents($this->github_download_url));

        // Unzip in wp-content/pb-temp
        $zip = new ZipArchive;
        $zip->open($temp_zip_path);
        $zip->extractTo(PB_TEMP_DIR);
        $zip->close();

        // Remove the zip file
        unlink($temp_zip_path);

        // Navigate to WordPress-Plugin-Boilerplate-master folder
        $source_folder = PB_TEMP_DIR . $this->github_dir_path;

        // Rename the existing plugin-name folder to a different name
        $old_plugin_name = 'plugin-name';

        $old_plugin_name_folder = $source_folder . $old_plugin_name;
        $new_plugin_name_folder = $source_folder . $slug;

        rename($old_plugin_name_folder, $new_plugin_name_folder);

        // Get all the files and rename instances of "plugin-name" to "your-new-name"
        $files = $this->getFilesInDir($new_plugin_name_folder);

        foreach ($files as $file) {
            // Get the new file name by replacing "plugin-name" with "your-new-name"
            $newFileName = str_replace($old_plugin_name, $slug, $file);

            // Rename the file
            rename($file, $newFileName);

            // Read the content of the file
            $content = file_get_contents($newFileName);

            //replacement strings
            $underscoresSlug = str_replace('-', '_', $slug); // plugin_name
            $underscoresName = str_replace(' ', '_', $name); // Plugin_Name
            $upperUnderscoresName = strtoupper($underscoresName) . '_'; // PLUGIN_NAME_

            $updated_string = [
                'plugin_name' => $underscoresSlug,
                'plugin-name' => $slug,
                'Plugin_Name' => $underscoresName,
                'PLUGIN_NAME_' => $upperUnderscoresName,
            ];

            // Replace strings in the content
            $content = str_replace(array_keys($updated_string), array_values($updated_string), $content);

            // Write the modified content back to the file
            file_put_contents($newFileName, $content);
        }

        $main = $new_plugin_name_folder . '/' . $slug . '.php';

        // Update the plugin header
        $this->update_plugin_header(
            $name,
            $url,
            $description,
            $authorName,
            $authorUrl,
            $main
        );

        // Zip the folder
        $zip = new ZipArchive;
        $zip->open($new_plugin_name_folder . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($new_plugin_name_folder),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($new_plugin_name_folder) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        $destinationFile = PB_TEMP_DIR . $slug . '.zip';

        // Move the file .zip to the pb-temp directory
        rename($new_plugin_name_folder . '.zip', $destinationFile);

        // Remove the temp folder
        $this->removeDirectory($source_folder);

        // Return the download link
        $plugin_zip_link = PB_TEMP_URL . $slug . '.zip';
        $plugin_zip_path = PB_TEMP_DIR . $slug . '.zip';

        // Install the plugin
        if ($install) {
            $this->install_plugin_from_zip($plugin_zip_path, $slug);
        }

        return wp_send_json_success($plugin_zip_link);
    }

    public function get_plugins_Callback()
    {
        $plugin_files = $this->getZipFilesInDir(PB_TEMP_DIR);
        return wp_send_json_success($plugin_files);
    }

    // get all files in a directory
    public function getFilesInDir($dir)
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    // get all zip files in a directory
    public function getZipFilesInDir($dir)
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'zip') {
                $files[] = [
                    "name" => $file->getFilename(),
                    "link" => PB_TEMP_URL . $file->getFilename(),
                ];
            }
        }

        return $files;
    }

    public function update_plugin_header($name, $url, $description, $authorName, $authorUrl, $file)
    {
        // Get the plugin file content
        $main_plugin_content = file_get_contents($file);

        // Define the header fields to update
        $header_fields = array(
            'Plugin Name' => $name,
            'Plugin URI' => $url,
            'Description' => $description,
            'Author' => $authorName,
            'Author URI' => $authorUrl,
        );

        foreach ($header_fields as $field => $value) {
            $pattern = "/$field:(.*)/i";
            $replacement = "$field:       $value";
            $main_plugin_content = preg_replace($pattern, $replacement, $main_plugin_content);
        }

        // Write the updated content back to the main plugin file
        file_put_contents($file, $main_plugin_content);
    }

    // remove a directory and its contents
    public function removeDirectory($path)
    {
        if (is_dir($path)) {
            $objects = scandir($path);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($path . DIRECTORY_SEPARATOR . $object)) {
                        $this->removeDirectory($path . DIRECTORY_SEPARATOR . $object);
                    } else {
                        unlink($path . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }
            rmdir($path);
        }
    }

    private function install_plugin_from_zip($plugin_zip_path, $slug)
    {
        // Specify the target directory for extraction (main plugins directory + subfolder)
        $target_directory = WP_PLUGIN_DIR . '/' . $slug;

        // Ensure the subfolder exists
        if (!file_exists($target_directory)) {
            wp_mkdir_p($target_directory);
        }

        // Open the ZIP file
        $zip = new ZipArchive;
        if ($zip->open($plugin_zip_path) === TRUE) {
            $zip->extractTo($target_directory);
            $zip->close();
            return true;
        } else {
            return false;
        }
    }
}

$plugin_boilerplate = new PluginBoilerplate();
