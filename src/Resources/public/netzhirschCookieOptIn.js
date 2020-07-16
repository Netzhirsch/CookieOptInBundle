(function($){
	$(document).ready(function () {
		let ncoiBehindField = $('.ncoi---behind');
		// falls CSS zu spät eingunden wird
		ncoiBehindField.removeClass('ncoi---no-transition');
		// no script Iframes bekommen keine checkbox
		// checked ist der default
		$('.ncoi--release-all').removeClass('ncoi---hidden');

		let errorMessage = '';
		$('.ncoi---mod-missing').each(function () {
			errorMessage = $(this).data('ncoi-mod-missing');
			if(errorMessage.localeCompare('') !== 0)
				console.error(errorMessage);
		});

		let storageKey = 'ncoi';
		let localStorage = getLocalStorage(storageKey);
		if (
			localStorage !== ''
			&& $('[data-ncoi-cookie-version]').data('ncoi-cookie-version') === parseInt(localStorage.cookieVersion)
			&& localStorage.expireTime >= dateString()
		) {
			track(0,storageKey);
			checkExternalMediaOnLoad(localStorage.cookieIds);
			checkGroupsOnLoad(localStorage.cookieIds);
		} else {
			$.ajax({
				dataType: "json",
				type: 'POST',
				url: '/cookie/delete',
				success: function () {
					setLocalStorage(storageKey,null);
				}
			});
			ncoiBehindField.removeClass('ncoi---hidden');
		}

		$('#ncoi---allowed').on('click', function (e) {
			e.preventDefault();
			$('.ncoi---behind').addClass('ncoi---hidden');
			checkExternalMediaOnClick();
			track(1,storageKey);
		});

		$('#ncoi---allowed--all').on('click', function (e) {
			e.preventDefault();
			$('.ncoi---behind').addClass('ncoi---hidden');
			$('.ncoi---cookie-group input').prop('checked',true);
			$('.ncoi---sliding').prop('checked',true);
			checkExternalMediaOnClick();
			track(1,storageKey);
		});

		$('.ncoi---revoke--button').on('click',function (e) {
			e.preventDefault();
			setLocalStorage(storageKey,null);
			$('.ncoi---behind').removeClass('ncoi---hidden--page-load')
				.removeClass('ncoi---hidden');
			$('#FBTracking').remove();
			$('#matomoTracking').remove();
		});

		$('#ncoi---infos--show').on('click',function (e) {
			e.preventDefault();
			$('.ncoi---cookie-groups').toggleClass('ncoi---hidden');
			$('.ncoi---hint').toggleClass('ncoi---hidden');
			$('.ncoi---table').toggleClass('ncoi---hidden');
			$('.ncoi---infos--show-active').toggleClass('ncoi---hidden');
			$('.ncoi---infos--show-deactivate').toggleClass('ncoi---hidden');

		});

		$('.ncoi---sliding-input').on('change',function () {
			let group = $(this);
			$('.ncoi---cookie').each(function () {
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
				if( cookie === group && !$(this).prop('checked'))
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
			let input = parent.find('.ncoi---sliding');
			if (input.prop('checked')) {
				//In der Info Tabelle entsprechen checken damit über track() gespeichert werden kann.
				$('[data-block-class="'+input.data('block-class')+'"]').prop('checked',true).trigger('change');
				track(1,storageKey);
				checkExternalMediaOnClick();

				let parents = $('.'+input.data('block-class'));
				parents.each(function () {
					addIframe($(this));
				})
			} else {
				addIframe(parent);
			}
		});
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
	function getLocalStorage(storageKey) {
		let storageData = localStorage.getItem(storageKey);
		if (storageData !== null) {
			storageData = JSON.parse(storageData);
		}
		return (storageData ? storageData: '');
	}
	function setLocalStorage(storageKey,storageValue) {
		localStorage.setItem(storageKey,storageValue)
	}
	function track(newConsent,storageKey){
		let id = null;
		let data = {
			id : id,
			cookieIds : [],
			modId : $('[data-ncoi-mod-id]').data('ncoi-mod-id'),
			newConsent : newConsent,
			storageKey : storageKey,
			cookieVersion : 0
		};
		if (newConsent === 1) {
			let cookieSelected = $('.ncoi---cookie');
			Object.keys(cookieSelected).forEach(function(key) {
				if (
					key.localeCompare('length') !== 0
					&& key.localeCompare('prevObject') !== 0
					&& key.localeCompare('context') !== 0
					&& key.localeCompare('selector') !== 0
				) {
					if ($(cookieSelected[key]).prop('checked')) {
						data.cookieIds.push($(cookieSelected[key]).data('cookie-id'))
					}
				}
			});
		} else {
			let storage = getLocalStorage(storageKey);
			data.cookieIds = storage.cookieIds;
			data.id = storage.id;
			data.cookieVersion = storage.cookieVersion;
		}
		$.ajax({
			dataType: "json",
			type: 'POST',
			url: '/cookie/allowed',
			data: {
				data : data
			},
			success: function (response) {
				let tools = response.tools;
				let body = $('body');
				let googleAnalytics = false;
				let matomo = false;
				let templateScriptsGoogle = $('.analytics-decoded-googleAnalytics');
				let templateScriptsMatomo = $('.analytics-decoded-matomo');
				let cookieVersion = 1;
				if (response.cookieVersion !== null)
					cookieVersion = response.cookieVersion;
				setLocalStorage(storageKey,JSON.stringify({
					id: response.id,
					cookieVersion: cookieVersion,
					cookieIds: data.cookieIds,
					expireTime: response.expireTime
				}));
				if (tools !== null) {
					tools.forEach(function (tool) {
						let toolName = tool.cookieToolsSelect;
						if (toolName.localeCompare('googleAnalytics') === 0) {
							googleAnalytics = true;
							let templateScriptsEncodeElement = $('#analytics-encoded-googleAnalytics');
							if(templateScriptsEncodeElement.length > 0 && templateScriptsGoogle.length === 0) {
								decodeAfter(templateScriptsEncodeElement);
							}
							else if(templateScriptsEncodeElement.length === 0 && templateScriptsGoogle.length === 0) {
								$.getScript('https://www.googletagmanager.com/gtag/js?id=' + tool.cookieToolsTrackingID);
								window.dataLayer = window.dataLayer || [];
								function gtag(){dataLayer.push(arguments);}
								gtag('js', new Date());

								gtag('config', tool.cookieToolsTrackingID,{
									'cookie_update': false,
									'cookie_flags':'SameSite=None;Secure'
								});
							}
						}
						if(toolName.localeCompare('googleTagManager') === 0) {
							(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
									new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
								j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
								'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
							})(window,document,'script','dataLayer',tool.cookieToolsTrackingID);
						}
						if (toolName.localeCompare('facebookPixel') === 0) {
							<!-- Facebook Pixel Code -->
							!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod? n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init', tool.cookieToolsTrackingID);fbq('track', 'PageView');
						}
						if (toolName.localeCompare('matomo') === 0) {
							matomo = true;
							let templateScriptsEncodeElement = $('#analytics-encoded-matomo');
							if(templateScriptsEncodeElement.length > 0 && templateScriptsMatomo.length === 0) {
								decodeAfter(templateScriptsEncodeElement);
							}
							else if(templateScriptsEncodeElement.length === 0 && templateScriptsMatomo.length === 0) {
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
						}
					});
					let otherScripts = response.otherScripts;
					if (otherScripts !== null) {
						otherScripts.forEach(function (otherScript) {
							body.append(otherScript.cookieToolsCode);
						});
					}
				}
				if (!googleAnalytics && templateScriptsGoogle.length > 0) {
					templateScriptsGoogle.remove();
				}
				if (!matomo && templateScriptsMatomo.length > 0) {
					templateScriptsMatomo.remove();
				}
			}
		});
	}// End Track

	//ausführung beim Speicher der Entscheidung
	function checkExternalMediaOnClick() {
		let cookiesInput = $('table tbody .ncoi---cookie');
		cookiesInput.each(function () {
			let blockClass = '.' + $(this).data('block-class');
			let blockClassElement = $(blockClass);
			if ($(this).prop('checked')) {
				//Klasses des Blockconainter aus input data-block-class auslesen
				// Nur gefunden BlockContainer werden bearbeitet
				// jedes Element separat
				blockClassElement.each(function () {
					addIframe($(this));
				});
			} else {
				blockClassElement.each(function () {
					$(this).removeClass('ncoi---hidden');
					$(this).next('iframe').addClass('ncoi---hidden');
				});
			}
		});
	}

	function checkExternalMediaOnLoad(cookieIds) {
		cookieIds.forEach(function (cookieId) {
			let iframe = $('.ncoi---cookie-id-' + cookieId);
			$('#'+cookieId).trigger('change');
			if (iframe.length > 0) {
				addIframe((iframe));
			}
		});
	}

	function checkGroupsOnLoad(cookieIds) {
		cookieIds.forEach(function (cookieId) {
			$('.ncoi---cookie-id-' + cookieId).prop('checked');
		});
	}

	function addIframe(parent){
		if (!parent.hasClass('ncoi---hidden')) {
			let html = '';
			try {
				html = atob(parent.find('script').text().trim());
			} catch (e) {
				console.error('Das IFrame html enthält invalide Zeichen.')
			}
			parent.addClass('ncoi---hidden');
			parent.after(html);
		}
	}

	function decodeAfter(templateScriptsEncodeElement) {
		let templateScriptsEncode = templateScriptsEncodeElement.html();
		templateScriptsEncode = templateScriptsEncode.replace('<!--','');
		templateScriptsEncode = templateScriptsEncode.replace('-->','');
		try {
		templateScriptsEncode = atob(templateScriptsEncode);
		} catch (e) {
			console.error('Das Analyse Template enthält invalide Zeichen.')
		}
		templateScriptsEncodeElement.after(templateScriptsEncode);
	}
	
	function dateString() {
		let datum = new Date();
		let monat = datum.getMonth()+1;
		let tag = datum.getDate();
		if(monat < 10)
			monat = '0' + monat;
		if(tag < 10)
			tag = '0' + tag;
		return datum.getFullYear()+'-'+monat+'-'+tag;
	}

})(jQuery);