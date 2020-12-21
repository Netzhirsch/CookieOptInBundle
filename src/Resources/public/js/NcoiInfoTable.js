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
        $(document).on('click','.ncoi---sliding-input', function (e) {
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
        $(document).on('click',cookiesSelect, function (e) {
            let cookie = $(this).data('group');
            let allChecked = true;
            let id = $(this).data('cookie-id');
            let checked = $(this).prop('checked');
            let inputInBlockContainer = $('.ncoi---cookie-id-'+id).find('.ncoi---sliding');
            inputInBlockContainer.prop('checked',checked)
            cookiesSelect.each(function () {
                let group = $(this).data('group');
                if (cookie === group && !checked)
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