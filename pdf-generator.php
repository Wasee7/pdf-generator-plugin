<?php
/**
 * Plugin Name: PDF Generator
 * Description: Generate a PDF after a specific Contact Form 7 form submission.
 * Version: 0.3
 * Author: Waseem Akram
 */

if (!class_exists('FPDF')) {
    require_once plugin_dir_path(__FILE__) . 'fpdf/fpdf.php';
}

define('TARGET_CF7_FORM_TITLE', 'Report');

add_action('wpcf7_mail_sent', 'cf7_generate_pdf_after_submission_specific_form');
function cf7_generate_pdf_after_submission_specific_form($contact_form) {
    // Check the form title
    $form_title = $contact_form->title();
    if ($form_title !== TARGET_CF7_FORM_TITLE) {
        return;
    }

    // The rest of the logic will come later
}
