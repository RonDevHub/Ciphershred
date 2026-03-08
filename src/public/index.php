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
        <h1>Ciphershred</h1>
        
        <div id="view-create">
            <textarea id="text" placeholder="Deine geheime Nachricht..."></textarea>
            <div class="file-upload">
                <label for="file">Optional: Datei (max 50MB)</label>
                <input type="file" id="file">
            </div>
            <select id="expires">
                <option value="3600">1 Stunde</option>
                <option value="86400">1 Tag</option>
                <option value="604800">1 Woche</option>
            </select>
            <button id="shred-btn" onclick="app.shred()">Verschlüsseln & Shreddern</button>
        </div>

        <div id="view-result" class="hidden">
            <div id="link-display">
                <div class="field">
                    <label>Full Link (inkl. Key):</label>
                    <input id="res-full" readonly onclick="this.select()">
                </div>
                <div class="field">
                    <label>Safe Link (nur ID):</label>
                    <input id="res-id" readonly onclick="this.select()">
                </div>
                <div class="field">
                    <label>Secret Key:</label>
                    <input id="res-key" readonly onclick="this.select()">
                </div>
                <button onclick="location.hash=''; location.reload()" style="margin-top:20px; background:#444;">Neue Nachricht</button>
            </div>
            
            <div id="read-display" class="hidden">
                <h3>Entschlüsselte Nachricht:</h3>
                <div id="decrypted-text"></div>
                <div id="file-area" class="hidden">
                    <hr>
                    <p>Anhang gefunden:</p>
                    <a id="download-link" class="btn-download">Datei herunterladen</a>
                </div>
                <p style="color:#ff4757; font-size:0.8em; margin-top:20px;">ℹ️ Diese Nachricht wurde soeben vom Server gelöscht.</p>
            </div>
        </div>
    </div>

    <script src="js/crypto.js"></script>
    <script>
        const app = {
            toast(msg, err = false) {
                const t = document.getElementById('toast');
                t.innerText = msg;
                t.className = err ? 'error' : 'success';
                setTimeout(() => t.className = 'hidden', 4000);
            },

            async shred() {
                const btn = document.getElementById('shred-btn');
                const textVal = document.getElementById('text').value;
                const fileInput = document.getElementById('file');
                
                if(!textVal && !fileInput.files[0]) {
                    return this.toast("Bitte Text eingeben oder Datei wählen!", true);
                }

                try {
                    btn.disabled = true;
                    btn.innerText = "Sichere Daten...";

                    const key = await Crypto.createKey();
                    const rawKey = btoa(String.fromCharCode(...new Uint8Array(await crypto.subtle.exportKey("raw", key))));
                    
                    let fd = new FormData();
                    fd.append('expires', document.getElementById('expires').value);

                    if (textVal) {
                        const encryptedText = await Crypto.encrypt(textVal, key);
                        fd.append('content', encryptedText);
                    }

                    if (fileInput.files[0]) {
                        fd.append('file', fileInput.files[0]);
                    }

                    const res = await fetch('api/upload.php', { method: 'POST', body: fd });
                    const data = await res.json();

                    if(!data.success) throw new Error(data.error);

                    document.getElementById('view-create').classList.add('hidden');
                    document.getElementById('view-result').classList.remove('hidden');
                    
                    const baseUrl = window.location.origin + window.location.pathname;
                    document.getElementById('res-full').value = `${baseUrl}#id=${data.id}&key=${rawKey}`;
                    document.getElementById('res-id').value = data.id;
                    document.getElementById('res-key').value = rawKey;
                    
                    this.toast("Erfolgreich verschlüsselt!");
                } catch(e) {
                    this.toast("Fehler: " + e.message, true);
                } finally {
                    btn.disabled = false;
                    btn.innerText = "Verschlüsseln & Shreddern";
                }
            },

            async load() {
                const urlParams = new URLSearchParams(window.location.hash.substring(1));
                const id = urlParams.get('id');
                const keyStr = urlParams.get('key');

                if(id && keyStr) {
                    document.getElementById('view-create').classList.add('hidden');
                    document.getElementById('view-result').classList.remove('hidden');
                    document.getElementById('link-display').classList.add('hidden');
                    document.getElementById('read-display').classList.remove('hidden');

                    try {
                        const res = await fetch('api/download.php?id=' + id);
                        if(!res.ok) throw new Error("Nicht gefunden");

                        // Prüfen ob es eine Datei oder Text ist
                        const contentType = res.headers.get('Content-Type');
                        const key = await Crypto.importKey(keyStr);

                        if (contentType === 'application/octet-stream') {
                            // Es ist eine Datei
                            document.getElementById('decrypted-text').innerText = "Datei empfangen. Klicke zum Speichern.";
                            document.getElementById('file-area').classList.remove('hidden');
                            const blob = await res.blob();
                            const url = window.URL.createObjectURL(blob);
                            const dl = document.getElementById('download-link');
                            dl.href = url;
                            dl.download = "decrypted_file";
                        } else {
                            // Es ist Text
                            const cipher = await res.text();
                            const dec = await Crypto.decrypt(cipher, key);
                            document.getElementById('decrypted-text').innerText = new TextDecoder().decode(dec);
                        }
                    } catch(e) {
                        document.getElementById('decrypted-text').innerText = "❌ Nachricht abgelaufen oder bereits vernichtet.";
                    }
                }
            }
        };
        window.onload = app.load;
    </script>
</body>
</html>