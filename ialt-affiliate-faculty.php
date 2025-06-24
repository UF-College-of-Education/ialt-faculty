<?php
/*
Plugin Name: IALT Affiliate Faculty 
Description: Manages affiliate faculty members 
Version: 1.7
Author: Eve
*/

// Prevent direct access to the plugin file
if (!defined('ABSPATH')) {
    exit;
}

//sort by last name
 function sort_faculty_by_last_name($a, $b) {
    $name_a = get_post_meta($a->ID, 'display-name', true);
    $name_b = get_post_meta($b->ID, 'display-name', true);
    
    $last_name_a = get_last_word($name_a);
    $last_name_b = get_last_word($name_b);
    
    return strcasecmp($last_name_a, $last_name_b);
}

function get_last_word($string) {
    $words = explode(' ', trim($string));
    return end($words);
}


// Define create_faculty_post_type() in the global scope
function create_faculty_post_type() {
    $args = array(
        'public' => true,
        'label'  => 'Faculty Members',
        'supports' => array('title'),
        'menu_icon' => 'dashicons-groups',
        'rewrite' => array('slug' => 'f'),
        'publicly_queryable' => true,
        'query_var' => true,
    );
    register_post_type('faculty', $args);
}

function faculty_management_init() {
    add_action('init', 'create_faculty_post_type');

    // Add Meta Boxes
    function add_faculty_meta_boxes() {
        add_meta_box('faculty_details', 'Faculty Details', 'faculty_details_callback', 'faculty', 'normal', 'default');
    }
    add_action('add_meta_boxes', 'add_faculty_meta_boxes');

    // Meta Box Callback
    function faculty_details_callback($post) {
        wp_nonce_field(basename(__FILE__), 'faculty_nonce');
        $faculty_meta = get_post_meta($post->ID);
        ?>
        <style>
            .faculty-meta-field { margin-bottom: 15px; }
            .faculty-meta-field label { display: block; margin-bottom: 5px; }
            .faculty-meta-field input[type="text"],
            .faculty-meta-field input[type="email"],
            .faculty-meta-field input[type="url"],
            .faculty-meta-field textarea { width: 100%; }
        </style>
        <div class="faculty-meta-field">
            <label for="display-name">Name:</label>
            <input type="text" name="display-name" id="display-name" value="<?php echo esc_attr($faculty_meta['display-name'][0] ?? ''); ?>">
        </div>
        <div class="faculty-meta-field">
            <label for="degree">Degree:</label>
            <input type="text" name="degree" id="degree" value="<?php echo esc_attr($faculty_meta['degree'][0] ?? ''); ?>">
        </div>
        <div class="faculty-meta-field">
            <label for="image">Image URL:</label>
            <input type="text" name="image" id="image" value="<?php echo esc_attr($faculty_meta['image'][0] ?? ''); ?>">
            <button type="button" class="upload-image-button">Upload Image</button>
        </div>
        <div class="faculty-meta-field">
            <label for="uf-email">UF Email:</label>
            <input type="email" name="uf-email" id="uf-email" value="<?php echo esc_attr($faculty_meta['uf-email'][0] ?? ''); ?>">
        </div>
        <div class="faculty-meta-field">
            <label for="title">Title:</label>
            <input type="text" name="title" id="title" value="<?php echo esc_attr($faculty_meta['title'][0] ?? ''); ?>">
        </div>
        <div class="faculty-meta-field">
            <label for="professional-profile-url">UF Professional Profile URL:</label>
            <input type="url" name="professional-profile-url" id="professional-profile-url" value="<?php echo esc_attr($faculty_meta['professional-profile-url'][0] ?? ''); ?>">
        </div>
        <div class="faculty-meta-field">
            <label>College:</label><br>
            <?php
            $colleges = array('None', 'College of Arts', 'College of Dentistry', 'College of Design, Construction and Planning', 'College of Education', 'Herbert Wertheim College of Engineering', 'College of Health and Human Performance', 'College of Journalism and Communications', 'College of Liberal Arts and Sciences', 'College of Medicine', 'College of Nursing', 'College of Pharmacy','College of Public Health and Health Professions', 'College of Veterinary Medicine');
            $saved_college = isset($faculty_meta['college']) ? $faculty_meta['college'][0] : '';
            foreach ($colleges as $college) {
                $checked = ($college === $saved_college) ? 'checked' : '';
                $value = ($college === 'None') ? '' : $college;
                echo '<label><input type="radio" name="college" value="' . esc_attr($value) . '" ' . $checked . '> ' . esc_html($college) . '</label><br>';
            }
            ?>
        </div>
        <div class="faculty-meta-field">
            <label for="other-affiliation">Other Affiliation:</label>
            <input type="text" name="other-affiliation" id="other-affiliation" value="<?php echo esc_attr($faculty_meta['other-affiliation'][0] ?? ''); ?>">
        </div>
        <div class="faculty-meta-field">
            <label>Research Interests:</label>
            <div id="research-interests">
                <?php
                $research_interests = isset($faculty_meta['research-interest']) ? maybe_unserialize($faculty_meta['research-interest'][0]) : array();
                if (!empty($research_interests)) {
                    foreach ($research_interests as $interest) {
                        echo '<p><input type="text" name="research-interest[]" value="' . esc_attr($interest) . '"> <button type="button" class="remove-interest">Remove</button></p>';
                    }
                }
                ?>
            </div>
            <button type="button" id="add-research-interest">Add Research Interest</button>
        </div>
        <?php
    }

    // Save Meta Box Data
    function save_faculty_meta($post_id) {
        if (!isset($_POST['faculty_nonce']) || !wp_verify_nonce($_POST['faculty_nonce'], basename(__FILE__))) {
            return $post_id;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }
        if (isset($_POST['college'])) {
            update_post_meta($post_id, 'college', sanitize_text_field($_POST['college']));
        } else {
            delete_post_meta($post_id, 'college'); //if none is selected
        }

        $fields = array('display-name', 'image', 'uf-email', 'title', 'professional-profile-url', 'degree', 'college', 'other-affiliation');
        foreach ($fields as $field) {
            if (array_key_exists($field, $_POST)) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        if (isset($_POST['research-interest'])) {
            $interests = array_filter($_POST['research-interest']); // Remove empty values
            update_post_meta($post_id, 'research-interest', $interests);
        } else {
            delete_post_meta($post_id, 'research-interest');
        }
    }
    add_action('save_post', 'save_faculty_meta');

    // Shortcode to display faculty
    function faculty_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'limit' => 10, // 10 per page
            'college' => '',
        ), $atts);
    
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    
        $args = array(
            'post_type' => 'faculty',
            'posts_per_page' => $atts['limit'],
            'paged' => $paged,
            'order' => 'ASC',
        );
    
        if (!empty($atts['id'])) {
            $args['p'] = $atts['id'];
        }
    
        if (!empty($atts['college'])) {
            $args['meta_query'] = array(
                array(
                    'key' => 'college',
                    'value' => $atts['college'],
                    'compare' => '='
                )
            );
        }
    
        $faculty_query = new WP_Query($args);
        $faculty_members = $faculty_query->posts;
    
        // Custom sorting function
        usort($faculty_members, 'sort_faculty_by_last_name');
        
        $output = '<div class="faculty-filter">
                       <select id="college-filter">
                           <option value="">All Colleges</option>
                           <option value="College of Arts">College of Arts</option>
                           <option value="College of Dentistry">College of Dentistry</option>
                           <option value="College of Design, Construction and Planning">College of Design, Construction and Planning</option>
                           <option value="College of Education">College of Education</option>
                           <option value="Herbert Wertheim College of Engineering">Herbert Wertheim College of Engineering</option>
                           <option value="College of Health and Human Performance">College of Health and Human Performance</option>
                           <option value="College of Journalism and Communications">College of Journalism and Communications</option>
                           <option value="College of Liberal Arts and Sciences">College of Liberal Arts and Sciences</option>
                           <option value="College of Medicine">College of Medicine</option>
                           <option value="College of Nursing">College of Nursing</option>
                           <option value="College of Pharmacy">College of Pharmacy</option>
                           <option value="College of Public Health and Health Professions">College of Public Health and Health Professions</option>
                           <option value="College of Veterinary Medicine">College of Veterinary Medicine</option>
                           <option value="None">Others</option>
                       </select>
                   </div>';
        $output .= '<div class="faculty-grid">';

        foreach ($faculty_members as $post){
            setup_postdata($post);
                $faculty_query->the_post();
                $display_name = get_post_meta($post->ID, 'display-name', true);
                $image = get_post_meta($post->ID, 'image', true);
                $uf_email = get_post_meta($post->ID, 'uf-email', true);
                $degree = get_post_meta($post->ID, 'degree', true);
                $college = get_post_meta($post->ID, 'college', true);
                $college = empty($college) ? 'None' : $college; //making none into others option
                $research_interests = get_post_meta($post->ID, 'research-interest', true);
                $other_affiliation = get_post_meta($post->ID, 'other-affiliation', true);

                $output .= '
                <div class="faculty-card" data-college="' . esc_attr($college) . '">
                    <img src="' . esc_url($image) . '" alt="' . esc_attr($display_name) . '">
                    <div class="faculty-content">
                    <h3>' . esc_html($display_name) . ', ' . esc_html($degree) . '</h3>
                    <div class="sd-institution">
                        <p class="college">';
    
                if (!empty($college) && $college !== 'None') {
                    $output .= esc_html($college);
                    if (!empty($other_affiliation)) {
                        $output .= ', ' . esc_html($other_affiliation);
                    }
                } elseif (!empty($other_affiliation)) {
                    $output .= esc_html($other_affiliation);
                }
    
                $output .= '</p>
                    </div>
                    <div class="sd-email"><p class="email"><a href="mailto:' . esc_attr($uf_email) . '">' . esc_html($uf_email) . '</a></p></div>
                    <div class="sd-research-area"><p class="research-interests">' . esc_html(implode(', ', $research_interests)) . '</p></div>
                    </div>
                </div>';
            
        }
        $output .= '</div>';

        // Add pagination
            $big = 999999999; // need an unlikely integer
            $pagination = paginate_links(array(
                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format' => '?paged=%#%',
                'current' => max(1, get_query_var('paged')),
                'total' => $faculty_query->max_num_pages,
                'prev_text' => __('&laquo; Previous'),
                'next_text' => __('Next &raquo;'),
            ));

            if ($pagination) {
                $output .= '<div class="faculty-pagination">' . $pagination . '</div>';
            }

        wp_reset_postdata();
        return $output;
    }
    add_shortcode('faculty', 'faculty_shortcode');


    // Customizer options
    function faculty_customize_register($wp_customize) {
        $wp_customize->add_section('faculty_styles', array(
            'title' => __('Faculty Styles', 'faculty-management'),
            'priority' => 30,
        ));

        $settings = array(
            'faculty_card_border_color' => array(
                'default' => '#ddd',
                'type' => 'color',
                'label' => __('Card Border Color', 'faculty-management'),
            ),
            'faculty_card_width' => array(
                'default' => '300',
                'type' => 'number',
                'label' => __('Card Width (px)', 'faculty-management'),
            ),
            'faculty_name_color' => array(
                'default' => '#2e8540',
                'type' => 'color',
                'label' => __('Name Color', 'faculty-management'),
            ),
            'faculty_college_color' => array(
                'default' => '#666',
                'type' => 'color',
                'label' => __('College Text Color', 'faculty-management'),
            ),
        );

        foreach ($settings as $setting_key => $setting_data) {
            $wp_customize->add_setting($setting_key, array(
                'default' => $setting_data['default'],
                'sanitize_callback' => $setting_data['type'] === 'color' ? 'sanitize_hex_color' : 'absint',
            ));

            if ($setting_data['type'] === 'color') {
                $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, $setting_key, array(
                    'label' => $setting_data['label'],
                    'section' => 'faculty_styles',
                )));
            } else {
                $wp_customize->add_control($setting_key, array(
                    'type' => $setting_data['type'],
                    'label' => $setting_data['label'],
                    'section' => 'faculty_styles',
                ));
            }
        }
    }
    add_action('customize_register', 'faculty_customize_register');

    // Random string generation for URLs
    function generate_random_string($length = 10) {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }

    // Set random post name
    function set_faculty_random_post_name($post_id) {
        $post = get_post($post_id);
        if ($post->post_type == 'faculty' && empty($post->post_name)) {
            $random_string = generate_random_string();
            wp_update_post(array(
                'ID' => $post_id,
                'post_name' => $random_string
            ));
        }
    }
    add_action('save_post_faculty', 'set_faculty_random_post_name');

    // Modify permalink structure
    function faculty_custom_post_link($post_link, $post) {
        if ($post->post_type === 'faculty') {
            return home_url("f/{$post->post_name}/");
        }
        return $post_link;
    }
    add_filter('post_type_link', 'faculty_custom_post_link', 10, 2);

    // Enqueue scripts for admin
    function enqueue_faculty_admin_scripts() {
        global $post_type;
        if ('faculty' == $post_type) {
            wp_enqueue_media();
            wp_enqueue_script('faculty-admin-script', plugins_url('/faculty-admin-script.js', __FILE__), array('jquery'), '1.0', true);
        }
    }
    add_action('admin_enqueue_scripts', 'enqueue_faculty_admin_scripts');

    // Enqueue scripts for frontend
    function enqueue_faculty_scripts() {
        wp_enqueue_script('faculty-filter-script', plugins_url('/faculty-filter-script.js', __FILE__), array('jquery'), '1.0', true);
    }
    add_action('wp_enqueue_scripts', 'enqueue_faculty_scripts');
}


    // Run the initialization function
    add_action('plugins_loaded', 'faculty_management_init');


// Flush rewrite rules on activation
function faculty_rewrite_flush() {
    // Register the post type
    $args = array(
        'public' => true,
        'label'  => 'Faculty Members',
        'supports' => array('title'),
        'menu_icon' => 'dashicons-groups',
        'rewrite' => array('slug' => 'f'),
        'publicly_queryable' => true,
        'query_var' => true,
    );
    register_post_type('faculty', $args);

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'faculty_rewrite_flush');


