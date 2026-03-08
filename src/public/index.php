<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8"><title>Ciphershred</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Ciphershred</h2>
        <div id="ui-upload">
            <textarea id="text" placeholder="Nachricht..."></textarea>
            <input type="file" id="file">
            <select id="expires">
                <option value="3600">1 Stunde</option>
                <option value="86400">1 Tag</option>
            </select>
            <button onclick="app.shred()">Verschlüsseln & Hochladen</button>
        </div>
        <div id="ui-result" class="hidden">
            <p>Full Link:</p><input id="res-full" readonly>
            <p>Safe Link:</p><input id="res-id" readonly>
            <p>Key:</p><input id="res-key" readonly>
        </div>
    </div>
    <script src="js/crypto.js"></script>
    <script>
        const app = {
            async shred() {
                const key = await Crypto.createKey();
                const rawKey = btoa(String.fromCharCode(...new Uint8Array(await crypto.subtle.exportKey("raw", key))));
                const cipher = await Crypto.encrypt(document.getElementById('text').value, key);
                
                let fd = new FormData();
                fd.append('content', cipher);
                fd.append('expires', document.getElementById('expires').value);
                if(document.getElementById('file').files[0]) fd.append('file', document.getElementById('file').files[0]);

                const res = await fetch('/api/upload.php', {method: 'POST', body: fd});
                const data = await res.json();
                
                document.getElementById('ui-upload').classList.add('hidden');
                document.getElementById('ui-result').classList.remove('hidden');
                document.getElementById('res-full').value = window.location.origin + "/#id=" + data.id + "&key=" + rawKey;
                document.getElementById('res-id').value = data.id;
                document.getElementById('res-key').value = rawKey;
            }
        };
    </script>
</body>
</html>