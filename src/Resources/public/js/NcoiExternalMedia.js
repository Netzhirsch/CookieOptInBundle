class NcoiExternalMedia {
    
    constructor($) {
        this.$ = $;
    }

    addOnClickEvent(storageKey) {
        let $ = this.$;
        let that = this;
        $(document).on('click', '.ncoi---release', function (e) {
            e.preventDefault();
            //Um richtige Chechbox zu finden
            //und um Blockcontainer vielleicht auszublenden und iFrame anzuhängen
            let parent = $(this).parents('.ncoi---blocked');
            let input = parent.find('.ncoi---sliding');
            let blockClass = $('[data-block-class="' + input.data('block-class') + '"]');
            if (input.prop('checked')) {
                //In der Info Tabelle entsprechen checken damit über track() gespeichert werden kann.
                blockClass.prop('checked', true).trigger('change');
                let inputClass = input.data('block-class') + "";
                let blockClassIds = $('[data-block-class="ncoi---' + inputClass + '"]');
                blockClassIds.prop('checked', true).trigger('change');
                let ncoiTrack = new NcoiTrack($);
                ncoiTrack.track(1, storageKey);
                that.decode(storageKey);

                let parents = $('.' + input.data('block-class'));
                parents.each(function () {
                    that.addIframe($(this));
                })
            } else {
                if (that.isCustomGmap(parent)) {
                    that.showCustomGmap(parent)
                } else {
                    that.addIframe(parent);
                    parent.trigger('change');
                }
            }
        });
    }

    onChangeSliding() {
        let $ = this.$;
        $(document).on('click', '.ncoi---sliding.ncoi---blocked', function () {
            let checked = $(this).prop('checked');
            let ids = $(this).data('cookie-ids') + '';
            if (ids.indexOf(',') > 0) {
                ids = ids.split(',');
                ids.forEach(function (id) {
                    $('#ncoi---table-cookie-' + id).prop('checked', checked);
                });
            } else {
                $('#ncoi---table-cookie-' + ids).prop('checked', checked);
            }
        });
    }

    decode(storageKey) {
        let that = this;
        let $ = that.$;
        let ncoiApp = new NcoiApp($);
        let localStorage = ncoiApp.getLocalStorage(storageKey);
        let cookieIds = localStorage.cookieIds;
        $('.ncoi---blocked').each(function (key, value) {
            let iframe = $(this);
            cookieIds.forEach(function (cookieId) {
                if ($(value).hasClass('ncoi---cookie-id-' + cookieId)) {
                    if (iframe.length > 0) {
                        if (that.isCustomGmap(iframe)) {
                            that.showCustomGmap(iframe)
                        } else {
                            that.addIframe(iframe);
                            iframe.trigger('change');
                        }
                    }
                }
            });
        });
    }

    showCustomGmap() {
        let $ = this.$;
        $('.ce_google_map').removeClass('ncoi---hidden');
        $('.mod_catalogUniversalView').removeClass('ncoi---hidden');
        $('.ncoi---custom_gmap').addClass('ncoi---hidden');
    }

    isCustomGmap(iframe) {
        let customGmap = iframe.parent('.ncoi---custom_gmap');
        return customGmap.length > 0;

    }

    addIframe(parent) {
        let $ = this.$;
        if (!parent.hasClass('ncoi---hidden')) {
            let html = '';

            if (parent.length > 1) {
                for (let i = 0; i < parent.length; i++) {
                    $('.' + parent[i].classList[3]).each(function () {
                        html = $(this).find('script').text().trim();
                    });
                }
            } else {
                html = parent.find('script').text().trim();
            }

            parent.addClass('ncoi---hidden');

            if (html.indexOf('data-only-script') >= 0)
                $('head').append(html);
            else
                parent.after(html);
        }
    }
}