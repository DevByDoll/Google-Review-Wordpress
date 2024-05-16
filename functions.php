<?php
function dollies_enqueue_styles() {
    wp_register_style('dollies-style', plugins_url('style.css', __FILE__));
    wp_enqueue_style('dollies-style');
}
add_action('wp_enqueue_scripts', 'dollies_enqueue_styles');
// Admin-Menü
if (!function_exists('dollies_google_reviews_admin_menu')) {
    function dollies_google_reviews_admin_menu() {
        add_menu_page('Google Reviews Setup', 'Google Reviews', 'manage_options', 'google-reviews-setup', 'dollies_google_reviews_setup_page', 'dashicons-admin-generic', 3);
        add_submenu_page('google-reviews-setup', 'API Settings', 'API Settings', 'manage_options', 'google-reviews-api-settings', 'dollies_google_reviews_api_settings_page');
        add_submenu_page('google-reviews-setup', 'Manage Reviews', 'Manage Reviews', 'manage_options', 'google-reviews-manage', 'dollies_google_reviews_manage_page');
    }
}

if (!function_exists('dollies_google_reviews_setup_page')) {
    function dollies_google_reviews_setup_page() {
        echo '<div class="wrap"><h2>Google Places API Setup</h2>';
        echo '<p>Um die Google Places API zu nutzen, folgen Sie bitte diesen Schritten:</p>';
        echo '<ol>';
        echo '<li>Erstellen Sie ein Projekt in der <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>.</li>';
        echo '<li>Aktivieren Sie die Places API in Ihrem Projekt.</li>';
        echo '<li>Erstellen Sie API-Schlüssel für Ihre Website:</li>';
        echo '<ul>';
        echo '<li>Gehen Sie zu "APIs & Services" > "Anmeldedaten".</li>';
        echo '<li>Klicken Sie auf "Anmeldedaten erstellen" und wählen Sie "API-Schlüssel".</li>';
        echo '<li>Kopieren Sie den erstellten API-Schlüssel.</li>';
        echo '<li>Fügen Sie den API-Schlüssel im Admin-Bereich unter "API Settings" Ihres WordPress ein.</li>';
        echo '</ul>';
        echo '<li>Suchen Sie die Place ID für Ihren Standort:</li>';
        echo '<ul>';
        echo '<li>Gehen Sie zu <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank">Place ID Finder</a>.</li>';
        echo '<li>Geben Sie den Namen Ihres Unternehmens ein und kopieren Sie die Place ID.</li>';
        echo '<li>Fügen Sie die Place ID im Admin-Bereich unter "API Settings" Ihres WordPress ein.</li>';
        echo '</ul>';
        echo '</ol>';
        echo '<p>Für weitere Funktionen und Unterstützung besuchen Sie unsere Dokumentationsseite auf <a href="https://by-doll.online/support">by-doll.online/support</a>.</p>';
        echo '</div>';
    }
}

function dollies_google_reviews_admin_init() {
    register_setting('google_reviews_options', 'google_reviews_options');

    add_settings_section('api_settings', 'API Settings', null, 'google-reviews-api-settings');

    add_settings_field('api_key', 'API Schlüssel', 'dollies_google_reviews_api_key_field', 'google-reviews-api-settings', 'api_settings');
    add_settings_field('place_id', 'Place ID', 'dollies_google_reviews_place_id_field', 'google-reviews-api-settings', 'api_settings');
}
add_action('admin_init', 'dollies_google_reviews_admin_init');

function dollies_google_reviews_api_key_field() {
    $options = get_option('google_reviews_options', []);
    $api_key = $options['api_key'] ?? '';
    echo '<input type="text" name="google_reviews_options[api_key]" value="' . esc_attr($api_key) . '" />';
}

function dollies_google_reviews_place_id_field() {
    $options = get_option('google_reviews_options', []);
    $place_id = $options['place_id'] ?? '';
    echo '<input type="text" name="google_reviews_options[place_id]" value="' . esc_attr($place_id) . '" />';
}

function dollies_get_all_reviews() {
    $options = get_option('google_reviews_options', []);
    $api_key = $options['api_key'] ?? '';
    $place_id = $options['place_id'] ?? '';

    $url = "https://maps.googleapis.com/maps/api/place/details/json?placeid={$place_id}&key={$api_key}&language=de";

    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        error_log('Error retrieving place details: ' . $response->get_error_message());
        return [];
    }

    $place_details = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($place_details['result']['reviews'])) {
        return $place_details['result']['reviews'];
    }

    return [];
}

function dollies_get_reviews($place_id, $api_key) {
    $reviews_endpoint = "https://maps.googleapis.com/maps/api/place/details/json?placeid={$place_id}&fields=review&key={$api_key}";

    $response = wp_remote_get($reviews_endpoint, ['timeout' => 45]);

    if (is_wp_error($response)) {
        error_log('Error retrieving reviews: ' . $response->get_error_message());
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if ($response_code !== 200) {
        error_log('Failed to retrieve reviews, response code: ' . $response_code);
        error_log('Response body: ' . $response_body);
        return false;
    }

    $data = json_decode($response_body, true);

    if (isset($data['result']['reviews'])) {
        return $data['result']['reviews'];
    }

    error_log('Reviews not found in the response.');
    return false;
}

function dollies_google_reviews_api_settings_page() {
    $options = get_option('google_reviews_options', []);
    $api_key = $options['api_key'] ?? '';
    $place_id = $options['place_id'] ?? '';

    echo '<div class="wrap">';
    echo '<h2>Google Places API Einstellungen</h2>';
    echo '<form method="post" action="options.php">';
    settings_fields('google_reviews_options');
    echo '<table class="form-table">';
    echo '<tr valign="top">';
    echo '<th scope="row">API Schlüssel</th>';
    echo '<td><input type="text" name="google_reviews_options[api_key]" value="' . esc_attr($api_key) . '" /></td>';
    echo '</tr>';
    echo '<tr valign="top">';
    echo '<th scope="row">Place ID</th>';
    echo '<td><input type="text" name="google_reviews_options[place_id]" value="' . esc_attr($place_id) . '" /></td>';
    echo '</tr>';
    echo '</table>';
    submit_button();
    echo '</form>';
    echo '</div>';
}
function dollies_check_google_reviews_settings_action() {
    $options = get_option('google_reviews_options', []);
    if (empty($options['api_key']) || empty($options['place_id'])) {
        add_action('admin_notices', 'dollies_google_reviews_missing_settings_notice');
    }
}

function dollies_google_reviews_missing_settings_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Google Reviews: Bitte füllen Sie die API-Schlüssel und Place ID in den Einstellungen aus.', 'dollies-google-tools'); ?></p>
    </div>
    <?php
}

function dollies_display_google_reviews_shortcode() {
    $reviews = dollies_get_all_reviews();
    $output = '<div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">';
    $output .= '<div class="carousel-inner">';
    if (is_array($reviews) && !empty($reviews)) {
        foreach ($reviews as $index => $review) {
            $activeClass = $index === 0 ? ' active' : '';
            $output .= '<div class="carousel-item' . $activeClass . '">';
            $output .= '<div class="row justify-content-center">';
            $output .= '<div class="card col-12 col-sm-8 col-md-6 col-lg-4" style="width: 18rem;">';
            $output .= '<div class="card-body">';
            $output .= '<h5 class="card-title">' . esc_html($review['author_name']) . '</h5>';
            $output .= '<div class="star-rating mt-0 card-subtitle ">';
            for ($i = 1; $i <= 5; $i++) {
                if ($i <= intval($review['rating'])) {
                    $output .= '<span class="star filled">&#9733;</span>';
                } else {
                    $output .= '<span class="star">&#9734;</span>';
                }
            }
            $output .= '<h6 class="mb-2 text-muted">' . intval($review['rating']) . ' Sterne</h6>';
            $output .= '</div>'; // end star-rating
            $output .= '<p class="card-text">' . esc_html($review['text']) . '</p>';
            $output .= '<p class="card-text"><small class="text-muted">' . esc_html($review['relative_time_description']) . '</small></p>';
            $output .= '</div>'; 
            $output .= '</div>'; 
            $output .= '</div>'; 
            $output .= '</div>'; 
        }
    } else {
        $output .= '<p>No reviews found or error retrieving reviews.</p>';
    }
    $place_id = get_option('google_reviews_options')['place_id'];
    $output .= '</div>'; 
    $output .= '<button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">';
    $output .= '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
    $output .= '<span class="visually-hidden">Previous</span>';
    $output .= '</button>';
    $output .= '<button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">';
    $output .= '<span class="carousel-control-next-icon" aria-hidden="true"></span>';
    $output .= '<span class="visually-hidden">Next</span>';
    $output .= '</button>';
    $output .= '</div>'; 

    $output .= '<div class="text-center mt-5">';
    $output .= '<h5>Ich würde mich auch über deine Rezension freuen.</h5>';
    $output .= '<p><a class="btn btn-success" href="https://search.google.com/local/writereview?placeid=' . $place_id . '" target="_blank">Jetzt schreiben</a></p>';
    $output .= '</div>'; 

    return $output;
}

function dollies_update_review_reply($review_name, $reply_text) {
    $options = get_option('google_reviews_options');
    if (empty($options['access_token'])) {
        return false;
    }

    $access_token = $options['access_token'];
    $reply_endpoint = "https://mybusiness.googleapis.com/v4/{$review_name}/reply";

    $response = wp_remote_request($reply_endpoint, [
        'method' => 'PUT',
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode(['comment' => $reply_text]),
        'timeout' => 45
    ]);

    if (is_wp_error($response)) {
        error_log('Error updating review reply: ' . $response->get_error_message());
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if ($response_code !== 200) {
        error_log('Failed to update review reply, response code: ' . $response_code);
        error_log('Response body: ' . $response_body);
        return false;
    }

    return json_decode($response_body, true);
}

function dollies_delete_review_reply($review_name) {
    $options = get_option('google_reviews_options');
    if (empty($options['access_token'])) {
        return false;
    }

    $access_token = $options['access_token'];
    $delete_endpoint = "https://mybusiness.googleapis.com/v4/{$review_name}/reply";

    $response = wp_remote_request($delete_endpoint, [
        'method' => 'DELETE',
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token
        ],
        'timeout' => 45
    ]);

    if (is_wp_error($response)) {
        error_log('Error deleting review reply: ' . $response->get_error_message());
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if ($response_code !== 200) {
        error_log('Failed to delete review reply, response code: ' . $response_code);
        error_log('Response body: ' . $response_body);
        return false;
    }

    return true;
}

function dollies_google_reviews_manage_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    ob_start();

    echo '<div class="wrap">';
    echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
    echo '<p>Comming soon...</p>';
    echo '<h2>Shortcodes</h2>';
    echo '<p>Verwenden Sie volgenden Shortcode, um die Google Bewertungen anzuzeigen: <code>[display_google_reviews]</code></p>';
    echo '</div>';
    echo ob_get_clean();
}

add_action('admin_menu', 'dollies_google_reviews_admin_menu');
add_shortcode('display_google_reviews', 'dollies_display_google_reviews_shortcode');
add_action('admin_init', 'dollies_check_google_reviews_settings_action');