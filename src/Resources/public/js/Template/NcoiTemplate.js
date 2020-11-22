class NcoiTemplate {

    addToolTemplates(toolName, trackingId, body) {
        let template = this.getChildTemplate(toolName);
        if (typeof template !== 'undefined')
            template.setCookies(trackingId,body);
    }

    addOtherScriptTemplate(otherScripts,body) {
        otherScripts.forEach(function (otherScript) {
            body.append(otherScript.cookieToolsCode);
        });
    }

    getTrackingId(tool) {
        return tool.cookieToolsTrackingID;
    }

    getUrl(tool) {
        let url = tool.cookieToolsTrackingServerUrl;
        if (url.slice(-1) !== '/')
            url += '/';
        return url;
    }

    getWrapper(template) {
        let wrapper = template.getWrapper();
        if (wrapper.length > 0)
            return template.getWrapper();
        return null
    }

    remove() {
        let matomo = new NcoiMatomoTemplate();
        let wrapperMatomo = this.getWrapper(matomo);
        if (wrapperMatomo !== null)
            wrapperMatomo.remove();
    }

    getChildTemplate(toolName) {
        let template;
        if (toolName.localeCompare('googleAnalytics') === 0) {
            template = new NcoiAnalyticsGoogleTemplate(this.$);
        } else if (toolName.localeCompare('googleTagManager') === 0) {
            template = new NcoiTagManagerGoogleTemplate();
        } else if (toolName.localeCompare('facebookPixel') === 0) {
            template = new NcoiFacebookPixelTemplate();
        }
        return template;
    }
}