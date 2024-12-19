<?php
/**
 * Plugin Name: PDF Generator
 * Description: Generate a PDF after a specific Contact Form 7 form submission.
 * Version: 0.2
 * Author: Waseem Akram
 */

if (!class_exists('FPDF')) {
    require_once plugin_dir_path(__FILE__) . 'fpdf/fpdf.php';
}

// The form title we are targeting
define('TARGET_CF7_FORM_TITLE', 'Report');
