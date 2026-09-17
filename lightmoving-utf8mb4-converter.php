<?php
/**
 * Plugin Name: LightMoving UTF8MB4 Converter
 * Plugin URI: https://github.com/LightMoving/lightmoving-utf8mb4-converter
 * Description: Safely scan and convert WordPress database tables to utf8mb4 for 4-byte character support, including emojis and expanded Unicode characters.
 * Version: 1.0.28
 * Author: Debo Grim
 * Author URI: https://github.com/lightmoving
 * License: GPLv2 or later
 * Text Domain: lightmoving-utf8mb4-converter
 */

if (!defined('ABSPATH')) {
    exit;
}

class LightMoving_UTF8MB4_Converter {
    const VERSION = '1.0.28';
    const TARGET_CHARSET = 'utf8mb4';
    const TARGET_COLLATION = 'utf8mb4_unicode_ci';
    const NONCE_ACTION = 'lightmoving_utf8mb4_converter_action';
    const PAGE_SLUG = 'lightmoving-utf8mb4-converter';

    private $messages = array();
    private $errors = array();
    private $conversion_log = array();
    private $conversion_completed = false;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_tools_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function add_tools_page() {
        add_management_page(
            __('LightMoving UTF8MB4 Converter', 'lightmoving-utf8mb4-converter'),
            __('LightMoving UTF8MB4 Converter', 'lightmoving-utf8mb4-converter'),
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'render_page')
        );
    }

    public function enqueue_admin_assets($hook) {
        if ('tools_page_' . self::PAGE_SLUG !== $hook) {
            return;
        }

        wp_register_style('lightmoving-utf8mb4-converter-admin', false, array(), self::VERSION);
        wp_enqueue_style('lightmoving-utf8mb4-converter-admin');

        $css = '
            .lightmoving-utf8mb4-converter-wrap { max-width: 1180px; }
            .lightmoving-utf8mb4-converter-hero { background: linear-gradient(135deg, #112442 0%, #204e7a 55%, #216fae 100%); color: #fff; padding: 26px 30px; border-radius: 16px; margin: 18px 0 20px; box-shadow: 0 14px 34px rgba(17, 36, 66, 0.18); }
            .lightmoving-utf8mb4-converter-hero h1 { color: #fff; margin: 0 0 8px; font-size: 28px; line-height: 1.2; }
            .lightmoving-utf8mb4-converter-hero p { max-width: 850px; color: rgba(255,255,255,0.88); margin: 0; font-size: 15px; }
            .utf8mb4-grid { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(300px, 0.8fr); gap: 22px; align-items: start; }
            .utf8mb4-card { background: #fff; border: 1px solid #dcdcde; border-radius: 14px; box-shadow: 0 7px 24px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 18px; }
            .utf8mb4-card-header { padding: 18px 20px; border-bottom: 1px solid #eef0f2; background: #f7f9fc; }
            .utf8mb4-card-header h2 { margin: 0 0 6px; font-size: 18px; }
            .utf8mb4-card-header p { margin: 0; color: #646970; }
            .utf8mb4-card-body { padding: 20px; }
            .utf8mb4-status-good { color: #008a20; font-weight: 600; }
            .utf8mb4-status-warn { color: #b26200; font-weight: 600; }
            .utf8mb4-status-bad { color: #b32d2e; font-weight: 600; }
            .utf8mb4-table-wrap { overflow-x: auto; }
            .utf8mb4-table td, .utf8mb4-table th { vertical-align: middle; }
            .utf8mb4-code { background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 8px; padding: 12px; white-space: pre-wrap; font-family: Consolas, Monaco, monospace; font-size: 12px; }
            .utf8mb4-warning-box { background: #fff8e5; border: 1px solid #dba617; border-left: 5px solid #dba617; padding: 14px 16px; border-radius: 10px; margin-bottom: 16px; }
            .utf8mb4-success-box { background: #edfaef; border: 1px solid #46b450; border-left: 5px solid #46b450; padding: 14px 16px; border-radius: 10px; margin-bottom: 16px; }
            .utf8mb4-error-box { background: #fcf0f1; border: 1px solid #d63638; border-left: 5px solid #d63638; padding: 14px 16px; border-radius: 10px; margin-bottom: 16px; }
            .utf8mb4-confirm-row { background: #f6f7f7; padding: 16px; border-radius: 10px; margin: 14px 0; }


            .utf8mb4-success-hero-banner {
                display: flex;
                align-items: center;
                gap: 16px;
                width: 100%;
                box-sizing: border-box;
                background: linear-gradient(135deg, #0fb83f 0%, #05a63c 55%, #059c36 100%);
                border: 1px solid rgba(255,255,255,0.38);
                border-radius: 10px;
                padding: 18px 20px;
                margin: 16px 0 18px;
                color: #fff;
                box-shadow: inset 0 1px 0 rgba(255,255,255,0.18), 0 8px 20px rgba(0,0,0,0.12);
            }
            .utf8mb4-success-hero-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 42px;
                width: 42px;
                height: 42px;
                border-radius: 999px;
                background: rgba(255,255,255,0.95);
                color: #0fa83d;
                font-size: 28px;
                font-weight: 800;
                line-height: 1;
            }
            .utf8mb4-success-hero-banner h2 {
                color: #fff;
                font-size: 22px;
                line-height: 1.25;
                margin: 0 0 4px;
            }
            .utf8mb4-success-hero-banner p {
                color: rgba(255,255,255,0.92);
                margin: 0;
                font-size: 14px;
            }

            .utf8mb4-conversion-summary {
                background: #edfaef;
                border: 1px solid #46b450;
                border-radius: 12px;
                padding: 18px 20px;
                margin: 18px 0;
                box-shadow: 0 8px 24px rgba(0,0,0,0.04);
            }
            .utf8mb4-conversion-summary h2 {
                margin: 0 0 8px;
                font-size: 18px;
                color: #1d2327;
            }
            .utf8mb4-conversion-summary p {
                margin: 0 0 12px;
            }
            .utf8mb4-conversion-log {
                background: #fff;
                border: 1px solid #46b450;
                border-radius: 10px;
                margin-top: 12px;
                max-height: 260px;
                overflow: auto;
                padding: 12px 14px;
            }
            .utf8mb4-conversion-log ul {
                margin: 0;
                padding-left: 0;
                list-style: none;
            }
            .utf8mb4-conversion-log li {
                position: relative;
                margin: 4px 0;
                padding-left: 28px;
                font-family: Consolas, Monaco, monospace;
                font-size: 12px;
            }
            .utf8mb4-conversion-log li:before {
                content: "✓";
                position: absolute;
                left: 0;
                top: -1px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: #0fa83d;
                color: #fff;
                font-family: Arial, sans-serif;
                font-size: 12px;
                font-weight: 700;
            }

            @media (max-width: 960px) { .utf8mb4-grid { grid-template-columns: 1fr; } }
        ';

        wp_add_inline_style('lightmoving-utf8mb4-converter-admin', $css);
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lightmoving-utf8mb4-converter'));
        }

        $this->handle_post_actions();

        $db_info = $this->get_database_info();
        $tables = $this->get_wordpress_tables();
        $supports_utf8mb4 = $this->supports_utf8mb4();
        $needs_conversion = $this->get_tables_needing_conversion($tables);
        ?>
        <div class="wrap lightmoving-utf8mb4-converter-wrap">
            <div class="lightmoving-utf8mb4-converter-hero">
                <h1><?php echo esc_html__('LightMoving UTF8MB4 Converter', 'lightmoving-utf8mb4-converter'); ?></h1>
                <?php if ($this->conversion_completed) : ?>
                    <div class="utf8mb4-success-hero-banner">
                        <span class="utf8mb4-success-hero-icon" aria-hidden="true">✓</span>
                        <div>
                            <h2><?php echo esc_html__('Conversion Completed Successfully!', 'lightmoving-utf8mb4-converter'); ?></h2>
                            <p><?php echo esc_html__('Your database and tables are now using utf8mb4 and are ready to support 4-byte characters, including emojis.', 'lightmoving-utf8mb4-converter'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                <p><?php echo esc_html__('Scan and convert WordPress database tables to utf8mb4 so your site can safely support 4-byte Unicode characters, including emojis and expanded multilingual characters.', 'lightmoving-utf8mb4-converter'); ?></p>
            </div>

            <?php $this->render_notices(); ?>

            <div class="utf8mb4-grid">
                <div>
                    <div class="utf8mb4-card">
                        <div class="utf8mb4-card-header">
                            <h2><?php echo esc_html__('Database Status', 'lightmoving-utf8mb4-converter'); ?></h2>
                            <p><?php echo esc_html__('Review your current database charset, collation, and WordPress table status before converting.', 'lightmoving-utf8mb4-converter'); ?></p>
                        </div>
                        <div class="utf8mb4-card-body">
                            <table class="widefat striped"><tbody>
                                <tr><th><?php echo esc_html__('Database', 'lightmoving-utf8mb4-converter'); ?></th><td><?php echo esc_html($this->get_database_name()); ?></td></tr>
                                <tr><th><?php echo esc_html__('Current Database Charset', 'lightmoving-utf8mb4-converter'); ?></th><td><?php echo esc_html($db_info['charset']); ?></td></tr>
                                <tr><th><?php echo esc_html__('Current Database Collation', 'lightmoving-utf8mb4-converter'); ?></th><td><?php echo esc_html($db_info['collation']); ?></td></tr>
                                <tr><th><?php echo esc_html__('Target Charset / Collation', 'lightmoving-utf8mb4-converter'); ?></th><td><code><?php echo esc_html(self::TARGET_CHARSET . ' / ' . self::TARGET_COLLATION); ?></code></td></tr>
                                <tr><th><?php echo esc_html__('Server utf8mb4 Support', 'lightmoving-utf8mb4-converter'); ?></th><td><?php echo $supports_utf8mb4 ? '<span class="utf8mb4-status-good">' . esc_html__('Supported', 'lightmoving-utf8mb4-converter') . '</span>' : '<span class="utf8mb4-status-bad">' . esc_html__('Not detected', 'lightmoving-utf8mb4-converter') . '</span>'; ?></td></tr>
                                <tr><th><?php echo esc_html__('Tables Needing Conversion', 'lightmoving-utf8mb4-converter'); ?></th><td><?php echo empty($needs_conversion) ? '<span class="utf8mb4-status-good">' . esc_html__('None detected', 'lightmoving-utf8mb4-converter') . '</span>' : '<span class="utf8mb4-status-warn">' . esc_html(count($needs_conversion)) . '</span>'; ?></td></tr>
                            </tbody></table>
                        </div>
                    </div>

                    <div class="utf8mb4-card">
                        <div class="utf8mb4-card-header"><h2><?php echo esc_html__('WordPress Tables', 'lightmoving-utf8mb4-converter'); ?></h2><p><?php echo esc_html__('This tool scans tables using your current WordPress table prefix.', 'lightmoving-utf8mb4-converter'); ?></p></div>
                        <div class="utf8mb4-card-body">
                            <?php if (empty($tables)) : ?>
                                <div class="utf8mb4-error-box"><?php echo esc_html__('No WordPress tables were found for the current table prefix.', 'lightmoving-utf8mb4-converter'); ?></div>
                            <?php else : ?>
                                <div class="utf8mb4-table-wrap"><table class="widefat striped utf8mb4-table"><thead><tr><th><?php echo esc_html__('Table', 'lightmoving-utf8mb4-converter'); ?></th><th><?php echo esc_html__('Engine', 'lightmoving-utf8mb4-converter'); ?></th><th><?php echo esc_html__('Collation', 'lightmoving-utf8mb4-converter'); ?></th><th><?php echo esc_html__('Status', 'lightmoving-utf8mb4-converter'); ?></th></tr></thead><tbody>
                                <?php foreach ($tables as $table) : ?>
                                    <tr>
                                        <td><code><?php echo esc_html($table['name']); ?></code></td>
                                        <td><?php echo esc_html($table['engine']); ?></td>
                                        <td><?php echo esc_html($table['collation']); ?></td>
                                        <td><?php echo $this->table_is_utf8mb4($table) ? '<span class="utf8mb4-status-good">' . esc_html__('utf8mb4 ready', 'lightmoving-utf8mb4-converter') . '</span>' : '<span class="utf8mb4-status-warn">' . esc_html__('Needs conversion', 'lightmoving-utf8mb4-converter') . '</span>'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody></table></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="utf8mb4-card">
                        <div class="utf8mb4-card-header"><h2><?php echo esc_html__('Convert Database', 'lightmoving-utf8mb4-converter'); ?></h2><p><?php echo esc_html__('Run this only after making a complete database backup.', 'lightmoving-utf8mb4-converter'); ?></p></div>
                        <div class="utf8mb4-card-body">
                            <div class="utf8mb4-warning-box"><strong><?php echo esc_html__('Backup required.', 'lightmoving-utf8mb4-converter'); ?></strong><?php echo esc_html__(' Converting database tables can lock tables temporarily and may take time on large sites. Make a full database backup before continuing.', 'lightmoving-utf8mb4-converter'); ?></div>
                            <?php if (!$supports_utf8mb4) : ?>
                                <div class="utf8mb4-error-box"><?php echo esc_html__('This server does not appear to support utf8mb4 through WordPress database capabilities. Conversion is disabled.', 'lightmoving-utf8mb4-converter'); ?></div>
                            <?php elseif (empty($needs_conversion) && $db_info['charset'] === self::TARGET_CHARSET) : ?>
                                <div class="utf8mb4-success-box"><?php echo esc_html__('Your WordPress tables and database default already appear to be utf8mb4 ready.', 'lightmoving-utf8mb4-converter'); ?></div>
                            <?php else : ?>
                                <form method="post">
                                    <?php wp_nonce_field(self::NONCE_ACTION, 'utf8mb4_converter_nonce'); ?>
                                    <input type="hidden" name="utf8mb4_converter_action" value="convert">
                                    <div class="utf8mb4-confirm-row">
                                        <p><label><input type="checkbox" name="utf8mb4_confirm_backup" value="1" required> <?php echo esc_html__('I confirm that I have created a complete database backup.', 'lightmoving-utf8mb4-converter'); ?></label></p>
                                        <p><label for="utf8mb4_confirm_text"><?php echo esc_html__('Type CONVERT to continue:', 'lightmoving-utf8mb4-converter'); ?></label><br><input type="text" id="utf8mb4_confirm_text" name="utf8mb4_confirm_text" class="regular-text" autocomplete="off" required></p>
                                    </div>
                                    <p><button type="submit" class="button button-primary button-hero"><?php echo esc_html__('Convert Database and WordPress Tables to utf8mb4', 'lightmoving-utf8mb4-converter'); ?></button></p>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <aside>
                    <div class="utf8mb4-card"><div class="utf8mb4-card-header"><h2><?php echo esc_html__('What This Does', 'lightmoving-utf8mb4-converter'); ?></h2></div><div class="utf8mb4-card-body"><p><?php echo esc_html__('This tool updates the database default and converts each WordPress table using your current table prefix.', 'lightmoving-utf8mb4-converter'); ?></p><div class="utf8mb4-code">ALTER DATABASE database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE wp_posts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</div></div></div>
                    <div class="utf8mb4-card"><div class="utf8mb4-card-header"><h2><?php echo esc_html__('Safety Notes', 'lightmoving-utf8mb4-converter'); ?></h2></div><div class="utf8mb4-card-body"><ul><li><?php echo esc_html__('No conversion runs automatically on activation.', 'lightmoving-utf8mb4-converter'); ?></li><li><?php echo esc_html__('Only administrators can access this tool.', 'lightmoving-utf8mb4-converter'); ?></li><li><?php echo esc_html__('Only tables using the current WordPress prefix are converted.', 'lightmoving-utf8mb4-converter'); ?></li><li><?php echo esc_html__('A manual confirmation is required before conversion.', 'lightmoving-utf8mb4-converter'); ?></li><li><?php echo esc_html__('Large tables may take longer depending on hosting resources.', 'lightmoving-utf8mb4-converter'); ?></li></ul></div></div>
                </aside>
            </div>
        </div>
        <?php
    }

    private function handle_post_actions() {
        if (empty($_POST['utf8mb4_converter_action'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            $this->errors[] = __('You do not have permission to perform this action.', 'lightmoving-utf8mb4-converter');
            return;
        }

        if (empty($_POST['utf8mb4_converter_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['utf8mb4_converter_nonce'])), self::NONCE_ACTION)) {
            $this->errors[] = __('Security check failed. Please try again.', 'lightmoving-utf8mb4-converter');
            return;
        }

        $action = sanitize_key(wp_unslash($_POST['utf8mb4_converter_action']));
        if ('convert' !== $action) {
            return;
        }

        $confirmed_backup = !empty($_POST['utf8mb4_confirm_backup']);
        $confirm_text = isset($_POST['utf8mb4_confirm_text']) ? sanitize_text_field(wp_unslash($_POST['utf8mb4_confirm_text'])) : '';

        if (!$confirmed_backup || 'CONVERT' !== $confirm_text) {
            $this->errors[] = __('Conversion was not started. Please confirm your backup and type CONVERT exactly.', 'lightmoving-utf8mb4-converter');
            return;
        }

        if (!$this->supports_utf8mb4()) {
            $this->errors[] = __('This server does not appear to support utf8mb4 through WordPress database capabilities.', 'lightmoving-utf8mb4-converter');
            return;
        }

        $this->run_conversion();
    }

    private function run_conversion() {
        global $wpdb;

        $database_name = $this->get_database_name();
        $tables = $this->get_wordpress_tables();

        if (empty($tables)) {
            $this->errors[] = __('No WordPress tables were found for the current table prefix.', 'lightmoving-utf8mb4-converter');
            return;
        }

        $database_sql = 'ALTER DATABASE ' . $this->quote_identifier($database_name) . ' CHARACTER SET ' . self::TARGET_CHARSET . ' COLLATE ' . self::TARGET_COLLATION;
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Intentional admin-confirmed ALTER DATABASE operation using safely quoted database identifier and fixed charset/collation constants.
        $database_result = $wpdb->query($database_sql);
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared

        if (false === $database_result) {
            $this->errors[] = sprintf(
                /* translators: %s: database error message. */
                __('Database default conversion failed: %s', 'lightmoving-utf8mb4-converter'),
                $wpdb->last_error
            );
            return;
        }

        $this->conversion_log[] = __('Database default charset/collation updated successfully.', 'lightmoving-utf8mb4-converter');

        foreach ($tables as $table) {
            if ($this->table_is_utf8mb4($table)) {
                continue;
            }

            $table_sql = 'ALTER TABLE ' . $this->quote_identifier($table['name']) . ' CONVERT TO CHARACTER SET ' . self::TARGET_CHARSET . ' COLLATE ' . self::TARGET_COLLATION;
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Intentional admin-confirmed ALTER TABLE operation using safely quoted table identifier and fixed charset/collation constants.
            $result = $wpdb->query($table_sql);
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared

            if (false === $result) {
                $this->errors[] = sprintf(
                    /* translators: 1: database table name, 2: database error message. */
                    __('Table %1$s failed: %2$s', 'lightmoving-utf8mb4-converter'),
                    $table['name'],
                    $wpdb->last_error
                );
                continue;
            }

            $this->messages[] = sprintf(
                /* translators: %s: converted database table name. */
                __('Converted table: %s', 'lightmoving-utf8mb4-converter'),
                $table['name']
            );
        }

        if (empty($this->errors)) {
            $this->conversion_completed = true;
            /* Conversion summary is rendered from the conversion log. */
        }
    }

    private function render_notices() {
        foreach ($this->errors as $error) {
            echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
        }

        if (!empty($this->conversion_log)) {
            echo '<div class="utf8mb4-conversion-summary">';
            echo '<h2>' . esc_html__('Conversion Log', 'lightmoving-utf8mb4-converter') . '</h2>';
            echo '<p>' . esc_html__('The database conversion completed and the following actions were recorded:', 'lightmoving-utf8mb4-converter') . '</p>';
            echo '<div class="utf8mb4-conversion-log"><ul>';

            foreach ($this->conversion_log as $log_entry) {
                echo '<li>' . esc_html($log_entry) . '</li>';
            }

            echo '</ul></div>';
            echo '</div>';
            return;
        }

        foreach ($this->messages as $message) {
            echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
        }
    }

    private function supports_utf8mb4() {
        global $wpdb;
        return method_exists($wpdb, 'has_cap') ? (bool) $wpdb->has_cap('utf8mb4') : false;
    }

    private function get_database_name() {
        if (defined('DB_NAME') && DB_NAME) {
            return DB_NAME;
        }
        global $wpdb;
        return isset($wpdb->dbname) ? $wpdb->dbname : '';
    }

    private function get_database_info() {
        global $wpdb;
        $default = array('charset' => __('Unknown', 'lightmoving-utf8mb4-converter'), 'collation' => __('Unknown', 'lightmoving-utf8mb4-converter'));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional database metadata read for charset/collation status.
        $row = $wpdb->get_row($wpdb->prepare("SELECT DEFAULT_CHARACTER_SET_NAME AS charset, DEFAULT_COLLATION_NAME AS collation FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = %s LIMIT 1", $this->get_database_name()), ARRAY_A);
        if (!$row) {
            return $default;
        }
        return array('charset' => isset($row['charset']) ? $row['charset'] : $default['charset'], 'collation' => isset($row['collation']) ? $row['collation'] : $default['collation']);
    }

    private function get_wordpress_tables() {
        global $wpdb;
        $like = $wpdb->esc_like($wpdb->prefix) . '%';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional database metadata read for WordPress table collation status.
        $rows = $wpdb->get_results($wpdb->prepare('SHOW TABLE STATUS LIKE %s', $like), ARRAY_A);
        if (empty($rows)) {
            return array();
        }
        $tables = array();
        foreach ($rows as $row) {
            if (empty($row['Name'])) {
                continue;
            }
            $tables[] = array('name' => $row['Name'], 'engine' => isset($row['Engine']) ? $row['Engine'] : '', 'collation' => isset($row['Collation']) ? $row['Collation'] : '');
        }
        return $tables;
    }

    private function get_tables_needing_conversion($tables) {
        $needs_conversion = array();
        foreach ($tables as $table) {
            if (!$this->table_is_utf8mb4($table)) {
                $needs_conversion[] = $table;
            }
        }
        return $needs_conversion;
    }

    private function table_is_utf8mb4($table) {
        return !empty($table['collation']) && 0 === strpos(strtolower($table['collation']), 'utf8mb4_');
    }

    private function quote_identifier($identifier) {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}


add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'lightmoving_utf8mb4_converter_action_links');

/**
 * Add a direct Tools link on the Plugins page.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function lightmoving_utf8mb4_converter_action_links($links) {
    $tools_link = '<a href="' . esc_url(admin_url('tools.php?page=lightmoving-utf8mb4-converter')) . '">' . esc_html__('Tools', 'lightmoving-utf8mb4-converter') . '</a>';

    array_unshift($links, $tools_link);

    return $links;
}


new LightMoving_UTF8MB4_Converter();
