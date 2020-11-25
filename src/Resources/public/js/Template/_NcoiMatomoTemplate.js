class _NcoiMatomoTemplate extends NcoiTemplate{

    setCookies (trackingId) {
        if (this.hasContaoTemplate) {
            this.addContaoTemplate();
        } else if (this.getWrapper().length === 0 && this.getScript().length === 0) {
            this.executeDefault(trackingId)
        }
    }

    hasContaoTemplate() {
        let script = this.getScript();
        let wrapper = this.getWrapper();
        return script.length > 0 && wrapper.length === 0;
    }

    addContaoTemplate() {
        let script = this.getScript();
        let templateScriptsEncode = script.html();
        templateScriptsEncode = templateScriptsEncode.replace('<!--', '');
        templateScriptsEncode = templateScriptsEncode.replace('-->', '');
        try {
            templateScriptsEncode = atob(templateScriptsEncode);
        } catch (e) {
            console.error('Das Analyse Template enthält invalide Zeichen.')
        }
        script.after(templateScriptsEncode);
    }

    executeDefault(tool,body) {
        let trackingId = this.getTrackingId(tool);
        let url = this.getUrl(tool);
        body.append("<script type=\"text/javascript\">" +
            "var _paq = window._paq || [];" +
            "_paq.push(['trackPageView']);" +
            "_paq.push(['enableLinkTracking']);" +
            "(function() {" +
            "var u = '" + url + "';" +
            "_paq.push(['setTrackerUrl', u+'matomo.php']);" +
            "_paq.push(['setSiteId', '" + trackingId + "']);" +
            "var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];" +
            "g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);" +
            "})();" +
            "</script>");
    }

    getWrapper() {
        let $ = this.$;
        return $('.analytics-decoded-matomo');
    }

    getScript() {
        let $ = this.$;
        return $('#analytics-encoded-matomo');
    }

    remove() {
        let wrapperGoogle = this.getWrapper();
        if (wrapperGoogle !== null)
            wrapperGoogle.remove();
    }

}