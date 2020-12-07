class NcoiApp {

    constructor($) {
        this.$ = $;
    }

    getMainWrapper() {
        let $ = this.$;
        return $('.ncoi---behind');
    }

    getStorageKey() {
        return 'ncoi';
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

        let isExcludePage = mainWrapper.find('.ncoi---container').data('is-exclude-page')
        if  (isExcludePage === 1)
            return;

        const ncoiLoad = new NcoiLoad($);
        ncoiLoad.fixTooLateCssLoad(mainWrapper)
        ncoiLoad.removeLoadAlwaysForNoScript();
        ncoiLoad.showAllMissingModuleMessage();

        const ncoiSaveButton = new NcoiSaveButton($);
        const ncoiTrack = new NcoiTrack($);
        ncoiSaveButton.addOnClickEvents(storageKey,ncoiTrack);

        const ncoiExternalMedia = new NcoiExternalMedia($);
        ncoiExternalMedia.addOnClickEvent(storageKey);

        const ncoiInfoTable = new NcoiInfoTable($);
        ncoiInfoTable.addOnClickShowEvent();
        ncoiInfoTable.onChangeGroupActive();
        ncoiInfoTable.onChangeCookieActive();


        const ncoiCookie = new NcoiCookie($);
        if (ncoiLoad.isLocalStorageIsUpToDate(localStorage,storageKey,mainWrapper)) {
            ncoiTrack.track(0, storageKey,localStorage);
            ncoiExternalMedia.onClick();
            ncoiExternalMedia.encode(localStorage.cookieIds);
            ncoiInfoTable.setCookieCheckboxes(localStorage.cookieIds);
            mainWrapper.addClass('ncoi---hidden');
        } else {
            ncoiCookie.ajaxDeleteCookies(storageKey);
            ncoiCookie.removeCookies(Cookies.get());
            mainWrapper.removeClass('ncoi---hidden');
        }
    });
}(jQuery,NcoiApp))