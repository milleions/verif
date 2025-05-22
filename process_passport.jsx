#target photoshop

// Define file paths
var scriptDir = File($.fileName).parent;
var jsonFile = new File(scriptDir + "/user_data.json");
var userData = readJSON(jsonFile);
var psdFile = new File(scriptDir.parent + "/assets/psd/passport_IPAD.psd");

var photoFile = new File(scriptDir.parent + "/assets/final/" + userData.PhotoFileName);
var exportFileName = userData.ExportFileName || "passport_output.png";
var outputFile = new File(scriptDir.parent + "/assets/output/" + exportFileName);



// Function to read JSON data
function readJSON(file) {
    if (!file.exists) {
        alert("JSON file not found: " + file.fsName);
        return null;
    }
    file.open('r');
    var content = file.read();
    file.close();

    if (typeof JSON === "undefined") {
        eval("var JSON = { parse: function(s) { return eval('(' + s + ')'); } };");
    }

    return JSON.parse(content);
}

// Function to update text layers
function updateTextLayer(doc, layerName, newText) {
    newText = newText.replace(/\\n/g, String.fromCharCode(13)); // "\\n" to newline
    for (var i = 0; i < doc.layers.length; i++) {
        var layer = doc.layers[i];
        if (layer.kind === LayerKind.TEXT && layer.name === layerName) {
            layer.textItem.contents = newText;
            return;
        } else if (layer.typename === "LayerSet") {
            updateTextLayer(layer, layerName, newText);
        }
    }
}



// Function to replace smart object contents
function replaceSmartObjectContents(layer, filePath) {
    var idplacedLayerReplaceContents = stringIDToTypeID("placedLayerReplaceContents");
    var desc = new ActionDescriptor();
    desc.putPath(charIDToTypeID("null"), new File(filePath));
    executeAction(idplacedLayerReplaceContents, desc, DialogModes.NO);
}

// Load data

if (userData === null) throw new Error("Failed to read user data.");
if (!psdFile.exists) throw new Error("PSD not found: " + psdFile.fsName);
if (!photoFile.exists) throw new Error("Photo not found: " + photoFile.fsName);

app.open(psdFile);
var doc = app.activeDocument;

// Replace photo
var photoLayer = null;
for (var i = 0; i < doc.layers.length; i++) {
    if (doc.layers[i].name === "Photo Placeholder") {
        photoLayer = doc.layers[i];
        break;
    }
}
if (!photoLayer) throw new Error("Layer 'Photo Placeholder' not found");

doc.activeLayer = photoLayer;
replaceSmartObjectContents(photoLayer, photoFile.fsName);



// Position photo
/*var bounds = photoLayer.bounds;
var currentX = bounds[0].as("px");
var currentY = bounds[1].as("px");
photoLayer.translate(2614 - currentX, 6388 - currentY);*/

// Replace photo 2
var littlePhoto = null;
for (var i = 0; i < doc.layers.length; i++) {
    if (doc.layers[i].name === "LITTLE PHOTO") {
        littlePhoto = doc.layers[i];
        break;
    }
}
if (!littlePhoto) throw new Error("Layer 'LITTLE PHOTO' not found");

doc.activeLayer = littlePhoto;
replaceSmartObjectContents(littlePhoto, photoFile.fsName);

// Duplicate and process LITTLE PHOTO
/*var littlePhoto = photoLayer.duplicate();
littlePhoto.name = "LITTLE PHOTO";
doc.activeLayer = littlePhoto;*/

// Safer resize to 202x267 pixels
var res = doc.resolution;
var lb = littlePhoto.bounds;
var left = lb[0].as("in") * res;
var top = lb[1].as("in") * res;
var right = lb[2].as("in") * res;
var bottom = lb[3].as("in") * res;
var lw = right - left;
var lh = bottom - top;

if (lw > 0 && lh > 0) {
    var scaleX = (808 / lw) * 100;
    var scaleY = (999 / lh) * 100;
    littlePhoto.resize(scaleX, scaleY);
}

// Move LITTLE PHOTO to final coordinates
/*var newBounds = littlePhoto.bounds;
var newLeft = newBounds[0].as("px");
var newTop = newBounds[1].as("px");
littlePhoto.translate(3466 - newLeft, 4037 - newTop);*/

// Rasterize
doc.activeLayer = littlePhoto;
var idRasterize = stringIDToTypeID("rasterizeLayer");
var desc = new ActionDescriptor();
var ref = new ActionReference();
ref.putEnumerated(charIDToTypeID("Lyr "), charIDToTypeID("Ordn"), charIDToTypeID("Trgt"));
desc.putReference(charIDToTypeID("null"), ref);
executeAction(idRasterize, desc, DialogModes.NO);

// Greyscale adjustment
var idAdj = charIDToTypeID("AdjL");
var desc2 = new ActionDescriptor();
var list = new ActionList();
var desc3 = new ActionDescriptor();
desc3.putInteger(charIDToTypeID("H   "), 0);
desc3.putInteger(charIDToTypeID("Strt"), -100);
desc3.putInteger(charIDToTypeID("Lght"), 0);
list.putObject(charIDToTypeID("HStr"), desc3);
desc2.putList(charIDToTypeID("Adjs"), list);
executeAction(charIDToTypeID("HStr"), desc2, DialogModes.NO);

// Set blending mode to Multiply and opacity to 80%
littlePhoto.blendMode = BlendMode.MULTIPLY;
littlePhoto.opacity = 80;

// Replace signature
var signatureFile = new File(scriptDir.parent + "/assets/sig/" + userData.Signature);
if (!signatureFile.exists) {
    throw new Error("Signature file not found: " + signatureFile.fsName);
}

var signatureLayer = null;
for (var i = 0; i < doc.layers.length; i++) {
    if (doc.layers[i].name === "Signature") {
        signatureLayer = doc.layers[i];
        break;
    }
}
if (!signatureLayer) throw new Error("Layer 'Signature' not found");

doc.activeLayer = signatureLayer;
replaceSmartObjectContents(signatureLayer, signatureFile.fsName);

// Resize to max height of 400px
var sigBounds = signatureLayer.bounds;
var sigHeight = sigBounds[3].as("px") - sigBounds[1].as("px");
if (sigHeight > 0) {
    var scale = (400 / sigHeight) * 100;
    signatureLayer.resize(scale, scale);
}

// Set blending mode to Multiply and opacity to 80%
/*signatureLayer.blendMode = BlendMode.MULTIPLY;
signatureLayer.opacity = 80;*/

// === BARCODE ===
var barcodeFile = new File(userData.BarcodeImage);
if (!barcodeFile.exists) throw new Error("Barcode image not found: " + barcodeFile.fsName);

var barcodeLayer = null;
for (var i = 0; i < doc.layers.length; i++) {
    if (doc.layers[i].name === "BARCODE") {
        barcodeLayer = doc.layers[i];
        break;
    }
}
if (!barcodeLayer) throw new Error("Layer 'BARCODE' not found");

doc.activeLayer = barcodeLayer;
replaceSmartObjectContents(barcodeLayer, barcodeFile.fsName);

// Update text layers
updateTextLayer(doc, "FirstName", userData.FirstName);
updateTextLayer(doc, "LastName", userData.LastName);
updateTextLayer(doc, "DOB", userData.DOB);
updateTextLayer(doc, "Gender", userData.Gender);
updateTextLayer(doc, "PlaceOfBirth", userData.PlaceOfBirth);
updateTextLayer(doc, "PassportNumber", userData.PassportNumber);
// Explode PassportNumber into characters and update P1 through P9 layers
var passportChars = userData.PassportNumber.split('');
for (var i = 0; i < 8; i++) {
    var layerName = 'P' + (i + 1);
    var character = passportChars[i] || '';
    updateTextLayer(doc, layerName, character);
}
updateTextLayer(doc, "IssueDate", userData.IssueDate);
updateTextLayer(doc, "ExpiryDate", userData.ExpiryDate);
updateTextLayer(doc, "IssuingAuthority", userData.IssuingAuthority);
updateTextLayer(doc, "MRZ", userData.MRZ);
updateTextLayer(doc, "Serial1", userData.SerialNumber);
var passportChars = userData.SerialNumber.split('');
for (var i = 0; i < 8; i++) {
    var layerName = 'S' + (i + 1);
    var character = passportChars[i] || '';
    updateTextLayer(doc, layerName, character);
}
updateTextLayer(doc, "SerialPunch", userData.SerialNumber);
updateTextLayer(doc, "SerialPunchMirror", userData.SerialNumber);
updateTextLayer(doc, "SerialBarcode", userData.SerialNumber);

// Export
/*var jpgOptions = new JPEGSaveOptions();
jpgOptions.quality = 12;
doc.saveAs(outputFile, jpgOptions, true);
doc.close(SaveOptions.DONOTSAVECHANGES);*/

// Export as transparent PNG
var pngOptions = new PNGSaveOptions();
pngOptions.interlaced = false; // or true if you want interlaced PNG

// Ensure the export path ends in .png
/*if (!/\.png$/i.test(outputFile.name)) {
    outputFile = new File(outputFile.path + "/" + outputFile.name.replace(/\.[^\.]+$/, ".png"));
}*/

doc.saveAs(outputFile, pngOptions, true);
doc.close(SaveOptions.DONOTSAVECHANGES);

//#target photoshop

// Read user data
var exportTemplateFileName = userData.ExportTemplateFileName;


// Define paths
var templateMap = {
    "PASSPORT_SCAN": "passport_scan.psd",
    "PASSPORT_PHOTO": "passport_photo.psd",
    "PASSPORT_CUT_PREP": "passport_cut_prep.psd"
};

var templateFile = new File("/Users/kinsleykeli/Sites/milleionskob/assets/psd/templates/" + templateMap[userData.Template]);
if (!templateFile.exists) {
    throw new Error("Template file not found: " + templateFile.fsName);
}

app.open(templateFile);
var doc = app.activeDocument;

// Replace 'generated_passport' layer
var passportLayer = doc.artLayers.getByName("generated_passport");
if (!passportLayer) {
    throw new Error("Layer 'generated_passport' not found in template.");
}

// Ensure the layer is visible and active
passportLayer.visible = true;
doc.activeLayer = passportLayer;

// Replace contents
var passportImage = new File(scriptDir.parent + "/assets/output/" + userData.ExportFileName);
replaceSmartObjectContents(passportLayer, passportImage.fsName);

// If template is 'passport_photo', replace 'background' layer
if (userData.Template === "passport_photo") {
    var backgroundLayer = doc.artLayers.getByName("background");
    if (!backgroundLayer) {
        throw new Error("Layer 'background' not found in template.");
    }

    // Ensure the layer is visible and active
    backgroundLayer.visible = true;
    doc.activeLayer = backgroundLayer;

    var backgroundImage = new File("/Users/kinsleykeli/Sites/milleionskob/assets/psd/templates/background/" + userData.Background);
    replaceSmartObjectContents(backgroundLayer, backgroundImage.fsName);
}

// Ensure the document is compatible with JPEG format
if (doc.mode != DocumentMode.RGB) {
    doc.changeMode(ChangeMode.RGB);
}
if (doc.bitsPerChannel !== BitsPerChannelType.EIGHT) {
    doc.bitsPerChannel = BitsPerChannelType.EIGHT;
}
//doc.flatten();

// Change resolution to 150 DPI without resampling
doc.resizeImage(
    undefined, // keep width
    undefined, // keep height
    300,       // set resolution
    ResampleMethod.NONE // Don't resample; only change DPI
);

// Ensure filename ends with .jpg
var fileName = userData.ExportTemplateFileName;
/*if (!fileName.toLowerCase().endsWith(".jpg")) {
    fileName = fileName.replace(/\.[^\.]+$/, "") + ".jpg";
}*/
var exportFile = new File(scriptDir.parent + "/assets/final_templates/" + fileName);

// Export as JPEG
var jpegOptions = new JPEGSaveOptions();
jpegOptions.quality = 12;
doc.saveAs(exportFile, jpegOptions, true);

// Additionally export as PDF if template is PASSPORT_CUT_PREP
if (userData.Template === "PASSPORT_CUT_PREP") {
    var pdfFile = new File(scriptDir.parent + "/assets/final_templates/" + fileName.replace(/\.[^\.]+$/, ".pdf"));
    var pdfOptions = new PDFSaveOptions();
    pdfOptions.pDFPreset = "highg quality latest acrobat"; // Ensure this preset exists in your Photoshop
    pdfOptions.embedColorProfile = true;
    pdfOptions.optimization = true;
    pdfOptions.layers = false;
    pdfOptions.colorConversion = false;
    pdfOptions.preserveEditing = false;

    doc.saveAs(pdfFile, pdfOptions, true);
}

doc.close(SaveOptions.DONOTSAVECHANGES);
