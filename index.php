
<?php
// 📦 Fatima GitHub Repo Viewer (Advanced v3)

// --------- GLOBAL CONFIG ---------
$reposDir = __DIR__ . "/repos";
$repoListFile = "$reposDir/repos.json";
if (!file_exists($reposDir)) mkdir($reposDir);
if (!file_exists($repoListFile)) file_put_contents($repoListFile, json_encode([]));

$repoUrl = $_GET['repo'] ?? '';
$selectedRepo = $_GET['select'] ?? '';
$treeMode = isset($_GET['tree']);
$repos = json_decode(file_get_contents($repoListFile), true);

// Get all existing repo folders
$existingRepos = [];
if (is_dir($reposDir)) {
    $items = scandir($reposDir);
    foreach ($items as $item) {
        if ($item != "." && $item != ".." && $item != "repos.json" && is_dir("$reposDir/$item")) {
            $existingRepos[] = $item;
        }
    }
}

// Update repos list with existing folders
$allRepos = array_unique(array_merge($repos, $existingRepos));
file_put_contents($repoListFile, json_encode($allRepos));
$repos = $allRepos;

function getRepoInfo($url) {
    $url = trim($url);
    if (preg_match('/github.com\/(.+?)\/(.+?)(\.git)?$/', $url, $m)) {
        return ["user" => $m[1], "name" => $m[2], "branch" => "main"];
    }
    return null;
}

function isReadableTextFile($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed = ['txt','md','html','php','js','css','json','py','c','cpp','xml','ts','sh','java','rb','go','swift','kt','rs','m','pl','r','vbs','vb','ps1','ini','conf','env','yaml','yml','cs','asp','jsp','lua','sql','jsx','tsx'];
    return in_array($ext, $allowed);
}

function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'txt' => '📄', 'md' => '📝', 'html' => '🌐', 'php' => '🐘', 'js' => '📜', 'jsx' => '⚛️',
        'css' => '🎨', 'json' => '🔧', 'py' => '🐍', 'c' => '⚙️', 'cpp' => '⚙️', 'xml' => '📋',
        'ts' => '📘', 'tsx' => '⚛️', 'sh' => '🔧', 'java' => '☕', 'rb' => '💎', 'go' => '🐹',
        'swift' => '🍎', 'kt' => '🔥', 'rs' => '🦀', 'sql' => '🗃️', 'yml' => '📋', 'yaml' => '📋',
        'png' => '🖼️', 'jpg' => '🖼️', 'jpeg' => '🖼️', 'gif' => '🖼️', 'svg' => '🖼️',
        'pdf' => '📕', 'zip' => '📦', 'tar' => '📦', 'gz' => '📦'
    ];
    return $icons[$ext] ?? '📄';
}

function sanitize($str) {
    return htmlspecialchars($str, ENT_QUOTES);
}

function listFiles($dir, &$fileCount, &$folderCount, &$types, $base = '', $level = 0) {
    $html = '';
    $files = scandir($dir);
    $indent = str_repeat('  ', $level);
    
    foreach ($files as $file) {
        if ($file == "." || $file == "..") continue;
        $fullPath = "$dir/$file";
        $relativePath = ltrim("$base/$file", '/');
        $safeId = 'content-' . md5($relativePath);
        
        if (is_dir($fullPath)) {
            $folderCount++;
            $html .= "<div class='folder' style='margin-left: " . ($level * 20) . "px;'>📁 $file</div>";
            $html .= "<div class='folder-content'>" . listFiles($fullPath, $fileCount, $folderCount, $types, $relativePath, $level + 1) . "</div>";
        } else {
            $fileCount++;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $types[$ext] = ($types[$ext] ?? 0) + 1;
            $icon = getFileIcon($file);
            $class = "file-$ext";
            $isReadable = isReadableTextFile($file);
            $readableIcon = $isReadable ? '👁️' : '❌';
            
            $html .= "<div class='file $class' style='margin-left: " . ($level * 20) . "px;' onclick=\"toggleContent('$safeId')\">";
            $html .= "$icon $file <span class='readable-indicator'>$readableIcon</span>";
            $html .= "</div>";
            
            if ($isReadable) {
                $content = sanitize(file_get_contents($fullPath));
                $html .= "<div class='code-container' id='$safeId' style='display:none;'>";
                $html .= "<button class='copy-btn' onclick=\"copyContent('$safeId', event)\">📋 Copy</button>";
                $html .= "<pre class='code-content'>$content</pre>";
                $html .= "</div>";
            }
        }
    }
    return $html;
}

if ($repoUrl) {
    $info = getRepoInfo($repoUrl);
    if ($info) {
        $user = $info['user'];
        $name = $info['name'];
        $branch = $info['branch'];
        $folderName = "$name-$branch";
        $repoPath = "$reposDir/$folderName";

        if (!is_dir($repoPath)) {
            $downloadUrl = "https://github.com/$user/$name/archive/refs/heads/$branch.zip";
            $localZip = "$reposDir/$folderName.zip";
            file_put_contents($localZip, fopen($downloadUrl, 'r'));
            $zip = new ZipArchive;
            if ($zip->open($localZip) === TRUE) {
                $zip->extractTo($reposDir);
                $zip->close();
                unlink($localZip);
            }
            if (!in_array($folderName, $repos)) {
                $repos[] = $folderName;
                file_put_contents($repoListFile, json_encode($repos));
            }
        }
        $selectedRepo = $folderName;
    }
}

?><!DOCTYPE html><html>
<head>
    <title>📂 GitHub Repo Viewer – Fatima x Arjun</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); 
            color: #fff; 
            min-height: 100vh;
        }
        
        .header {
            background: rgba(0,0,0,0.2);
            padding: 15px 20px;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .header h1 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .stats {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .stat-item {
            background: rgba(255,255,255,0.1);
            padding: 5px 12px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .menu-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(0,0,0,0.8);
            color: white;
            border: none;
            padding: 12px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        
        .menu-button:hover {
            background: rgba(0,0,0,0.9);
            transform: translateY(-2px);
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: -350px;
            width: 350px;
            height: 100vh;
            background: rgba(0,0,0,0.95);
            backdrop-filter: blur(20px);
            transition: left 0.3s ease;
            z-index: 999;
            padding: 80px 20px 20px;
            overflow-y: auto;
        }
        
        .sidebar.open {
            left: 0;
        }
        
        .sidebar h3 {
            margin-bottom: 15px;
            color: #4fc3f7;
            font-size: 1.2rem;
        }
        
        .sidebar form {
            margin-bottom: 30px;
        }
        
        .sidebar input[type=text], .sidebar select {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .sidebar input[type=text]:focus, .sidebar select:focus {
            outline: none;
            border-color: #4fc3f7;
            box-shadow: 0 0 0 2px rgba(79, 195, 247, 0.2);
        }
        
        .sidebar button {
            width: 100%;
            background: linear-gradient(45deg, #4fc3f7, #29b6f6);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        
        .sidebar button:hover {
            background: linear-gradient(45deg, #29b6f6, #4fc3f7);
            transform: translateY(-1px);
        }
        
        .content {
            margin-left: 0;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        
        .folder { 
            color: #4fc3f7; 
            margin: 8px 0; 
            font-weight: bold; 
            cursor: pointer;
            padding: 8px;
            background: rgba(255,255,255,0.05);
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .folder:hover {
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        
        .folder-content { 
            margin-left: 20px; 
            border-left: 2px solid rgba(79, 195, 247, 0.3);
            padding-left: 10px;
        }
        
        .file { 
            cursor: pointer; 
            margin: 5px 0; 
            padding: 8px;
            background: rgba(255,255,255,0.05);
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .file:hover {
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        
        .readable-indicator {
            margin-left: 10px;
            font-size: 0.8rem;
        }
        
        .file-html, .file-php { color: #00e5ff; }
        .file-js, .file-jsx { color: #ffeb3b; }
        .file-css { color: #4caf50; }
        .file-json { color: #e91e63; }
        .file-md, .file-txt { color: #ccc; }
        .file-py { color: #ffeb3b; }
        .file-ts, .file-tsx { color: #2196f3; }
        
        .code-container {
            position: relative;
            margin: 10px 0 20px;
            background: rgba(0,0,0,0.8);
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.1);
            overflow: hidden;
        }
        
        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(79, 195, 247, 0.8);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            z-index: 10;
            transition: all 0.3s ease;
        }
        
        .copy-btn:hover {
            background: rgba(79, 195, 247, 1);
            transform: translateY(-1px);
        }
        
        .code-content {
            background: transparent;
            color: #4caf50;
            padding: 15px;
            overflow-x: auto;
            margin: 0;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            line-height: 1.5;
            white-space: pre-wrap;
            word-wrap: break-word;
            padding-top: 45px;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 998;
            display: none;
        }
        
        .overlay.show {
            display: block;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                left: -100%;
            }
            
            .stats {
                flex-direction: column;
                gap: 10px;
            }
            
            .stat-item {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <button class="menu-button" onclick="toggleSidebar()">☰ Menu</button>
    
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>
    
    <div class="sidebar" id="sidebar">
        <h3>🔄 Load New Repo</h3>
        <form method="get">
            <input type="text" name="repo" placeholder="https://github.com/user/repo.git" />
            <button type="submit">🔄 Load Repository</button>
        </form>
        
        <h3>📂 Select Repository</h3>
        <form method="get">
            <select name="select" onchange="this.form.submit()">
                <option value="">-- Select Repository --</option>
                <?php foreach ($repos as $r): ?>
                    <option value="<?= sanitize($r) ?>" <?= $selectedRepo === $r ? 'selected' : '' ?>><?= sanitize($r) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="tree" value="1">🌳 Tree View</button>
        </form>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <h3>📊 Legend</h3>
            <div style="font-size: 0.9rem; line-height: 1.8;">
                <div>👁️ - Readable file</div>
                <div>❌ - Binary/Non-readable file</div>
                <div>📁 - Folder</div>
                <div>📋 - Copy button (for readable files)</div>
            </div>
        </div>
    </div>
    
    <?php
    if ($selectedRepo && is_dir("$reposDir/$selectedRepo")) {
        $fileCount = 0; 
        $folderCount = 0; 
        $types = [];
        
        // Get counts first
        ob_start();
        $fileTree = listFiles("$reposDir/$selectedRepo", $fileCount, $folderCount, $types);
        ob_end_clean();
        
        // Calculate total files and type counts
        $totalFiles = array_sum($types);
        $typesList = '';
        foreach ($types as $type => $count) {
            $typesList .= " $type($count)";
        }
        
        echo "<div class='header'>";
        echo "<h1>📁 " . sanitize($selectedRepo) . "</h1>";
        echo "<div class='stats'>";
        echo "<div class='stat-item'>📂 Folders: $folderCount</div>";
        echo "<div class='stat-item'>📄 Files: $totalFiles</div>";
        echo "<div class='stat-item'>🏷️ Types: $typesList</div>";
        echo "</div>";
        echo "</div>";
        
        echo "<div class='content'>";
        echo $fileTree;
        echo "</div>";
    } else {
        echo "<div class='content'>";
        echo "<div style='text-align: center; margin-top: 100px; font-size: 1.2rem;'>";
        echo "<h2>📂 Welcome to GitHub Repo Viewer</h2>";
        echo "<p style='margin-top: 20px; opacity: 0.8;'>Click the menu button to load or select a repository</p>";
        echo "</div>";
        echo "</div>";
    }
    ?>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                sidebar.classList.add('open');
                overlay.classList.add('show');
            }
        }
        
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        }
        
        function toggleContent(id) {
            const el = document.getElementById(id);
            if (el) {
                el.style.display = el.style.display === 'none' ? 'block' : 'none';
            }
        }
        
        function copyContent(id, event) {
            event.stopPropagation();
            const container = document.getElementById(id);
            const codeContent = container.querySelector('.code-content');
            const text = codeContent ? codeContent.innerText : '';
            
            navigator.clipboard.writeText(text).then(() => {
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '✅ Copied!';
                btn.style.background = '#4caf50';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = 'rgba(79, 195, 247, 0.8)';
                }, 1500);
            }).catch(() => {
                alert('Copy failed! Please select and copy manually.');
            });
        }
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const menuButton = document.querySelector('.menu-button');
            
            if (!sidebar.contains(e.target) && !menuButton.contains(e.target)) {
                closeSidebar();
            }
        });
    </script>
</body>
</html>
