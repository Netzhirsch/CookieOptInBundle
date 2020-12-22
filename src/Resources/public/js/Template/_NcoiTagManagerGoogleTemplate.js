class _NcoiTagManagerGoogleTemplate extends NcoiTemplate {

    setCookies(tool,body) {
        let trackingId = this.getTrackingId(tool)
        body.append(
        " <script type=\"text/javascript\">"+
                "$.getScript('https://www.googletagmanager.com/gtag/js?id=" + trackingId+"');"+
               "window.dataLayer = window.dataLayer || [];"+
                "function gtag() {"+
                "dataLayer.push(arguments);"+
            "}"+
            "gtag('js', new Date());"+
            "gtag('config', '"+trackingId+"', {"+
                "'cookie_update': false,"+
                "'cookie_flags': 'SameSite=none'"+
            "});"+
            "</script>"
        )
    }
}