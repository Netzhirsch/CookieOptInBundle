class NcoiLoad {

    constructor($) {
        this.$ = $;
    }
    fixTooLateCssLoad(mainWrapper){
        mainWrapper.removeClass('ncoi---no-transition');
    }

    removeLoadAlwaysForNoScript() {
        let $ = this.$;
        $('.ncoi--release-all').removeClass('ncoi---hidden');
    }

    showAllMissingModuleMessage(){
        let $ = this.$;
        let that = this;
        $('.ncoi---mod-missing').each(function () {
            that.showMissingModuleMessage($(this).data('ncoi-mod-missing'))
        });
    }

    isLocalStorageUpToDate(localStorage, storageKey, mainWrapper) {
        let ncoiDate = new NcoiDate();
        return localStorage !== '' && !this.doNotTrackByBrowserSetting(storageKey, mainWrapper) && localStorage.expireTime >= ncoiDate.dateString();
    }

    showMissingModuleMessage(errorMessage){
        if (errorMessage.localeCompare('') !== 0)
            console.error(errorMessage);
    }

    doNotTrackByBrowserSetting(storageKey,mainWrapper){
        let $ = this.$;
        if (
            $('[data-ncoi-respect-do-not-track]').data('ncoi-respect-do-not-track') === 1
            && navigator.doNotTrack === "1"
        ) {
            let ncoiCookie = new NcoiCookie($);
            ncoiCookie.ajaxDeleteCookies(storageKey);
            ncoiCookie.removeCookies(Cookies.get());
            return true;
        } else {
            mainWrapper.removeClass('ncoi---hidden');
            return false;
        }
    }
}