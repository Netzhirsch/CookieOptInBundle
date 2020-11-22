class NcoiApp {

    constructor($) {
        this.$ = $;
    }

    getMainWrapper() {
        return $('.ncoi---behind');
    }

    getStorageKey() {
        return 'ncoi';
    }

    getLocalStorage(storageKey) {
        let storageData = localStorage.getItem(storageKey);
        if (storageData !== null) {
            storageData = JSON.parse(storageData);
        }
        return (storageData ? storageData : '');
    }

    setLocalStorage(storageKey, storageValue) {
        localStorage.setItem(storageKey, storageValue)
    }
}

(function($,NcoiApp) {
    $(document).ready(function () {

        const ncoiLoad = new NcoiLoad($);
        const ncoiApp = new NcoiApp($);
        let mainWrapper  = ncoiApp.getMainWrapper();
        ncoiLoad.fixTooLateCssLoad(mainWrapper)
        ncoiLoad.removeLoadAlwaysForNoScript();
        ncoiLoad.showAllMissingModuleMessage();

        const ncoiSaveButton = new NcoiSaveButton($);
        let storageKey = ncoiApp.getStorageKey();
        const ncoiTrack = new NcoiTrack($);
        ncoiSaveButton.addOnClickEvents(storageKey,ncoiTrack);

        const ncoiExternalMedia = new NcoiExternalMedia($);
        ncoiExternalMedia.onClick();
        ncoiExternalMedia.onClickRelease();

        const ncoiInfoTable = new NcoiInfoTable($);
        ncoiInfoTable.addOnClickShowEvent();
        ncoiInfoTable.onChangeGroupActive();
        ncoiInfoTable.onChangeCookieActive();

        const ncoiRevoke = new NcoiRevoke($);
        let localStorage = ncoiApp.getLocalStorage(storageKey);
        ncoiRevoke.addOnClickEvent(storageKey);
        const ncoiCookie = new NcoiCookie($);
        if (ncoiLoad.isLocalStorageIsUpToDate(localStorage,storageKey,mainWrapper,(new NcoiDate))) {
            ncoiTrack.track(0, storageKey,localStorage,ncoiCookie,ncoiApp);
            ncoiExternalMedia.encode(localStorage.cookieIds);
            ncoiInfoTable.setCookieCheckboxes(localStorage.cookieIds);
        } else {
            ncoiCookie.ajaxDeleteCookies(storageKey,ncoiApp);
            ncoiCookie.removeCookies(Cookies.get());
            mainWrapper.removeClass('ncoi---hidden');
        }
    });
}(jQuery,NcoiApp))