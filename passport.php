<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';

// Set maximum execution time
set_time_limit(0);

// Define paths
$pythonPath = '/Users/kinsleykeli/rembg-env/bin/python3.11';
$rembgPath = '/Users/kinsleykeli/rembg-env/bin/rembg';
$uploadDir = dirname(__DIR__) . '/assets/uploads/';
$outputDir = dirname(__DIR__) . '/assets/outputs/';
$finalDir = dirname(__DIR__) . '/assets/final/';
$sigDir = dirname(__DIR__) . '/assets/sig/';
$psdTemplate = dirname(__DIR__) . '/assets/psd/passport_IPAD.psd';
$scriptsDir = dirname(__DIR__) . '/';
$userDataJson = __DIR__ . '/user_data.json';
$jsxScriptPath = __DIR__ . '/process_passport.jsx';

// Create directories if they don't exist
foreach ([$uploadDir, $outputDir, $finalDir, $sigDir, dirname(__DIR__) . '/assets/output', $scriptsDir] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

// Initialize variables
$finalResult = '';
$outputPath = '';
$finalPath = '';
$imgPath = '';

if (isset($_GET['generate']) && $_GET['generate'] === 'passport') {
    echo json_encode(['value' => generateUniquePassportNumber($pdo)]);
    exit;
}
if (isset($_GET['generate']) && $_GET['generate'] === 'serial') {
    echo json_encode(['value' => generateUniqueSerialNumber($pdo)]);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Passport Photo and Data Processor</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        img { max-width: 600px; height: auto; margin: 10px 0; }
        .preview { margin-bottom: 20px; }

        .input-preview-group {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .input-preview-group img {
            max-width: 150px;
            max-height: 150px;
            display: none;
            border: 1px solid #ccc;
            padding: 5px;
            background-color: #f8f8f8;
        }
    </style>
</head>
<body>
<div style="display: flex; gap: 40px; align-items: flex-start;">
    <div style="flex: 1;">
        <h1>Upload Passport Photo and Enter Details</h1>
        <form method="POST" enctype="multipart/form-data">

    <div class="input-preview-group">
        <div>
            <label>Select a Photo:</label><br>
            <input type="file" name="image" accept="image/*" required onchange="previewImage(event)">
        </div>
        <img id="preview-img" src="#" alt="Photo Preview">
    </div>

    <div class="input-preview-group">
        <div>
            <label>Select a Signature:</label><br>
            <input type="file" name="signature" accept="image/*" required onchange="previewSignature(event)">
        </div>
        <img id="signature-preview" src="#" alt="Signature Preview">
    </div>

    <label for="prenom">First Name:</label>
    <input type="text" id="prenom" name="prenom" required><br><br>

    <label for="nom">Last Name:</label>
    <input type="text" id="nom" name="nom" required><br><br>

    <label for="date_naissance">Date of Birth:</label>
    <input type="date" id="date_naissance" name="date_naissance" required><br><br>

    <label for="sexe">Gender:</label>
    <input type="radio" id="sexe_m" name="sexe" value="M" required>
    <label for="sexe_m">M</label>
    <input type="radio" id="sexe_f" name="sexe" value="F" required>
    <label for="sexe_f">F</label><br><br>

    <label for="place_of_birth">Place of Birth:</label>
    <input type="text" id="place_of_birth" name="place_of_birth" required><br><br>

    <label for="issuing_auth">Issuing Authority:</label>
    <input type="text" id="issuing_auth" name="issuing_auth" required><br><br>

    <label for="issue_date">Issue Date:</label>
    <input type="date" id="issue_date" name="issue_date" required><br><br>

    <label for="passport_number">Passport Number:</label>
    <input type="text" id="passport_number" name="passport_number" required>
    <button type="button" onclick="generateField('passport_number', 'passport')">Generate</button><br><br>

    <label for="serial_number">Serial Number:</label>
    <input type="text" id="serial_number" name="serial_number" required>
    <button type="button" onclick="generateField('serial_number', 'serial')">Generate</button><br><br>

    <!-- Template Selection -->
    <!--<label for="template">Select Template:</label>
    <select id="template" name="template" required onchange="toggleBackgroundSelector()">
        <option value="passport_scan">Passport Scan</option>
        <option value="passport_photo">Passport Photo</option>
        <option value="passport_cut_prep">Passport Cut Prep</option>
    </select><br><br>-->

   <!-- <input type="hidden" name="background" value=" ">-->


        <label for="background">Select Background:</label>
        <select id="background" name="background">
            <?php
            $backgroundDir = dirname(__DIR__) . '/assets/psd/templates/background/';
            $backgroundFiles = glob($backgroundDir . '*.{jpg,jpeg,png}', GLOB_BRACE);
            foreach ($backgroundFiles as $file) {
                $filename = basename($file);
                echo "<option value=\"$filename\">$filename</option>";
            }
            ?>
        </select><br><br>

    <button type="submit">Upload & Generate</button>
    <button type="reset" onclick="window.location.href=window.location.href">Reset</button>
</form>



<script>
    function previewImage(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById("preview-img");
            img.src = e.target.result;
            img.style.display = "block";  // This replaces the outdated line
        };
        reader.readAsDataURL(file);
    }

    function previewSignature(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById("signature-preview");
            img.src = e.target.result;
            img.style.display = "block";
        };
        reader.readAsDataURL(file);
    }

    function generateField(fieldId, type) {
        fetch(`?generate=${type}`)
            .then(response => response.json())
            .then(data => {
                if (data.value) {
                    document.getElementById(fieldId).value = data.value;
                }
            })
            .catch(err => console.error('Error generating value:', err));
    }

    function toggleBackgroundSelector() {
        var template = document.getElementById('template').value;
        var bgSelector = document.getElementById('background-selector');
        bgSelector.style.display = (template === 'passport_photo') ? 'block' : 'none';
    }

</script>

<?php
// Helpers
function calculateCheckDigit($input) {
    $weights = [7, 3, 1];
    $sum = 0;
    for ($i = 0, $len = strlen($input); $i < $len; $i++) {
        $char = $input[$i];
        $val = ($char === '<') ? 0 : (ctype_digit($char) ? (int)$char : ord($char) - 55);
        $sum += $val * $weights[$i % 3];
    }
    return $sum % 10;
}

function formatDateCustom($date_str) {
    $date = new DateTime($date_str);
    $months = [
        1 => ['JAN', 'JAN'], 2 => ['FEB', 'FÉV'], 3 => ['MAR', 'MAR'], 4 => ['APR', 'AVR'],
        5 => ['MAY', 'MAI'], 6 => ['JUNE', 'JUIN'], 7 => ['JULY', 'JUIL'], 8 => ['AUG', 'AOU'],
        9 => ['SEPT', 'SEPT'], 10 => ['OCT', 'OCT'], 11 => ['NOV', 'NOV'], 12 => ['DEC', 'DÉC']
    ];
    $day = $date->format('d');
    $month = $months[(int)$date->format('n')];
    $year = $date->format('y');
    return "$day {$month[0]}/{$month[1]} $year";
}

// Begin POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    // Input data
    $prenom = strtoupper($_POST['prenom']);
    $nom = strtoupper($_POST['nom']);
    $date_naissance = $_POST['date_naissance'];
    $sexe = strtoupper($_POST['sexe']);
    $place_of_birth = strtoupper($_POST['place_of_birth']) . ' CAN';
    $issue_date = $_POST['issue_date'];
    $issuing_auth = strtoupper($_POST['issuing_auth']);
    $passport_number = strtoupper($_POST['passport_number']);
    $serial_number = strtoupper($_POST['serial_number']);
    //$template = strtoupper($_POST['template']);
    $background = $_POST['background'];


    try {
        savePassportAndSerial($pdo, $passport_number, $serial_number);
    } catch (Exception $e) {
        // Handle the error (e.g., log it, display a message, etc.)
        echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        // Optionally, halt further execution
        exit;
    }

    $prenom = strtoupper($_POST['prenom']);
    $nom = strtoupper($_POST['nom']);
    $dt = new DateTime('now', new DateTimeZone('America/Toronto')); // Montreal shares this timezone
    $timestamp = $dt->format('dmY_Hi'); // e.g., 07052025_1423

// Sanitize names for filenames (no spaces or special characters)
    $safePrenom = preg_replace('/[^a-zA-Z0-9]/', '_', $prenom);
    $safeNom = preg_replace('/[^a-zA-Z0-9]/', '_', $nom);

    $dynamicExportName = "{$safePrenom}_{$safeNom}_{$timestamp}.png";
    $dynamicExportTemplateName = "{$safePrenom}_{$safeNom}_{$timestamp}";
    $dynamicPreExportName = "{$safePrenom}_{$safeNom}_{$timestamp}";
    //$dynamicPreExportTemplateName = "{$safePrenom}_{$safeNom}_{$timestamp}_{$template}";
    $exportPath = dirname(__DIR__) . '/assets/output/' . $dynamicExportName;
    $exportPathTemplate = dirname(__DIR__) . '/assets/final_templates/' . $dynamicExportTemplateName . '.jpeg';

    // Expiry and MRZ
    $dob = new DateTime($date_naissance);
    $issue = new DateTime($issue_date);
    $expiry = (clone $issue)->modify('+10 years');
    $dob_code = $dob->format('ymd');
    $exp_code = $expiry->format('ymd');
    $formatted_dob = formatDateCustom($date_naissance);
    $formatted_issue = formatDateCustom($issue_date);
    $formatted_expiry = formatDateCustom($expiry->format('Y-m-d'));
    $codenom = str_replace([' ', '-'], '<', $nom);
    $codeprenom = str_replace([' ', '-'], '<', $prenom);
    $mrz1 = 'P<CAN' . $codenom . '<<' . $codeprenom;
    $mrz1 .= str_repeat('<', 44 - strlen($mrz1));
    $pncheckdigit = calculateCheckDigit($passport_number);
    $dobcheckdigit = calculateCheckDigit($dob_code);
    $expcheckdigit = calculateCheckDigit($exp_code);
    $final_check = calculateCheckDigit($passport_number . $pncheckdigit . '<' . $dob_code . $dobcheckdigit);
    $mrz2 = "{$passport_number}<{$pncheckdigit}CAN{$dob_code}{$dobcheckdigit}{$sexe}{$exp_code}{$expcheckdigit}<<<<<<<<<<<<<<0{$final_check}";
    $full_mrz = "$mrz1\r$mrz2";

    $signatureFile = $_FILES['signature'];
    $safeSigName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($signatureFile['name']));
    $sigInputPath = $uploadDir . $safeSigName;
    $sigOutputName = pathinfo($safeSigName, PATHINFO_FILENAME) . '_sig_nobg.png';
    $sigOutputPath = $outputDir . $sigOutputName;
    $finalSigName =  $safePrenom . $safeNom . $timestamp . '_signature_final.png';
    $finalSigPath = $sigDir . $finalSigName;


    $file = $_FILES['image'];
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
    $inputPath = $uploadDir . $safeName;
    $outputName = pathinfo($safeName, PATHINFO_FILENAME) . '_nobg.png';
    $outputPath = $outputDir . $outputName;
    $finalPath = $finalDir . $dynamicPreExportName . '_photo_final.png';
    $photoFileName = $dynamicPreExportName . '_photo_final.png';

    $barcodeCLI = '/Applications/BCStudio.app/Contents/MacOS/BCStudio_Console';
    $barcodeSettings = '/Users/kinsleykeli/Sites/milleionskob/verif/barcodeSettings/code128b_passport.bc';
    $barcodeDir = dirname(__DIR__) . '/assets/barcode/';
    $barcodeFileName = $dynamicPreExportName . '_barcode.png';
    $barcodeOutputPath = $barcodeDir . $barcodeFileName;

// Ensure barcode directory exists
    if (!is_dir($barcodeDir)) mkdir($barcodeDir, 0755, true);

// Build and run command
    $barcodeCmd = escapeshellcmd($barcodeCLI) .
        ' -d=' . escapeshellarg($serial_number) .
        ' -out=' . escapeshellarg($barcodeOutputPath) .
        ' -s=' . escapeshellarg($barcodeSettings) . ' -o';

    exec($barcodeCmd, $barcodeLog, $barcodeExit);
    if ($barcodeExit !== 0 || !file_exists($barcodeOutputPath)) {
        echo "<p style='color:red;'>❌ Barcode generation failed.<br><pre>" . implode("\n", $barcodeLog) . "</pre></p>";
        exit;
    }

    // Save data to JSON
    file_put_contents($userDataJson, json_encode([
        "FirstName" => $prenom,
        "LastName" => $nom,
        "DOB" => $formatted_dob,
        "Gender" => $sexe,
        "PlaceOfBirth" => $place_of_birth,
        "PassportNumber" => $passport_number,
        "IssueDate" => $formatted_issue,
        "ExpiryDate" => $formatted_expiry,
        "IssuingAuthority" => $issuing_auth,
        "MRZ" => $full_mrz,
        "SerialNumber" => $serial_number,
        "ExportFileName" => $dynamicExportName,
        "Signature" => $finalSigName,
        "PhotoFileName" => $photoFileName,
        "BarcodeImage" => $barcodeOutputPath,
        "Background" => $background,
        "ExportTemplateFileName" => $dynamicExportTemplateName
    ], JSON_PRETTY_PRINT));

    // Image processing

    if (move_uploaded_file($signatureFile['tmp_name'], $sigInputPath)) {
        $cmdSig = 'PATH=' . escapeshellarg('/opt/homebrew/bin:/usr/bin:/bin') . ' ' .
            escapeshellcmd($pythonPath) . ' ' . escapeshellarg($rembgPath) .
            ' i -m bria-rmbg ' . escapeshellarg($sigInputPath) . ' ' . escapeshellarg($sigOutputPath);
        exec($cmdSig, $logSig, $rembgSigExit);

        if ($rembgSigExit === 0 && file_exists($sigOutputPath)) {
            // Crop signature to remove transparent padding
            $cropSignatureScript = __DIR__ . '/crop_signature.py';
            exec(escapeshellcmd($pythonPath) . ' ' . escapeshellarg($cropSignatureScript) . ' ' .
                escapeshellarg($sigOutputPath) . ' ' . escapeshellarg($finalSigPath), $cropSigLog, $cropSigExit);

            if ($cropSigExit !== 0 || !file_exists($finalSigPath)) {
                echo "<p>Error cropping signature.<br><pre>" . implode("\n", $cropSigLog) . "</pre></p>";
            }
        } else {
            echo "<p>Error removing background from signature. (Code: $rembgSigExit)</p>";
        }
    } else {
        echo "<p>Failed to upload signature image.</p>";
    }


    if (move_uploaded_file($file['tmp_name'], $inputPath)) {
        $cmd = 'PATH=' . escapeshellarg('/opt/homebrew/bin:/usr/bin:/bin') . ' ' .
            escapeshellcmd($pythonPath) . ' ' . escapeshellarg($rembgPath) .
            ' i -m bria-rmbg ' . escapeshellarg($inputPath) . ' ' . escapeshellarg($outputPath);
        exec($cmd, $log, $rembgExit);

        if ($rembgExit === 0 && file_exists($outputPath)) {
            $pyScript = __DIR__ . '/crop_face_id_style.py';
            exec(escapeshellcmd($pythonPath) . ' ' . escapeshellarg($pyScript) . ' ' .
                escapeshellarg($outputPath) . ' ' . escapeshellarg($finalPath), $cropLog, $cropExit);

            if ($cropExit === 0 && file_exists($finalPath)) {
                // Execute JSX script via AppleScript
                $escapedJsxPath = addslashes(realpath($jsxScriptPath));
                exec("osascript -e 'tell application \"Adobe Photoshop 2025\" to do javascript file \"$escapedJsxPath\"'", $psLog, $psExit);

                $psdResults = __DIR__ . '/psd_results_json/' . $dynamicExportTemplateName . '.json';


                if (file_exists($psdResults)) {
                    $data = json_decode(file_get_contents($psdResults), true);
                    $imgPathScan = '../assets/final_templates/' . $data['scanFilename'] . '?cache=' . time();
                    $imgPathPhoto = '../assets/final_templates/' . $data['photoFilename'] . '?cache=' . time();
                    $imgPathCut = '../assets/final_templates/' . $data['cutFilename'] . '?cache=' . time();
                    $finalResult = "<h2>✅ Final Passport Image:</h2><img src='$imgPathScan' style='max-width:400px;'><br><img src='$imgPathPhoto' style='max-width:400px;'><br><img src='$imgPathCut' style='max-width:400px;'>";
                } else {
                    $finalResult = "<p style='color:red;'>❌ Photoshop output not found.<br><pre>" . implode("\n", $psLog) . "</pre></p>";
                }
            } else {
                $finalResult = "<p>❌ Cropping script failed.<br><pre>" . implode("\n", $cropLog) . "</pre></p>";
            }
        } else {
            $finalResult = "<p>Error removing background. (Code: $rembgExit)</p>";
        }
    } else {
        $finalResult = "<p>Failed to upload image.</p>";
    }




}



// Show previews
if (!empty($outputPath) && file_exists($outputPath)) {
    echo "<div class='preview'><h2>Background Removed:</h2><img src='../assets/outputs/" . basename($outputPath) . "?cache=" . time() . "'></div>";
}
if (!empty($finalPath) && file_exists($finalPath)) {
    echo "<div class='preview'><h2>Cropped and Aligned:</h2><img src='../assets/final/" . basename($finalPath) . "?cache=" . time() . "'></div>";
}
echo "</div>"; // End of form container
echo "<div style='flex: 1;'>";
if (!empty($finalResult)) {
    echo str_replace("max-width:1000px;", "width: 1000px;", $finalResult);
}
/*echo "</div></div>"; // Close flex container
echo "Debug: <br>";
echo "exportPathTemplate: " . $exportPathTemplate . "<br>";
echo 'dynamicExportTemplateName' . $dynamicExportTemplateName . '<br>';
echo "imgPath:" . $imgPath . "<br>";*/
?>
</body>
</html>