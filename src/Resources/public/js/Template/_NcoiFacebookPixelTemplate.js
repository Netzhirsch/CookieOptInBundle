class _NcoiFacebookPixelTemplate extends NcoiTemplate{

    setCookies(tool,body) {
        let trackingId = this.getTrackingId(tool);
        body.append(
            '<script type=\"text/javascript\">'+
                '!function (f, b, e, v, n, t, s) {'+
                'if (f.fbq) return;'+
                'n = f.fbq = function () {'+
                    'n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments)'+
                '};'+
                'if (!f._fbq) f._fbq = n;'+
                'n.push = n;'+
                'n.loaded = !0;'+
                'n.version = \'2.0\';\n' +
                'n.queue = [];\n' +
                't = b.createElement(e);\n' +
                't.async = !0;\n' +
                't.src = v;\n' +
                's = b.getElementsByTagName(e)[0];\n' +
                's.parentNode.insertBefore(t, s)'+
                ' }(window, document, \'script\', \'https://connect.facebook.net/en_US/fbevents.js\');\n' +
                '        fbq(\'init\', '+trackingId+');\n' +
                '        fbq(\'track\', \'PageView\');' +
            '</script>'
        )
   }
}