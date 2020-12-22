class NcoiApp {

    constructor($) {
        this.$ = $;
    }

    getMainWrapper() {
        let $ = this.$;
        return $('.ncoi---behind');
    }

    getStorageKey() {
        let $ = this.$;
        return 'ncoi_'+$('[data-ncoi-mod-id]').data('ncoi-mod-id');
    }

    getLocalStorage(storageKey) {
        let storageData = localStorage.getItem(storageKey);
        if (storageData !== 'null' && storageData !== null) {
            return JSON.parse(storageData);
        }
        return '';
    }

    setLocalStorage(storageKey, storageValue) {
        localStorage.setItem(storageKey, storageValue)
    }
}

(function($,NcoiApp) {
    $(document).ready(function () {

        const ncoiApp = new NcoiApp($);
        let mainWrapper  = ncoiApp.getMainWrapper();
        const ncoiRevoke = new NcoiRevoke($);
        let storageKey = ncoiApp.getStorageKey();
        let localStorage = ncoiApp.getLocalStorage(storageKey);
        ncoiRevoke.addOnClickEvent(storageKey);

        const ncoiLoad = new NcoiLoad($);
        ncoiLoad.removeLoadAlwaysForNoScript();
        ncoiLoad.showAllMissingModuleMessage();
        ncoiLoad.removeNoScriptInputs();

        const ncoiSaveButton = new NcoiSaveButton($);
        const ncoiTrack = new NcoiTrack($);
        ncoiSaveButton.addOnClickEvents(storageKey,mainWrapper);

        const ncoiExternalMedia = new NcoiExternalMedia($);
        ncoiExternalMedia.addOnClickEvent(storageKey);
        ncoiExternalMedia.onChangeSliding();

        const ncoiInfoTable = new NcoiInfoTable($);
        ncoiInfoTable.addOnClickShowEvent();
        ncoiInfoTable.onChangeGroupActive();
        ncoiInfoTable.onChangeCookieActive();

        const ncoiCookie = new NcoiCookie($);
        let isExcludePage = mainWrapper.find('.ncoi---container').data('is-exclude-page')
        if  (isExcludePage === 1) {
            mainWrapper.addClass('ncoi---hidden');
            ncoiCookie.ajaxDeleteCookies(storageKey);
            ncoiCookie.removeCookies(Cookies.get());
            ncoiTrack.track(0, storageKey,localStorage);
            return;
        }

        if (ncoiLoad.isLocalStorageUpToDate(localStorage,storageKey,mainWrapper)) {
            ncoiTrack.track(0, storageKey,localStorage);
            ncoiExternalMedia.decode(storageKey);
            ncoiInfoTable.setCookieCheckboxes(localStorage.cookieIds);
            mainWrapper.addClass('ncoi---hidden');
        } else {
            ncoiCookie.ajaxDeleteCookies(storageKey);
            ncoiCookie.removeCookies(Cookies.get());
            mainWrapper.removeClass('ncoi---hidden');
        }
    });
}(jQuery,NcoiApp))