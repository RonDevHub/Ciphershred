const Crypto = {
    async createKey() {
        return await crypto.subtle.generateKey({name: "AES-GCM", length: 256}, true, ["encrypt", "decrypt"]);
    },
    async encrypt(data, key) {
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const enc = await crypto.subtle.encrypt({name: "AES-GCM", iv}, key, new TextEncoder().encode(data));
        const combined = new Uint8Array(iv.length + enc.byteLength);
        combined.set(iv); combined.set(new Uint8Array(enc), 12);
        return btoa(String.fromCharCode(...combined));
    },
    async decrypt(b64, key) {
        const buf = new Uint8Array(atob(b64).split("").map(c => c.charCodeAt(0)));
        return await crypto.subtle.decrypt({name: "AES-GCM", iv: buf.slice(0,12)}, key, buf.slice(12));
    }
};