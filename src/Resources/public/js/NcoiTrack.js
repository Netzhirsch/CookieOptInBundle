class NcoiTrack {

    constructor($) {
        this.$ = $;
    }

    track(newConsent, storageKey,localStorage) {
        let that = this;
        let userSettings = this.getDefaultUserSettings(newConsent, storageKey);
        if (newConsent === 1) {
            this.setNewUserSettings(userSettings);
        } else {
           this.setUserSettings(userSettings,localStorage);
        }
        that.makeAjax(userSettings,storageKey);

    }

    makeAjax(userSettings,storageKey){
        let $ = this.$;
        let that = this;
        $.ajax({
            dataType: "json",
            type: 'POST',
            url: '/cookie/allowed',
            data: {
                data: userSettings,
                isJava:true
            },
            async: false,
            success: function (response) {
                let cookieVersion = response.cookieVersion;
                that.saveUserSettings(
                    storageKey
                    ,JSON.stringify({
                        id: response.id,
                        cookieVersion: cookieVersion,
                        cookieIds: userSettings.cookieIds,
                        expireTime: response.expireTime
                    })
                );

                let cookiesToDelete = Cookies.get();
                let template = new NcoiTemplate($);
                let tools = response.tools;
                let body = $('body');
                let ncoiCookie = new NcoiCookie($);
                if (tools !== null) {
                    tools.forEach(function (tool) {
                        cookiesToDelete = ncoiCookie.unsetByTechnicalName(cookiesToDelete, tool.cookieToolsTechnicalName);
                        template.addToolTemplates(tool, body)
                    });
                } else {
                    let templateGoogle = new _NcoiAnalyticsGoogleTemplate($);
                    templateGoogle.remove();
                    let templateMatomo = new _NcoiMatomoTemplate($);
                    templateMatomo.remove();
                }
                let otherScripts = response.otherScripts;
                if (otherScripts !== null) {
                    otherScripts.forEach(function (otherScript) {
                        cookiesToDelete
                            = ncoiCookie.unsetByTechnicalName(
                            cookiesToDelete,
                            otherScript.cookieToolsTechnicalName
                        );
                        template.addOtherScriptTemplate(otherScripts,body)
                    });
                }
                ncoiCookie.removeCookies(cookiesToDelete);
            }
        });
    }

    getDefaultUserSettings(newConsent, storageKey) {
        let $ = this.$;
        let modId = $('[data-ncoi-mod-id]').data('ncoi-mod-id');
        return {
            id: null,
            cookieIds: [],
            modId: modId,
            newConsent: newConsent,
            storageKey: storageKey,
            cookieVersion: 0
        };
    }

    setNewUserSettings(userSettings) {
        let $ = this.$;
        userSettings.newConsent = true;
        let cookieSelected = $('.ncoi---table-input');
        cookieSelected.each(function (){
            let isChecked = $(this).prop('checked');
            if ($(this).data('cookie-id') && isChecked)
                userSettings.cookieIds.push($(this).data('cookie-id'))
        });
    }

    setUserSettings(userSettings,localStorage) {
        userSettings.cookieIds = localStorage.cookieIds;
        userSettings.id = localStorage.id;
        userSettings.cookieVersion = localStorage.cookieVersion;
    }

    saveUserSettings(storageKey, storageValue) {
        let $ = this.$;
        let ncoiApp = new NcoiApp($);
        ncoiApp.setLocalStorage(storageKey, storageValue);
    }

}