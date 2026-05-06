<?php
require 'config.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('PDF not found.');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Print Registration PDF</title>
  <style>
    html, body { margin: 0; height: 100%; background: #f3f4f6; }
    iframe { border: 0; width: 100%; height: 100%; }
  </style>
</head>
<body>
  <iframe id="pdfFrame" src="view_pdf.php?id=<?=$id?>"></iframe>
  <script>
    const frame = document.getElementById('pdfFrame');
    frame.addEventListener('load', () => {
      setTimeout(() => {
        try {
          frame.contentWindow.focus();
          frame.contentWindow.print();
        } catch (e) {
          window.print();
        }
      }, 600);
    });
  </script>
</body>
</html>
