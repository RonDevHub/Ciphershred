const Crypto = {
    async createKey() {
        return await crypto.subtle.generateKey(
            { name: "AES-GCM", length: 256 },
            true,
            ["encrypt", "decrypt"]
        );
    },

    async importKey(base64Key) {
        const rawKey = new Uint8Array(atob(base64Key).split("").map(c => c.charCodeAt(0)));
        return await crypto.subtle.importKey(
            "raw", 
            rawKey, 
            { name: "AES-GCM" }, 
            true, 
            ["encrypt", "decrypt"]
        );
    },

    async encrypt(data, key) {
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const encodedData = new TextEncoder().encode(data);
        const encrypted = await crypto.subtle.encrypt(
            { name: "AES-GCM", iv: iv },
            key,
            encodedData
        );
        const combined = new Uint8Array(iv.length + encrypted.byteLength);
        combined.set(iv);
        combined.set(new Uint8Array(encrypted), 12);
        return btoa(String.fromCharCode(...combined));
    },

    async decrypt(base64Data, key) {
        const combined = new Uint8Array(atob(base64Data).split("").map(c => c.charCodeAt(0)));
        const iv = combined.slice(0, 12);
        const data = combined.slice(12);
        return await crypto.subtle.decrypt(
            { name: "AES-GCM", iv: iv },
            key,
            data
        );
    }
};