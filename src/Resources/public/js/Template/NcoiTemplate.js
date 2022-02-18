class NcoiTemplate {

    constructor($) {
        this.$ = $;
    }

    addToolTemplates(tool, body) {
        let template = this.getChildTemplate(this.getToolsType(tool));
        if (typeof template !== 'undefined')
            template.setCookies(tool,body);
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

    getToolsType(tool){
        return tool.cookieToolsSelect;
    }

    getWrapper(template) {
        let wrapper = template.getWrapper();
        if (wrapper.length > 0)
            return template.getWrapper();
        return null
    }

    getChildTemplate(toolType) {
        let template;
        if (toolType.localeCompare('googleAnalytics') === 0) {
            template = new _NcoiAnalyticsGoogleTemplate(this.$);
        } else if (toolType.localeCompare('googleTagManager') === 0) {
            template = new _NcoiTagManagerGoogleTemplate();
        } else if (toolType.localeCompare('facebookPixel') === 0) {
            template = new _NcoiFacebookPixelTemplate(this.$);
        } else if (toolType.localeCompare('matomo') === 0) {
            template = new _NcoiMatomoTemplate(this.$);
        }
        return template;
    }
}