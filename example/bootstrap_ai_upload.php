<?php
require __DIR__ . '/../vendor/autoload.php';

use Sohagsrz\ResumeParser\OpenAIResumeParser;

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resume'])) {
    $file = $_FILES['resume'];
    $api_key = isset($_POST['openai_api_key']) ? trim($_POST['openai_api_key']) : '';
    if (!$api_key) {
        $error = 'Please enter your OpenAI API key.';
    } elseif ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $error = 'Please upload a PDF file.';
        } else {
            $tmpPath = $file['tmp_name'];
            $result = OpenAIResumeParser::parse($tmpPath, $api_key);
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
    <title>Resume PDF Parser (OpenAI) Demo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Resume PDF Parser (OpenAI) Demo</h1>
    <form method="post" enctype="multipart/form-data" class="mb-4">
        <div class="form-group">
            <label for="resume">Upload your resume (PDF only):</label>
            <input type="file" class="form-control-file" id="resume" name="resume" accept="application/pdf" required>
        </div>
        <div class="form-group">
            <label for="openai_api_key">OpenAI API Key:</label>
            <input type="password" class="form-control" id="openai_api_key" name="openai_api_key" required placeholder="sk-...">
        </div>
        <button type="submit" class="btn btn-primary">Parse Resume with OpenAI</button>
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