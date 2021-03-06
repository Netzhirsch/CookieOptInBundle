class NcoiSaveButton {

    constructor($) {
        this.$ = $;
    }

    addOnClickEvents(storageKey,mainWrapper){
        this.onClickSaveAllConsent(storageKey,mainWrapper);
        this.onClickSaveConsent(storageKey,mainWrapper);
    }

    onClickSaveConsent(storageKey,mainWrapper){
        let $ = this.$;
        let that = this;

        $(document).on('click','#ncoi---allowed', function (e) {
            e.preventDefault();
            that.saveConsent(storageKey,mainWrapper,'modified');
        });
    }

    onClickSaveAllConsent(storageKey,mainWrapper) {
        let $ = this.$;
        let that = this;
        $(document).on('click','#ncoi---allowed--all', function (e) {
            e.preventDefault();
            $('.ncoi---cookie-group input').prop('checked', true);
            $('.ncoi---sliding').prop('checked', true);
            that.saveConsent(storageKey,mainWrapper,'default');
        });
    }

    saveConsent(storageKey,mainWrapper,optOut) {
        let $ = this.$;
        const ncoiLoad = new NcoiLoad($);
        ncoiLoad.removeAnimation(mainWrapper);
        let ncoiTrack = new NcoiTrack($);
        $('.ncoi---behind').addClass('ncoi---hidden');
        ncoiTrack.track(1, storageKey,optOut);
        let externalMedia = new NcoiExternalMedia($);
        externalMedia.decode(storageKey)
    }
}