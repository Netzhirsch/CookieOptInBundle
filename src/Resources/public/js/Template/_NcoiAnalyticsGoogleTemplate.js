class _NcoiAnalyticsGoogleTemplate extends NcoiTemplate{

    setCookies (tool,body = null) {
        let googleConsent = $('.ncoi---behind').data('google-consent');
        if (googleConsent === 1) {
            gtag('consent', 'update', {
                'ad_user_data': 'granted',
                'ad_personalization': 'granted',
                'ad_storage': 'granted',
                'analytics_storage': 'granted'
            });
        } else {
            if (this.hasContaoGoogleTemplate()) {
                this.addContaoTemplate(body);
            } else {
                this.executeDefault(this.getTrackingId(tool))
            }
        }
    }

    hasContaoGoogleTemplate() {
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
        let $ = this.$;
        $('head').prepend(templateScriptsEncode);
    }

    executeDefault(trackingId) {
        let $ = this.$;
        $.getScript('https://www.googletagmanager.com/gtag/js?id=' + trackingId);
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());

        gtag('config', trackingId, {
            'cookie_update': false,
            'cookie_flags': 'SameSite=none;Secure'
        });
    }

    getWrapper() {
        let $ = this.$;
        return $('.analytics-decoded-googleAnalytics');
    }

    getScript() {
        let $ = this.$;
        return $('#analytics-encoded-googleAnalytics');
    }

    remove() {
        let wrapperGoogle = this.getWrapper();
        if (wrapperGoogle !== null)
            wrapperGoogle.remove();
    }
}