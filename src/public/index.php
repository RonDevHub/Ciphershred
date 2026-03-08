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
            <textarea id="text" placeholder="Geheime Nachricht..."></textarea>
            <input type="file" id="file">
            <select id="expires">
                <option value="3600">1 Stunde</option>
                <option value="86400">1 Tag</option>
                <option value="604800">1 Woche</option>
            </select>
            <button id="shred-btn" onclick="app.shred()">Verschlüsseln & Shreddern</button>
        </div>

        <div id="view-result" class="hidden">
            <div id="link-display">
                <p>Full Link:</p><input id="res-full" readonly onclick="this.select()">
                <p>ID:</p><input id="res-id" readonly onclick="this.select()">
                <p>Key:</p><input id="res-key" readonly onclick="this.select()">
                <button onclick="location.hash=''; location.reload()" style="margin-top:15px; background:#444;">Neu</button>
            </div>
            <div id="read-display" class="hidden">
                <div id="decrypted-text" style="background:#000; padding:15px; border-radius:5px; white-space:pre-wrap;"></div>
                <div id="file-area" class="hidden">
                    <a id="download-link" style="display:block; background:green; color:white; padding:10px; text-align:center; text-decoration:none; margin-top:10px; border-radius:5px;">Datei laden</a>
                </div>
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
                const btn = document.getElementById('shred-btn');
                const text = document.getElementById('text').value;
                const file = document.getElementById('file').files[0];
                if(!text && !file) return this.toast("Eingabe fehlt!", true);

                try {
                    btn.disabled = true; btn.innerText = "Sichere Daten...";
                    const key = await Crypto.createKey();
                    const rawKey = btoa(String.fromCharCode(...new Uint8Array(await crypto.subtle.exportKey("raw", key))));
                    
                    let fd = new FormData();
                    fd.append('expires', document.getElementById('expires').value);
                    if(text) fd.append('content', await Crypto.encrypt(text, key));
                    if(file) fd.append('file', file);

                    const res = await fetch('api/upload.php', { method: 'POST', body: fd });
                    const data = await res.json();
                    if(!data.success) throw new Error(data.error);

                    document.getElementById('view-create').classList.add('hidden');
                    document.getElementById('view-result').classList.remove('hidden');
                    const url = window.location.origin + window.location.pathname + "#id=" + data.id + "&key=" + rawKey;
                    document.getElementById('res-full').value = url;
                    document.getElementById('res-id').value = data.id;
                    document.getElementById('res-key').value = rawKey;
                    this.toast("Erfolg!");
                } catch(e) { this.toast(e.message, true); console.error(e); }
                finally { btn.disabled = false; btn.innerText = "Shredden"; }
            },
            async load() {
                const params = new URLSearchParams(window.location.hash.substring(1));
                if(params.has('id') && params.has('key')) {
                    document.getElementById('view-create').classList.add('hidden');
                    document.getElementById('view-result').classList.remove('hidden');
                    document.getElementById('link-display').classList.add('hidden');
                    document.getElementById('read-display').classList.remove('hidden');
                    try {
                        const res = await fetch('api/download.php?id=' + params.get('id'));
                        if(!res.ok) throw new Error();
                        const key = await Crypto.importKey(params.get('key'));
                        const type = res.headers.get('Content-Disposition');
                        if(type && type.includes('note.enc')) {
                            const dec = await Crypto.decrypt(await res.text(), key);
                            document.getElementById('decrypted-text').innerText = new TextDecoder().decode(dec);
                        } else {
                            const blob = await res.blob();
                            document.getElementById('decrypted-text').innerText = "Datei bereit zum Download.";
                            document.getElementById('file-area').classList.remove('hidden');
                            document.getElementById('download-link').href = URL.createObjectURL(blob);
                            document.getElementById('download-link').download = "shred_file";
                        }
                    } catch(e) { document.getElementById('decrypted-text').innerText = "Link ungültig oder vernichtet."; }
                }
            }
        };
        window.onload = app.load;
    </script>
</body>
</html>