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
            console.log("prevend Default");
            $('#ncoi---allowed--all').on('click', function (e) {
                e.preventDefault();
                $('.ncoi---cookie-group input').prop('checked', true);
                $('.ncoi---sliding').prop('checked', true);
                saveConsent(storageKey);
            });
        }

        function onClickSaveConsent(storageKey){
            console.log("prevend Default");
            $('#ncoi---allowed').on('click', function (e) {
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