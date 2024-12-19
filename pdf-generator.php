<?php
/**
 * Plugin Name: PDF Generator
 * Description: Generate a PDF after a specific Contact Form 7 form submission.
 * Version: 1.0
 * Author: Waseem Akram
 */

if (!class_exists('FPDF')) {
    require_once plugin_dir_path(__FILE__) . 'fpdf/fpdf.php';
}

define('TARGET_CF7_FORM_TITLE', 'Report');

add_action('wpcf7_mail_sent', 'cf7_generate_pdf_after_submission_specific_form');
function cf7_generate_pdf_after_submission_specific_form($contact_form) {
    $form_title = $contact_form->title();
    if ($form_title !== TARGET_CF7_FORM_TITLE) {
        return;
    }

    $submission = WPCF7_Submission::get_instance();
    if (!$submission) {
        return;
    }

    $data = $submission->get_posted_data();

    $income_sources = 'N/A';
    if (!empty($data['income-sources'])) {
        $income_sources = is_array($data['income-sources']) ? implode(', ', $data['income-sources']) : $data['income-sources'];
    }

    $landlord_type = 'N/A';
    if (!empty($data['landlord-type'])) {
        $landlord_type = is_array($data['landlord-type']) ? implode(', ', $data['landlord-type']) : $data['landlord-type'];
    }

    $rent_frequency = 'N/A';
    if (!empty($data['rent-frequency'])) {
        $rent_frequency = is_array($data['rent-frequency']) ? implode(', ', $data['rent-frequency']) : $data['rent-frequency'];
    }

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 18);

    $name = utf8_decode($data['full-name'] ?? 'N/A');
    $pdf->Cell(0, 10, $name, 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial', '', 14);
    $pdf->Cell(0, 10, utf8_decode('Details:'), 0, 1);
    $pdf->Ln(2);

    $fields = [
        utf8_decode('Name:') => utf8_decode($data['full-name'] ?? 'N/A'),
        utf8_decode('Date of Birth:') => utf8_decode($data['dob'] ?? 'N/A'),
        utf8_decode('Email Address:') => utf8_decode($data['email'] ?? 'N/A'),
        utf8_decode('Address of Property:') => utf8_decode($data['postcode'] ?? 'N/A'),
        utf8_decode('Type of Landlord:') => utf8_decode($landlord_type),
        utf8_decode('Amount of Rent Owed (£):') => utf8_decode($data['rent-arrears'] ?? 'N/A'),
        utf8_decode('Rent Payment Frequency:') => utf8_decode($rent_frequency),
        utf8_decode('Income Sources:') => utf8_decode($income_sources),
        utf8_decode('Approx. Total Monthly Income (£):') => utf8_decode($data['income-amount'] ?? 'N/A'),
        utf8_decode('How long have you lived in your home:') => utf8_decode($data['residence-duration'] ?? 'N/A'),
        utf8_decode('Who lives in your home with you:') => utf8_decode($data['household-members'] ?? 'N/A'),
        utf8_decode('Does anyone in your household have significant physical or mental health problems:') => utf8_decode($data['health-issues'] ?? 'N/A'),
        utf8_decode('Details of any disrepair issues in your home:') => utf8_decode($data['disrepair'] ?? 'N/A'),
    ];

    foreach ($fields as $label => $value) {
        $pdf->MultiCell(0, 10, $label . ' ' . $value);
        $pdf->Ln(2);
    }

    $upload_dir = wp_upload_dir();
    $pdf_file_path = $upload_dir['path'] . '/housing_help_submission_' . time() . '.pdf';
    $pdf->Output('F', $pdf_file_path);

    if (!file_exists($pdf_file_path)) {
        error_log("PDF not created at $pdf_file_path");
        return;
    }

    $option_key = 'cf7_pdf_file_path_' . md5(TARGET_CF7_FORM_TITLE);
    update_option($option_key, $pdf_file_path);
}

// Add JS only if we have a PDF for the target form
add_action('wp_footer', 'cf7_pdf_download_script_specific_form');
function cf7_pdf_download_script_specific_form() {
    $option_key = 'cf7_pdf_file_path_' . md5(TARGET_CF7_FORM_TITLE);
    $pdf_file_path = get_option($option_key);
    if ($pdf_file_path) {
        ?>
        <script>
        document.addEventListener('wpcf7submit', function(event) {
            var formIdentifier = '';
            event.detail.inputs.forEach(function(input) {
                if (input.name === 'form-identifier') {
                    formIdentifier = input.value;
                }
            });

            if (formIdentifier === 'report-form' && event.detail.status === 'mail_sent') {
                window.location.href = "<?php echo admin_url('admin-ajax.php?action=download_pdf_specific_form'); ?>";
            }
        }, false);
        </script>
        <?php
    }
}

add_action('wp_ajax_download_pdf_specific_form', 'cf7_download_pdf_via_ajax_specific_form');
add_action('wp_ajax_nopriv_download_pdf_specific_form', 'cf7_download_pdf_via_ajax_specific_form');
function cf7_download_pdf_via_ajax_specific_form() {
    $option_key = 'cf7_pdf_file_path_' . md5(TARGET_CF7_FORM_TITLE);
    $pdf_file_path = get_option($option_key);
    if (!$pdf_file_path || !file_exists($pdf_file_path)) {
        wp_die('PDF file not found.');
    }

    delete_option($option_key);

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($pdf_file_path).'"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($pdf_file_path));
    readfile($pdf_file_path);
    exit;
}
