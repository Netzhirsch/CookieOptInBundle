(function($){
	//ausführung beim Speicher der Entscheidung
	function checkExternalMedia() {

		let blockedElement = $('.ncoi---blocked');

		// Wenn Gruppe checked oder unchecked nicht einzele Cookies prüfen
		let isSetIFrames = false;
		if ( $('[data-external-media]').prop('checked')) {
				isSetIFrames = true;
				//nur Iframe setzten wenn nötig
				if (!blockedElement.hasClass('ncoi---hidden')) {
					let iframes = $('.ncoi---iframes');
					// nur ein script tag zur Zeit
					iframes.each(function () {
						addIframe($(this));
					});
				} else {
					isSetIFrames = false;
				}
		} else {
			$('iframe').addClass('ncoi---hidden');
			blockedElement.removeClass('ncoi---hidden');
		}
		if (!isSetIFrames) {
			let cookiesInput = $('table tbody .ncoi---cookie');
			cookiesInput.each(function () {
				if ($(this).prop('checked')) {
					//Klasses des Blockconainter aus input data-block-class auslesen
					let blockClass = '.' + $(this).data('block-class');
					let blockClassElement = $(blockClass);

					// Nur gefunden BlockContainer werden bearbeitet
					// jedes Element separat
					blockClassElement.each(function () {
						addIframe($(this));
					});
				}
			});
		}
	}

	function addIframe(parent){
		if (!parent.hasClass('ncoi---hidden')) {
			let html = atob((parent.find('script').text().trim()));
			parent.addClass('ncoi---hidden');
			parent.after(html);
		}
	}

	$(document).ready(function () {
		// falls CSS zu spät eingunden wird
		$('.ncoi---behind').removeClass('ncoi---no-transition');

		let errorMessage = '';
		$('.ncoi---mod-missing').each(function () {
			errorMessage = $(this).data('ncoi-mod-missing');
			if(errorMessage.localeCompare('') !== 0)
				console.error(errorMessage);
		});

		if (
			$('[data-ncoi-is-version-new]').data('ncoi-is-version-new') === 0
		) {
			track(0);
		}

		$('#ncoi---allowed').on('click', function (e) {
			e.preventDefault();
			$('.ncoi---behind').addClass('ncoi---hidden');
			checkExternalMedia();
			track(1);
		});

		$('#ncoi---allowed--all').on('click', function (e) {
			e.preventDefault();
			$('.ncoi---behind').addClass('ncoi---hidden');
			$('.ncoi---cookie-group input').prop('checked',true);
			$('.ncoi---sliding').prop('checked',true);
			checkExternalMedia();
			track(1);
		});

		$('#ncoi---revoke').on('click',function (e) {
			e.preventDefault();
			$('.ncoi---behind').removeClass('ncoi---hidden--page-load')
				.removeClass('ncoi---hidden');
			$('#FBTracking').remove();
			$('#matomoTracking').remove();
		});

		$('#ncoi---infos--show').on('click',function (e) {
			e.preventDefault();
			$('.ncoi---hint').toggleClass('ncoi---hidden');
			$('.ncoi---table').toggleClass('ncoi---hidden');
			$('.ncoi---infos--show-active').toggleClass('ncoi---hidden');
			$('.ncoi---infos--show-deactivate').toggleClass('ncoi---hidden');

		});

		$('.ncoi---sliding').on('change',function () {
			let group = $(this);
			$('.ncoi---sliding').each(function () {
				let cookie = $(this).data('group');
				if(group.val().localeCompare(cookie) === 0)
					$(this).prop('checked',group.prop('checked'));
			});
		});

		let cookiesSelect = $('.ncoi---cookie');

		cookiesSelect.on('change',function () {
			let cookie = $(this).data('group');
			let allChecked = true;
			cookiesSelect.each(function () {
				let group = $(this).data('group');
				if( group.localeCompare(cookie) === 0 && !$(this).prop('checked'))
					allChecked = false;
			});
			$('.ncoi---cookie-group input').each(function () {
				let group = $(this).val();
				if(group.localeCompare(cookie) === 0)
					$(this).prop('checked',allChecked);
			});

		});

		$('.ncoi---release').on('click',function (e) {
			e.preventDefault();
			//Um richtige Chechbox zu finden
			//und um Blockcontainer vielleicht auszublenden und iFrame anzuhängen
			let parent = $(this).parents('.ncoi---blocked');
			let input = parent.find('input');
			if (input.prop('checked')) {
				//In der Info Tabelle entsprechen checken damit über track() gespeichert werden kann.
				$('[data-block-class="'+input.data('block-class')+'"]').prop('checked',true);
				track(1);

				let parents = $('.'+input.data('block-class'));
				parents.each(function () {
					addIframe($(this));
				})
			} else {
				addIframe(parent);
			}
		});
		//  only for testing
		// console.log(getCookie('_netzhirsch_cookie_opt_in'));
	});

//  only for testing
// 	function getCookie(cname) {
// 		let name = cname + "=";
// 		let decodedCookie = decodeURIComponent(document.cookie);
// 		let ca = decodedCookie.split(';');
// 		for(let i = 0; i <ca.length; i++) {
// 			let c = ca[i];
// 			while (c.charAt(0).localeCompare(' ') === 0 ) {
// 				c = c.substring(1);
// 			}
// 			if (c.indexOf(name) === 0) {
// 				return c.substring(name.length, c.length);
// 			}
// 		}
// 		return false;
// 	}

	function track(newConsent){
		let data = {
			cookieIds : [{}],
			modID : {},
			newConsent : newConsent
		};
		let cookieSelected = $('.ncoi---cookie');
		Object.keys(cookieSelected).forEach(function(key) {
			if (key.localeCompare('length') !== 0 && key.localeCompare('prevObject') !== 0) {
				if ($(cookieSelected[key]).prop('checked')) {
					data.cookieIds.push($(cookieSelected[key]).data('cookie-id'))
				}
			}
		});
		data.modID = $('[data-ncoi-mod-id]').data('ncoi-mod-id');
		$.ajax({
			dataType: "json",
			type: 'POST',
			url: '/cookie/allowed',
			data: {
				data : data
			},
			success: function (data) {
				let tools = data.tools;
				let body = $('body');
				if (tools !== null) {
					tools.forEach(function (tool) {
						let toolName = tool.cookieToolsSelect;
						if (toolName.localeCompare('googleAnalytics') === 0) {
							$.getScript('https://www.googletagmanager.com/gtag/js?id=' + tool.cookieToolsTrackingID);
							window.dataLayer = window.dataLayer || [];
							function gtag(){dataLayer.push(arguments);}

							gtag('js', new Date());

							gtag('config', tool.cookieToolsTrackingID);
						}
						if (toolName.localeCompare('facebookPixel') === 0) {
							<!-- Facebook Pixel Code -->
						 !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod? n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init', tool.cookieToolsTrackingID);fbq('track', 'PageView');
						}
						if (toolName.localeCompare('matomo') === 0) {
							let url = tool.cookieToolsTrackingServerUrl;
							if (url.slice(-1) !== '/')
								url += '/';
							body.append("<script type=\"text/javascript\">" +
								"var _paq = window._paq || [];" +
								"_paq.push(['trackPageView']);" +
								"_paq.push(['enableLinkTracking']);" +
								"(function() {" +
								"var u = '"+url+"';" +
								"_paq.push(['setTrackerUrl', u+'matomo.php']);" +
								"_paq.push(['setSiteId', '"+tool.cookieToolsTrackingID+"']);" +
								"var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];" +
								"g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);" +
								"})();" +
								"</script>");
						}
					});
					let otherScripts = data.otherScripts;
					if (otherScripts !== null) {
						otherScripts.forEach(function (otherScript) {
							body.append(otherScript.cookieToolsCode);
						});
					}
				}
			}
		});
	}// End Track
})(jQuery);