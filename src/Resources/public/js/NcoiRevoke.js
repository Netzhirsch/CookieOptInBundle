class NcoiRevoke {

    constructor($) {
        this.$ = $;
    }
    
    addOnClickEvent(storageKey) {
        let $ = this.$;
        $('.ncoi---revoke--button').on('click', function (e) {
            e.preventDefault();
            let ncoiApp = new NcoiApp($);
            let storage = ncoiApp.getLocalStorage(storageKey);
            if (storage !== "") {
                let cookieFields = $('[id^="ncoi---table-cookie-"]');
                cookieFields.prop('checked',false);
                storage.cookieIds.forEach(function (cookieId) {
                    let cookieField = $('#ncoi---table-cookie-'+cookieId);
                    cookieField.prop('checked',true);
                });
                $('[id^="group-"]').prop('checked',true);
                cookieFields.each(function () {
                    if (!$(this).prop('checked')) {
                        $('#group-'+$(this).data('group')).prop('checked',false);
                    }
                });
            }
            let ncoiCookie = new NcoiCookie($);
            ncoiCookie.ajaxDeleteCookies(storageKey);
            ncoiCookie.removeCookies(Cookies.get());
            $('.ncoi---behind').removeClass('ncoi---hidden--page-load')
            .removeClass('ncoi---hidden');
            $('#FBTracking').remove();
            $('#matomoTracking').remove();
        });
    }
}