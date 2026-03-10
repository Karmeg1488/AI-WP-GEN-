<?php
if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'generator.php';
require_once plugin_dir_path(__FILE__) . 'image-generator.php';

function aicg_admin_page_render() {
   
    if (isset($_POST['aicg_save_settings'])) {
        check_admin_referer('aicg_admin_nonce', 'aicg_admin_nonce_field');

        update_option('aicg_api_key', sanitize_text_field( wp_unslash($_POST['aicg_api_key'] ?? '') ));
        update_option('aicg_categories', sanitize_text_field( wp_unslash($_POST['aicg_categories'] ?? '') ));
        update_option('aicg_author_count', intval( wp_unslash($_POST['aicg_author_count'] ?? 3) ));
        update_option('aicg_articles_per_category', intval( wp_unslash($_POST['aicg_articles_per_category'] ?? 3) ));
        
        // Update Site Name and sync with WordPress blogname
        $site_name = sanitize_text_field( wp_unslash($_POST['aicg_site_name'] ?? '') );
        update_option('aicg_site_name', $site_name);
        if (!empty($site_name)) {
            update_option('blogname', $site_name);
        }
        
        update_option('ang_site_topic', sanitize_textarea_field( wp_unslash($_POST['ang_site_topic'] ?? '') ));
        update_option('ang_style_prompt', sanitize_textarea_field( wp_unslash($_POST['ang_style_prompt'] ?? '') ));
        update_option('ang_language', sanitize_text_field( wp_unslash($_POST['ang_language'] ?? 'en') ));

        echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
    }

    // Handle logs clearing
    if (isset($_POST['aicg_clear_logs'])) {
        check_admin_referer('aicg_admin_nonce', 'aicg_admin_nonce_field');
        delete_option('aicg_debug_logs');
        echo '<div class="notice notice-success is-dismissible"><p>Logs cleared successfully.</p></div>';
    }

    // Handle authors and articles generation
    if (isset($_POST['aicg_generate_articles'])) {
        check_admin_referer('aicg_admin_nonce', 'aicg_admin_nonce_field');

        $result = aicg_generate_authors_categories_articles();
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result) . '</p></div>';
    }

    // Handle image generation
    if (isset($_POST['aicg_generate_images'])) {
        check_admin_referer('aicg_admin_nonce', 'aicg_admin_nonce_field');

        $result = aicg_generate_images_for_articles();
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result) . '</p></div>';
    }

    // Get current settings values
        $api_key = get_option('aicg_api_key', '');
        $categories = get_option('aicg_categories', '');
        $author_count = get_option('aicg_author_count', 3);
        $articles_per_category = get_option('aicg_articles_per_category', 3);
        $site_name = get_option('aicg_site_name', '');
    ?>

    <div class="wrap">
        <h1>✨ AI WP GEN</h1>
        <h2 class="nav-tab-wrapper" id="aicg-tabs">
            <a href="#aicg-tab-settings" class="nav-tab nav-tab-active">Settings</a>
            <a href="#aicg-tab-generate" class="nav-tab">Generation</a>
            <a href="#aicg-tab-logs" class="nav-tab">Logs</a>
        </h2>
        <div id="aicg-tab-settings" class="aicg-tab-content" style="display:block;">
            <div class="aicg-section" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.05) 100%); border: 1px solid rgba(102, 126, 234, 0.2); margin-bottom: 28px;">
                <h3>⚙️ Getting Started</h3>
                <p>To use AI WP GEN, you need an <strong>OpenAI API key</strong>. Here's what to configure:</p>
                <ul style="margin: 12px 0; padding-left: 20px; color: #5f6368; font-size: 14px;">
                    <li>Add your OpenAI API key</li>
                    <li>Set your site categories (comma separated)</li>
                    <li>Choose the number of authors and articles</li>
                    <li>Select your preferred language</li>
                    <li>Click "Save" and then go to "Generation" tab</li>
                </ul>
            </div>
            <form method="post">
                <?php wp_nonce_field('aicg_admin_nonce', 'aicg_admin_nonce_field'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="aicg_api_key">OpenAI API Key</label></th>
                            <td><input name="aicg_api_key" type="text" id="aicg_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="aicg_categories">Categories (comma separated)</label></th>
                            <td><input name="aicg_categories" type="text" id="aicg_categories" value="<?php echo esc_attr($categories); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="aicg_author_count">Number of Authors</label></th>
                            <td><input name="aicg_author_count" type="number" id="aicg_author_count" value="<?php echo esc_attr($author_count); ?>" min="1" class="small-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="aicg_articles_per_category">Articles per Category</label></th>
                            <td><input name="aicg_articles_per_category" type="number" id="aicg_articles_per_category" value="<?php echo esc_attr($articles_per_category); ?>" min="1" class="small-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="aicg_site_name">Site Name</label></th>
                            <td>
                                <input name="aicg_site_name" type="text" id="aicg_site_name" value="<?php echo esc_attr($site_name); ?>" class="regular-text" placeholder="e.g., Tech News Daily">
                                <p class="description">Your website's main title. This will be displayed in browser tabs, headers, and throughout the site. Also used for content generation (logos, pages, etc.)</p>
                            </td>
                        </tr>
                    
<tr>
    <th scope="row"><label for="ang_site_topic">Main Content & Design Prompt</label></th>
    <td>
        <textarea name="ang_site_topic" id="ang_site_topic" class="large-text code" rows="8" style="width: 100%; max-width: 600px; font-family: 'Courier New', monospace;"><?php echo esc_textarea(get_option('ang_site_topic','')); ?></textarea>
        <p class="description" style="margin-top: 8px;">
            <strong>Used for:</strong> Articles, blog pages, about/contact pages, logos, and overall design direction.<br>
            <strong>Optional:</strong> Leave empty for default prompts.<br>
            <strong>Examples:</strong><br>
            • "Tech news and AI articles for professionals, modern minimalist design"<br>
            • "Business consulting content with professional corporate theme"<br>
            • "Lifestyle blog about fitness and wellness with vibrant colors"
        </p>
    </td>
</tr>
<tr>
    <th scope="row"><label for="ang_style_prompt">CSS Style Prompt (Colors, Fonts, Layout)</label></th>
    <td>
        <textarea name="ang_style_prompt" id="ang_style_prompt" class="large-text code" rows="6" style="width: 100%; max-width: 600px; font-family: 'Courier New', monospace;"><?php echo esc_textarea(get_option('ang_style_prompt','')); ?></textarea>
        <p class="description" style="margin-top: 8px;">Optional: Describe your CSS styling preferences - colors, gradients, fonts, spacing, dark/light theme. This generates the base.css file. Example: "Dark purple gradient theme with white sans-serif fonts, minimal spacing"</p>
    </td>
</tr>
<tr>
    <th scope="row">Language</th>
    <td>
        <select name="ang_language">
            <?php $l=get_option('ang_language','en'); ?>
            <option value="en" <?php selected($l,'en'); ?>>English</option>
            <option value="pl" <?php selected($l,'pl'); ?>>Polish</option>
            <option value="de" <?php selected($l,'de'); ?>>German</option>
            <option value="hu" <?php selected($l,'hu'); ?>>Hungarian</option>
            <option value="uk" <?php selected($l,'uk'); ?>>Ukrainian</option>
            <option value="fr" <?php selected($l,'fr'); ?>>French</option>
            <option value="nl" <?php selected($l,'nl'); ?>>Dutch</option>
        </select>
    </td>
</tr>

</tbody>
                </table>
                <p class="submit">
                    <button type="submit" name="aicg_save_settings" class="button button-primary">Save</button>
                </p>
            </form>
        </div>
        <div id="aicg-tab-generate" class="aicg-tab-content" style="display:none;">
            <!-- Content Section -->
            <div style="margin-bottom: 32px;">
                <h2>📝 Content Generation</h2>
                
                <div class="aicg-section">
                    <h3>Authors & Articles</h3>
                    <p>Automatically generate authors, categories, and articles for your website based on your settings.</p>
                    <button type="button" id="aicg-generate-articles" class="button button-secondary">Generate Articles</button>
                    <div id="aicg-generate-articles-result"></div>
                </div>

                <div class="aicg-section">
                    <h3>Featured Images</h3>
                    <p>Generate and assign featured images for posts using AI. Requires existing posts.</p>
                    <button type="button" id="aicg_generate_images" class="button button-secondary">Generate Images</button>
                    <div id="aicg-generate-images-result"></div>
                </div>
            </div>

            <!-- Site Settings Section -->
            <div style="margin-bottom: 32px;">
                <h2>🎨 Site Branding</h2>
                
                <div class="aicg-section">
                    <h3>Base CSS Styles</h3>
                    <p>Generate a custom base.css file based on your styling preferences. This will be automatically loaded on your website.</p>
                    <button type="button" id="aicg-generate-css" class="button button-secondary">Generate CSS</button>
                    <div id="aicg-generate-css-result"></div>
                </div>

                <div class="aicg-section">
                    <h3>Site Title & Tagline</h3>
                    <p>Generate a creative title and tagline for your website based on your domain.</p>
                    <button type="button" id="aicg-generate-title-tagline" class="button button-secondary">Generate Title & Tagline</button>
                    <div id="aicg-title-tagline-result"></div>
                </div>

                <div class="aicg-section">
                    <h3>Logo</h3>
                    <p>Create a modern, minimalistic logo based on your site title and tagline.</p>
                    <button type="button" id="aicg-generate-logo" class="button button-secondary">Generate Logo</button>
                    <div id="aicg-logo-result"></div>
                </div>

                <div class="aicg-section">
                    <h3>Favicon</h3>
                    <p>Use your generated logo as a favicon or create a dedicated favicon.</p>
                    <button type="button" id="aicg-use-logo-favicon" class="button button-secondary">Use Logo as Favicon</button>
                    <div id="aicg-use-logo-favicon-result"></div>
                </div>
            </div>

            <!-- Pages Section -->
            <div style="margin-bottom: 32px;">
                <h2>📄 Static Pages</h2>
                
                <div class="aicg-section">
                    <h3>Contact & About Us</h3>
                    <p>Generate "Contact" and "About Us" pages based on your site language and tagline.</p>
                    <button type="button" id="aicg-generate-contact-about" class="button button-secondary">Generate Pages</button>
                    <div id="aicg-generate-contact-about-result"></div>
                </div>

                <div class="aicg-section">
                    <h3>📋 Policy Pages</h3>
                    <p>Generate Privacy Policy, Terms & Conditions, and Cookie Policy pages in your selected language.</p>
                    <button type="button" id="aicg-generate-policies" class="button button-secondary">Generate Policy Pages</button>
                    <div id="aicg-generate-policies-result"></div>
                </div>

                <div class="aicg-section">
                    <h3>🏠 Homepage</h3>
                    <p>Generate a complete homepage with Hero section, About section, latest blog posts, and customer testimonials.</p>
                    <button type="button" id="aicg-generate-homepage" class="button button-secondary">Generate Homepage</button>
                    <div id="aicg-generate-homepage-result"></div>
                </div>
            </div>

            <!-- Theme Generation Section -->
            <div style="margin-bottom: 32px;">
                <h2>🎭 Theme Generation (v1.6)</h2>
                <p style="color: #666; margin-bottom: 20px;">Create a complete custom WordPress theme with unique styles, layouts, and images based on your description.</p>
                
                <div class="aicg-section">
                    <h3>Create Custom Theme</h3>
                    <p>Generate a full WordPress theme with custom CSS, layouts, and images. After generation, activate it from Appearance → Themes.</p>
                    
                    <form id="aicg-theme-form" style="margin-top: 15px;">
                        <div style="margin-bottom: 15px;">
                            <label for="aicg-theme-name" style="display: block; margin-bottom: 5px; font-weight: 500;">Theme Name</label>
                            <input type="text" id="aicg-theme-name" name="theme_name" placeholder="e.g., Modern Business" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label for="aicg-theme-description" style="display: block; margin-bottom: 5px; font-weight: 500;">Theme Description</label>
                            <input type="text" id="aicg-theme-description" name="theme_description" placeholder="e.g., Professional business theme with modern design" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        
                        <div style="margin-bottom: 15px; padding: 12px; background: #e7f3ff; border-left: 4px solid #667eea; border-radius: 4px;">
                            <p style="margin: 0; color: #666;"><strong>Theme Design Prompt:</strong> Uses <strong>'Theme Design Prompt'</strong> from Settings</p>
                            <p style="margin: 5px 0 0 0; font-size: 13px; color: #999;">Edit the 'Theme Design Prompt' field in Settings tab to customize theme appearance.</p>
                        </div>
                        
                        <button type="button" id="aicg-generate-theme" class="button button-primary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; padding: 10px 20px; cursor: pointer;">🚀 Generate Theme</button>
                    </form>
                    
                    <div id="aicg-generate-theme-result" style="margin-top: 15px;"></div>
                </div>
            </div>
        </div>
        <div id="aicg-tab-logs" class="aicg-tab-content" style="display:none;">
            <h2>📋 Debug Logs</h2>
            <p>Last 100 log entries from plugin operations:</p>
            <div style="background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; padding: 15px; max-height: 500px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 12px;">
                <?php 
                $logs = get_option('aicg_debug_logs', []);
                if (empty($logs)) {
                    echo '<p style="color: #999; margin: 0;">No logs yet. Run a generation to see logs here.</p>';
                } else {
                    foreach (array_reverse($logs) as $log) {
                        echo '<div style="margin-bottom: 8px; padding: 8px; background: white; border-left: 3px solid #667eea;">';
                        echo '<strong>[' . esc_html($log['time']) . ']</strong><br>';
                        echo esc_html($log['message']) . '<br>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
            <div style="margin-top: 15px;">
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('aicg_admin_nonce', 'aicg_admin_nonce_field'); ?>
                    <button type="submit" name="aicg_clear_logs" class="button" style="background: #f44336; color: white; border: none;">Clear Logs</button>
                </form>
            </div>
        </div>
    </div>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .wrap {
            background: linear-gradient(135deg, #f5f7fa 0%, #f0f2f5 100%);
            border-radius: 12px;
            padding: 0;
            margin-top: 20px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .wrap h1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 28px 32px;
            margin: 0;
            font-size: 28px;
            font-weight: 600;
            border-radius: 12px 12px 0 0;
            letter-spacing: -0.5px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .nav-tab-wrapper {
            background: white;
            display: flex;
            border-bottom: 2px solid #e8eaed;
            margin: 0;
            padding: 0 32px;
            border-radius: 0;
        }

        .nav-tab {
            padding: 16px 24px;
            font-size: 14px;
            font-weight: 600;
            color: #5f6368;
            border: none;
            background: transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            text-decoration: none;
            display: inline-block;
        }

        .nav-tab:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .nav-tab-active {
            color: #667eea;
        }

        .nav-tab-active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px 2px 0 0;
        }

        .aicg-tab-content {
            background: white;
            padding: 32px;
            border-top: none;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .aicg-tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e8eaed;
        }

        .form-table tr {
            border-bottom: 1px solid #e8eaed;
        }

        .form-table tr:last-child {
            border-bottom: none;
        }

        .form-table tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.2s ease;
        }

        .form-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #202124;
            padding: 16px 20px;
            text-align: left;
            border-right: 1px solid #e8eaed;
        }

        .form-table td {
            padding: 16px 20px;
            color: #5f6368;
        }

        .form-table label {
            font-weight: 600;
            color: #202124;
        }

        .regular-text,
        .small-text,
        select {
            padding: 10px 14px;
            border: 1px solid #d3d3d3;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 400px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .regular-text:focus,
        .small-text:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .small-text {
            max-width: 120px;
        }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23667eea' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            padding-right: 38px;
        }

        .submit {
            margin: 24px 0 0 0;
            padding: 0;
        }

        .button {
            padding: 12px 28px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            text-decoration: none;
        }

        .button-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .button-primary:hover {
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .button-primary:active {
            transform: translateY(0);
        }

        .button-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-left: 0 !important;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .button-secondary:hover {
            background: rgba(102, 126, 234, 0.08);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .button-secondary:active {
            background: rgba(102, 126, 234, 0.12);
        }

        .button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        h2 {
            font-size: 18px;
            font-weight: 600;
            color: #202124;
            margin: 28px 0 16px 0;
            padding-bottom: 12px;
            border-bottom: 2px solid #e8eaed;
        }

        .aicg-section {
            background: white;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .aicg-section h3 {
            font-size: 16px;
            font-weight: 600;
            color: #202124;
            margin-bottom: 12px;
        }

        .aicg-section p {
            color: #5f6368;
            font-size: 13px;
            margin-bottom: 12px;
        }

        #aicg-progress-text {
            font-size: 13px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 8px;
        }

        #aicg-progress-bar {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            transition: width 0.3s ease;
        }

        .aicg-result {
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 13px;
            margin-top: 8px;
            display: inline-block;
            max-width: 100%;
            word-break: break-word;
        }

        .aicg-result.success {
            background: #ecf9f3;
            color: #137833;
            border-left: 4px solid #34a853;
        }

        .aicg-result.error {
            background: #fce8e6;
            color: #d33b27;
            border-left: 4px solid #ea4335;
        }

        .aicg-result.loading {
            background: #e8f0fe;
            color: #1a73e8;
            border-left: 4px solid #1a73e8;
        }

        .notice {
            border-radius: 8px;
            margin: 20px 0;
            padding: 16px 20px;
            border-left: 4px solid;
        }

        .notice-success {
            background-color: #ecf9f3;
            border-left-color: #34a853;
            color: #137833;
        }

        .notice-warning {
            background-color: #fff8e1;
            border-left-color: #f9ab00;
            color: #e37400;
        }

        .notice-error {
            background-color: #fce8e6;
            border-left-color: #ea4335;
            color: #d33b27;
        }

        .notice p {
            margin: 0;
            font-weight: 500;
        }

        .is-dismissible {
            position: relative;
        }

        .is-dismissible button.notice-dismiss {
            background: transparent;
            border: none;
            color: inherit;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s ease;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .is-dismissible button.notice-dismiss:hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .wrap {
                border-radius: 8px;
            }

            .wrap h1 {
                font-size: 22px;
                padding: 20px 16px;
            }

            .nav-tab-wrapper {
                padding: 0 16px;
                overflow-x: auto;
            }

            .aicg-tab-content {
                padding: 16px;
            }

            .form-table th,
            .form-table td {
                padding: 12px 16px;
            }

            .regular-text,
            .small-text,
            select {
                max-width: 100%;
            }

            .button {
                padding: 10px 20px;
                font-size: 13px;
            }

            .aicg-section {
                padding: 16px;
            }
        }

        /* Additional enhancements */
        .aicg-section:hover {
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .aicg-section button {
            margin-top: 12px;
        }

        .aicg-result {
            margin-top: 12px !important;
        }

        /* Loading animation */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        .button:disabled {
            animation: pulse 1.5s ease-in-out infinite;
        }

        /* Smooth transitions for better UX */
        * {
            -webkit-transition: box-shadow 0.2s ease;
            -moz-transition: box-shadow 0.2s ease;
            transition: box-shadow 0.2s ease;
        }

        /* Tooltip-like descriptions */
        .aicg-section p {
            margin-bottom: 16px;
            line-height: 1.6;
        }

        /* Better spacing for sections */
        #aicg-tab-generate > div {
            background: linear-gradient(180deg, rgba(102, 126, 234, 0.02) 0%, transparent 100%);
            padding: 24px;
            border-radius: 8px;
            margin-bottom: 32px;
        }

        /* Улучшение списков */
        .aicg-section ul {
            list-style-position: inside;
        }

        .aicg-section ul li {
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .aicg-section ul li strong {
            color: #667eea;
        }
    </style>

    <script>
    jQuery(document).ready(function($){
        // Tab switching with animation
        $('#aicg-tabs .nav-tab').on('click', function(e){
            e.preventDefault();
            const targetId = $(this).attr('href');
            
            $('#aicg-tabs .nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.aicg-tab-content').fadeOut(150).removeClass('active');
            $(targetId).fadeIn(150).addClass('active');
        });

        // Utility function to show results
        function showResult(elementId, message, isSuccess) {
            const $element = $('#' + elementId);
            const resultClass = isSuccess ? 'aicg-result success' : 'aicg-result error';
            $element.html('<div class="' + resultClass + '">' + message + '</div>');
        }

        // Utility function to show loading state
        function showLoading(elementId) {
            const $element = $('#' + elementId);
            $element.html('<div class="aicg-result loading">⏳ Processing...</div>');
        }

        // Generate Articles
        $('#aicg-generate-articles').on('click', function(){
            const $btn = $(this);
            $btn.prop('disabled', true).text('Generating...');
            showLoading('aicg-generate-articles-result');
            
            $.post(ajaxurl, {
                action: 'aicg_generate_articles',
                _wpnonce: '<?php echo esc_attr( wp_create_nonce("aicg_generate_articles") ); ?>'
            }, function(response){
                $btn.prop('disabled', false).text('Generate');
                if(response.success) {
                    showResult('aicg-generate-articles-result', '✓ ' + response.data, true);
                } else {
                    showResult('aicg-generate-articles-result', '✗ ' + response.data, false);
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Generate');
                showResult('aicg-generate-articles-result', '✗ Error: Request failed', false);
            });
        });

        // Generate Images
        $('#aicg_generate_images').on('click', function(){
            const $btn = $(this);
            $btn.prop('disabled', true).text('Generating...');
            showLoading('aicg-generate-images-result');
            
            $.post(ajaxurl, {
                action: 'aicg_generate_images',
                _wpnonce: '<?php echo esc_attr( wp_create_nonce("aicg_generate_images") ); ?>'
            }, function(response){
                $btn.prop('disabled', false).text('Generate');
                if(response.success) {
                    showResult('aicg-generate-images-result', '✓ ' + response.data, true);
                } else {
                    showResult('aicg-generate-images-result', '✗ ' + response.data, false);
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Generate');
                showResult('aicg-generate-images-result', '✗ Error: Request failed', false);
            });
        });

        // Generate CSS
        $('#aicg-generate-css').on('click', function(){
            const $btn = $(this);
            $btn.prop('disabled', true).text('Generating...');
            showLoading('aicg-generate-css-result');
            
            $.post(ajaxurl, {
                action: 'aicg_generate_css',
                _wpnonce: '<?php echo esc_attr( wp_create_nonce("aicg_generate_css") ); ?>'
            }, function(response){
                $btn.prop('disabled', false).text('Generate CSS');
                if(response.success) {
                    showResult('aicg-generate-css-result', '✓ ' + response.data, true);
                } else {
                    showResult('aicg-generate-css-result', '✗ ' + response.data, false);
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Generate CSS');
                showResult('aicg-generate-css-result', '✗ Error: Request failed', false);
            });
        });

        // Generate Title and Tagline
        $('#aicg-generate-title-tagline').on('click', function(){
            const $btn = $(this);
            $btn.prop('disabled', true).text('Generating...');
            showLoading('aicg-title-tagline-result');
            
            $.post(ajaxurl, {
                action: 'aicg_generate_title_tagline',
                _wpnonce: '<?php echo esc_attr( wp_create_nonce("aicg_generate_title_tagline") ); ?>'
            }, function(response){
                $btn.prop('disabled', false).text('Generate');
                if(response.success) {
                    showResult('aicg-title-tagline-result', '✓ ' + response.data, true);
                } else {
                    showResult('aicg-title-tagline-result', '✗ ' + response.data, false);
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Generate');
                showResult('aicg-title-tagline-result', '✗ Error: Request failed', false);
            });
        });

        // Generate Logo
        $('#aicg-generate-logo').on('click', function(){
            const $btn = $(this);
            $btn.prop('disabled', true).text('Generating...');
            showLoading('aicg-logo-result');
            
            $.post(ajaxurl, {
                action: 'aicg_generate_logo',
                _wpnonce: '<?php echo esc_attr( wp_create_nonce("aicg_generate_logo") ); ?>'
            }, function(response){
                $btn.prop('disabled', false).text('Generate Logo');
                if(response.success) {
                    showResult('aicg-logo-result', '✓ Logo generated successfully!', true);
                } else {
                    showResult('aicg-logo-result', '✗ ' + response.data, false);
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Generate Logo');
                showResult('aicg-logo-result', '✗ Error: Request failed', false);
            });
        });

        // Use Logo as Favicon
        $('#aicg-use-logo-favicon').on('click', function(){
            const $btn = $(this);
            $btn.prop('disabled', true).text('Processing...');
            showLoading('aicg-use-logo-favicon-result');
            
            $.post(ajaxurl, {
                action: 'aicg_use_logo_as_favicon',
                _wpnonce: '<?php echo esc_attr( wp_create_nonce("aicg_use_logo_as_favicon") ); ?>'
            }, function(response){
                $btn.prop('disabled', false).text('Use Logo as Favicon');
                if(response.success) {
                    showResult('aicg-use-logo-favicon-result', '✓ ' + response.data, true);
                } else {
                    showResult('aicg-use-logo-favicon-result', '✗ ' + response.data, false);
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Use Logo as Favicon');
                showResult('aicg-use-logo-favicon-result', '✗ Error: Request failed', false);
            });
        });

        // Generate Contact and About
        $('#aicg-generate-contact-about').on('click', function(){
            const $btn = $(this);
            $btn.prop('disabled', true).text('Generating...');
            showLoading('aicg-generate-contact-about-result');
            
            $.post(ajaxurl, {
                action: 'aicg_generate_contact_about',
                _wpnonce: '<?php echo esc_attr( wp_create_nonce("aicg_generate_contact_about") ); ?>'
            }, function(response){
                $btn.prop('disabled', false).text('Generate');
                if(response.success) {
                    showResult('aicg-generate-contact-about-result', '✓ ' + response.data, true);
                } else {
                    showResult('aicg-generate-contact-about-result', '✗ ' + response.data, false);
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Generate');
                showResult('aicg-generate-contact-about-result', '✗ Error: Request failed', false);
            });
        });

        // Generate Homepage
        $('#aicg-generate-homepage').on('click', function(){
            const $btn = $(this);
            $btn.prop('disabled', true).text('Generating...');
            showLoading('aicg-generate-homepage-result');
            
            $.post(ajaxurl, {
                action: 'aicg_generate_homepage',
                _wpnonce: '<?php echo esc_attr( wp_create_nonce("aicg_generate_homepage") ); ?>'
            }, function(response){
                $btn.prop('disabled', false).text('Generate Homepage');
                if(response.success) {
                    showResult('aicg-generate-homepage-result', '✓ ' + response.data, true);
                } else {
                    showResult('aicg-generate-homepage-result', '✗ ' + response.data, false);
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Generate Homepage');
                showResult('aicg-generate-homepage-result', '✗ Error: Request failed', false);
            });
        });

        // Generate Policy Pages
        $('#aicg-generate-policies').on('click', function(){
            const $btn = $(this);
            $btn.prop('disabled', true).text('Generating...');
            showLoading('aicg-generate-policies-result');
            
            $.post(ajaxurl, {
                action: 'aicg_generate_policies',
                _wpnonce: '<?php echo esc_attr( wp_create_nonce("aicg_generate_policies") ); ?>'
            }, function(response){
                $btn.prop('disabled', false).text('Generate Policy Pages');
                if(response.success) {
                    showResult('aicg-generate-policies-result', '✓ ' + response.data, true);
                } else {
                    showResult('aicg-generate-policies-result', '✗ ' + response.data, false);
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Generate Policy Pages');
                showResult('aicg-generate-policies-result', '✗ Error: Request failed', false);
            });
        });

        // Generate Theme
        $('#aicg-generate-theme').on('click', function(){
            const $btn = $(this);
            const themeName = $('#aicg-theme-name').val();
            const themeDescription = $('#aicg-theme-description').val();
            
            if (!themeName) {
                showResult('aicg-generate-theme-result', '✗ Please enter a theme name', false);
                return;
            }
            
            $btn.prop('disabled', true).text('Generating Theme (This may take a few minutes)...');
            showLoading('aicg-generate-theme-result');
            
            $.post(ajaxurl, {
                action: 'aicg_generate_theme',
                _wpnonce: '<?php echo esc_attr( wp_create_nonce("aicg_generate_theme") ); ?>',
                theme_name: themeName,
                theme_description: themeDescription
            }, function(response){
                $btn.prop('disabled', false).text('🚀 Generate Theme');
                if(response.success) {
                    const message = '<strong>' + response.data.message + '</strong><br><a href="/wp-admin/themes.php" style="margin-top: 10px; display: inline-block;" class="button button-primary">Go to Themes</a>';
                    showResult('aicg-generate-theme-result', '✓ ' + message, true);
                    $('#aicg-theme-form')[0].reset();
                } else {
                    showResult('aicg-generate-theme-result', '✗ ' + response.data, false);
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('🚀 Generate Theme');
                showResult('aicg-generate-theme-result', '✗ Error: Request failed', false);
            });
        });

        // Dismiss Notices
        $('body').on('click', '.notice .notice-dismiss', function(e){
            e.preventDefault();
            $(this).closest('.notice').fadeOut(150, function(){
                $(this).remove();
            });
        });
    });
    </script>
    <?php
}
