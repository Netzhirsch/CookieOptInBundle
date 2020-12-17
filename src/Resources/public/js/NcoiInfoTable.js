class NcoiInfoTable {

    constructor($) {
        this.$ = $;
    }

    setCookieCheckboxes(cookieIds) {
        let $ = this.$;
        cookieIds.forEach(function (cookieId) {
            $('.ncoi---cookie-id-' + cookieId).prop('checked');
        });
    }

    addOnClickShowEvent(){
        let $ = this.$;
        $(document).on('click','#ncoi---infos--show', function (e) {
            e.preventDefault();
            $('.ncoi---cookie-groups').toggleClass('ncoi---hidden');
            $('.ncoi---hint').toggleClass('ncoi---hidden');
            $('.ncoi---table').toggleClass('ncoi---hidden');
            $('.ncoi---infos--show-active').toggleClass('ncoi---hidden');
            $('.ncoi---infos--show-deactivate').toggleClass('ncoi---hidden');

        });
    }

    onChangeGroupActive(){
        let $ = this.$;
        $('.ncoi---sliding-input').on('change', function () {
            let group = $(this);
            $('.ncoi---cookie').each(function () {
                let cookie = $(this).data('group');
                if (group.val().localeCompare(cookie) === 0)
                    $(this).prop('checked', group.prop('checked'));
            });
        });
    }

    onChangeCookieActive(){
        let $ = this.$;
        let cookiesSelect = $('.ncoi---cookie');
        cookiesSelect.on('change', function () {
            let cookie = $(this).data('group');
            let allChecked = true;
            cookiesSelect.each(function () {
                let group = $(this).data('group');
                if (cookie === group && !$(this).prop('checked'))
                    allChecked = false;
            });
            $('.ncoi---cookie-group input').each(function () {
                let group = $(this).val();
                if (group.localeCompare(cookie) === 0)
                    $(this).prop('checked', allChecked);
            });

        });
    }
}