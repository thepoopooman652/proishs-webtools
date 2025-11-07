# JSON to CSV Converter - Complete Documentation

## Overview

The JSON to CSV Converter is a web-based tool built with PHP that allows users to upload JSON (JavaScript Object Notation) files and convert them to CSV (Comma-Separated Values) format. The tool provides a clean, user-friendly interface with real-time conversion and instant download capabilities.

## Features

### Core Functionality

1. **File Upload**
   - Accepts JSON files through a drag-and-drop style interface
   - File type validation to ensure only JSON files are processed
   - Visual feedback showing the selected file name

2. **JSON to CSV Conversion**
   - Parses JSON arrays of objects
   - Extracts headers from the first object's keys
   - Converts each object to a CSV row
   - Handles special characters with proper CSV escaping
   - Converts data types appropriately for CSV format

3. **File Download**
   - One-click download of converted CSV file
   - Automatic filename generation (replaces .json extension with .csv)
   - Browser-based download without server-side file storage

4. **Error Handling**
   - File upload validation
   - JSON syntax verification
   - Data structure validation (must be array of objects)
   - Element-level validation (all items must be objects)
   - Clear error messages for users

## How It Works

### Technical Architecture

The application is built as a single-page PHP application that combines:
- **HTML** for structure
- **CSS** for styling
- **JavaScript** for client-side interactions
- **PHP** for server-side JSON processing

### Workflow Process

1. **User Uploads JSON File**
   - User clicks "Choose JSON File" button
   - Browser file picker opens with .json filter
   - Selected file name is displayed on the interface
   - "Convert to CSV" button becomes enabled

2. **Conversion Process**
   - User clicks "Convert to CSV" button
   - JavaScript prevents default form submission
   - File is sent to server via AJAX POST request
   - PHP processes the file on the server

3. **Server-Side Processing**
   - PHP validates the uploaded file
   - Checks file extension is .json
   - Reads and parses the JSON content
   - Validates JSON syntax using `json_decode()`
   - Checks that data is a non-empty array
   - Validates that all elements are objects/arrays

4. **Data Validation**
   The converter performs multiple validation checks:
   - **JSON syntax**: Must be valid JSON format
   - **Array structure**: Must be an array, not a single object
   - **Non-empty**: Array must contain at least one element
   - **Object elements**: Each array element must be an object
   - **Element consistency**: All elements validated individually

5. **CSV Generation**
   - Headers extracted from first object's keys
   - Each object converted to a CSV row
   - Data types handled appropriately:
     - **Booleans**: Converted to "true" or "false" strings
     - **Null values**: Converted to empty strings
     - **Nested objects/arrays**: Serialized as JSON strings
     - **Other values**: Converted to strings

6. **Download**
   - CSV data array sent back to browser
   - JavaScript creates properly formatted CSV string
   - Special characters escaped (commas, quotes, newlines)
   - Blob object created from CSV string
   - Browser downloads file with appropriate name

## File Structure

### json2csv.php

The main application file contains:

#### HTML Structure
- Modern, responsive container layout
- File upload form with styled input
- Upload area with visual feedback
- Convert and download buttons
- Information section with usage instructions

#### CSS Styling
- Gradient background for visual appeal
- Card-based container design
- Interactive hover effects
- Responsive design for mobile devices
- Color-coded success/error messages
- Disabled button states for better UX

#### JavaScript Functionality
- File input change detection
- Form submission handling via AJAX
- Dynamic UI updates (messages, button states)
- CSV formatting with proper escaping
- CSV blob creation and download
- Automatic filename generation

#### PHP Backend
- POST request handling
- File upload validation
- JSON parsing and validation
- Element-level validation loop
- CSV data array generation
- HTTP response with JSON data

## Usage Instructions

### For End Users

1. **Open the Application**
   - Navigate to json2csv.php in your web browser
   - You'll see the JSON to CSV Converter interface

2. **Upload Your JSON File**
   - Click the "Choose JSON File" button
   - Select a JSON file from your computer
   - The file name will appear below the button

3. **Convert the File**
   - Click the "Convert to CSV" button
   - Wait for the conversion (usually instant)
   - Success message will appear with record count

4. **Download the Result**
   - Click the "Download CSV File" button
   - The CSV file will download to your computer
   - File will have the same name as your JSON but with .csv extension

### JSON File Requirements

Your JSON file should be formatted as follows:

- **Structure**: Array of objects (not a single object)
- **Non-empty**: Must contain at least one object
- **Consistent format**: All elements should be objects
- **Encoding**: UTF-8 recommended for special characters

**Example JSON:**
```json
[
  {
    "name": "John Doe",
    "age": 30,
    "email": "john@example.com",
    "active": true
  },
  {
    "name": "Jane Smith",
    "age": 25,
    "email": "jane@example.com",
    "active": false
  },
  {
    "name": "Bob Johnson",
    "age": 35,
    "email": "bob@example.com",
    "active": true
  }
]
```

**Resulting CSV:**
```csv
name,age,email,active
John Doe,30,john@example.com,true
Jane Smith,25,jane@example.com,false
Bob Johnson,35,bob@example.com,true
```

## Technical Details

### PHP Functions Used

1. **`file_get_contents()`**
   - Reads entire file into a string
   - Used to get JSON content from uploaded file
   - Returns false on failure

2. **`json_decode()`**
   - Converts JSON string to PHP array/object
   - Second parameter `true` converts to associative array
   - Sets error code accessible via `json_last_error()`

3. **`json_last_error()`**
   - Returns last JSON parsing error code
   - Used to validate JSON syntax
   - `JSON_ERROR_NONE` indicates successful parsing

4. **`json_encode()`**
   - Converts PHP array/object to JSON string
   - Used for:
     - Creating API responses
     - Serializing nested objects in CSV cells

5. **`array_keys()`**
   - Extracts keys from associative array
   - Used to get CSV headers from first object
   - Returns array of key names

6. **`pathinfo()`**
   - Gets file extension for validation
   - Ensures only .json files are processed

### Data Type Conversion Logic

The converter uses the following rules for CSV generation:

```php
// Boolean conversion
if (is_bool($value)) {
    $value = $value ? 'true' : 'false';
}
// Null conversion
elseif (is_null($value)) {
    $value = '';
}
// Nested objects/arrays
elseif (is_array($value) || is_object($value)) {
    $value = json_encode($value);
}
// All other types remain as-is
```

### CSV Escaping Logic

JavaScript handles CSV escaping client-side:

```javascript
row.map(cell => {
    const cellStr = String(cell);
    // Escape if contains comma, quote, or newline
    if (cellStr.includes(',') || cellStr.includes('"') || cellStr.includes('\n')) {
        // Wrap in quotes and escape existing quotes
        return '"' + cellStr.replace(/"/g, '""') + '"';
    }
    return cellStr;
}).join(',')
```

Rules:
- **Commas**: Field wrapped in double quotes
- **Quotes**: Doubled ("" for each ")
- **Newlines**: Field wrapped in double quotes
- **Normal text**: No escaping needed

### Security Features

1. **File Type Validation**
   - Only .json extensions accepted
   - Server-side validation in addition to client-side

2. **Upload Error Checking**
   - Verifies file was uploaded successfully
   - Checks for PHP upload errors

3. **JSON Validation**
   - Syntax validation via `json_decode()`
   - Structure validation (array check)
   - Element-level validation (all items are objects)

4. **File Size Limits**
   - Controlled by PHP configuration
   - Default limits apply from php.ini

5. **No Server-Side Storage**
   - Files processed in memory
   - Temporary files cleaned up automatically
   - No persistent storage of user data

### Browser Compatibility

The application works with modern browsers that support:
- HTML5 File API
- Fetch API
- Blob and URL.createObjectURL
- ES6 JavaScript features (arrow functions, template literals)

Compatible browsers include:
- Chrome 60+
- Firefox 55+
- Safari 11+
- Edge 79+

## Error Messages

The application provides clear error messages for common issues:

| Error | Cause | Solution |
|-------|-------|----------|
| "No file uploaded or upload error occurred" | File upload failed | Try uploading again |
| "Only JSON files are allowed" | Wrong file type selected | Select a .json file |
| "Unable to read the JSON file" | File is corrupted or unreadable | Check file integrity |
| "Invalid JSON format: [error]" | Malformed JSON syntax | Validate JSON syntax |
| "JSON must be a non-empty array of objects" | Wrong JSON structure | Use array format |
| "Invalid data at index N: all elements must be objects/arrays" | Mixed data types in array | Ensure all elements are objects |

## Customization Options

### Changing CSV Delimiter

The default delimiter is comma (,). To use a different delimiter (e.g., semicolon):

```javascript
// Change this line in the download handler
.join(',')

// To this (for semicolon delimiter)
.join(';')
```

### Modifying CSV Escaping

To change escaping behavior, modify the download handler logic:

```javascript
// Current: Escape commas, quotes, and newlines
if (cellStr.includes(',') || cellStr.includes('"') || cellStr.includes('\n'))

// Example: Only escape quotes
if (cellStr.includes('"'))
```

### Styling Customization

The CSS is embedded in the HTML. Key variables you might want to change:

- **Primary color**: `#667eea` (purple-blue gradient start)
- **Secondary color**: `#764ba2` (purple gradient end)
- **Container width**: `max-width: 600px`
- **Border radius**: `border-radius: 12px`

## Limitations

1. **File Size**
   - Limited by PHP's `upload_max_filesize` setting
   - Default is typically 2MB-8MB depending on server
   - Large files may timeout or run out of memory

2. **JSON Structure**
   - Must be an array of objects
   - All array elements must be objects/arrays
   - Headers taken from first object only

3. **Browser Storage**
   - CSV stored temporarily in browser memory
   - Very large conversions may cause memory issues
   - Recommended for files under 10MB

4. **Header Consistency**
   - Headers based on first object's keys
   - If objects have different keys, some cells may be empty
   - Missing keys filled with empty values

## Troubleshooting

### File Won't Upload

- Check file extension is .json
- Verify file isn't corrupted
- Try with a smaller file
- Check browser console for errors

### Conversion Fails

- Validate JSON syntax using an online validator
- Ensure JSON is an array, not a single object
- Check that all array elements are objects
- Look for special characters causing issues

### Download Doesn't Work

- Enable pop-ups for the site
- Check browser's download settings
- Ensure JavaScript is enabled
- Try a different browser

### Empty or Incorrect CSV

- Verify JSON structure matches expected format
- Check that objects have consistent keys
- Ensure no nested arrays at the top level
- Validate data types in JSON

## Advanced Usage

### Nested Objects

If your JSON contains nested objects, they will be serialized as JSON strings in the CSV:

**Input JSON:**
```json
[
  {
    "name": "John",
    "address": {
      "city": "NYC",
      "zip": "10001"
    }
  }
]
```

**Output CSV:**
```csv
name,address
John,"{""city"":""NYC"",""zip"":""10001""}"
```

### Boolean and Null Values

- **Booleans**: Converted to literal strings "true" and "false"
- **Null**: Converted to empty strings in CSV
- This ensures compatibility with most CSV parsers

### Array Values

Arrays within objects are serialized as JSON strings:

**Input:**
```json
[
  {
    "name": "John",
    "hobbies": ["reading", "coding"]
  }
]
```

**Output:**
```csv
name,hobbies
John,"[""reading"",""coding""]"
```

## Conclusion

The JSON to CSV Converter is a reliable tool for transforming JSON data into CSV format. It handles the most common JSON to CSV conversion needs while maintaining data integrity and providing a smooth user experience. The single-file architecture makes it easy to deploy on any PHP-enabled web server without additional dependencies or database requirements.
