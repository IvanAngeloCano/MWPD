<?php
// This script will directly update the signature handling in the generate_direct_hire_clearance.php file

// Define the path to the signature file
$signatureFile = 'signatures/Signature.png';

// Check if the signature file exists
if (file_exists($signatureFile)) {
    echo "Found signature file: $signatureFile\n";
    
    // Update the generate_direct_hire_clearance.php file to use this signature directly
    $filePath = 'generate_direct_hire_clearance.php';
    $fileContent = file_get_contents($filePath);
    
    if ($fileContent !== false) {
        // Replace the signature handling code with direct file reference
        $searchCode = "// Add e-signature placeholders\n        for (\$i = 1; \$i <= 3; \$i++) {\n            \$signatory = isset(\$signatories[\$i-1]) ? \$signatories[\$i-1] : \$default_signatories[\$i-1];\n            \n            \$template->setValue(\"signature{\$i}_name\", \$signatory['name']);\n            \$template->setValue(\"signature{\$i}_position\", \$signatory['position']);\n            \$template->setValue(\"signature{\$i}_date\", date('F j, Y'));\n            \n            // Add e-signature image if available\n            if (isset(\$signatory['signature_file']) && !empty(\$signatory['signature_file'])) {\n                \$sig_path = \"signatures/{\$signatory['signature_file']}\";\n                if (file_exists(\$sig_path)) {\n                    try {\n                        \$template->setImageValue(\"signature{\$i}_image\", [\n                            'path' => \$sig_path,\n                            'width' => 100,\n                            'height' => 50,\n                            'ratio' => false\n                        ]);\n                    } catch (Exception \$e) {\n                        // Log error but continue processing\n                        error_log(\"Error setting signature image: \" . \$e->getMessage());\n                    }\n                } else {\n                    error_log(\"Signature file not found: {\$sig_path}\");\n                }\n            }\n        }";
        
        $replaceCode = "// Add e-signature placeholders\n        for (\$i = 1; \$i <= 3; \$i++) {\n            \$signatory = isset(\$signatories[\$i-1]) ? \$signatories[\$i-1] : \$default_signatories[\$i-1];\n            \n            \$template->setValue(\"signature{\$i}_name\", \$signatory['name']);\n            \$template->setValue(\"signature{\$i}_position\", \$signatory['position']);\n            \$template->setValue(\"signature{\$i}_date\", date('F j, Y'));\n            \n            // Add e-signature image\n            if (\$i == 1) {\n                // Use the direct signature file for the first signatory\n                try {\n                    \$template->setImageValue(\"signature{\$i}_image\", [\n                        'path' => '$signatureFile',\n                        'width' => 100,\n                        'height' => 50,\n                        'ratio' => false\n                    ]);\n                    echo \"Set signature image for first signatory\\n\";\n                } catch (Exception \$e) {\n                    // Log error but continue processing\n                    echo \"Error setting signature image: \" . \$e->getMessage() . \"\\n\";\n                }\n            } else if (isset(\$signatory['signature_file']) && !empty(\$signatory['signature_file'])) {\n                \$sig_path = \"signatures/{\$signatory['signature_file']}\";\n                if (file_exists(\$sig_path)) {\n                    try {\n                        \$template->setImageValue(\"signature{\$i}_image\", [\n                            'path' => \$sig_path,\n                            'width' => 100,\n                            'height' => 50,\n                            'ratio' => false\n                        ]);\n                    } catch (Exception \$e) {\n                        // Log error but continue processing\n                        echo \"Error setting signature image: \" . \$e->getMessage() . \"\\n\";\n                    }\n                } else {\n                    echo \"Signature file not found: {\$sig_path}\\n\";\n                }\n            }\n        }";
        
        $updatedContent = str_replace($searchCode, $replaceCode, $fileContent);
        
        if ($updatedContent !== $fileContent) {
            // Write the updated content back to the file
            if (file_put_contents($filePath, $updatedContent) !== false) {
                echo "Successfully updated the signature handling in $filePath\n";
            } else {
                echo "Failed to write updated content to $filePath\n";
            }
        } else {
            echo "No changes were made to $filePath\n";
        }
    } else {
        echo "Failed to read $filePath\n";
    }
} else {
    echo "Signature file not found: $signatureFile\n";
}

echo "\nDone!\n";
