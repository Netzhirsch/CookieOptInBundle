class NcoiRevoke {

    constructor($) {
        this.$ = $;
    }

    addOnClickEvent(storageKey) {
        let $ = this.$;
        let ncoiCookie = new NcoiCookie($);
        $('.ncoi---revoke--button').on('click', function (e) {
            e.preventDefault();
            ncoiCookie.ajaxDeleteCookies(storageKey);
            ncoiCookie.removeCookies(Cookies.get());
            $('.ncoi---behind').removeClass('ncoi---hidden--page-load')
                .removeClass('ncoi---hidden');
            $('#FBTracking').remove();
            $('#matomoTracking').remove();
        });
    }
}