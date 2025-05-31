<?php
require __DIR__ . '/../vendor/autoload.php';

use Sohagsrz\ResumeParser\ResumeParser;

// Path to your sample resume PDF
$pdf_path = __DIR__ . '/resume.pdf';

if (!file_exists($pdf_path)) {
    echo "Sample resume PDF not found at: $pdf_path\n";
    echo "Please place a PDF file named 'resume.pdf' in the example directory.\n";
    exit(1);
}

$result = ResumeParser::parse($pdf_path);
echo json_encode($result, JSON_PRETTY_PRINT); 