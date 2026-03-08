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
            <textarea id="text" placeholder="Nachricht..."></textarea>
            <input type="file" id="file">
            <select id="expires">
                <option value="3600">1 Stunde</option>
                <option value="86400">1 Tag</option>
                <option value="604800">1 Woche</option>
            </select>
            <button id="shred-btn" onclick="app.shred()">Shredden</button>
        </div>
        <div id="view-result" class="hidden">
            <div id="link-display">
                <p>Full Link:</p><input id="res-full" readonly>
                <p>Safe Link:</p><input id="res-id" readonly>
                <p>Key:</p><input id="res-key" readonly>
                <button onclick="location.reload()" style="margin-top:10px; background:#444;">Neu</button>
            </div>
            <div id="read-display" class="hidden">
                <h3>Nachricht:</h3>
                <div id="decrypted-text" style="background:#111; padding:15px; border-radius:5px; margin-bottom:10px;"></div>
                <p style="color:red; font-size:0.8em;">Diese Nachricht wurde vom Server gelöscht.</p>
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
                try {
                    btn.disabled = true; btn.innerText = "Processing...";
                    const key = await Crypto.createKey();
                    const rawKey = btoa(String.fromCharCode(...new Uint8Array(await crypto.subtle.exportKey("raw", key))));
                    const cipher = await Crypto.encrypt(document.getElementById('text').value || "Kein Text", key);
                    
                    let fd = new FormData();
                    fd.append('content', cipher);
                    fd.append('expires', document.getElementById('expires').value);
                    if(document.getElementById('file').files[0]) fd.append('file', document.getElementById('file').files[0]);

                    const res = await fetch('api/upload.php', {method: 'POST', body: fd});
                    const data = await res.json();
                    
                    document.getElementById('view-create').classList.add('hidden');
                    document.getElementById('view-result').classList.remove('hidden');
                    document.getElementById('res-full').value = window.location.origin + window.location.pathname + "#id=" + data.id + "&key=" + rawKey;
                    document.getElementById('res-id').value = data.id;
                    document.getElementById('res-key').value = rawKey;
                } catch(e) { this.toast("Fehler!", true); }
                finally { btn.disabled = false; btn.innerText = "Shredden"; }
            },
            async load() {
                const p = new URLSearchParams(window.location.hash.substring(1));
                if(p.has('id') && p.has('key')) {
                    document.getElementById('view-create').classList.add('hidden');
                    document.getElementById('view-result').classList.remove('hidden');
                    document.getElementById('link-display').classList.add('hidden');
                    document.getElementById('read-display').classList.remove('hidden');
                    try {
                        const res = await fetch('api/download.php?id=' + p.get('id'));
                        const cipher = await res.text();
                        const key = await Crypto.importKey(p.get('key'));
                        const dec = await Crypto.decrypt(cipher, key);
                        document.getElementById('decrypted-text').innerText = new TextDecoder().decode(dec);
                    } catch(e) { document.getElementById('decrypted-text').innerText = "Fehler oder bereits gelöscht."; }
                }
            }
        };
        window.onload = app.load;
    </script>
</body>
</html>