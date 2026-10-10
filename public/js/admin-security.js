(() => {
    const translate = text => window.adminTranslations?.[text] ?? text;
    const status = document.querySelector('[data-security-status]');
    function decode(value) { return Uint8Array.from(atob(value.replace(/-/g,'+').replace(/_/g,'/')), char => char.charCodeAt(0)); }
    function encode(value) { return btoa(String.fromCharCode(...new Uint8Array(value))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,''); }
    async function api(path, method='GET', body) {
        const response = await fetch('/admin/'+path, {method, headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}, ...(body ? {body:JSON.stringify(body)} : {})});
        const data = await response.json().catch(()=>({}));
        if (!response.ok) {
            if (response.status === 423) { location.href='/admin/user/confirm-password'; }
            throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || translate('Die Anfrage ist fehlgeschlagen.'));
        }
        return data;
    }
    document.querySelectorAll('[data-passkey]').forEach(button => button.addEventListener('click', async () => {
        if (!window.isSecureContext || !window.PublicKeyCredential) { status.textContent=translate('Passkeys benötigen HTTPS und einen unterstützten Browser.'); return; }
        button.disabled=true;status.textContent=translate('Bitte bestätige die Anfrage auf deinem Gerät.');
        try {
            const action=button.dataset.passkey;
            const registering=action==='register';
            const path=registering ? 'user/passkeys' : 'passkeys/'+action;
            const data=await api(path + (registering ? '/options' : '/options'));
            const options=data.options;
            options.challenge=decode(options.challenge);
            if (registering) options.user.id=decode(options.user.id);
            for (const key of ['allowCredentials','excludeCredentials']) for (const descriptor of options[key] || []) descriptor.id=decode(descriptor.id);
            const credential=await navigator.credentials[registering ? 'create' : 'get']({publicKey:options});
            if (!credential) throw new Error(translate('Die Passkey-Anfrage wurde abgebrochen.'));
            const response=credential.response;
            const serialized={id:credential.id,rawId:encode(credential.rawId),type:credential.type,authenticatorAttachment:credential.authenticatorAttachment,clientExtensionResults:credential.getClientExtensionResults(),response:{clientDataJSON:encode(response.clientDataJSON)}};
            if (registering) { serialized.response.attestationObject=encode(response.attestationObject);serialized.response.transports=response.getTransports?.() || []; }
            else { serialized.response.authenticatorData=encode(response.authenticatorData);serialized.response.signature=encode(response.signature);serialized.response.userHandle=response.userHandle ? encode(response.userHandle) : null; }
            await api(path, 'POST', {credential:serialized, ...(registering ? {name:document.querySelector('#passkey-name').value || translate('Mein Gerät')} : {remember:false})});
            location.href=registering ? '/admin/security' : action==='confirm' ? '/admin' : '/admin';
        } catch(error) { status.textContent=error.name==='NotAllowedError' ? translate('Die Passkey-Anfrage wurde abgebrochen.') : error.message; }
        finally {button.disabled=false;}
    }));
})();
