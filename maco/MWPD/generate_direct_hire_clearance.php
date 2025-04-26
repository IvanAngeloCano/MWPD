<?php
require_once 'vendor/autoload.php'; 
require_once 'connection.php';

use PhpOffice\PhpWord\TemplateProcessor;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['record_id'])) {
    $record_id = (int)$_GET['record_id'];
    if ($record_id <= 0) {
        die('Invalid record ID');
    }
    try {
        // Get record details
        $stmt = $pdo->prepare("SELECT * FROM direct_hire WHERE id = ?");
        $stmt->execute([$record_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            die('Record not found');
        }
        $template = new TemplateProcessor('Directhireclearance.docx');
        $template->setValue('control_no', $record['control_no']);
        $template->setValue('name', $record['name']);
        $template->setValue('jobsite', $record['jobsite']);
        $template->setValue('type', ucfirst($record['type']));
        $template->setValue('status', ucfirst($record['status']));
        $template->setValue('evaluator', $record['evaluator'] ?? 'Not assigned');
        $template->setValue('evaluated', !empty($record['evaluated']) ? date('F j, Y', strtotime($record['evaluated'])) : 'Not set');
        $template->setValue('for_confirmation', !empty($record['for_confirmation']) ? date('F j, Y', strtotime($record['for_confirmation'])) : 'Not set');
        $template->setValue('emailed_to_dhad', !empty($record['emailed_to_dhad']) ? date('F j, Y', strtotime($record['emailed_to_dhad'])) : 'Not set');
        $template->setValue('received_from_dhad', !empty($record['received_from_dhad']) ? date('F j, Y', strtotime($record['received_from_dhad'])) : 'Not set');
        $template->setValue('current_date', date('F j, Y'));
        $template->setValue('comments', !empty($record['note']) ? $record['note'] : 'No additional comments.');
        $isApproved = ($record['status'] === 'approved');
        if ($isApproved) {
            $signatureFile = 'signatures/Signature.png';
            if (file_exists($signatureFile)) {
                // Clear any existing value
                $template->setValue('signature1_image', '');
                
                // Use absolute path for better compatibility
                $signaturePath = realpath($signatureFile);
                
                try {
                    $template->setImageValue('signature1_image', [
                        'path' => $signaturePath,
                        'width' => 150,
                        'height' => 75,
                        'ratio' => false
                    ]);
                } catch (Exception $e) {
                    // Fallback if image insertion fails
                    $template->setValue('signature1_image', '✓ APPROVED');
                    error_log('Error inserting signature: ' . $e->getMessage());
                }
            } else {
                $template->setValue('signature1_image', '✓ APPROVED');
                error_log('Signature file not found: ' . realpath(dirname(__FILE__)) . '/' . $signatureFile);
            }
        } else {
            $template->setValue('signature1_image', '');
        }
        // Generate unique temp file names
        $docName = 'Clearance_' . $record['control_no'] . '_' . date('Ymd_His');
        $tempDocx = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $docName . '.docx';
        $tempPdf = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $docName . '.pdf';
        $template->saveAs($tempDocx);
        // Find LibreOffice
        $libreOfficePath = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
        $possiblePaths = [
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files\\LibreOffice 7\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice 7\\program\\soffice.exe',
            'C:\\xampp\\LibreOffice\\program\\soffice.exe'
        ];
        if (!file_exists($libreOfficePath)) {
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $libreOfficePath = $path;
                    break;
                }
            }
        }
        // Convert DOCX to PDF
        $command = '"' . $libreOfficePath . '" --headless --convert-to pdf --outdir "' . sys_get_temp_dir() . '" "' . $tempDocx . '"';
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
        // Serve PDF if conversion succeeded
        if ($returnVar === 0 && file_exists($tempPdf)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $docName . '.pdf"');
            header('Content-Length: ' . filesize($tempPdf));
            readfile($tempPdf);
            unlink($tempPdf);
            if (file_exists($tempDocx)) unlink($tempDocx);
            exit;
        } else {
            // Serve DOCX if PDF conversion failed
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: inline; filename="' . $docName . '.docx"');
            header('Content-Length: ' . filesize($tempDocx));
            readfile($tempDocx);
            if (file_exists($tempDocx)) unlink($tempDocx);
            exit;
        }
    } catch (Exception $e) {
        die('Error: ' . $e->getMessage());
    }
} else {
    die('Invalid request.');
}
?>
