<?php
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

try {
    $template = new TemplateProcessor('Directhireclearance.docx');
    echo "Placeholders found in the template:\n";
    print_r($template->getVariables());
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
