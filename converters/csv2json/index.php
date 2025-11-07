<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['convert'])) {
    header('Content-Type: application/json');
    
    if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'message' => 'No file uploaded or upload error occurred'
        ]);
        exit;
    }
    
    $file = $_FILES['csvFile'];
    $fileName = $file['name'];
    $tmpName = $file['tmp_name'];
    
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($extension !== 'csv') {
        echo json_encode([
            'success' => false,
            'message' => 'Only CSV files are allowed'
        ]);
        exit;
    }
    
    $handle = fopen($tmpName, 'r');
    if ($handle === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Unable to read the CSV file'
        ]);
        exit;
    }
    
    $data = [];
    $headers = [];
    $rowIndex = 0;
    
    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
        if ($rowIndex === 0) {
            $headers = array_map('trim', $row);
            $rowIndex++;
            continue;
        }
        
        if (count($row) === count($headers)) {
            $rowData = [];
            foreach ($headers as $index => $header) {
                $value = trim($row[$index]);
                
                if (is_numeric($value)) {
                    if (strpos($value, '.') !== false) {
                        $rowData[$header] = floatval($value);
                    } else {
                        $rowData[$header] = intval($value);
                    }
                } elseif (strtolower($value) === 'true') {
                    $rowData[$header] = true;
                } elseif (strtolower($value) === 'false') {
                    $rowData[$header] = false;
                } elseif ($value === '') {
                    $rowData[$header] = null;
                } else {
                    $rowData[$header] = $value;
                }
            }
            $data[] = $rowData;
        }
        $rowIndex++;
    }
    
    fclose($handle);
    
    if (empty($data)) {
        echo json_encode([
            'success' => false,
            'message' => 'No data found in CSV file or invalid format'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'CSV successfully converted to JSON! (' . count($data) . ' records)',
        'json' => $data
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV to JSON Converter</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: #f8f9ff;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            border-color: #764ba2;
            background: #f0f2ff;
        }
        
        .upload-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        input[type="file"] {
            display: none;
        }
        
        .file-label {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
            font-weight: 500;
        }
        
        .file-label:hover {
            background: #764ba2;
        }
        
        .file-name {
            margin-top: 15px;
            color: #333;
            font-weight: 500;
        }
        
        .convert-btn {
            width: 100%;
            background: #667eea;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-bottom: 20px;
        }
        
        .convert-btn:hover:not(:disabled) {
            background: #764ba2;
        }
        
        .convert-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.show {
            display: block;
        }
        
        .download-btn {
            width: 100%;
            background: #28a745;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            display: none;
        }
        
        .download-btn:hover {
            background: #218838;
        }
        
        .download-btn.show {
            display: block;
        }
        
        .info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #667eea;
            margin-top: 20px;
        }
        
        .info h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info ul {
            margin-left: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .info li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>CSV to JSON Converter</h1>
        <p class="subtitle">Upload your CSV file and convert it to JSON format instantly</p>
        
        <div id="message" class="message"></div>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="upload-area">
                <div class="upload-icon">📄</div>
                <label for="csvFile" class="file-label">Choose CSV File</label>
                <input type="file" name="csvFile" id="csvFile" accept=".csv" required>
                <div class="file-name" id="fileName"></div>
            </div>
            
            <button type="submit" name="convert" class="convert-btn" id="convertBtn" disabled>
                Convert to JSON
            </button>
        </form>
        
        <button id="downloadBtn" class="download-btn">Download JSON File</button>
        
        <div class="info">
            <h3>How to use:</h3>
            <ul>
                <li>Click "Choose CSV File" to upload your CSV file</li>
                <li>Click "Convert to JSON" to process the file</li>
                <li>Download the converted JSON file</li>
            </ul>
        </div>
    </div>
    
    <script>
        const fileInput = document.getElementById('csvFile');
        const fileName = document.getElementById('fileName');
        const convertBtn = document.getElementById('convertBtn');
        const downloadBtn = document.getElementById('downloadBtn');
        const messageDiv = document.getElementById('message');
        
        let jsonData = null;
        let originalFileName = '';
        
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                originalFileName = this.files[0].name;
                fileName.textContent = '📎 ' + originalFileName;
                convertBtn.disabled = false;
                downloadBtn.classList.remove('show');
                messageDiv.classList.remove('show');
            } else {
                fileName.textContent = '';
                convertBtn.disabled = true;
            }
        });
        
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('convert', '1');
            
            messageDiv.textContent = 'Converting...';
            messageDiv.className = 'message show';
            convertBtn.disabled = true;
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    jsonData = data.json;
                    messageDiv.textContent = '✅ ' + data.message;
                    messageDiv.className = 'message success show';
                    downloadBtn.classList.add('show');
                    convertBtn.disabled = false;
                } else {
                    messageDiv.textContent = '❌ ' + data.message;
                    messageDiv.className = 'message error show';
                    convertBtn.disabled = false;
                }
            })
            .catch(error => {
                messageDiv.textContent = '❌ Error: ' + error.message;
                messageDiv.className = 'message error show';
                convertBtn.disabled = false;
            });
        });
        
        downloadBtn.addEventListener('click', function() {
            if (jsonData) {
                const jsonString = JSON.stringify(jsonData, null, 2);
                const blob = new Blob([jsonString], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = originalFileName.replace('.csv', '.json');
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
        });
    </script>
</body>
</html>
