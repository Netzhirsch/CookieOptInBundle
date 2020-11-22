class NcoiSaveButton {

    constructor($) {
        this.$ = $;
        this.externalMedia = new NcoiExternalMedia();
    }

    addOnClickEvents(storageKey){

        onClickSaveAllConsent(storageKey);
        onClickSaveConsent(storageKey);

        function  onClickSaveAllConsent(storageKey) {
            let $ = this.$;
            $('#ncoi---allowed--all').on('click', function (e) {
                e.preventDefault();
                saveConsent(storageKey);
                $('.ncoi---cookie-group input').prop('checked', true);
                $('.ncoi---sliding').prop('checked', true);
            });
        }

        function onClickSaveConsent(storageKey){
            let $ = this.$;
            $('#ncoi---allowed').on('click', function (e) {
                e.preventDefault();
                let saveConsent = this.va;
                saveConsent(storageKey);
            });
        }

        function saveConsent(storageKey) {
            let $ = this.$;
            let ncoiTrack = new NcoiTrack();
            $('.ncoi---behind').addClass('ncoi---hidden');
            ncoiTrack.track(1, storageKey);
            this.externalMedia.onClick()
        }

    }
}