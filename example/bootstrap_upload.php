<?php
require __DIR__ . '/../vendor/autoload.php';

use Sohagsrz\ResumeParser\ResumeParser;

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resume'])) {
    $file = $_FILES['resume'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $error = 'Please upload a PDF file.';
        } else {
            $tmpPath = $file['tmp_name'];
            $result = ResumeParser::parse($tmpPath);
        }
    } else {
        $error = 'File upload error.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Resume PDF Parser Demo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Resume PDF Parser Demo</h1>
    <form method="post" enctype="multipart/form-data" class="mb-4">
        <div class="form-group">
            <label for="resume">Upload your resume (PDF only):</label>
            <input type="file" class="form-control-file" id="resume" name="resume" accept="application/pdf" required>
        </div>
        <button type="submit" class="btn btn-primary">Parse Resume</button>
    </form>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($result): ?>
        <h2>Extracted Data</h2>
        <pre class="bg-light p-3 border rounded"><?php echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
    <?php endif; ?>
</div>
</body>
</html> 