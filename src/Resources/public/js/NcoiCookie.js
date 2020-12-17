class NcoiCookie {

    constructor($) {
        this.$ = $;
    }

    ajaxDeleteCookies(storageKey) {
        let $ = this.$;
        let ncoiApp = new NcoiApp($);
        $.ajax({
            dataType: "json",
            type: 'POST',
            url: '/cookie/delete',
            success: function () {
                ncoiApp.setLocalStorage(storageKey, null);
            }
        });
    }

    unsetByTechnicalName(cookiesToDelete, technicalName) {
        let that = this;
        if (technicalName.indexOf(',') > -1) {
            cookiesToDelete = that.unsetManyCookiesByTechnicalName(cookiesToDelete,technicalName)
        } else {
            cookiesToDelete = that.unsetOneCookieByTechnicalName(cookiesToDelete,technicalName);
        }
        return cookiesToDelete;
    }

    unsetOneCookieByTechnicalName(cookiesToDelete,technicalName) {
        let that = this;
        for (let cookie in cookiesToDelete) {
            if (
                cookiesToDelete.hasOwnProperty(cookie)
                && technicalName === cookie
            )
                that.unsetCookie(technicalName,cookie,cookiesToDelete);
        }
        return cookiesToDelete;
    }

    unsetManyCookiesByTechnicalName(cookiesToDelete,technicalName) {
        let that = this;
        let technicalNames = technicalName.split(',');
        let cookiesToDeleteIndex = 0;
        for (let cookie in cookiesToDelete) {
            technicalNames.forEach(function (element, index) {
                if (
                    technicalNames[index] === cookie
                    && cookiesToDelete.hasOwnProperty(cookie)
                ) {
                    that.unsetCookie(technicalNames[index],cookie,cookiesToDelete);
                }
            });
            cookiesToDeleteIndex++;
        }
    }

    removeCookies(cookiesToDelete) {
        for (let cookie in cookiesToDelete) {
            if (
                cookiesToDelete.hasOwnProperty(cookie)
                && cookie !== 'XDEBUG_SESSION'
                && cookie !== 'BE_USER_AUTH'
                && cookie !== 'FE_USER_AUTH'
                && cookie !== 'BE_PAGE_OFFSET'
                && cookie !== 'trusted_device'
                && cookie !== 'csrf_contao_csrf_token'
                && cookie !== 'csrf_https-contao_csrf_token'
                && cookie !== 'PHPSESSID'
                && cookie !== 'contao_settings'
            ) {
                //tries all paths from root to current subpage
                let cookiePath = '',
                    cookiePaths = window.location.pathname.split('/')
                while (cookiePaths.length > 0) {
                    cookiePath = cookiePath + cookiePaths.shift() + '/';
                    Cookies.remove(cookie, {path: cookiePath});
                }
            }
        }
    }
    unsetCookie(cookieToolsTechnicalName,cookie,cookiesToDelete) {
        if (cookieToolsTechnicalName === cookie) {
            delete cookiesToDelete[cookie];
        }
    }
}