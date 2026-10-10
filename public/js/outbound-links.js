function outboundUrl(value, placement) {
    try {
        const url = new URL(value);
        if (!['http:', 'https:'].includes(url.protocol) || url.origin === location.origin) return value;
        const [address, ...fragment] = value.split('#');
        const separator = address.indexOf('?');
        const path = separator === -1 ? address : address.slice(0, separator);
        const query = separator === -1 ? '' : address.slice(separator + 1);
        const keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content'];
        const parameters = query.split('&').filter(parameter => parameter && !keys.includes(new URLSearchParams(parameter).keys().next().value));
        parameters.push(new URLSearchParams({utm_source:window.outboundSource || location.hostname, utm_medium:'referral', utm_campaign:'outbound_links', utm_content:placement}).toString());
        return path + '?' + parameters.join('&') + (fragment.length ? '#' + fragment.join('#') : '');
    } catch { return value; }
}
