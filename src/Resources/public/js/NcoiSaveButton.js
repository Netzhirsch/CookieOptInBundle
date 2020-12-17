class NcoiSaveButton {

    constructor($) {
        this.$ = $;
    }


    addOnClickEvents(storageKey){

        let that = this;
        let $ = that.$;
        onClickSaveAllConsent(storageKey);
        onClickSaveConsent(storageKey);

        function  onClickSaveAllConsent(storageKey) {
            $(document).on('click','#ncoi---allowed--all', function (e) {
                e.preventDefault();
                $('.ncoi---cookie-group input').prop('checked', true);
                $('.ncoi---sliding').prop('checked', true);
                saveConsent(storageKey);
            });
        }

        function onClickSaveConsent(storageKey){
            $(document).on('click','#ncoi---allowed', function (e) {
                e.preventDefault();
                saveConsent(storageKey);
            });
        }

        function saveConsent(storageKey) {
            let ncoiTrack = new NcoiTrack($);
            $('.ncoi---behind').addClass('ncoi---hidden');
            ncoiTrack.track(1, storageKey);
            let externalMedia = new NcoiExternalMedia($);
            externalMedia.onClick()
        }

    }
}