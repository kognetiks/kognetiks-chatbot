<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Reporting Page
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It handles the reporting settings and other parameters.
 *
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

// Reporting section callback - Ver 1.6.3
function chatbot_chatgpt_reporting_section_callback($args) {
    ?>
    <div>
        <h3>Visitor Interactions</h3>        
        <p><?php echo do_shortcode('[chatbot_chatgpt_simple_chart from_database="true"]'); ?></p>
    </div>
    <?php
}

// Knowledge Navigator Analysis section callback - Ver 1.6.2
function chatbot_chatgpt_reporting_period_callback($args) {
    // Get the saved chatbot_chatgpt_reporting_period value or default to "Daily"
    $output_choice = esc_attr(get_option('chatbot_chatgpt_reporting_period', 'Daily'));
    // DIAG - Log the output choice
    // chatbot_chatgpt_back_trace( 'NOTICE', 'chatbot_chatgpt_reporting_period' . $output_choice);
    ?>
    <select id="chatbot_chatgpt_reporting_period" name="chatbot_chatgpt_reporting_period">
        <option value="<?php echo esc_attr( 'Daily' ); ?>" <?php selected( $output_choice, 'Daily' ); ?>><?php echo esc_html( 'Daily' ); ?></option>
        <!-- <option value="<?php echo esc_attr( 'Weekly' ); ?>" <?php selected( $output_choice, 'Weekly' ); ?>><?php echo esc_html( 'Weekly' ); ?></option> -->
        <option value="<?php echo esc_attr( 'Monthly' ); ?>" <?php selected( $output_choice, 'Monthly' ); ?>><?php echo esc_html( 'Monthly' ); ?></option>
        <option value="<?php echo esc_attr( 'Yearly' ); ?>" <?php selected( $output_choice, 'Yearly' ); ?>><?php echo esc_html( 'Yearly' ); ?></option>
    </select>
    <?php
}


// Chatbot ChatGPT Simple Chart - Ver 1.6.3
function generate_gd_bar_chart($labels, $data, $colors, $name) {
    // Create an image
    $width = 500;
    $height = 300;
    $image = imagecreatetruecolor($width, $height);

    // Allocate colors
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $light_blue = imagecolorallocate($image, 173, 216, 230); // Light Blue color

    // Fill the background
    imagefill($image, 0, 0, $white);

    // Add title
    $title = "Visitor Interactions";
    $font = 5;
    $title_x = ($width - imagefontwidth($font) * strlen($title)) / 2;
    $title_y = 5;
    imagestring($image, $font, $title_x, $title_y, $title, $black);

    // Calculate number of bars and bar width
    $bar_count = count($data);
    // $bar_width = (int)($width / ($bar_count * 2));
    $bar_width = round($width / ($bar_count * 2));

    // Offset for the chart
    $offset_x = 25;
    $offset_y = 25;
    $top_padding = 5;

    // Bottom line
    imageline($image, 0, $height - $offset_y, $width, $height - $offset_y, $black);

    // Font size for data and labels
    $font_size = 8;

    // Draw bars
    $chart_title_height = 30; // adjust this to the height of your chart title
    for ($i = 0; $i < $bar_count; $i++) {
        $bar_height = (int)(($data[$i] * ($height - $offset_y - $top_padding - $chart_title_height)) / max($data));
        $x1 = $i * $bar_width * 2 + $offset_x;
        $y1 = $height - $bar_height - $offset_y + $top_padding;
        $x2 = ($i * $bar_width * 2) + $bar_width + $offset_x;
        $y2 = $height - $offset_y;

        // Draw a bar
        imagefilledrectangle($image, $x1, $y1, $x2, $y2, $light_blue);

        // Draw data and labels
        $center_x = $x1 + ($bar_width / 2);
        $data_value_x = $center_x - (imagefontwidth($font_size) * strlen($data[$i]) / 2);
        $data_value_y = $y1 - 15;
        $data_value_y = $data_value_y < 0 ? 0 : $data_value_y;

        // Draw a bar
        imagefilledrectangle($image, $x1, $y1, $x2, $y2, $light_blue);

        // Draw data and labels
        $center_x = round($x1 + ($bar_width / 2));

        $data_value_x = $center_x - (imagefontwidth(round($font_size)) * strlen($data[$i]) / 2);
        $label_x = $center_x - (imagefontwidth(round($font_size)) * strlen($labels[$i]) / 2);

        $data_value_y = $y1 - 5; // Moves the counts up or down
        $data_value_y = $data_value_y < 0 ? 0 : $data_value_y;

        // Fix: Explicitly cast to int
        $data_value_x = (int)($data_value_x);
        $data_value_y = (int)($data_value_y);

        // https://fonts.google.com/specimen/Roboto - Ver 1.6.7
        $fontFile = plugin_dir_path(__FILE__) . '../assets/fonts/roboto/Roboto-Black.ttf'; // Replace this with the path to your font file

        imagettftext($image, $font_size, 0, $data_value_x, $data_value_y, $black, $fontFile, $data[$i]);

        $label_x = $center_x - ($font_size * strlen($labels[$i]) / 2) + 7; // Moves the dates left or right
        $label_y = $height - $offset_y + 15; // Moves the dates up or down

        imagettftext($image, $font_size, 0, $label_x, $label_y, $black, $fontFile, $labels[$i]);

    }

    // Save the image
    $img_path = plugin_dir_path(__FILE__) . '../assets/images/' . $name . '.png';
    imagepng($image, $img_path);

    // Free memory
    imagedestroy($image);

    return $img_path;
}


// Chatbot ChatGPT Charts - Ver 1.6.3
function chatbot_chatgpt_simple_chart_shortcode_function( $atts ) {

    // Check is GD Library is installed - Ver 1.6.3
    if (extension_loaded('gd')) {
        // DIAG - Diagnostic - Ver 1.6.3
        // echo '<p>ALERT: GD Library is installed and loaded!</p>';
    } else {
        echo '<p>ALERT: GD Library is not installed! No chart will be displayed.</p>';
        return;
    }

    // Retrieve the reporting period
    $reporting_period = get_option('chatbot_chatgpt_reporting_period');

    // Parsing shortcode attributes
    $a = shortcode_atts( array(
        'name' => 'visitorsChart_' . rand(100, 999),
        'type' => 'bar',
        'labels' => 'label',
        ), $atts );

    if(isset($atts['from_database']) && $atts['from_database'] == 'true') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_chatgpt_interactions';
        
        // Get the reporting period from the options
        $reporting_period = get_option('chatbot_chatgpt_reporting_period');
        
        // Calculate the start date and group by clause based on the reporting period
        if($reporting_period === 'Daily') {
            $start_date = date('Y-m-d', strtotime("-7 days"));
            // $group_by = "DATE_FORMAT(date, '%Y-%m-%d')";
            $group_by = "DATE_FORMAT(date, '%m-%d')";
        } elseif($reporting_period === 'Monthly') {
            $start_date = date('Y-m-01', strtotime("-3 months"));
            $group_by = "DATE_FORMAT(date, '%Y-%m')";
        } else {
            $start_date = date('Y-01-01', strtotime("-3 years"));
            $group_by = "DATE_FORMAT(date, '%Y')";
        }
        
        // Modify the SQL query to group the results based on the reporting period
        $results = $wpdb->get_results("SELECT $group_by AS date, SUM(count) AS count FROM $table_name WHERE date >= '$start_date' GROUP BY $group_by");

        if(!empty($results)) {
            $labels = [];
            $data = [];
            foreach ($results as $result) {
                $labels[] = $result->date;
                $data[] = $result->count;
            }
            
            $a['labels'] = $labels;
            $atts['data'] = $data;
        }
    }

    if (empty( $a['labels']) || empty($atts['data'])) {
        return '<p>You need to specify both the labels and data for the chart to work.</p>';
    }

    // Generate the chart
    $img_path = generate_gd_bar_chart($a['labels'], $atts['data'], isset($atts['color']) ? $atts['color'] : null, $a['name']);
    $img_url = plugin_dir_url(__FILE__) . '../assets/images/' . $a['name'] . '.png';

    wp_schedule_single_event(time() + 60, 'chatbot_chatgpt_delete_chart', array($img_path)); // 60 seconds delay

    return '<img src="' . $img_url . '" alt="Bar Chart">';
}

// Add shortcode
add_shortcode('chatbot_chatgpt_simple_chart', 'chatbot_chatgpt_simple_chart_shortcode_function');


// Clean up ../image subdirectory - Ver 1.6.3
function chatbot_chatgpt_delete_chart() {
    $img_dir_path = plugin_dir_path(__FILE__) . '../assets/images/'; // Replace with your actual directory path
    $png_files = glob($img_dir_path . '*.png'); // Search for .png files in the directory

    foreach ($png_files as $png_file) {
        unlink($png_file); // Delete each .png file
    }
}
add_action('chatbot_chatgpt_delete_chart', 'chatbot_chatgpt_delete_chart');

