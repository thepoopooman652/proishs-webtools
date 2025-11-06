<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced PHP File Editor</title>
    
    <!-- CodeMirror CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/theme/material-darker.min.css">

    <style>
        body, html { margin: 0; padding: 0; height: 100%; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .container { display: flex; height: 100vh; }
        .sidebar { width: 250px; border-right: 1px solid #ccc; background-color: #f7f7f7; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-header { padding: 10px; border-bottom: 1px solid #ccc; background: #f0f0f0; }
        #new-file-btn { width: 100%; padding: 8px; background-color: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 14px; }
        .resizer { width: 5px; cursor: col-resize; background-color: #f0f0f0; flex-shrink: 0; border-right: 1px solid #ccc; transition: background-color 0.2s ease; }
        .resizer:hover { background-color: #007bff; }
        #new-file-btn:hover { background-color: #218838; }
        .file-list-container { flex-grow: 1; overflow-y: auto; }
        .main-content { flex-grow: 1; display: flex; flex-direction: column; }
        #file-list { list-style: none; padding: 0; margin: 0; }
        .sidebar-footer { padding: 10px; border-top: 1px solid #ccc; background: #f0f0f0; }
        #upload-btn { width: 100%; padding: 8px; background-color: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 14px; }
        #upload-btn:hover { background-color: #0069d9; }
        #upload-status { font-size: 0.9em; color: #17a2b8; margin-top: 5px; text-align: center; display: block; min-height: 1.2em; }
        #file-list li { display: flex; align-items: center; padding: 0 15px; height: 40px; cursor: pointer; border-bottom: 1px solid #eee; }
        .sidebar-footer .upload-buttons-container { display: flex; gap: 5px; margin-bottom: 5px; }
        .sidebar-footer .upload-buttons-container button { flex-grow: 1; padding: 8px; background-color: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 14px; }
        .sidebar-footer .upload-buttons-container button:hover { background-color: #0069d9; }
        .sidebar-footer .upload-buttons-container button:disabled { background-color: #007bff; opacity: 0.65; cursor: not-allowed; }
        .file-name { flex-grow: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 5px; }
        .file-item .delete-btn { background: none; border: none; cursor: pointer; font-size: 1.2em; color: #999; padding: 0 5px; display: none; /* Hidden by default */ }
        .file-item:hover .delete-btn { display: inline-block; /* Show on hover */ }
        .delete-btn:hover { color: #dc3545; }
        .file-item:hover { background-color: #e0e0e0; }
        .file-item.active { background-color: #007bff; color: white; }
        .folder-item > .folder-header { display: flex; align-items: center; padding: 0 15px; height: 40px; cursor: pointer; border-bottom: 1px solid #eee; }
        .folder-item > .folder-header:hover { background-color: #e0e0e0; }
        .folder-name { flex-grow: 1; }
        .folder-toggle { margin-right: 5px; width: 1em; display: inline-block; }
        .folder-children { padding-left: 20px; display: none; } /* Hidden by default */
        .editor-toolbar { padding: 5px 10px; background: #f0f0f0; border-bottom: 1px solid #ccc; display: flex; align-items: center; min-height: 26px; }
        #file-info { font-weight: bold; margin-right: auto; color: #333; }
        #save-btn { padding: 4px 12px; margin-right: 10px; cursor: pointer; }
        #save-btn:disabled { cursor: not-allowed; opacity: 0.6; }
        #save-status { font-size: 0.9em; color: #17a2b8; }
        #viewer { flex-grow: 1; position: relative; overflow: auto; background-color: #263238;}
        .CodeMirror { position: absolute !important; top: 0; left: 0; right: 0; bottom: 0; height: 100%; }
        .media-wrapper { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; text-align: center; }
        #viewer img, #viewer video, #viewer audio { max-width: 100%; max-height: 100%; object-fit: contain; }
        #viewer p { color: #ccc; }
        #viewer a { color: #82aaff; }
        #viewer iframe { border: none; }
        .loader { text-align: center; padding: 40px; font-style: italic; color: #888; }
    </style>
</head>
<body>

    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <button id="new-file-btn">+ New File</button>
            </div>
            <div class="file-list-container">
                <ul id="file-list">
                    <li class="loader">Loading files...</li>
                </ul>
            </div>
            <div class="sidebar-footer">
                <div class="upload-buttons-container">
                    <button id="upload-single-btn">Upload Single File</button>
                    <button id="upload-multiple-btn">Upload Multiple Files</button>
                </div>
                <input type="file" id="file-upload-input" style="display: none;" multiple>
                <span id="upload-status"></span>
            </div>
        </div>
        <div class="resizer" id="drag-handle"></div>
        <div class="main-content">
            <div class="editor-toolbar">
                <span id="file-info"></span>
                <button id="save-btn" disabled>Save</button>
                <span id="save-status"></span>
            </div>
            <div id="viewer">
                <div class="media-wrapper"><p>Select a file to view or edit.</p></div>
            </div>
        </div>
    </div>

    <!-- CodeMirror JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/mode/meta.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/mode/clike/clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.15/mode/htmlmixed/htmlmixed.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fileListEl = document.getElementById('file-list');
            const viewerEl = document.getElementById('viewer');
            let codeMirrorInstance = null;
            let activeFileObject = null;
            let currentFiles = [];
            let fileTree = [];
            let currentUserIP = null;

            const showLoader = (message) => {
                // Ensure CodeMirror is destroyed before replacing viewerEl content
                // This prevents issues if CodeMirror tries to operate on a detached DOM element
                if (codeMirrorInstance) {
                    codeMirrorInstance.toTextArea();
                    codeMirrorInstance = null;
                }
                viewerEl.innerHTML = `<div class="media-wrapper"><div class="loader">${message}</div></div>`;
                document.getElementById('file-info').textContent = '';
                document.getElementById('save-btn').disabled = true;
                document.getElementById('save-status').textContent = '';
            };

            const buildFileTree = (files) => {
                const root = { children: [] };

                files.forEach(file => {
                    let currentNode = root;
                    const parts = file.path.split('/');
                    parts.forEach((part, index) => {
                        const isLastPart = index === parts.length - 1;
                        let childNode = currentNode.children.find(child => child.name === part && child.type === 'directory');

                        if (!childNode) {
                            if (isLastPart) { // It's a file
                                childNode = { type: 'file', name: part, fileObject: file };
                                currentNode.children.push(childNode);
                            } else { // It's a directory
                                const existingPath = parts.slice(0, index + 1).join('/');
                                childNode = { type: 'directory', name: part, path: existingPath, children: [], open: false };
                                currentNode.children.push(childNode);
                            }
                        }
                        currentNode = childNode;
                    });
                });

                const sortTree = (nodes) => {
                    nodes.sort((a, b) => {
                        if (a.type === 'directory' && b.type === 'file') return -1;
                        if (a.type === 'file' && b.type === 'directory') return 1;
                        return a.name.localeCompare(b.name);
                    });
                    nodes.forEach(node => {
                        if (node.type === 'directory') sortTree(node.children);
                    });
                };

                sortTree(root.children);
                return root.children;
            };

            const renderTree = (nodes, container) => {
                nodes.forEach(node => {
                    if (node.type === 'directory') {
                        const li = document.createElement('li');
                        li.className = 'folder-item';
                        li.dataset.path = node.path;

                        const header = document.createElement('div');
                        header.className = 'folder-header';
                        header.innerHTML = `<span class="folder-toggle">${node.open ? '&#9662;' : '&#9656;'}</span><span class="folder-name">${node.name}</span>`;
                        li.appendChild(header);

                        const childrenContainer = document.createElement('ul');
                        childrenContainer.className = 'folder-children';
                        childrenContainer.style.display = node.open ? 'block' : 'none';
                        li.appendChild(childrenContainer);

                        renderTree(node.children, childrenContainer);
                        container.appendChild(li);
                    } else { // file
                        const li = document.createElement('li');
                        li.className = 'file-item';
                        li.title = `${node.fileObject.path} (${(node.fileObject.size / 1024).toFixed(2)} KB)`;
                        li.dataset.path = node.fileObject.path;

                        const fileNameSpan = document.createElement('span');
                        fileNameSpan.className = 'file-name';
                        fileNameSpan.textContent = node.name;
                        li.appendChild(fileNameSpan);

                        if (!node.fileObject.readonly) {
                            const deleteBtn = document.createElement('button');
                            deleteBtn.className = 'delete-btn';
                            deleteBtn.innerHTML = '&#128465;';
                            deleteBtn.title = `Delete ${node.name}`;
                            deleteBtn.dataset.path = node.fileObject.path;
                            li.appendChild(deleteBtn);
                        }

                        if (activeFileObject && node.fileObject.path === activeFileObject.path) {
                            li.classList.add('active');
                        }
                        container.appendChild(li);
                    }
                });
            };

            const renderFileList = () => {
                fileListEl.innerHTML = '';
                if (fileTree.length === 0) {
                    fileListEl.innerHTML = '<li>No files found.</li>';
                    return;
                }
                renderTree(fileTree, fileListEl);
            };

            const loadFile = async (file) => {
                if (!file) return;

                activeFileObject = file;
                renderFileList();
                showLoader(`Loading ${file.path}...`);

                const saveBtn = document.getElementById('save-btn');
                const fileInfoEl = document.getElementById('file-info');
                fileInfoEl.textContent = file.path;

                const isTextEditable = file.type.startsWith('text/') || file.type === 'application/json';
                saveBtn.disabled = file.readonly || !isTextEditable;

                const fileUrl = `api.php?action=get-content&path=${encodeURIComponent(file.path)}`;

                if (isTextEditable) {
                    try {
                        const response = await fetch(fileUrl);
                        if (!response.ok) throw new Error(`Server error: ${response.statusText}`);
                        const data = await response.json();
                        if (!data.success) throw new Error(data.message);

                        viewerEl.innerHTML = ''; // Clear viewer for CodeMirror
                        const textarea = document.createElement('textarea');
                        viewerEl.appendChild(textarea);
                        
                        const modeInfo = CodeMirror.findModeByMIME(data.mime);
                        
                        codeMirrorInstance = CodeMirror.fromTextArea(textarea, {
                            lineNumbers: true,
                            theme: 'material-darker',
                            mode: modeInfo ? modeInfo.mode : 'text/plain',
                            readOnly: file.readonly,
                        });
                        codeMirrorInstance.setValue(data.content);

                    } catch (error) {
                        viewerEl.innerHTML = `<div class="media-wrapper"><p style="color:red;">Error loading text file: ${error.message}</p></div>`;
                    }
                    return;
                }

                // Handle non-text files
                const mediaWrapper = `<div class="media-wrapper">`;
                const mediaWrapperEnd = `</div>`;
                if (file.type.startsWith('image/')) {
                    viewerEl.innerHTML = `${mediaWrapper}<img src="${fileUrl}" alt="${file.path}">${mediaWrapperEnd}`;
                } else if (file.type.startsWith('audio/')) {
                    viewerEl.innerHTML = `${mediaWrapper}<audio controls src="${fileUrl}" title="${file.path}"></audio>${mediaWrapperEnd}`;
                } else if (file.type.startsWith('video/')) {
                    viewerEl.innerHTML = `${mediaWrapper}<video controls src="${fileUrl}" title="${file.path}"></video>${mediaWrapperEnd}`;
                } else if (file.type === 'application/pdf') {
                    viewerEl.innerHTML = `<iframe src="${fileUrl}" width="100%" height="100%" title="${file.path}"></iframe>`;
                } else {
                    viewerEl.innerHTML = `${mediaWrapper}<div><p>Cannot display this file type (${file.type}).</p><a href="${fileUrl}" download="${file.path}">Download ${file.path}</a></div>${mediaWrapperEnd}`;
                }
            };

            const fetchFiles = async () => {
                try {
                    const response = await fetch('api.php?action=list-files');
                    if (!response.ok) throw new Error(`Server error: ${response.statusText}`);
                    const data = await response.json();
                    if (!data.success) throw new Error(data.message);
                    
                    currentFiles = data.files;
                    fileTree = buildFileTree(currentFiles);
                    currentUserIP = data.user_ip;
                    renderFileList();
                } catch (error) {
                    fileListEl.innerHTML = `<li style="color:red;">Error fetching file list: ${error.message}</li>`;
                }
            };

            const handleSave = async () => {
                if (!activeFileObject || activeFileObject.readonly || !codeMirrorInstance) {
                    return;
                }

                const saveBtn = document.getElementById('save-btn');
                const saveStatusEl = document.getElementById('save-status');
                
                saveBtn.disabled = true;
                saveStatusEl.textContent = 'Saving...';
                saveStatusEl.style.color = '#17a2b8';

                try {
                    const content = codeMirrorInstance.getValue();
                    const formData = new FormData();
                    formData.append('path', activeFileObject.path);
                    formData.append('content', content);

                    const response = await fetch('api.php?action=save-file', { method: 'POST', body: formData });
                    if (!response.ok) throw new Error(`Server error: ${response.statusText}`);
                    const result = await response.json();
                    if (!result.success) throw new Error(result.message);

                    saveStatusEl.textContent = `Saved at ${new Date().toLocaleTimeString()}`;
                } catch (error) {
                    saveStatusEl.textContent = `Error: ${error.message}`;
                    saveStatusEl.style.color = 'red';
                } finally {
                    if (activeFileObject && !activeFileObject.readonly) {
                        saveBtn.disabled = false;
                    }
                    setTimeout(() => { saveStatusEl.textContent = ''; }, 4000);
                }
            };

            const handleNewFile = async () => {
                const filename = prompt(`Enter new filename for your folder (${currentUserIP}):`);
                if (!filename) return;

                try {
                    const formData = new FormData();
                    formData.append('filename', filename);
                    const response = await fetch('api.php?action=create-file', { method: 'POST', body: formData });
                    if (!response.ok) throw new Error(`Server error: ${response.statusText}`);
                    const result = await response.json();
                    if (!result.success) throw new Error(result.message);
                    await fetchFiles();
                    const newFile = currentFiles.find(f => f.path === result.path);
                    if (newFile) loadFile(newFile);
                } catch (error) {
                    alert(`Error creating file: ${error.message}`);
                }
            };

            const handleUpload = async (event) => {
                const files = event.target.files;
                if (files.length === 0) return;

                const uploadStatusEl = document.getElementById('upload-status');
                const uploadSingleBtn = document.getElementById('upload-single-btn');
                const uploadMultipleBtn = document.getElementById('upload-multiple-btn');

                uploadStatusEl.textContent = `Uploading ${files.length} file(s)...`;
                uploadStatusEl.style.color = '#17a2b8';
                uploadSingleBtn.disabled = true;
                uploadMultipleBtn.disabled = true;

                const formData = new FormData();
                let successfulUploads = 0;
                let failedUploads = 0;
                let errorMessages = [];

                for (const file of files) {
                    const fileFormData = new FormData(); // Create new FormData for each file
                    fileFormData.append('uploadedFile', file);

                    try {
                        const response = await fetch('api.php?action=upload-file', {
                            method: 'POST',
                            body: fileFormData
                        });
                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.message || `Server error: ${response.statusText}`);
                        }
                        const result = await response.json();
                        if (!result.success) {
                            throw new Error(result.message);
                        }
                        successfulUploads++;
                    } catch (error) {
                        failedUploads++;
                        errorMessages.push(`${file.name}: ${error.message}`);
                    }
                }

                if (failedUploads === 0) {
                    uploadStatusEl.textContent = `Successfully uploaded ${successfulUploads} file(s)!`;
                    uploadStatusEl.style.color = 'green';
                } else if (successfulUploads === 0) {
                    uploadStatusEl.textContent = `Failed to upload any files: ${errorMessages.join('; ')}`;
                    uploadStatusEl.style.color = 'red';
                } else {
                    uploadStatusEl.textContent = `Uploaded ${successfulUploads} file(s), failed ${failedUploads}: ${errorMessages.join('; ')}`;
                    uploadStatusEl.style.color = 'orange';
                }

                await fetchFiles(); // Refresh file list after all uploads
                event.target.value = null; // Reset the input so the user can upload the same file again
                uploadSingleBtn.disabled = false;
                uploadMultipleBtn.disabled = false;
                setTimeout(() => { uploadStatusEl.textContent = ''; }, 10000); // Keep message longer for multiple files
            };

            const handleDelete = async (filePath) => {
                if (!confirm(`Are you sure you want to delete "${filePath}"?`)) {
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('path', filePath);

                    const response = await fetch('api.php?action=delete-file', {
                        method: 'POST',
                        body: formData
                    });
                    if (!response.ok) throw new Error(`Server error: ${response.statusText}`);
                    const result = await response.json();
                    if (!result.success) throw new Error(result.message);

                    // If the deleted file was the one being viewed, clear the editor
                    if (activeFileObject && activeFileObject.path === filePath) {
                        activeFileObject = null;
                        showLoader('File deleted. Select another file.');
                    }

                    await fetchFiles(); // Refresh the file list

                } catch (error) {
                    alert(`Error deleting file: ${error.message}`);
                }
            };

            fileListEl.addEventListener('click', (e) => {
                const target = e.target;

                if (target.classList.contains('delete-btn')) {
                    e.stopPropagation(); // Prevent the li click event from firing
                    handleDelete(target.dataset.path);
                    return;
                }

                const fileItem = target.closest('.file-item');
                if (fileItem && fileItem.dataset.path) {
                    const fileToLoad = currentFiles.find(f => f.path === fileItem.dataset.path);
                    loadFile(fileToLoad);
                    return;
                }

                const folderHeader = target.closest('.folder-header');
                if (folderHeader) {
                    const folderItem = folderHeader.closest('.folder-item');
                    const path = folderItem.dataset.path;
                    const childrenContainer = folderItem.querySelector('.folder-children');
                    const toggle = folderHeader.querySelector('.folder-toggle');

                    const findAndToggle = (nodes, p) => {
                        for (const node of nodes) {
                            if (node.type === 'directory' && node.path === p) {
                                node.open = !node.open;
                                childrenContainer.style.display = node.open ? 'block' : 'none';
                                toggle.innerHTML = node.open ? '&#9662;' : '&#9656;';
                                return;
                            }
                        }
                    };
                    findAndToggle(fileTree, path);
                }
            });

            document.getElementById('save-btn').addEventListener('click', handleSave);
            document.getElementById('new-file-btn').addEventListener('click', handleNewFile);
            document.getElementById('upload-single-btn').addEventListener('click', () => {
                document.getElementById('file-upload-input').removeAttribute('multiple'); // Ensure single file selection
                document.getElementById('file-upload-input').click();
            });
            document.getElementById('upload-multiple-btn').addEventListener('click', () => {
                document.getElementById('file-upload-input').setAttribute('multiple', 'multiple'); // Enable multiple file selection
                document.getElementById('file-upload-input').click(); 
            });
            document.getElementById('file-upload-input').addEventListener('change', handleUpload);

            fetchFiles();

            // --- Sidebar Resizing Logic ---
            const sidebar = document.querySelector('.sidebar');
            const resizer = document.getElementById('drag-handle');

            const resize = (e) => {
                const newWidth = e.clientX - sidebar.getBoundingClientRect().left;
                // Set min and max width for the sidebar
                if (newWidth > 150 && newWidth < 600) {
                    sidebar.style.width = `${newWidth}px`;
                }
            };

            resizer.addEventListener('mousedown', (e) => {
                e.preventDefault();
                document.body.style.cursor = 'col-resize';
                document.body.style.userSelect = 'none';
                document.addEventListener('mousemove', resize);
                document.addEventListener('mouseup', () => {
                    document.removeEventListener('mousemove', resize);
                    document.body.style.cursor = 'default';
                    document.body.style.userSelect = 'auto';
                    // Refresh CodeMirror instance if it exists
                    if (codeMirrorInstance) codeMirrorInstance.refresh();
                }, { once: true });
            });

        });
    </script>
</body>
</html>
