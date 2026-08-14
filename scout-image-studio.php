<?php
/**
 * Plugin Name: Scout Image Studio
 * Description: AI-powered Media, URL & Metadata Management for WordPress with safe image renaming, AI filename generation, SEO guidance, URL synchronization, and undo history.
 * Version: 2.2.15
 * Author: Debo Grim
 * Requires at least: 6.0
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: scout-image-studio
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Scout_Image_Studio {
    const VERSION = '2.2.15';
    const HISTORY_OPTION = 'sins_rename_history';
    const NONCE_ACTION = 'sins_admin_action';

    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_post_sins_bulk_rename', [$this, 'handle_bulk_rename']);
        add_action('admin_post_sins_undo', [$this, 'handle_undo']);
        add_action('admin_post_sins_clear_history', [$this, 'handle_clear_history']);
        add_action('admin_post_sins_save_ai_settings', [$this, 'handle_save_ai_settings']);
        add_action('wp_ajax_sins_ai_suggest', [$this, 'handle_ai_suggest']);
        add_action('wp_ajax_sins_ai_test_connection', [$this, 'handle_ai_test_connection']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_plugin_action_links']);
    }


    public function add_plugin_action_links($links) {
        $asset_link = '<a href="' . esc_url(admin_url('upload.php?page=scout-image-studio')) . '">' . esc_html__('Image Studio', 'scout-image-studio') . '</a>';
        $seo_link = '<a href="' . esc_url(admin_url('upload.php?page=scout-image-studio-ai')) . '">' . esc_html__('AI & SEO Studio', 'scout-image-studio') . '</a>';
        array_unshift($links, $seo_link);
        array_unshift($links, $asset_link);
        return $links;
    }

    public function register_admin_page() {
        add_media_page(
            __('Scout Image Studio', 'scout-image-studio'),
            __('Scout Image Studio', 'scout-image-studio'),
            'upload_files',
            'scout-image-studio',
            [$this, 'render_admin_page']
        );
        add_media_page(
            __('Scout AI & SEO Studio', 'scout-image-studio'),
            __('AI & SEO Studio', 'scout-image-studio'),
            'upload_files',
            'scout-image-studio-ai',
            [$this, 'render_ai_page']
        );
    }

    public function enqueue_assets($hook) {
        if (!in_array($hook, ['media_page_scout-image-studio', 'media_page_scout-image-studio-ai'], true)) {
            return;
        }
        wp_enqueue_style(
            'sins-admin',
            plugin_dir_url(__FILE__) . 'assets/admin.css',
            [],
            self::VERSION
        );
        wp_enqueue_script(
            'sins-admin',
            plugin_dir_url(__FILE__) . 'assets/admin.js',
            [],
            self::VERSION,
            true
        );
        wp_localize_script('sins-admin', 'SINS_DATA', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sins_ai_suggest'),
            'aiConfigured' => $this->is_ai_configured(),
            'strings' => [
                'selectImages' => __('Select at least one image first.', 'scout-image-studio'),
                'aiWorking' => __('Scout AI is naming the selected images…', 'scout-image-studio'),
                'aiDone' => __('Scout AI has successfully created names for your selected images.', 'scout-image-studio'),
                'aiAnotherWorking' => __('Scout AI is creating another set of ideas…', 'scout-image-studio'),
                'aiAnotherDone' => __('Scout AI has successfully created another set of names for your selected images.', 'scout-image-studio'),
                'aiCleared' => __('AI suggestions have been cleared. Your images have not been renamed.', 'scout-image-studio'),
                'aiError' => __('AI naming failed.', 'scout-image-studio'),
                'testing' => __('Testing connection…', 'scout-image-studio'),
                'connected' => __('Connection successful.', 'scout-image-studio'),
                'testError' => __('Connection test failed.', 'scout-image-studio'),
                'aiError' => __('AI naming failed.', 'scout-image-studio'),
            ],
        ]);
    }

    private function require_capability() {
        if (!current_user_can('upload_files')) {
            wp_die(esc_html__('You do not have permission to rename media files.', 'scout-image-studio'));
        }
    }

    public function render_admin_page() {
        $this->require_capability();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination does not change site data.
        $paged = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only search does not change site data.
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $per_page = 50;

        $query = new WP_Query([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            's'              => $search,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $history = get_option(self::HISTORY_OPTION, []);
        if (!is_array($history)) {
            $history = [];
        }

        ?>
        <div class="wrap sins-wrap">
            <div class="sins-hero">
                <div class="sins-brand-mark"><img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/icon-256.png'); ?>" alt=""></div>
                <div>
                    <h1><?php esc_html_e('Scout Image Studio', 'scout-image-studio'); ?></h1>
                    <p class="sins-kicker"><?php esc_html_e('AI-powered Media, URL & Metadata Management for WordPress', 'scout-image-studio'); ?></p>
                </div>
            </div>
            <p class="description sins-intro">
                <?php esc_html_e('Manage WordPress assets with confidence. Scout safely renames physical files, updates generated sizes, attachment metadata, URLs, post content, and compatible references while preserving a complete undo history.', 'scout-image-studio'); ?>
            </p>
            <nav class="sins-studio-tabs" aria-label="<?php esc_attr_e('Scout Image Studio sections', 'scout-image-studio'); ?>">
                <a class="sins-studio-tab is-active" href="<?php echo esc_url(admin_url('upload.php?page=scout-image-studio')); ?>"><?php esc_html_e('Assets', 'scout-image-studio'); ?></a>
                <a class="sins-studio-tab" href="<?php echo esc_url(admin_url('upload.php?page=scout-image-studio-ai')); ?>"><?php esc_html_e('AI & SEO Studio', 'scout-image-studio'); ?></a>
            </nav>

            <?php $this->render_notice(); ?>

            <div class="sins-grid">
                <section class="sins-panel sins-panel-main">
                    <form method="get" class="sins-search-form">
                        <input type="hidden" name="page" value="scout-image-studio">
                        <label class="screen-reader-text" for="sins-search"><?php esc_html_e('Search images', 'scout-image-studio'); ?></label>
                        <input id="sins-search" type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search images by title…">
                        <button class="button"><?php esc_html_e('Search', 'scout-image-studio'); ?></button>
                    </form>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="sins-rename-form">
                        <input type="hidden" name="action" value="sins_bulk_rename">
                        <input type="hidden" name="return_paged" value="<?php echo esc_attr($paged); ?>">
                        <input type="hidden" name="return_search" value="<?php echo esc_attr($search); ?>">
                        <input type="hidden" name="return_scroll" id="sins-return-scroll" value="0">
                        <?php wp_nonce_field(self::NONCE_ACTION); ?>

                        <div class="sins-sequence-box">
                            <div class="sins-sequence-heading">
                                <div>
                                    <strong><?php esc_html_e('Sequential Bulk Naming', 'scout-image-studio'); ?></strong>
                                    <p class="description"><?php esc_html_e('Choose images, enter one shared name, and Scout prepares a numbered series in displayed order.', 'scout-image-studio'); ?></p>
                                </div>
                                <span class="sins-step-badge"><?php esc_html_e('Preview before applying', 'scout-image-studio'); ?></span>
                            </div>
                            <div class="sins-sequence-controls">
                                <label><?php esc_html_e('Base name', 'scout-image-studio'); ?>
                                    <input type="text" id="sins-sequence-base" placeholder="e.g., Scout Trails">
                                </label>
                                <label><?php esc_html_e('Start', 'scout-image-studio'); ?>
                                    <input type="number" id="sins-sequence-start" min="0" value="1">
                                </label>
                                <label><?php esc_html_e('Number format', 'scout-image-studio'); ?>
                                    <select id="sins-sequence-padding"><option value="1">1</option><option value="2" selected>01</option><option value="3">001</option><option value="4">0001</option></select>
                                </label>
                                <button type="button" class="button button-primary sins-primary-action" id="sins-apply-sequence"><?php esc_html_e('Apply Numbered Names', 'scout-image-studio'); ?></button>
                            </div>
                            <div class="sins-sequence-preview" id="sins-sequence-preview" aria-live="polite">
                                <span><?php esc_html_e('Live preview', 'scout-image-studio'); ?></span>
                                <code>scout-trails-01.jpg</code><code>scout-trails-02.jpg</code><code>scout-trails-03.jpg</code>
                            </div>
                        </div>

                        <div class="sins-seo-guidance">
                            <div class="sins-seo-guidance-heading">
                                <div><strong><?php esc_html_e('SEO-Guided AI Naming', 'scout-image-studio'); ?></strong><p class="description"><?php esc_html_e('Enter the word or phrase you want Scout to prefer. AI will blend it naturally into descriptive filenames and URLs when it fits the image.', 'scout-image-studio'); ?></p></div>
                                <span class="sins-step-badge"><?php esc_html_e('Preferred mode', 'scout-image-studio'); ?></span>
                            </div>
                            <div class="sins-seo-guidance-controls">
                                <label><?php esc_html_e('Preferred SEO phrase', 'scout-image-studio'); ?><input type="text" id="sins-seo-phrase" value="<?php echo esc_attr($this->get_ai_settings()['seo_phrase']); ?>" placeholder="e.g., Scout hiking trails"></label>
                                <label><?php esc_html_e('Optional website context', 'scout-image-studio'); ?><input type="text" id="sins-seo-context" value="<?php echo esc_attr($this->get_ai_settings()['seo_context']); ?>" placeholder="e.g., Global trail discovery and hiking safety"></label>
                                <label><?php esc_html_e('Maximum length', 'scout-image-studio'); ?><input type="number" id="sins-seo-max-length" min="30" max="120" value="<?php echo esc_attr($this->get_ai_settings()['seo_max_length']); ?>"></label>
                            </div>
                            <div class="sins-seo-guidance-footer"><span><?php esc_html_e('The generated filename becomes the image URL after you review and rename it.', 'scout-image-studio'); ?></span><a href="<?php echo esc_url(admin_url('upload.php?page=scout-image-studio-ai')); ?>"><?php esc_html_e('Open AI & SEO settings', 'scout-image-studio'); ?> →</a></div>
                        </div>

                        <div class="sins-quick-actions" aria-label="<?php esc_attr_e('Quick Actions', 'scout-image-studio'); ?>">
                            <div class="sins-quick-actions-heading">
                                <strong><?php esc_html_e('Quick Actions', 'scout-image-studio'); ?></strong>
                                <span class="description"><?php esc_html_e('Choose assets, generate AI names, review them, then rename.', 'scout-image-studio'); ?></span>
                            </div>
                            <div class="sins-toolbar">
                                <label>
                                    <input type="checkbox" id="sins-select-all">
                                    <?php esc_html_e('Select all on this page', 'scout-image-studio'); ?>
                                </label>
                                <button type="button" class="button sins-secondary-action" id="sins-ai-suggest"><span class="sins-ai-button-icon" aria-hidden="true">✨</span><span class="sins-ai-button-label"><?php esc_html_e('Select Name with AI', 'scout-image-studio'); ?></span></button>
                                <button type="button" class="button sins-secondary-action sins-ai-followup" id="sins-ai-another" hidden><span aria-hidden="true">💡</span><span><?php esc_html_e('Generate Another Idea', 'scout-image-studio'); ?></span></button>
                                <button type="button" class="button sins-tertiary-action sins-ai-followup" id="sins-ai-clear" hidden><span aria-hidden="true">🧹</span><span><?php esc_html_e('Clear Suggestions', 'scout-image-studio'); ?></span></button>
                                <span id="sins-ai-status" class="description" role="status" aria-live="polite"></span>
                                <button type="submit" class="button button-primary sins-primary-action sins-rename-submit" data-label="<?php esc_attr_e('Rename Selected Images', 'scout-image-studio'); ?>" data-loading-label="<?php esc_attr_e('Renaming Assets…', 'scout-image-studio'); ?>" onclick="return confirm('Rename the selected image files and update their WordPress references?');">
                                    <span class="sins-button-spinner" aria-hidden="true"></span>
                                    <span class="sins-button-label"><?php esc_html_e('Rename Selected Images', 'scout-image-studio'); ?></span>
                                </button>
                            </div>
                        </div>

                        <div class="sins-table-wrap">
                            <table class="widefat fixed striped sins-table">
                                <thead>
                                    <tr>
                                        <th class="check-column"></th>
                                        <th><?php esc_html_e('Image', 'scout-image-studio'); ?></th>
                                        <th><?php esc_html_e('Current filename', 'scout-image-studio'); ?></th>
                                        <th><?php esc_html_e('New filename', 'scout-image-studio'); ?></th>
                                        <th><?php esc_html_e('Source', 'scout-image-studio'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($query->have_posts()) : ?>
                                    <?php while ($query->have_posts()) : $query->the_post();
                                        $attachment_id = get_the_ID();
                                        $file = get_attached_file($attachment_id);
                                        if (!$file) {
                                            continue;
                                        }
                                        $current = wp_basename($file);
                                        $extension = pathinfo($current, PATHINFO_EXTENSION);
                                        $suggestion_data = $this->get_suggestion($attachment_id);
                                        $suggested_base = $suggestion_data['name'];
                                        $suggested = $suggested_base . ($extension ? '.' . strtolower($extension) : '');
                                        $thumb = wp_get_attachment_image($attachment_id, [80, 80], true, ['class' => 'sins-thumb']);
                                        ?>
                                        <tr>
                                            <td data-label="Select"><input class="sins-row-check" type="checkbox" name="selected[]" value="<?php echo esc_attr($attachment_id); ?>"></td>
                                            <td data-label="Image">
                                                <?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                                <div class="sins-title"><?php echo esc_html(get_the_title($attachment_id)); ?></div>
                                            </td>
                                            <td data-label="Current filename"><code><?php echo esc_html($current); ?></code></td>
                                            <td data-label="New filename">
                                                <input
                                                    type="text"
                                                    class="regular-text sins-new-name"
                                                    name="new_names[<?php echo esc_attr($attachment_id); ?>]"
                                                    value=""
                                                    data-suggestion="<?php echo esc_attr($suggested); ?>"
                                                    placeholder="<?php echo esc_attr($suggested); ?>"
                                                    data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                                                >
                                            </td>
                                            <td data-label="Source"><?php echo esc_html($suggestion_data['source']); ?></td>
                                        </tr>
                                    <?php endwhile; wp_reset_postdata(); ?>
                                <?php else : ?>
                                    <tr><td colspan="5"><?php esc_html_e('No images found.', 'scout-image-studio'); ?></td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>

                    <?php
                    $total_assets = (int) $query->found_posts;
                    $total_pages  = max(1, (int) $query->max_num_pages);
                    $page_start   = $total_assets > 0 ? (($paged - 1) * $per_page) + 1 : 0;
                    $page_end     = min($paged * $per_page, $total_assets);
                    $pagination = paginate_links([
                        'base'      => add_query_arg('paged', '%#%'),
                        'format'    => '',
                        'current'   => $paged,
                        'total'     => $total_pages,
                        'type'      => 'list',
                        'prev_text' => __('← Previous', 'scout-image-studio'),
                        'next_text' => __('Next →', 'scout-image-studio'),
                        'mid_size'  => 2,
                        'end_size'  => 1,
                        'add_args'  => $search ? ['s' => $search] : [],
                    ]);
                    echo '<div class="sins-pagination-bar">';
                    echo '<div class="sins-pagination-summary">';
                    /* translators: %s: Number of media assets. */
                    echo '<strong>' . esc_html(sprintf(_n('%s asset', '%s assets', $total_assets, 'scout-image-studio'), number_format_i18n($total_assets))) . '</strong>';
                    if ($total_assets > 0) {
                        /* translators: 1: First visible asset number, 2: Last visible asset number. */
                        echo '<span>' . esc_html(sprintf(__('Showing %1$s–%2$s', 'scout-image-studio'), number_format_i18n($page_start), number_format_i18n($page_end))) . '</span>';
                    }
                    /* translators: 1: Current page number, 2: Total page count. */
                    echo '<span>' . esc_html(sprintf(__('Page %1$s of %2$s', 'scout-image-studio'), number_format_i18n($paged), number_format_i18n($total_pages))) . '</span>';
                    echo '</div>';
                    if ($pagination) {
                        echo '<nav class="sins-pagination" aria-label="' . esc_attr__('Asset pages', 'scout-image-studio') . '">' . wp_kses_post($pagination) . '</nav>';
                    }
                    echo '</div>';
                    ?>
                </section>

                <aside class="sins-panel sins-panel-side">
                    <h2><?php esc_html_e('How names are suggested', 'scout-image-studio'); ?></h2>
                    <ol>
                        <li><?php esc_html_e('Alt text', 'scout-image-studio'); ?></li>
                        <li><?php esc_html_e('Media title', 'scout-image-studio'); ?></li>
                        <li><?php esc_html_e('Parent post or page title', 'scout-image-studio'); ?></li>
                        <li><?php esc_html_e('Current filename', 'scout-image-studio'); ?></li>
                    </ol>
                    <p><?php esc_html_e('Names are converted to lowercase, spaces become hyphens, and unsafe characters are removed.', 'scout-image-studio'); ?></p>

                    <div class="sins-ai-summary-card">
                        <span class="sins-ai-eyebrow"><?php esc_html_e('Scout AI Studio', 'scout-image-studio'); ?></span>
                        <h2><?php esc_html_e('AI filename intelligence', 'scout-image-studio'); ?></h2>
                        <p><?php esc_html_e('Connect OpenAI or Google Gemini, then generate concise filenames for selected images. Every suggestion remains editable before you rename anything.', 'scout-image-studio'); ?></p>
                        <a class="button button-primary" href="<?php echo esc_url(admin_url('upload.php?page=scout-image-studio-ai')); ?>"><?php esc_html_e('Open AI Studio', 'scout-image-studio'); ?></a>
                    </div>

                    <div class="sins-history-heading">
                        <h2><?php esc_html_e('Recent rename history', 'scout-image-studio'); ?></h2>
                        <?php if (!empty($history)) : ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="sins_clear_history">
                                <input type="hidden" name="return_paged" value="<?php echo esc_attr($paged); ?>">
                                <input type="hidden" name="return_search" value="<?php echo esc_attr($search); ?>">
                                <?php wp_nonce_field(self::NONCE_ACTION); ?>
                                <button class="button button-small sins-clear-history" onclick="return confirm('Clear rename history? This does not undo or rename any files.');"><?php esc_html_e('Clear History', 'scout-image-studio'); ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($history)) : ?>
                        <p><?php esc_html_e('No rename operations yet.', 'scout-image-studio'); ?></p>
                    <?php else : ?>
                        <ul class="sins-history">
                            <?php foreach (array_slice(array_reverse($history, true), 0, 10, true) as $operation_id => $operation) : ?>
                                <li>
                                    <strong><?php echo esc_html($operation['label']); ?></strong><br>
                                    <span><?php echo esc_html($operation['date']); ?></span>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="sins_undo">
                                        <input type="hidden" name="operation_id" value="<?php echo esc_attr($operation_id); ?>">
                                        <?php wp_nonce_field(self::NONCE_ACTION); ?>
                                        <button class="button button-small" onclick="return confirm('Undo this rename operation?');"><?php esc_html_e('Undo', 'scout-image-studio'); ?></button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
        <?php
    }

    public function render_ai_page() {
        $this->require_capability();
        $ai = $this->get_ai_settings();
        ?>
        <div class="wrap sins-wrap sins-ai-page">
            <div class="sins-hero">
                <div class="sins-brand-mark"><img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/icon-256.png'); ?>" alt=""></div>
                <div><h1><?php esc_html_e('Scout AI & SEO Studio', 'scout-image-studio'); ?></h1><p class="sins-kicker"><?php esc_html_e('AI-powered filename, URL and SEO guidance for WordPress assets', 'scout-image-studio'); ?></p></div>
            </div>
            <nav class="sins-studio-tabs" aria-label="<?php esc_attr_e('Scout Image Studio sections', 'scout-image-studio'); ?>"><a class="sins-studio-tab" href="<?php echo esc_url(admin_url('upload.php?page=scout-image-studio')); ?>"><?php esc_html_e('Assets', 'scout-image-studio'); ?></a><a class="sins-studio-tab is-active" href="<?php echo esc_url(admin_url('upload.php?page=scout-image-studio-ai')); ?>"><?php esc_html_e('AI & SEO Studio', 'scout-image-studio'); ?></a></nav>
            <?php $this->render_notice(); ?>
            <div class="sins-ai-layout">
                <section class="sins-panel">
                    <span class="sins-ai-eyebrow"><?php esc_html_e('Connection', 'scout-image-studio'); ?></span>
                    <h2><?php esc_html_e('Choose your AI provider', 'scout-image-studio'); ?></h2>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sins-ai-settings sins-ai-settings-full" id="sins-ai-settings-form">
                        <input type="hidden" name="action" value="sins_save_ai_settings">
                        <?php wp_nonce_field(self::NONCE_ACTION); ?>
                        <div class="sins-provider-grid">
                            <label class="sins-provider-card"><input type="radio" name="provider" value="openai" <?php checked($ai['provider'], 'openai'); ?>><span><strong>OpenAI</strong><small><?php esc_html_e('Excellent image understanding and concise filename generation.', 'scout-image-studio'); ?></small></span></label>
                            <label class="sins-provider-card"><input type="radio" name="provider" value="gemini" <?php checked($ai['provider'], 'gemini'); ?>><span><strong>Google Gemini</strong><small><?php esc_html_e('Fast multimodal analysis with strong visual recognition.', 'scout-image-studio'); ?></small></span></label>
                        </div>
                        <div class="sins-ai-core-fields">
                            <label class="sins-ai-field"><span><?php esc_html_e('API key', 'scout-image-studio'); ?></span><input type="password" name="api_key" value="" placeholder="<?php echo $ai['api_key'] ? esc_attr__('Saved — enter a new key only to replace it', 'scout-image-studio') : esc_attr__('Paste your API key', 'scout-image-studio'); ?>" autocomplete="new-password"></label>
                            <label class="sins-ai-field"><span><?php esc_html_e('Model', 'scout-image-studio'); ?></span><input type="text" name="model" value="<?php echo esc_attr($ai['model']); ?>"></label>
                        </div>
                        <div class="sins-ai-seo-profile"><span class="sins-ai-eyebrow"><?php esc_html_e('SEO profile', 'scout-image-studio'); ?></span><h3><?php esc_html_e('Preferred SEO guidance', 'scout-image-studio'); ?></h3><p class="description"><?php esc_html_e('Scout treats this phrase as preferred guidance, not a requirement. It will use it naturally when relevant.', 'scout-image-studio'); ?></p><label><?php esc_html_e('Preferred SEO phrase', 'scout-image-studio'); ?><input type="text" name="seo_phrase" value="<?php echo esc_attr($ai['seo_phrase']); ?>" placeholder="e.g., Scout hiking trails"></label><label><?php esc_html_e('Website context', 'scout-image-studio'); ?><input type="text" name="seo_context" value="<?php echo esc_attr($ai['seo_context']); ?>" placeholder="e.g., Global trail discovery and hiking safety"></label><label><?php esc_html_e('Maximum filename length', 'scout-image-studio'); ?><input type="number" name="seo_max_length" min="30" max="120" value="<?php echo esc_attr($ai['seo_max_length']); ?>"></label></div>
                        <div class="sins-ai-actions"><button class="button button-primary" type="submit"><?php esc_html_e('Save AI Settings', 'scout-image-studio'); ?></button><button class="button" type="button" id="sins-ai-test"><?php esc_html_e('Test Connection', 'scout-image-studio'); ?></button><a class="button sins-back-to-assets" href="<?php echo esc_url(admin_url('upload.php?page=scout-image-studio')); ?>"><?php esc_html_e('Back to Scout Image Studio', 'scout-image-studio'); ?></a><span id="sins-ai-test-status" class="description" aria-live="polite"></span></div>
                        <p class="description sins-ai-external-service-notice"><?php esc_html_e('Privacy note: AI naming is optional. When you explicitly request an AI suggestion, the selected image, filename-generation instructions, and any SEO phrase, website context, attachment title, alt text, or parent title used as context are sent directly to your selected provider (OpenAI or Google Gemini). No image is sent merely by opening this page or testing a connection.', 'scout-image-studio'); ?></p>
                    </form>
                </section>
                <aside class="sins-panel sins-ai-guide">
                    <span class="sins-ai-eyebrow"><?php esc_html_e('Setup guide', 'scout-image-studio'); ?></span>
                    <h2><?php esc_html_e('Get an API key', 'scout-image-studio'); ?></h2>
                    <a class="sins-key-link" href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer"><strong><?php esc_html_e('OpenAI API Keys', 'scout-image-studio'); ?></strong><span>platform.openai.com ↗</span></a>
                    <a class="sins-key-link" href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer"><strong><?php esc_html_e('Google Gemini API Keys', 'scout-image-studio'); ?></strong><span>aistudio.google.com ↗</span></a>
                    <ol><li><?php esc_html_e('Choose a provider.', 'scout-image-studio'); ?></li><li><?php esc_html_e('Create and paste your API key.', 'scout-image-studio'); ?></li><li><?php esc_html_e('Save settings and test the connection.', 'scout-image-studio'); ?></li><li><?php esc_html_e('Return to Assets, select images, and choose Select Name with AI.', 'scout-image-studio'); ?></li></ol>
                    <p class="description"><?php esc_html_e('Your key remains stored on your WordPress site and is sent only to the provider you select when you explicitly run an AI task.', 'scout-image-studio'); ?></p>
                </aside>
            </div>
        </div>
        <?php
    }

    private function render_notice() {
        if (empty($_GET['sins_notice']) || empty($_GET['sins_notice_nonce'])) {
            return;
        }
        $notice_nonce = sanitize_text_field(wp_unslash($_GET['sins_notice_nonce']));
        if (!wp_verify_nonce($notice_nonce, 'sins_display_notice')) {
            return;
        }
        $is_error = isset($_GET['sins_type']) && sanitize_key(wp_unslash($_GET['sins_type'])) === 'error';
        $message = sanitize_text_field(wp_unslash($_GET['sins_notice']));
        $class = $is_error ? 'sins-result-card sins-result-card-error' : 'sins-result-card sins-result-card-success';
        $title = $is_error ? __('Action needs attention', 'scout-image-studio') : __('Assets Updated', 'scout-image-studio');
        echo '<div class="' . esc_attr($class) . '" role="status">';
        echo '<div class="sins-result-icon" aria-hidden="true">' . ($is_error ? '!' : '✓') . '</div>';
        echo '<div><strong>' . esc_html($title) . '</strong><p>' . esc_html($message) . '</p>';
        if (!$is_error) {
            echo '<ul><li>' . esc_html__('URLs synchronized', 'scout-image-studio') . '</li><li>' . esc_html__('References updated', 'scout-image-studio') . '</li><li>' . esc_html__('Metadata refreshed', 'scout-image-studio') . '</li></ul>';
        }
        echo '</div></div>';
    }

    private function get_suggestion($attachment_id) {
        $alt = trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true));
        $title = trim((string) get_the_title($attachment_id));
        $parent_id = (int) wp_get_post_parent_id($attachment_id);
        $parent_title = $parent_id ? trim((string) get_the_title($parent_id)) : '';
        $file = get_attached_file($attachment_id);
        $current = $file ? pathinfo(wp_basename($file), PATHINFO_FILENAME) : 'image-' . $attachment_id;

        if ($alt !== '') {
            $source = __('Alt text', 'scout-image-studio');
            $value = $alt;
        } elseif ($title !== '') {
            $source = __('Media title', 'scout-image-studio');
            $value = $title;
        } elseif ($parent_title !== '') {
            $source = __('Parent title', 'scout-image-studio');
            $value = $parent_title;
        } else {
            $source = __('Current filename', 'scout-image-studio');
            $value = $current;
        }

        $slug = sanitize_title($value);
        if ($slug === '') {
            $slug = 'image-' . $attachment_id;
        }

        return ['name' => $slug, 'source' => $source];
    }

    private function get_ai_settings() {
        $settings = get_option('sins_ai_settings', []);
        $defaults = ['provider' => 'openai', 'api_key' => '', 'model' => 'gpt-5-mini', 'seo_phrase' => '', 'seo_context' => '', 'seo_mode' => 'preferred', 'seo_max_length' => 70];
        return wp_parse_args(is_array($settings) ? $settings : [], $defaults);
    }

    private function is_ai_configured() {
        $settings = $this->get_ai_settings();
        return !empty($settings['api_key']);
    }

    public function handle_save_ai_settings() {
        $this->require_capability();
        check_admin_referer(self::NONCE_ACTION);
        $provider = isset($_POST['provider']) && $_POST['provider'] === 'gemini' ? 'gemini' : 'openai';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';
        $existing = $this->get_ai_settings();
        if ($api_key === '') { $api_key = $existing['api_key']; }
        $model = isset($_POST['model']) ? sanitize_text_field(wp_unslash($_POST['model'])) : '';
        if ($model === '') {
            $model = $provider === 'gemini' ? 'gemini-2.5-flash' : 'gpt-5-mini';
        }
        $seo_phrase = isset($_POST['seo_phrase']) ? sanitize_text_field(wp_unslash($_POST['seo_phrase'])) : $existing['seo_phrase'];
        $seo_context = isset($_POST['seo_context']) ? sanitize_text_field(wp_unslash($_POST['seo_context'])) : $existing['seo_context'];
        $seo_max_length = isset($_POST['seo_max_length']) ? max(30, min(120, absint($_POST['seo_max_length']))) : (int) $existing['seo_max_length'];
        $seo_mode = 'preferred';
        update_option('sins_ai_settings', compact('provider', 'api_key', 'model', 'seo_phrase', 'seo_context', 'seo_mode', 'seo_max_length'), false);
        wp_safe_redirect(add_query_arg(['page' => 'scout-image-studio-ai', 'sins_notice' => __('AI settings saved.', 'scout-image-studio')], admin_url('upload.php'))); exit;
    }

    public function handle_ai_test_connection() {
        $this->require_capability();
        check_ajax_referer('sins_ai_suggest', 'nonce');
        $settings = $this->get_ai_settings();
        if (empty($settings['api_key'])) wp_send_json_error(['message' => __('Save an API key first.', 'scout-image-studio')], 400);
        if ($settings['provider'] === 'gemini') {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($settings['model']) . '?key=' . rawurlencode($settings['api_key']);
            $response = wp_remote_get($url, ['timeout' => 25]);
        } else {
            $response = wp_remote_get('https://api.openai.com/v1/models/' . rawurlencode($settings['model']), ['timeout' => 25, 'headers' => ['Authorization' => 'Bearer ' . $settings['api_key']]]);
        }
        if (is_wp_error($response)) wp_send_json_error(['message' => $response->get_error_message()], 500);
        $code = wp_remote_retrieve_response_code($response);
        $json = json_decode(wp_remote_retrieve_body($response), true);

        // A reasoning model can consume a very small token budget before it emits
        // visible text. Retry once with a larger budget when OpenAI reports that
        // exact incomplete condition.
        if (
            $settings['provider'] === 'openai'
            && $code >= 200 && $code < 300
            && isset($json['status'], $json['incomplete_details']['reason'])
            && $json['status'] === 'incomplete'
            && $json['incomplete_details']['reason'] === 'max_output_tokens'
        ) {
            $body['max_output_tokens'] = 600;
            $body['reasoning'] = ['effort' => 'low'];
            $request_args['body'] = wp_json_encode($body);
            $response = wp_remote_post('https://api.openai.com/v1/responses', $request_args);
            if (is_wp_error($response)) return $response;
            $code = wp_remote_retrieve_response_code($response);
            $json = json_decode(wp_remote_retrieve_body($response), true);
        }

        if ($code < 200 || $code >= 300) {
            $message = $json['error']['message'] ?? __('The provider rejected the connection.', 'scout-image-studio');
            wp_send_json_error(['message' => $message], $code ?: 500);
        }
        /* translators: 1: AI provider name, 2: AI model name. */
        wp_send_json_success(['message' => sprintf(__('Connected to %1$s using %2$s.', 'scout-image-studio'), $settings['provider'] === 'gemini' ? 'Google Gemini' : 'OpenAI', $settings['model'])]);
    }

    public function handle_ai_suggest() {
        $this->require_capability();
        check_ajax_referer('sins_ai_suggest', 'nonce');
        $ids = isset($_POST['ids']) ? array_values(array_filter(array_map('absint', (array) $_POST['ids']))) : [];
        if (!$ids) {
            wp_send_json_error(['message' => __('Select at least one image.', 'scout-image-studio')], 400);
        }
        if (count($ids) > 20) {
            wp_send_json_error(['message' => __('AI naming is limited to 20 images per request.', 'scout-image-studio')], 400);
        }
        $settings = $this->get_ai_settings();
        $guidance = [
            'phrase' => isset($_POST['seo_phrase']) ? sanitize_text_field(wp_unslash($_POST['seo_phrase'])) : $settings['seo_phrase'],
            'context' => isset($_POST['seo_context']) ? sanitize_text_field(wp_unslash($_POST['seo_context'])) : $settings['seo_context'],
            'mode' => 'preferred',
            'max_length' => isset($_POST['seo_max_length']) ? max(30, min(120, absint($_POST['seo_max_length']))) : (int) $settings['seo_max_length'],
        ];
        if (empty($settings['api_key'])) {
            wp_send_json_error(['message' => __('Add an API key in the AI naming settings first.', 'scout-image-studio')], 400);
        }
        $names = [];
        $errors = [];
        foreach ($ids as $id) {
            $result = $this->ai_name_attachment($id, $settings, $guidance);
            if (is_wp_error($result)) {
                $data = $result->get_error_data();
                $errors[$id] = [
                    'message' => $result->get_error_message(),
                    'raw'     => is_array($data) && isset($data['raw']) ? (string) $data['raw'] : '',
                    'provider'=> $settings['provider'],
                    'model'   => $settings['model'],
                ];
                continue;
            }
            $file = get_attached_file($id);
            $ext = $file ? strtolower(pathinfo($file, PATHINFO_EXTENSION)) : '';
            $names[$id] = $result . ($ext ? '.' . $ext : '');
        }
        if (!$names && $errors) {
            $first_id = array_key_first($errors);
            wp_send_json_error([
                'message' => sprintf('#%d: %s', $first_id, $errors[$first_id]['message']),
                'errors'  => $errors,
            ], 500);
        }
        wp_send_json_success([
            'names'  => $names,
            'errors' => $errors,
            'message'=> $errors
                ? sprintf(
                    /* translators: 1: Successful AI suggestions, 2: Images needing another attempt. */
                    __('Scout created %1$d suggestion(s); %2$d image(s) need another try.', 'scout-image-studio'), count($names), count($errors))
                : __('Scout AI has successfully created names for your selected images.', 'scout-image-studio'),
        ]);
    }

    private function ai_name_attachment($attachment_id, array $settings, array $guidance = []) {
        $file = get_attached_file($attachment_id);
        if (!$file || !file_exists($file)) {
            return new WP_Error('sins_ai_missing', __('Image file not found.', 'scout-image-studio'));
        }
        $source = $file;
        $meta = wp_get_attachment_metadata($attachment_id);
        if (!empty($meta['sizes']['medium']['file'])) {
            $candidate = trailingslashit(dirname($file)) . $meta['sizes']['medium']['file'];
            if (file_exists($candidate)) $source = $candidate;
        }
        $bytes = file_get_contents($source);
        if ($bytes === false) return new WP_Error('sins_ai_read', __('Could not read the image.', 'scout-image-studio'));
        $mime = wp_check_filetype($source)['type'] ?: get_post_mime_type($attachment_id);
        $context = trim(implode(' | ', array_filter([
            get_the_title($attachment_id),
            get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            wp_get_post_parent_id($attachment_id) ? get_the_title(wp_get_post_parent_id($attachment_id)) : '',
        ])));
        $seo_phrase = trim((string) ($guidance['phrase'] ?? ''));
        $seo_context = trim((string) ($guidance['context'] ?? ''));
        $max_length = max(30, min(120, (int) ($guidance['max_length'] ?? 70)));
        $prompt = 'Analyze this image and return exactly one concise, descriptive, SEO-friendly filename phrase. Describe only what is visibly present. Do not guess a specific place, species, person, landmark, brand, or identity unless clearly supported by the image or supplied context. Use 3 to 10 words. Return only the filename text: no explanation, no markdown, no JSON, no numbering, no quotation marks, and no file extension. Lowercase kebab-case is preferred, but Scout will normalize the result.';
        if ($seo_phrase !== '') {
            $prompt .= ' Preferred SEO phrase: "' . $seo_phrase . '". Use this phrase naturally when it is relevant to the image; do not force it, repeat it, or keyword-stuff.';
        }
        if ($seo_context !== '') {
            $prompt .= ' Website context: ' . $seo_context . '.';
        }
        $prompt .= ' Keep the final normalized filename under approximately ' . $max_length . ' characters. Existing asset context: ' . ($context ?: 'none');
        $data_url = 'data:' . $mime . ';base64,' . base64_encode($bytes);
        if ($settings['provider'] === 'gemini') {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($settings['model']) . ':generateContent?key=' . rawurlencode($settings['api_key']);
            $body = ['contents' => [['parts' => [['text' => $prompt], ['inline_data' => ['mime_type' => $mime, 'data' => base64_encode($bytes)]]]]]];
            $response = wp_remote_post($url, ['timeout' => 60, 'headers' => ['Content-Type' => 'application/json'], 'body' => wp_json_encode($body)]);
        } else {
            $body = [
                'model' => $settings['model'],
                'input' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => $prompt],
                        ['type' => 'input_image', 'image_url' => $data_url, 'detail' => 'low'],
                    ],
                ]],
                // Reasoning models count internal reasoning against this budget. Keep
                // reasoning light and leave enough room for the visible filename.
                'reasoning' => ['effort' => 'low'],
                'text' => ['verbosity' => 'low'],
                'max_output_tokens' => 300,
            ];
            $request_args = [
                'timeout' => 60,
                'headers' => [
                    'Authorization' => 'Bearer ' . $settings['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode($body),
            ];
            $response = wp_remote_post('https://api.openai.com/v1/responses', $request_args);
        }
        if (is_wp_error($response)) return $response;
        $code = wp_remote_retrieve_response_code($response);
        $json = json_decode(wp_remote_retrieve_body($response), true);

        // A reasoning model can consume a very small token budget before it emits
        // visible text. Retry once with a larger budget when OpenAI reports that
        // exact incomplete condition.
        if (
            $settings['provider'] === 'openai'
            && $code >= 200 && $code < 300
            && isset($json['status'], $json['incomplete_details']['reason'])
            && $json['status'] === 'incomplete'
            && $json['incomplete_details']['reason'] === 'max_output_tokens'
        ) {
            $body['max_output_tokens'] = 600;
            $body['reasoning'] = ['effort' => 'low'];
            $request_args['body'] = wp_json_encode($body);
            $response = wp_remote_post('https://api.openai.com/v1/responses', $request_args);
            if (is_wp_error($response)) return $response;
            $code = wp_remote_retrieve_response_code($response);
            $json = json_decode(wp_remote_retrieve_body($response), true);
        }

        if ($code < 200 || $code >= 300) {
            $message = $json['error']['message'] ?? __('The AI provider returned an error.', 'scout-image-studio');
            return new WP_Error('sins_ai_api', $message);
        }
        if (!empty($json['status']) && $json['status'] === 'incomplete') {
            $reason = $json['incomplete_details']['reason'] ?? 'unknown';
            return new WP_Error(
                'sins_ai_incomplete',
                sprintf(
                    /* translators: %s: Reason the AI response was incomplete. */
                    __('The AI response was incomplete (%s). Nothing was changed. Please retry.', 'scout-image-studio'), sanitize_text_field($reason)),
                ['raw' => wp_json_encode($json)]
            );
        }

        $text = $this->extract_ai_text($json, $settings['provider']);
        $slug = $this->normalize_ai_filename($text);
        if ($slug === '') {
            return new WP_Error(
                'sins_ai_empty',
                __('Scout could not confidently extract a filename. Nothing was changed; retry or enter a name manually.', 'scout-image-studio'),
                ['raw' => $text !== '' ? $text : wp_json_encode($json)]
            );
        }
        return substr($slug, 0, $max_length);
    }

    private function extract_ai_text(array $json, $provider) {
        if ($provider === 'gemini') {
            $parts = $json['candidates'][0]['content']['parts'] ?? [];
            $texts = [];
            foreach ((array) $parts as $part) {
                if (isset($part['text']) && is_string($part['text']) && trim($part['text']) !== '') {
                    $texts[] = trim($part['text']);
                }
            }
            if ($texts) return trim(implode("\n", $texts));
        }

        if (!empty($json['output_text']) && is_string($json['output_text'])) {
            return trim($json['output_text']);
        }

        // OpenAI Responses API commonly nests text at output[].content[].text.
        foreach ((array) ($json['output'] ?? []) as $output) {
            foreach ((array) ($output['content'] ?? []) as $content) {
                if (!empty($content['text']) && is_string($content['text'])) {
                    return trim($content['text']);
                }
                if (!empty($content['value']) && is_string($content['value'])) {
                    return trim($content['value']);
                }
            }
        }

        // Provider-tolerant fallback: collect likely human-readable values only.
        $texts = [];
        $walk = function ($value, $key = '') use (&$walk, &$texts) {
            if (is_string($value)) {
                if (in_array((string) $key, ['text', 'output_text', 'value', 'content', 'filename', 'name', 'slug', 'title'], true) && trim($value) !== '') {
                    $texts[] = trim($value);
                }
                return;
            }
            if (!is_array($value)) return;
            foreach ($value as $child_key => $item) {
                $walk($item, is_string($child_key) ? $child_key : $key);
            }
        };
        $walk($json);
        return trim(implode("\n", array_values(array_unique($texts))));
    }

    private function normalize_ai_filename($text) {
        $text = html_entity_decode(wp_strip_all_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim($text);
        if ($text === '') return '';

        // Remove fenced code blocks while preserving their content.
        $text = preg_replace('/```(?:json|text|markdown)?/i', '', $text);
        $text = str_replace('```', '', $text);
        $text = trim($text);

        // Decode JSON returned either directly or inside surrounding prose.
        $json_candidates = [$text];
        if (preg_match('/\{.*\}/s', $text, $match)) $json_candidates[] = $match[0];
        if (preg_match('/\[.*\]/s', $text, $match)) $json_candidates[] = $match[0];
        foreach ($json_candidates as $candidate) {
            $decoded = json_decode(trim($candidate), true);
            $json_value = $this->find_filename_value($decoded);
            if ($json_value !== '') {
                $text = $json_value;
                break;
            }
        }

        $text = preg_replace('/[*_#>`]+/', ' ', $text);
        $text = preg_replace('/^\s*(?:[-•*]|\d+[.)])\s*/u', '', $text);

        // Prefer explicitly labelled filename-like phrases.
        $patterns = [
            '/(?:suggested\s+)?(?:file\s*name|filename|name|slug|title)\s*(?:is|would\s+be|should\s+be|:|-)?\s*["\'“”]?([^\r\n"\'“”]+?)(?:["\'“”]|$)/iu',
            '/(?:best|good|recommended|ideal)\s+(?:file\s*name|filename)\s+(?:is|would\s+be|:)?\s*["\'“”]?([^\r\n"\'“”]+?)(?:["\'“”]|$)/iu',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match) && !empty($match[1])) {
                $text = $match[1];
                break;
            }
        }

        // Otherwise choose the first plausible non-explanatory line.
        $lines = preg_split('/\R+/', $text);
        $chosen = '';
        foreach ((array) $lines as $line) {
            $line = trim(preg_replace('/^\s*(?:[-•*]|\d+[.)])\s*/u', '', $line));
            $line = trim($line, " \t\n\r\0\x0B`'\"“”.:,;—–-");
            if ($line === '') continue;
            if (preg_match('/^(?:here(?:\'s| is)|the image|this image|suggestion|suggested filename|filename|response|result)\b/i', $line) && str_word_count($line) < 5) continue;
            $chosen = $line;
            break;
        }
        if ($chosen !== '') $text = $chosen;

        $text = preg_replace('/\.(?:jpe?g|png|gif|webp|avif|heic|tiff?|bmp|svg)\s*$/i', '', trim($text));
        $text = preg_replace('/^(?:the\s+)?(?:suggested\s+)?(?:file\s*name|filename|name|slug|title)\s*(?:is|would\s+be|should\s+be|:|-)?\s*/i', '', $text);
        $text = trim($text, " \t\n\r\0\x0B`'\"“”.:,;—–-");

        $slug = sanitize_title($text);
        if ($slug === '') return '';

        // Reject generic provider chatter accidentally captured as a filename.
        $generic = ['i-cannot', 'i-cant', 'unable-to', 'sorry', 'as-an-ai', 'the-image', 'suggested-filename', 'filename'];
        foreach ($generic as $prefix) {
            if ($slug === $prefix || strpos($slug, $prefix . '-') === 0) return '';
        }
        return $slug;
    }

    private function find_filename_value($value) {
        if (is_string($value)) return trim($value);
        if (!is_array($value)) return '';
        foreach (['filename', 'file_name', 'name', 'slug', 'title', 'suggestion'] as $key) {
            if (isset($value[$key])) {
                $found = $this->find_filename_value($value[$key]);
                if ($found !== '') return $found;
            }
        }
        foreach ($value as $item) {
            $found = $this->find_filename_value($item);
            if ($found !== '') return $found;
        }
        return '';
    }

    public function handle_bulk_rename() {
        $this->require_capability();
        check_admin_referer(self::NONCE_ACTION);

        $selected = isset($_POST['selected']) ? array_map('absint', (array) $_POST['selected']) : [];
        $return_paged = isset($_POST['return_paged']) ? max(1, absint($_POST['return_paged'])) : 1;
        $return_search = isset($_POST['return_search']) ? sanitize_text_field(wp_unslash($_POST['return_search'])) : '';
        $new_names = isset($_POST['new_names']) ? array_map('sanitize_text_field', (array) wp_unslash($_POST['new_names'])) : [];

        if (empty($selected)) {
            $this->redirect_notice(__('Select at least one image.', 'scout-image-studio'), true, $return_paged, $return_search);
        }

        $operation_id = wp_generate_uuid4();
        $operation = [
            /* translators: %d: Number of images included in the rename operation. */
            'label' => sprintf(_n('%d image renamed', '%d images renamed', count($selected), 'scout-image-studio'), count($selected)),
            'date'  => current_time('mysql'),
            'items' => [],
        ];
        $success = 0;
        $errors = [];

        foreach ($selected as $attachment_id) {
            $raw_name = isset($new_names[$attachment_id]) ? trim((string) $new_names[$attachment_id]) : '';
            if ($raw_name === '') {
                $suggestion = $this->get_suggestion($attachment_id);
                $raw_name = $suggestion['name'];
            }

            $result = $this->rename_attachment($attachment_id, $raw_name);
            if (is_wp_error($result)) {
                $errors[] = sprintf('#%d: %s', $attachment_id, $result->get_error_message());
            } else {
                $operation['items'][] = $result;
                $success++;
            }
        }

        if ($success > 0) {
            $history = get_option(self::HISTORY_OPTION, []);
            if (!is_array($history)) {
                $history = [];
            }
            $history[$operation_id] = $operation;
            if (count($history) > 50) {
                $history = array_slice($history, -50, null, true);
            }
            update_option(self::HISTORY_OPTION, $history, false);
        }

        /* translators: %d: Number of images renamed successfully. */
        $message = sprintf(_n('%d image renamed successfully.', '%d images renamed successfully.', $success, 'scout-image-studio'), $success);
        if ($errors) {
            $message .= ' ' . implode(' | ', array_slice($errors, 0, 3));
        }
        $this->redirect_notice($message, $success === 0, $return_paged, $return_search);
    }

    private function move_file($source, $destination) {
        global $wp_filesystem;

        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!WP_Filesystem()) {
            return false;
        }
        return (bool) $wp_filesystem->move($source, $destination, false);
    }

    private function rename_attachment($attachment_id, $requested_name) {
        if (get_post_type($attachment_id) !== 'attachment' || strpos((string) get_post_mime_type($attachment_id), 'image/') !== 0) {
            return new WP_Error('sins_not_image', __('The selected item is not an image attachment.', 'scout-image-studio'));
        }

        $old_abs = get_attached_file($attachment_id);
        if (!$old_abs || !file_exists($old_abs)) {
            return new WP_Error('sins_missing_file', __('The original image file could not be found.', 'scout-image-studio'));
        }

        $upload_dir = wp_get_upload_dir();
        $old_rel = get_post_meta($attachment_id, '_wp_attached_file', true);
        $old_url = wp_get_attachment_url($attachment_id);
        $old_title = get_the_title($attachment_id);
        $dir = trailingslashit(dirname($old_abs));
        $extension = strtolower(pathinfo($old_abs, PATHINFO_EXTENSION));
        $base = pathinfo(wp_basename($requested_name), PATHINFO_FILENAME);
        $base = sanitize_file_name($base);
        $base = sanitize_title($base);
        if ($base === '') {
            return new WP_Error('sins_bad_name', __('The proposed filename is empty after sanitization.', 'scout-image-studio'));
        }

        $desired = $base . ($extension ? '.' . $extension : '');
        $unique = wp_unique_filename($dir, $desired);
        $new_abs = $dir . $unique;

        if (wp_basename($old_abs) === $unique) {
            return new WP_Error('sins_same_name', __('The proposed name is already the current filename.', 'scout-image-studio'));
        }

        $metadata = wp_get_attachment_metadata($attachment_id);
        if (!is_array($metadata)) {
            $metadata = [];
        }
        $old_metadata = $metadata;
        $renamed_files = [];

        if (!$this->move_file($old_abs, $new_abs)) {
            return new WP_Error('sins_rename_failed', __('WordPress could not rename the original file. Check file permissions.', 'scout-image-studio'));
        }
        $renamed_files[] = ['old' => $old_abs, 'new' => $new_abs];

        $old_stem = pathinfo(wp_basename($old_abs), PATHINFO_FILENAME);
        $new_stem = pathinfo($unique, PATHINFO_FILENAME);

        if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size_key => &$size_data) {
                if (empty($size_data['file'])) {
                    continue;
                }
                $old_size_name = wp_basename($size_data['file']);
                $old_size_abs = $dir . $old_size_name;
                $size_ext = pathinfo($old_size_name, PATHINFO_EXTENSION);
                $size_stem = pathinfo($old_size_name, PATHINFO_FILENAME);
                $suffix = '';
                if (strpos($size_stem, $old_stem) === 0) {
                    $suffix = substr($size_stem, strlen($old_stem));
                } else {
                    $suffix = '-' . $size_key;
                }
                $new_size_name = wp_unique_filename($dir, $new_stem . $suffix . ($size_ext ? '.' . $size_ext : ''));
                $new_size_abs = $dir . $new_size_name;
                if (file_exists($old_size_abs) && $this->move_file($old_size_abs, $new_size_abs)) {
                    $renamed_files[] = ['old' => $old_size_abs, 'new' => $new_size_abs];
                    $size_data['file'] = $new_size_name;
                }
            }
            unset($size_data);
        }

        $new_rel = trailingslashit(dirname($old_rel)) . $unique;
        $new_rel = ltrim($new_rel, './');
        if (dirname($old_rel) === '.') {
            $new_rel = $unique;
        }

        update_attached_file($attachment_id, $new_abs);
        if (!empty($metadata)) {
            $metadata['file'] = $new_rel;
            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        $new_url = trailingslashit(dirname($old_url)) . rawurlencode($unique);
        // Keep the Media Library title synchronized with the actual renamed file.
        // This also ensures the next suggested filename reflects the new name instead
        // of continuing to display the attachment's original imported title.
        $new_title = ucwords(str_replace(['-', '_'], ' ', $new_stem));
        wp_update_post([
            'ID'         => $attachment_id,
            'guid'       => esc_url_raw($new_url),
            'post_title' => sanitize_text_field($new_title),
        ]);

        $url_map = [$old_url => $new_url];
        foreach ($renamed_files as $pair) {
            $old_file_url = $this->absolute_path_to_url($pair['old'], $upload_dir);
            $new_file_url = $this->absolute_path_to_url($pair['new'], $upload_dir);
            if ($old_file_url && $new_file_url) {
                $url_map[$old_file_url] = $new_file_url;
            }
        }
        $this->replace_references($url_map);

        return [
            'attachment_id' => $attachment_id,
            'old_abs'       => $old_abs,
            'new_abs'       => $new_abs,
            'old_rel'       => $old_rel,
            'new_rel'       => $new_rel,
            'old_url'       => $old_url,
            'new_url'       => $new_url,
            'old_title'     => $old_title,
            'new_title'     => $new_title,
            'old_metadata'  => $old_metadata,
            'new_metadata'  => $metadata,
            'renamed_files' => $renamed_files,
            'url_map'       => $url_map,
        ];
    }

    private function absolute_path_to_url($path, $upload_dir) {
        $basedir = wp_normalize_path($upload_dir['basedir']);
        $path = wp_normalize_path($path);
        if (strpos($path, $basedir) !== 0) {
            return '';
        }
        $relative = ltrim(substr($path, strlen($basedir)), '/');
        return trailingslashit($upload_dir['baseurl']) . str_replace('%2F', '/', rawurlencode($relative));
    }

    private function replace_references(array $url_map) {
        global $wpdb;
        foreach ($url_map as $old => $new) {
            if (!$old || !$new || $old === $new) {
                continue;
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- A prepared bulk replacement is required to preserve references across post content and excerpts.
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s), post_excerpt = REPLACE(post_excerpt, %s, %s) WHERE post_content LIKE %s OR post_excerpt LIKE %s",
                $old,
                $new,
                $old,
                $new,
                '%' . $wpdb->esc_like($old) . '%',
                '%' . $wpdb->esc_like($old) . '%'
            ));

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- A prepared LIKE lookup is required to find serialized metadata containing the old asset URL.
            $meta_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT meta_id, post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE %s",
                '%' . $wpdb->esc_like($old) . '%'
            ));
            $changed_post_ids = [];
            foreach ($meta_rows as $row) {
                $value = maybe_unserialize($row->meta_value);
                $updated = $this->deep_replace($old, $new, $value);
                if ($updated !== $value) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Updating by meta_id preserves serialized values after a deep URL replacement.
                    $wpdb->update(
                        $wpdb->postmeta,
                        ['meta_value' => maybe_serialize($updated)],
                        ['meta_id' => (int) $row->meta_id],
                        ['%s'],
                        ['%d']
                    );
                    $changed_post_ids[(int) $row->post_id] = true;
                }
            }
            foreach (array_keys($changed_post_ids) as $post_id) {
                clean_post_cache($post_id);
            }
        }
    }

    private function deep_replace($old, $new, $value) {
        if (is_string($value)) {
            return str_replace($old, $new, $value);
        }
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->deep_replace($old, $new, $item);
            }
            return $value;
        }
        if (is_object($value)) {
            foreach (get_object_vars($value) as $key => $item) {
                $value->{$key} = $this->deep_replace($old, $new, $item);
            }
        }
        return $value;
    }


    public function handle_clear_history() {
        $this->require_capability();
        check_admin_referer(self::NONCE_ACTION);

        $return_paged = isset($_POST['return_paged']) ? max(1, absint($_POST['return_paged'])) : 1;
        $return_search = isset($_POST['return_search']) ? sanitize_text_field(wp_unslash($_POST['return_search'])) : '';
        delete_option(self::HISTORY_OPTION);
        $this->redirect_notice(__('Rename history cleared. No image files were changed.', 'scout-image-studio'), false, $return_paged, $return_search);
    }

    public function handle_undo() {
        $this->require_capability();
        check_admin_referer(self::NONCE_ACTION);

        $operation_id = isset($_POST['operation_id']) ? sanitize_text_field(wp_unslash($_POST['operation_id'])) : '';
        $history = get_option(self::HISTORY_OPTION, []);
        if (!$operation_id || empty($history[$operation_id]['items'])) {
            $this->redirect_notice(__('The rename operation could not be found.', 'scout-image-studio'), true);
        }

        $errors = [];
        foreach (array_reverse($history[$operation_id]['items']) as $item) {
            $result = $this->undo_item($item);
            if (is_wp_error($result)) {
                $errors[] = $result->get_error_message();
            }
        }

        if (empty($errors)) {
            unset($history[$operation_id]);
            update_option(self::HISTORY_OPTION, $history, false);
            $this->redirect_notice(__('Rename operation undone successfully.', 'scout-image-studio'));
        }

        $this->redirect_notice(implode(' | ', array_slice($errors, 0, 3)), true);
    }

    private function undo_item($item) {
        $attachment_id = isset($item['attachment_id']) ? absint($item['attachment_id']) : 0;
        if (!$attachment_id) {
            return new WP_Error('sins_undo_bad_item', __('Undo data is incomplete.', 'scout-image-studio'));
        }

        if (!empty($item['renamed_files']) && is_array($item['renamed_files'])) {
            foreach (array_reverse($item['renamed_files']) as $pair) {
                if (!empty($pair['new']) && !empty($pair['old']) && file_exists($pair['new'])) {
                    if (file_exists($pair['old'])) {
                        /* translators: %s: Filename that cannot be restored. */
                        return new WP_Error('sins_undo_collision', sprintf(__('Cannot restore %s because a file with that name already exists.', 'scout-image-studio'), wp_basename($pair['old'])));
                    }
                    if (!$this->move_file($pair['new'], $pair['old'])) {
                        /* translators: %s: Filename that could not be restored. */
                        return new WP_Error('sins_undo_failed', sprintf(__('Could not restore %s.', 'scout-image-studio'), wp_basename($pair['old'])));
                    }
                }
            }
        }

        if (!empty($item['old_abs'])) {
            update_attached_file($attachment_id, $item['old_abs']);
        }
        if (!empty($item['old_metadata']) && is_array($item['old_metadata'])) {
            wp_update_attachment_metadata($attachment_id, $item['old_metadata']);
        }
        if (!empty($item['old_url']) || isset($item['old_title'])) {
            $post_update = ['ID' => $attachment_id];
            if (!empty($item['old_url'])) {
                $post_update['guid'] = esc_url_raw($item['old_url']);
            }
            if (isset($item['old_title'])) {
                $post_update['post_title'] = sanitize_text_field($item['old_title']);
            }
            wp_update_post($post_update);
        }
        if (!empty($item['url_map']) && is_array($item['url_map'])) {
            $this->replace_references(array_flip($item['url_map']));
        }
        return true;
    }

    private function redirect_notice($message, $error = false, $paged = 1, $search = '') {
        $args = [
            'page'        => 'scout-image-studio',
            'sins_notice' => rawurlencode($message),
            'sins_type'   => $error ? 'error' : 'success',
            'sins_notice_nonce' => wp_create_nonce('sins_display_notice'),
        ];
        if ($paged > 1) {
            $args['paged'] = absint($paged);
        }
        if ($search !== '') {
            $args['s'] = sanitize_text_field($search);
        }
        $url = add_query_arg($args, admin_url('upload.php'));
        wp_safe_redirect($url);
        exit;
    }
}

new Scout_Image_Studio();
