class NcoiDate {
    dateString() {
        let datum = new Date();
        let monat = datum.getMonth() + 1;
        let tag = datum.getDate();
        if (monat < 10)
            monat = '0' + monat;
        if (tag < 10)
            tag = '0' + tag;
        return datum.getFullYear() + '-' + monat + '-' + tag;
    }
}