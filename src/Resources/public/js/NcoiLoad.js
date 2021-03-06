class NcoiLoad {

    constructor($) {
        this.$ = $;
    }

    removeAnimation(mainWrapper){
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

    isLocalStorageUpToDate(localStorage) {
        let ncoiDate = new NcoiDate();
        let $ = this.$;
        return localStorage !== ''
            && localStorage.expireTime >= ncoiDate.dateString()
            && $('[data-ncoi-cookie-version]').data('ncoi-cookie-version') === parseInt(localStorage.cookieVersion);
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
            mainWrapper.removeClass('ncoi---hidden');
            return true;
        } else {
            return false;
        }
    }

    removeNoScriptInputs(){
        let $ = this.$;
        $('.ncoi---no-script--hidden').each(function (){
            $(this).remove();
        });
    }
}