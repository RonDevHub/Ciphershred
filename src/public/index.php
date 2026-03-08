<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ciphershred</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div id="toast" class="hidden"></div>
    <div class="container">
        <h1 id="ui-title">Ciphershred</h1>
        <p id="ui-subtitle">Sicheres Teilen.</p>

        <!-- Create Secret -->
        <div id="view-create">
            <textarea id="text" placeholder="Nachricht..."></textarea>
            <input type="file" id="file">
            <select id="expires">
                <option value="3600">1 Stunde</option>
                <option value="86400">1 Tag</option>
                <option value="604800">1 Woche</option>
            </select>
            <button onclick="app.shred()">Shredden</button>
        </div>

        <!-- Result / Read View -->
        <div id="view-result" class="hidden">
            <div id="read-content" class="hidden">
                <h3>Deine Nachricht:</h3>
                <div id="decrypted-text" style="background:#222; padding:15px; white-space:pre-wrap;"></div>
                <div id="file-download-area" class="hidden">
                    <button id="download-btn">Datei herunterladen</button>
                </div>
            </div>
            <div id="link-display" class="hidden">
                <p>Full Link (inkl. Key):</p><input id="res-full" readonly onclick="this.select()">
                <p>Safe Link (ohne Key):</p><input id="res-id" readonly onclick="this.select()">
                <p>Key:</p><input id="res-key" readonly onclick="this.select()">
            </div>
        </div>
    </div>

    <script src="js/crypto.js"></script>
    <script>
        const app = {
            toast(msg, err = false) {
                const t = document.getElementById('toast');
                t.innerText = msg; t.className = err ? 'error' : 'success';
                setTimeout(() => t.className = 'hidden', 3000);
            },

            async shred() {
                try {
                    const key = await Crypto.createKey();
                    const rawKey = btoa(String.fromCharCode(...new Uint8Array(await crypto.subtle.exportKey("raw", key))));
                    const cipher = await Crypto.encrypt(document.getElementById('text').value, key);
                    
                    let fd = new FormData();
                    fd.append('content', cipher);
                    fd.append('expires', document.getElementById('expires').value);
                    if(document.getElementById('file').files[0]) fd.append('file', document.getElementById('file').files[0]);

                    const res = await fetch('/api/upload.php', {method: 'POST', body: fd});
                    const data = await res.json();
                    
                    document.getElementById('view-create').classList.add('hidden');
                    document.getElementById('view-result').classList.remove('hidden');
                    document.getElementById('link-display').classList.remove('hidden');
                    
                    const full = window.location.origin + "/#id=" + data.id + "&key=" + rawKey;
                    document.getElementById('res-full').value = full;
                    document.getElementById('res-id').value = data.id;
                    document.getElementById('res-key').value = rawKey;
                    this.toast("Verschlüsselt!");
                } catch(e) { this.toast("Fehler!", true); }
            },

            async checkHash() {
                const params = new URLSearchParams(window.location.hash.substring(1));
                const id = params.get('id');
                const keyStr = params.get('key');

                if (id && keyStr) {
                    document.getElementById('view-create').classList.add('hidden');
                    document.getElementById('view-result').classList.remove('hidden');
                    document.getElementById('read-content').classList.remove('hidden');
                    
                    try {
                        const key = await Crypto.importKey(keyStr);
                        const res = await fetch('/api/download.php?id=' + id);
                        if (!res.ok) throw new Error();
                        
                        const encryptedData = await res.text();
                        const decrypted = await Crypto.decrypt(encryptedData, key);
                        document.getElementById('decrypted-text').innerText = new TextDecoder().decode(decrypted);
                        this.toast("Entschlüsselt & Vernichtet!");
                    } catch(e) {
                        document.getElementById('decrypted-text').innerText = "Nachricht existiert nicht oder wurde bereits gelöscht.";
                    }
                }
            }
        };
        window.onload = () => app.checkHash();
    </script>
</body>
</html>