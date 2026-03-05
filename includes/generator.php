<?php
if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'openai-helper.php';

/**
 * Map language code to language name for OpenAI prompts
 */
if (!function_exists('get_language_name_from_code')) {
function get_language_name_from_code($code) {
    $map = [
        'en' => 'English',
        'pl' => 'Polish',
        'de' => 'German',
        'hu' => 'Hungarian',
        'uk' => 'Ukrainian',
        'tr' => 'Turkish',
        'it' => 'Italian',
        'cs' => 'Czech',
        'fr' => 'French',
        'nl' => 'Dutch',
    ];

    $code = strtolower($code);
    return $map[$code] ?? 'English';
}
}

/**
 * Map WordPress locale to language name for OpenAI prompts (legacy)
 */
if (!function_exists('get_language_name_from_locale')) {
function get_language_name_from_locale($locale) {
    $code = strtolower(substr($locale, 0, 2));
    return get_language_name_from_code($code);
}
}

/**
 * Get localized page titles based on language code
 */
if (!function_exists('aicg_get_localized_page_title')) {
function aicg_get_localized_page_title($page_key, $language_code = 'en') {
    $language_code = strtolower($language_code);
    
    $titles = [
        'contact' => [
            'en' => 'Contact',
            'pl' => 'Kontakt',
            'de' => 'Kontakt',
            'hu' => 'Kapcsolat',
            'uk' => 'Контакти',
            'tr' => 'İletişim',
            'it' => 'Contatti',
            'cs' => 'Kontakt',
            'fr' => 'Contact',
            'nl' => 'Contact',
        ],
        'about' => [
            'en' => 'About Us',
            'pl' => 'O nas',
            'de' => 'Über uns',
            'hu' => 'Rólunk',
            'uk' => 'Про нас',
            'tr' => 'Hakkımızda',
            'it' => 'Chi siamo',
            'cs' => 'O nás',
            'fr' => 'À propos de nous',
            'nl' => 'Over ons',
        ],
        'home' => [
            'en' => 'Home',
            'pl' => 'Strona główna',
            'de' => 'Startseite',
            'hu' => 'Kezdőoldal',
            'uk' => 'Домашня',
            'tr' => 'Anasayfa',
            'it' => 'Home',
            'cs' => 'Domů',
            'fr' => 'Accueil',
            'nl' => 'Home',
        ],
        'privacy' => [
            'en' => 'Privacy Policy',
            'pl' => 'Polityka prywatności',
            'de' => 'Datenschutzerklärung',
            'hu' => 'Adatvédelmi irányelvek',
            'uk' => 'Політика конфіденційності',
            'tr' => 'Gizlilik Politikası',
            'it' => 'Informativa sulla privacy',
            'cs' => 'Zásady ochrany osobních údajů',
            'fr' => 'Politique de confidentialité',
            'nl' => 'Privacybeleid',
        ],
        'terms' => [
            'en' => 'Terms & Conditions',
            'pl' => 'Warunki i postanowienia',
            'de' => 'Allgemeine Geschäftsbedingungen',
            'hu' => 'Feltételek és Condiciones',
            'uk' => 'Умови користування',
            'tr' => 'Şartlar ve Koşullar',
            'it' => 'Termini e Condizioni',
            'cs' => 'Podmínky a ustanovení',
            'fr' => 'Conditions générales',
            'nl' => 'Algemene voorwaarden',
        ],
        'cookies' => [
            'en' => 'Cookie Policy',
            'pl' => 'Polityka plików cookies',
            'de' => 'Cookie-Richtlinie',
            'hu' => 'Cookie-szabályzat',
            'uk' => 'Політика використання cookies',
            'tr' => 'Çerez Politikası',
            'it' => 'Informativa sui cookie',
            'cs' => 'Zásady používání cookies',
            'fr' => 'Politique relative aux cookies',
            'nl' => 'Cookiebeleid',
        ],
    ];
    
    $page_key = strtolower($page_key);
    return $titles[$page_key][$language_code] ?? $titles[$page_key]['en'];
}
}

/**
 * Generate authors, categories, and articles
 */
function aicg_generate_authors_categories_articles() {
    $api_key = get_option('aicg_api_key');
    if (!$api_key) {
        return 'OpenAI API key is not set in the settings.';
    }

    $author_count = max(1, intval(get_option('aicg_author_count', 3)));
    $categories_raw = get_option('aicg_categories', '');
    $articles_per_category = max(1, intval(get_option('aicg_articles_per_category', 3)));

    if (empty($categories_raw)) {
        return 'Categories are not set.';
    }

    $categories = array_map('trim', explode(',', $categories_raw));
    if (count($categories) === 0) {
        return 'Categories are not set.';
    }

    // Get language from plugin settings
    $language_code = get_option('ang_language', 'en');
    $language = get_language_name_from_code($language_code);

    // 1. Generate authors if needed
    $created_authors = aicg_create_authors($author_count, $api_key);

    // 2. Create categories if they don't exist
    foreach ($categories as $cat_name) {
        $cat_name = sanitize_text_field($cat_name);
        if (!term_exists($cat_name, 'category')) {
            wp_insert_term($cat_name, 'category');
        }
    }

    // Get authors generated by plugin to assign posts
    $authors = get_users([
        'role' => 'author',
        'meta_key' => 'aicg_generated',
        'meta_value' => '1',
        'number' => $author_count,
        'orderby' => 'ID',
        'order' => 'ASC'
    ]);
    if (count($authors) === 0) {
        return 'Failed to retrieve generated authors.';
    }

    $articles_created = 0;

    // 3. Generate articles per category
    foreach ($categories as $category_name) {
        $term = get_term_by('name', $category_name, 'category');
        if (!$term) continue;

        for ($i = 0; $i < $articles_per_category; $i++) {
            // Generate article title with language consideration
            $title = aicg_openai_generate_title($api_key, $category_name, $language);

            // Check for title uniqueness
            $query = new WP_Query([
                'post_type'      => 'post',
                'title'          => $title,
                'posts_per_page' => 1,
                'post_status'    => 'publish',
            ]);

            if ($query->have_posts()) {
                wp_reset_postdata();
                continue; // Skip duplicate title
            }
            wp_reset_postdata();

            // Generate article content with language consideration
            // Retry up to 3 times if content generation fails
            $content = null;
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $content = aicg_openai_generate_content($api_key, $title, $language);
                if ($content) {
                    error_log('AI WP GEN - Article content generated on attempt ' . $attempt);
                    break;
                }
                error_log('AI WP GEN - Article content generation failed on attempt ' . $attempt . ', retrying...');
                if ($attempt < 3) {
                    sleep(2); // Wait 2 seconds before retry
                }
            }

            // Skip article if content generation failed
            if (!$content) {
                error_log('AI WP GEN - Skipping article "' . $title . '" - failed to generate content after 3 attempts');
                continue;
            }

            // Pick a random author from the list
            $author = $authors[array_rand($authors)];

            // Insert the post
            $post_id = wp_insert_post([
                'post_title'    => wp_strip_all_tags($title),
                'post_content'  => $content,
                'post_status'   => 'publish',
                'post_author'   => $author->ID,
                'post_category' => [$term->term_id],
            ]);

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, 'aicg_image_needed', 1);
                $articles_created++;
            }
        }
    }

    return "Authors created: {$created_authors}. Articles created: {$articles_created}.";
}

/**
 * Generate authors with real names via OpenAI
 */
function aicg_create_authors($count, $api_key) {
    $existing_authors = get_users([
        'role' => 'author',
        'meta_key' => 'aicg_generated',
        'meta_value' => '1'
    ]);

    $created = 0;
    $to_create = max(0, $count - count($existing_authors));
    if ($to_create <= 0) {
        return 0; // Enough authors exist already
    }

    // Request OpenAI for real names
    $names = aicg_openai_generate_names($api_key, $to_create);

    foreach ($names as $full_name) {
        $parts = explode(' ', $full_name);
        if (count($parts) < 2) continue;

        $first = sanitize_user(strtolower($parts[0]));
        $last = sanitize_user(strtolower($parts[1]));
        $login = $first . '.' . $last;

        // If username exists, append number suffix
        $suffix = 1;
        $base_login = $login;
        while (username_exists($login)) {
            $login = $base_login . $suffix;
            $suffix++;
        }

        $password = wp_generate_password(12, true);
        $email = $login . '@example.com';

        $user_id = wp_create_user($login, $password, $email);
        if (!is_wp_error($user_id)) {
            wp_update_user([
                'ID' => $user_id,
                'role' => 'author',
                'display_name' => $full_name
            ]);
            update_user_meta($user_id, 'aicg_generated', '1');
            $created++;
        }
    }

    return $created;
}

/**
 * Generate article title based on category and language
 */
function aicg_openai_generate_title($api_key, $category, $language) {
    $custom_prompt = get_option('ang_site_topic', '');
    
    if ($custom_prompt) {
        // Use custom prompt with context
        $prompt = "Based on this context: \"{$custom_prompt}\" - Write a compelling, specific {$language} headline for an article in the category \"{$category}\". The headline should:
- Be catchy and unique
- Contain specific details or numbers when possible
- Avoid clichés and generic phrases
- Be relevant to both the category and site topic
- Intrigue readers to want to learn more";
    } else {
        // Default behavior
        $styles = [
            "catchy",
            "serious and formal",
            "question-style",
            "breaking news style",
            "surprising or unusual tone",
        ];
        $style = $styles[array_rand($styles)];
        $prompt = "Write a {$style}, unique, specific {$language} headline for a news article in the category \"{$category}\". The headline should:
- Be compelling and memorable
- Avoid clichés and repetition
- Include specific details or numbers when possible
- Make readers want to click and read more";
    }

    $response = aicg_openai_request($api_key, $prompt);
    return $response ?: 'Untitled News';
}

/**
 * Generate article content based on title and language
 */
function aicg_openai_generate_content($api_key, $title, $language) {
    $custom_prompt = get_option('ang_site_topic', '');
    
    if ($custom_prompt) {
        // Use custom prompt with context
        $prompt = "Based on this site topic: \"{$custom_prompt}\" - Write a comprehensive, detailed {$language} news article (800-1200 words) with the headline \"{$title}\". The article should be:
- Well-structured with clear introduction, 3-4 main body sections, and conclusion
- Informative with specific details, facts, and insights
- Relevant to the site topic and engaging for readers
- Use varied paragraph lengths and natural transitions
- Include practical information or actionable insights where applicable
- Professional journalistic tone but accessible to general audience

Make the content look natural and complete, as if written by a professional journalist.";
    } else {
        // Default behavior
        $tones = [
            "objective and journalistic",
            "analytical with a hint of commentary",
            "fact-focused with quotes and data",
            "engaging and narrative-driven",
        ];
        $tone = $tones[array_rand($tones)];
        $prompt = "Write a comprehensive, detailed {$tone} news article (800-1200 words) in {$language} with the headline \"{$title}\". The article should:
- Be well-organized with introduction, multiple body sections, and conclusion
- Contain specific details, examples, and relevant information
- Use varied paragraph lengths for natural readability
- Include practical insights or actionable information
- Be unique, informative, and free from generic phrases
- Sound professional yet accessible

Ensure the content appears natural and complete.";
    }

    $response = aicg_openai_request($api_key, $prompt);
    if (!$response) {
        error_log('AI WP GEN - Failed to generate article content for: ' . $title);
        return false;
    }
    return $response;
}

function aicg_generate_contact_and_about_pages() {
    $api_key = get_option('aicg_api_key');
    if (!$api_key) {
        return 'OpenAI API key is not set.';
    }

    $language_code = get_option('ang_language', 'en');
    $language = get_language_name_from_code($language_code);
    $tagline = get_option('blogdescription', '');
    
    // Get site name from plugin settings, fallback to WordPress blogname
    $title = get_option('aicg_site_name', '');
    if (empty($title)) {
        $title = get_option('blogname', '');
    }

    // Contact page prompt
    $contact_prompt = "Generate a comprehensive, detailed, and professional contact page content for a website in {$language}. The content should include:

1. **Welcome/Introduction** - Brief descriptive introduction
2. **Multiple Contact Methods** - Email, phone, postal address (with realistic details for the country), contact form description
3. **Office Hours & Availability** - Clear information about when you're available
4. **Location Information** - Map directions or location details
5. **Contact Form Guidelines** - What information to provide and expected response time
6. **Social Media Links** - Links to social profiles
7. **FAQ About Contact** - 3-4 common questions answered
8. **Call to Action** - Encouraging message inviting customers/visitors to reach out

Make it professional, thorough, engaging, and naturally structured with multiple paragraphs. Format as HTML without wrapper tags, only page body content. Minimum 600 words.";

    // About page prompt
    $about_prompt = "Generate a comprehensive, detailed, and engaging 'About Us' page content for a website called \"{$title}\" with the tagline \"{$tagline}\" in {$language}. The content should include:

1. **Opening/Mission** - Compelling introduction and mission statement
2. **Our Story** - Detailed background and history (3-4 paragraphs)
3. **Core Values & Principles** - What the company stands for
4. **What We Do** - Detailed explanation of services/products
5. **Our Team** - Description of team expertise and dedication
6. **Achievements & Milestones** - Key accomplishments and success stories
7. **Why Choose Us** - Unique selling points and competitive advantages (3-4 benefits)
8. **Customer Testimonials** - Sample quotes or success stories (2-3)
9. **Our Vision for the Future** - Future goals and direction
10. **Call to Action** - Invitation to work with or learn more

Make it professional, engaging, well-structured with varied paragraph lengths, and reflect the style of the tagline. Use natural language and specific details. Minimum 800 words. Format as HTML without wrapper tags.";

    // Generate content
    $contact_content = aicg_openai_chat_request($api_key, $contact_prompt);
    $about_content = aicg_openai_chat_request($api_key, $about_prompt);
    
    if (empty($contact_content)) {
        error_log('AI WP GEN - Failed to generate Contact page content');
        $contact_content = '<p>Unable to generate contact page. Please contact administrator.</p>';
    }
    if (empty($about_content)) {
        error_log('AI WP GEN - Failed to generate About page content');
        $about_content = '<p>Unable to generate about page. Please contact administrator.</p>';
    }
    
    $contact_content = aicg_strip_html_wrappers($contact_content);
    $about_content = aicg_strip_html_wrappers($about_content);

    // Create or update Contact page
    $contact_page = get_page_by_path('contact');
    $contact_data = [
        'post_title'   => aicg_get_localized_page_title('contact', $language_code),
        'post_name'    => 'contact',
        'post_content' => $contact_content,
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ];
    if ($contact_page) {
        $contact_data['ID'] = $contact_page->ID;
        wp_update_post($contact_data);
    } else {
        wp_insert_post($contact_data);
    }

    // Create or update About page
    $about_page = get_page_by_path('about');
    $about_data = [
        'post_title'   => aicg_get_localized_page_title('about', $language_code),
        'post_name'    => 'about',
        'post_content' => $about_content,
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ];
    if ($about_page) {
        $about_data['ID'] = $about_page->ID;
        wp_update_post($about_data);
    } else {
        wp_insert_post($about_data);
    }

    return 'Contact and About Us pages generated.';
}
function aicg_strip_html_wrappers($content) {
    // Remove <html>, <body> tags from start/end
    $content = preg_replace('#^\s*<html[^>]*>\s*#i', '', $content);
    $content = preg_replace('#^\s*<body[^>]*>\s*#i', '', $content);
    $content = preg_replace('#\s*</body>\s*$#i', '', $content);
    $content = preg_replace('#\s*</html>\s*$#i', '', $content);
    return trim($content);
}

/**
 * Generate Home/Front Page with multiple sections
 */
function aicg_generate_homepage() {
    $api_key = get_option('aicg_api_key');
    if (!$api_key) {
        return 'OpenAI API key is not set.';
    }

    $language_code = get_option('ang_language', 'en');
    $language = get_language_name_from_code($language_code);
    
    $site_name = get_option('aicg_site_name', '');
    if (empty($site_name)) {
        $site_name = get_option('blogname', 'Our Website');
    }
    
    $tagline = get_option('blogdescription', '');
    $custom_prompt = get_option('ang_site_topic', '');

    // 1. Generate Hero Section (heading + subtitle + detailed description based on custom prompt)
    $hero_prompt = "Create a compelling hero section for a website called \"$site_name\" with tagline \"$tagline\". " . 
        ($custom_prompt ? "This is the main focus: $custom_prompt. " : "") .
        "Generate: 1) A powerful main headline (5-10 words) that captures the essence of the topic; 2) An engaging subheading (1-2 sentences describing the main value proposition); 3) A detailed, compelling description (4-5 sentences about what this business/topic offers, benefits, and unique value); 4) A clear call-to-action text (2-4 words). " .
        "Language: $language. Respond with JSON: {\"headline\": \"...\", \"subheading\": \"...\", \"description\": \"...\", \"cta_text\": \"...\"}. ONLY JSON, no other text.";

    $hero_response = aicg_openai_chat_request($api_key, $hero_prompt);
    $hero = json_decode($hero_response, true) ?: [];
    
    // Ensure we have default values
    $hero['headline'] = $hero['headline'] ?? $site_name;
    $hero['subheading'] = $hero['subheading'] ?? $tagline;
    $hero['description'] = $hero['description'] ?? '';
    $hero['cta_text'] = $hero['cta_text'] ?? 'Get Started';

    // 2. Generate About Us short section
    $about_prompt = "Create a comprehensive 'About Us' section (400-600 words, multiple paragraphs) for \"$site_name\". " .
        ($custom_prompt ? "Topic/Focus: $custom_prompt. " : "") .
        "Include: 1) Opening statement about the company mission and vision; 2) Detailed background and what makes this unique; 3) Key achievements or expertise; 4) What the company offers and its benefits; 5) Commitment to customers/quality; 6) Forward-looking statement. " .
        "Write naturally with varied paragraph lengths, specific details, and compelling narrative. Language: $language.";

    $about_text = aicg_openai_chat_request($api_key, $about_prompt);

    // 3. Generate 3 customer testimonials
    $testimonials_prompt = "Generate 3 detailed, authentic customer testimonials for a service/product/business called \"$site_name\". " .
        ($custom_prompt ? "Business context: $custom_prompt. " : "") .
        "Each testimonial should have: (1) A customer name (realistic, credible first and last name); (2) A detailed, specific comment (3-4 sentences) praising concrete benefits, improvements, or remarkable results; (3) A rating word (excellent, amazing, outstanding, transformative, life-changing, etc). " .
        "Make testimonials diverse, natural-sounding, with specific details about benefits. Include metrics or specific outcomes where appropriate. " .
        "Language: $language. " .
        "Respond ONLY with valid JSON array format: [{\"name\": \"...\", \"comment\": \"...\", \"rating\": \"...\"}]. No markdown, no explanations, no other text.";

    $testimonials_response = aicg_openai_chat_request($api_key, $testimonials_prompt);
    
    // Validate and parse testimonials JSON
    $testimonials = [];
    if (!empty($testimonials_response)) {
        // Try to extract JSON from response
        $json_match = preg_match('/\[.*\]/s', $testimonials_response, $matches);
        if ($json_match && !empty($matches[0])) {
            $testimonials = json_decode($matches[0], true);
        }
        // Fallback if first attempt didn't work
        if (empty($testimonials)) {
            $testimonials = json_decode($testimonials_response, true);
        }
    }
    // Ensure we have valid testimonials array
    $testimonials = is_array($testimonials) ? array_filter($testimonials) : [];

    // 4. Get latest posts for blog section
    $latest_posts = get_posts([
        'posts_per_page' => 3,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    ]);

    // 5. Generate hero image and about image
    // Hero image should be based on the custom prompt with detailed specifications
    $hero_image_url = '';
    try {
        $hero_image_prompt = ($custom_prompt ? 
            "Create an extraordinarily stunning, cinematic hero image for: $custom_prompt. " : 
            "Create a breathtaking, professional hero image for website called $site_name. ") .
            "Requirements: High-resolution professional photography, 4K quality, cinematic lighting, modern composition, " .
            "vibrant colors, professional color grading, visually impressive, eye-catching, suitable for website banner. " .
            "Professional studio or natural lighting, beautiful composition, inspiring and engaging visual. " .
            "Include people or relevant visual elements. Modern, sleek, premium aesthetic.";
        
        $hero_image_url = aicg_generate_image_from_prompt($api_key, $hero_image_prompt, 500, 500);
        if (empty($hero_image_url)) {
            error_log('Hero image generation returned empty URL');
        }
    } catch (Exception $e) {
        error_log('Hero image generation error: ' . $e->getMessage());
    }

    $about_image_url = '';
    try {
        $about_image_prompt = ($custom_prompt ? 
            "Create a professional, high-quality business/team image related to: $custom_prompt. " : 
            "Create a professional, engaging company/team image for $site_name. ") .
            "Requirements: Professional workplace environment, modern office setting, people collaborating, " .
            "team dynamics, positive energy, high quality photography, professional lighting, realistic, " .
            "inspiring atmosphere, corporate/business aesthetic, well-composed, visually appealing, " .
            "modern workplace, collaboration, teamwork, professionalism. 4K quality photography.";
        
        $about_image_url = aicg_generate_image_from_prompt($api_key, $about_image_prompt, 500, 500);
        if (empty($about_image_url)) {
            error_log('About image generation returned empty URL');
        }
    } catch (Exception $e) {
        error_log('About image generation error: ' . $e->getMessage());
    }

    // 6. Build homepage content HTML
    $homepage_html = '';

    // Hero Section - Image and text side by side with gradient background
    $homepage_html .= '<div class="homepage-hero" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 80px 20px;">';
    $homepage_html .= '<div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center;">';
    
    // Hero Text
    $homepage_html .= '<div>';
    $homepage_html .= '<h1 style="font-size: 48px; font-weight: bold; margin: 0 0 20px 0; line-height: 1.2;">' . esc_html($hero['headline']) . '</h1>';
    $homepage_html .= '<h2 style="font-size: 24px; font-weight: 600; margin: 0 0 20px 0; opacity: 0.95;">' . esc_html($hero['subheading']) . '</h2>';
    if (!empty($hero['description'])) {
        $homepage_html .= '<p style="font-size: 16px; line-height: 1.8; margin: 0 0 30px 0; opacity: 0.9;">' . esc_html($hero['description']) . '</p>';
    }
    $homepage_html .= '<a href="#contact" style="display: inline-block; background: white; color: #667eea; padding: 14px 40px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 16px; transition: all 0.3s ease;">' . esc_html($hero['cta_text']) . '</a>';
    $homepage_html .= '</div>';
    
    // Hero Image
    if ($hero_image_url) {
        $homepage_html .= '<div>';
        $homepage_html .= '<img src="' . esc_url($hero_image_url) . '" style="width: 100%; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);" alt="' . esc_attr($site_name) . ' - Hero Image">';
        $homepage_html .= '</div>';
    }
    
    $homepage_html .= '</div></div>';

    // About Section - Image and text side by side
    $homepage_html .= '<div class="homepage-about" style="padding: 80px 20px; background: #f8f9fa;">';
    $homepage_html .= '<div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center;">';
    
    // About Image (on the left)
    if ($about_image_url) {
        $homepage_html .= '<div>';
        $homepage_html .= '<img src="' . esc_url($about_image_url) . '" style="width: 100%; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);" alt="About ' . esc_attr($site_name) . '">';
        $homepage_html .= '</div>';
    }
    
    // About Text (on the right)
    $homepage_html .= '<div>';
    $homepage_html .= '<h2 style="font-size: 40px; font-weight: bold; margin: 0 0 20px 0; color: #202124;">About ' . esc_html($site_name) . '</h2>';
    $homepage_html .= '<p style="font-size: 16px; line-height: 2; color: #555; margin: 0;">' . wp_kses_post($about_text) . '</p>';
    $homepage_html .= '</div>';
    
    $homepage_html .= '</div></div>';

    // Blog Section
    if (!empty($latest_posts)) {
        $homepage_html .= '<div class="homepage-blog" style="padding: 80px 20px; background: white;">';
        $homepage_html .= '<div style="max-width: 1200px; margin: 0 auto;">';
        $homepage_html .= '<h2 style="font-size: 40px; font-weight: bold; margin: 0 0 50px 0; text-align: center; color: #202124;">Latest Articles</h2>';
        $homepage_html .= '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px;">';
        
        foreach ($latest_posts as $post) {
            $thumbnail = get_the_post_thumbnail_url($post->ID, 'medium') ?: 'https://via.placeholder.com/300x200?text=Article';
            $homepage_html .= '<div style="border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: all 0.3s ease; cursor: pointer;">';
            $homepage_html .= '<img src="' . esc_url($thumbnail) . '" style="width: 100%; height: 220px; object-fit: cover; display: block;" alt="' . esc_attr($post->post_title) . '">';
            $homepage_html .= '<div style="padding: 25px;">';
            $homepage_html .= '<h3 style="font-size: 18px; font-weight: 700; margin: 0 0 12px 0;"><a href="' . esc_url(get_permalink($post->ID)) . '" style="color: #202124; text-decoration: none; transition: color 0.3s;">' . esc_html($post->post_title) . '</a></h3>';
            $homepage_html .= '<p style="color: #666; font-size: 14px; line-height: 1.6; margin: 0 0 16px 0;">' . wp_trim_words($post->post_excerpt ?: $post->post_content, 18) . '</p>';
            $homepage_html .= '<a href="' . esc_url(get_permalink($post->ID)) . '" style="color: #667eea; text-decoration: none; font-weight: 600; font-size: 14px; transition: color 0.3s;">Read More →</a>';
            $homepage_html .= '</div></div>';
        }
        
        $homepage_html .= '</div></div></div>';
    }

    // Testimonials Section
    if (!empty($testimonials)) {
        $homepage_html .= '<div class="homepage-testimonials" style="padding: 80px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">';
        $homepage_html .= '<div style="max-width: 1200px; margin: 0 auto;">';
        $homepage_html .= '<h2 style="font-size: 40px; font-weight: bold; margin: 0 0 50px 0; text-align: center;">What Our Customers Say</h2>';
        $homepage_html .= '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px;">';
        
        foreach ($testimonials as $testimonial) {
            $homepage_html .= '<div style="background: rgba(255,255,255,0.12); padding: 35px; border-radius: 12px; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); transition: all 0.3s ease;">';
            $homepage_html .= '<div style="font-size: 26px; margin-bottom: 18px; letter-spacing: 2px;">★★★★★</div>';
            $homepage_html .= '<p style="font-size: 16px; line-height: 1.8; margin: 0 0 24px 0; font-style: italic;">"' . esc_html($testimonial['comment'] ?? '') . '"</p>';
            $homepage_html .= '<p style="font-weight: 700; margin: 0; font-size: 15px;">— ' . esc_html($testimonial['name'] ?? 'Customer') . '</p>';
            $homepage_html .= '</div>';
        }
        
        $homepage_html .= '</div></div></div>';
    }

    // Create or update Home page
    $home_page = get_page_by_path('');
    if (!$home_page) {
        $home_page = get_page_by_title('Home');
    }

    $home_data = [
        'post_title'   => aicg_get_localized_page_title('home', $language_code),
        'post_name'    => 'home',
        'post_content' => $homepage_html,
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ];

    if ($home_page) {
        $home_data['ID'] = $home_page->ID;
        wp_update_post($home_data);
    } else {
        $home_page_id = wp_insert_post($home_data);
        // Set as front page
        if ($home_page_id) {
            update_option('page_on_front', $home_page_id);
            update_option('show_on_front', 'page');
        }
    }

    return 'Homepage generated and set as front page successfully!';
}

/**
 * Generate base.css file based on style preferences
 */
function aicg_generate_base_css() {
    $api_key = get_option('aicg_api_key');
    if (!$api_key) {
        return new WP_Error('no_api_key', 'OpenAI API key is not set.');
    }

    $site_name = get_option('aicg_site_name', get_option('blogname', 'Website'));
    $style_prompt = get_option('ang_style_prompt', '');
    
    // If no style prompt provided, use default modern styling
    if (empty($style_prompt)) {
        $style_prompt = 'Modern, clean, professional design with a light background and dark text. Use a primary color of #667eea with accent colors. Sans-serif fonts, generous spacing, and smooth transitions.';
    }

    // Create prompt for CSS generation
    $css_prompt = "Generate a comprehensive professional base.css stylesheet for a website called \"$site_name\". " .
        "Styling preferences: $style_prompt. " .
        "Requirements: " .
        "1. Include comprehensive CSS variables (--primary-color, --secondary-color, --text-color, --bg-color, shadow colors, spacing vars, etc.) " .
        "2. Full reset and normalize styles for all HTML elements " .
        "3. Detailed styles for headings (h1-h6 with varied sizing and weights), paragraphs, links with hover states, buttons (multiple variations), forms (inputs, selects, textareas), images " .
        "4. Include comprehensive utility classes for spacing (padding, margin), text alignment, display properties, positioning " .
        "5. Add fully responsive design with multiple media query breakpoints (mobile, tablet, desktop) " .
        "6. Include smooth CSS transitions, hover effects, active states, focus states " .
        "7. Add animations for common elements (fade-in, slide, scale) " .
        "8. Include print styles " .
        "9. Make it production-ready, comprehensive, well-commented, and complete " .
        "Output ONLY the CSS code, no explanations or markdown. Start directly with /* or @import or :root. Minimum 500 lines of CSS.";

    $css_content = aicg_openai_chat_request($api_key, $css_prompt);
    
    if (empty($css_content)) {
        return new WP_Error('generation_failed', 'Failed to generate CSS from OpenAI.');
    }

    // Create directory for CSS files if it doesn't exist
    $upload_dir = wp_upload_dir();
    $css_dir = $upload_dir['basedir'] . '/aicg-styles';
    
    if (!is_dir($css_dir)) {
        wp_mkdir_p($css_dir);
    }

    // Save CSS file
    $css_file = $css_dir . '/base.css';
    $result = file_put_contents($css_file, $css_content);
    
    if (!$result) {
        return new WP_Error('file_write_failed', 'Could not write CSS file to disk.');
    }

    // Save CSS file URL/path to option
    $css_url = $upload_dir['baseurl'] . '/aicg-styles/base.css';
    update_option('aicg_css_file_url', $css_url);
    update_option('aicg_css_generated_at', current_time('mysql'));

    return 'CSS file generated successfully and will be loaded on your website!';
}

/**
 * Generate Policy Pages (Privacy Policy, Terms & Conditions, Cookie Policy)
 */
function aicg_generate_policy_pages() {
    $api_key = get_option('aicg_api_key');
    if (!$api_key) {
        return new WP_Error('no_api_key', 'OpenAI API key is not set.');
    }

    $language_code = get_option('ang_language', 'en');
    $language = get_language_name_from_code($language_code);
    
    $site_name = get_option('aicg_site_name', '');
    if (empty($site_name)) {
        $site_name = get_option('blogname', 'Our Website');
    }
    
    $site_url = get_home_url();
    $admin_email = get_option('admin_email', 'admin@example.com');

    // Generate comprehensive Privacy Policy
    $privacy_prompt = "Generate a COMPREHENSIVE, DETAILED Privacy Policy document for a website called \"$site_name\" (URL: $site_url). " .
        "The policy must be legally compliant and include ALL of these detailed sections with substantial content in each: " .
        "1. Introduction/Overview (brief introduction to the policy) " .
        "2. Information We Collect (detail about personal data collection methods and types) " .
        "3. How We Use Your Information (detailed explanation of data usage) " .
        "4. Legal Basis for Processing (grounds for data processing) " .
        "5. Cookies and Similar Technologies (detailed explanation of cookies, types, and purposes) " .
        "6. Third-Party Services and Data Sharing (external services, analytics, advertising) " .
        "7. Data Retention (how long data is kept) " .
        "8. Your Rights and Choices (GDPR/privacy rights, data access, deletion requests) " .
        "9. Data Security (security measures for protecting data) " .
        "10. International Data Transfers (if applicable) " .
        "11. Contact Information ($admin_email for privacy inquiries) " .
        "12. Policy Updates (how updates will be communicated) " .
        "Language: $language. " .
        "Format: Use clear HTML format with <h2> tags for main sections and <p> tags for content. Each section should be 2-4 paragraphs minimum. " .
        "Output: ONLY valid HTML content, no markdown, no explanations. Make it comprehensive and professional.";

    $privacy_content = aicg_openai_chat_request($api_key, $privacy_prompt);
    if (empty($privacy_content)) {
        error_log('AI WP GEN - Failed to generate Privacy Policy');
        $privacy_content = '<p>Unable to generate privacy policy. Please contact administrator.</p>';
    }
    
    // Generate comprehensive Terms & Conditions
    $terms_prompt = "Generate a COMPREHENSIVE, DETAILED Terms & Conditions document for a website called \"$site_name\" (URL: $site_url). " .
        "The document must be legally sound and include ALL of these detailed sections with substantial content in each: " .
        "1. Agreement to Terms (introduction and acceptance) " .
        "2. Use License (what users can and cannot do) " .
        "3. Disclaimer of Warranties (no warranties expressed or implied) " .
        "4. Limitations of Liability (limits on damages and liability) " .
        "5. Accuracy of Materials (disclaimer about content accuracy) " .
        "6. Materials License (intellectual property and usage rights) " .
        "7. Limitations of Use (prohibited activities and misuse) " .
        "8. Disclaimer and Limitations (extended liability disclaimers) " .
        "9. Accuracy of Information (accuracy guarantees and limitations) " .
        "10. Third-Party Links (policy on linking to external sites) " .
        "11. Modifications to Terms (right to modify terms) " .
        "12. Governing Law (jurisdiction and applicable law) " .
        "13. Contact Information ($admin_email for questions) " .
        "Language: $language. " .
        "Format: Use clear HTML format with <h2> tags for main sections and <p> tags for content. Each section should be 2-4 paragraphs minimum. " .
        "Output: ONLY valid HTML content, no markdown, no explanations. Make it comprehensive and professional.";

    $terms_content = aicg_openai_chat_request($api_key, $terms_prompt);
    if (empty($terms_content)) {
        error_log('AI WP GEN - Failed to generate Terms & Conditions');
        $terms_content = '<p>Unable to generate terms and conditions. Please contact administrator.</p>';
    }
    
    // Generate comprehensive Cookie Policy
    $cookies_prompt = "Generate a COMPREHENSIVE, DETAILED Cookie Policy document for a website called \"$site_name\" (URL: $site_url). " .
        "The policy must be GDPR-compliant and include ALL of these detailed sections with substantial content in each: " .
        "1. What Are Cookies (explanation of cookies and similar technologies) " .
        "2. Types of Cookies We Use (detailed list: essential, performance, functional, advertising, analytics) " .
        "3. Essential/Necessary Cookies (cookies required for functionality) " .
        "4. Performance and Analytics Cookies (tracking and analytics details) " .
        "5. Functional Cookies (customization and preferences) " .
        "6. Advertising/Marketing Cookies (targeting and retargeting) " .
        "7. Third-Party Cookies (external services and their purposes) " .
        "8. How Long Cookies Are Stored (retention periods for different cookie types) " .
        "9. Cookie Management and Controls (how to manage cookie preferences) " .
        "10. Browser Settings (instructions for disabling cookies) " .
        "11. Consent Management (consent process and opt-out options) " .
        "12. EU/GDPR Specific Information (GDPR compliance details) " .
        "13. Changes to This Policy (how updates are communicated) " .
        "14. Contact Information ($admin_email for cookie policy questions) " .
        "Language: $language. " .
        "Format: Use clear HTML format with <h2> tags for main sections and <p> tags for content. Each section should be 2-4 paragraphs minimum. " .
        "Output: ONLY valid HTML content, no markdown, no explanations. Make it comprehensive and GDPR-compliant.";

    $cookies_content = aicg_openai_chat_request($api_key, $cookies_prompt);
    if (empty($cookies_content)) {
        error_log('AI WP GEN - Failed to generate Cookie Policy');
        $cookies_content = '<p>Unable to generate cookie policy. Please contact administrator.</p>';
    }

    // Create or update Privacy Policy page
    $privacy_page = get_page_by_title('Privacy Policy');
    $privacy_data = [
        'post_title'   => aicg_get_localized_page_title('privacy', $language_code),
        'post_name'    => 'privacy-policy',
        'post_content' => wp_kses_post($privacy_content),
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ];
    
    if ($privacy_page) {
        $privacy_data['ID'] = $privacy_page->ID;
        wp_update_post($privacy_data);
    } else {
        wp_insert_post($privacy_data);
    }

    // Create or update Terms & Conditions page
    $terms_page = get_page_by_title('Terms & Conditions');
    $terms_data = [
        'post_title'   => aicg_get_localized_page_title('terms', $language_code),
        'post_name'    => 'terms-conditions',
        'post_content' => wp_kses_post($terms_content),
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ];
    
    if ($terms_page) {
        $terms_data['ID'] = $terms_page->ID;
        wp_update_post($terms_data);
    } else {
        wp_insert_post($terms_data);
    }

    // Create or update Cookie Policy page
    $cookies_page = get_page_by_title('Cookie Policy');
    $cookies_data = [
        'post_title'   => aicg_get_localized_page_title('cookies', $language_code),
        'post_name'    => 'cookie-policy',
        'post_content' => wp_kses_post($cookies_content),
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ];
    
    if ($cookies_page) {
        $cookies_data['ID'] = $cookies_page->ID;
        wp_update_post($cookies_data);
    } else {
        wp_insert_post($cookies_data);
    }

    return 'Policy pages (Privacy Policy, Terms & Conditions, Cookie Policy) generated successfully with comprehensive content!';
}

