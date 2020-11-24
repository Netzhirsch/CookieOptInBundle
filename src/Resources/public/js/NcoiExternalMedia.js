class NcoiExternalMedia {

    constructor($) {
        this.$ = $;
    }

    addOnClickEvent(storageKey) {
        let $ = this.$;
        let that = this;
        $('.ncoi---release').on('click', function (e) {
            e.preventDefault();
            //Um richtige Chechbox zu finden
            //und um Blockcontainer vielleicht auszublenden und iFrame anzuhängen
            let parent = $(this).parents('.ncoi---blocked');
            let input = parent.find('.ncoi---sliding');
            let blockClass = $('[data-block-class="' + input.data('block-class') + '"]');
            if (input.prop('checked')) {
                //In der Info Tabelle entsprechen checken damit über track() gespeichert werden kann.
                blockClass.prop('checked', true).trigger('change');
                let inputClass = input.data('block-class')+"";
                let blockClassIds = $('[data-block-class="ncoi---' + inputClass + '"]');
                blockClassIds.prop('checked', true).trigger('change');
                let ncoiTrack = new NcoiTrack($);
                ncoiTrack.track(1, storageKey);
                that.onClick();

                let parents = $('.' + input.data('block-class'));
                parents.each(function () {
                    that.addIframe($(this));
                })
            } else {
                that.addIframe(parent);
            }
        });
    }

    onClick() {
        let $ = this.$;
        let that = this;
        let cookiesInput = $('table tbody .ncoi---cookie');
        cookiesInput.each(function () {
            let blockClass = '.' + $(this).data('block-class');
            let blockClassElement = $(blockClass);
            if ($(this).prop('checked')) {
                //Klasses des Blockconainter aus input data-block-class auslesen
                // Nur gefunden BlockContainer werden bearbeitet
                // jedes Element separat
                blockClassElement.each(function () {
                    that.addIframe($(this));
                });
            } else {
                blockClassElement.each(function () {
                    if (!$(this).hasClass('ncoi---googleMaps')) {
                        $(this).removeClass('ncoi---hidden');
                        $(this).next('iframe').addClass('ncoi---hidden');
                    }
                });
            }
        });
    }

    encode(cookieIds) {
        let $ = this.$;
        $('.ncoi---blocked').each(function (key,value) {
            let that = this;
            let iframe = $(this);
            cookieIds.forEach(function (cookieId) {
                if ($(value).hasClass('ncoi---cookie-id-'+cookieId)) {
                    iframe.trigger('change');
                    if (iframe.length > 0) {
                        that.addIframe(iframe);
                    }
                }
            });
        });
    }

    addIframe(parent) {
        let $ = this.$;
        if (!parent.hasClass('ncoi---hidden')) {
            let html = '';
            try {
                if (parent.length > 1) {
                    for (let i = 0; i < parent.length; i++) {
                        $('.'+parent[i].classList[3]).each(function (){
                            html = atob($(this).find('script').text().trim());
                        });
                    }
                } else {
                    html = atob(parent.find('script').text().trim());
                }
            } catch (e) {
                console.error('Das IFrame html enthält invalide Zeichen.')
            }
            parent.addClass('ncoi---hidden');
            parent.after(html);
        }
    }
}