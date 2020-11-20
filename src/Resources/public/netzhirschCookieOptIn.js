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
			if (errorMessage.localeCompare('') !== 0)
				console.error(errorMessage);
		});

		let storageKey = 'ncoi';
		let localStorage = getLocalStorage(storageKey);
		//Respect "Do Not Track"
		let doNotTrack = false;
		if ($('[data-ncoi-respect-do-not-track]').data('ncoi-respect-do-not-track') === 1 && navigator.doNotTrack === "1") {
			doNotTrack = true;
			ajaxDeleteCookies(storageKey);
		}
		if(localStorage !== '' && !doNotTrack) {
			if (
				$('[data-ncoi-cookie-version]').data('ncoi-cookie-version') === parseInt(localStorage.cookieVersion)
				&& localStorage.expireTime >= dateString()
			) {
				track(0, storageKey);
				checkExternalMediaOnLoad(localStorage.cookieIds);
				checkGroupsOnLoad(localStorage.cookieIds);
			} else {
				ajaxDeleteCookies(storageKey);
				ncoiBehindField.removeClass('ncoi---hidden');
			}
		} else if(!doNotTrack) {
			ncoiBehindField.removeClass('ncoi---hidden');
		}

		$('#ncoi---allowed').on('click', function (e) {
			e.preventDefault();
			$('.ncoi---behind').addClass('ncoi---hidden');
			checkExternalMediaOnClick();
			track(1, storageKey);
			$('.ncoi---cookie').each(function (){
				if ($(this).data('block-class') === 'ncoi---googleMaps' && $(this).prop('checked')) {
					addCustomGmapOnClickLoad();
				}
			});
		});

		$('#ncoi---allowed--all').on('click', function (e) {
			e.preventDefault();
			$('.ncoi---behind').addClass('ncoi---hidden');
			$('.ncoi---cookie-group input').prop('checked', true);
			$('.ncoi---sliding').prop('checked', true);
			checkExternalMediaOnClick();
			addCustomGmapOnClickLoad();
			track(1, storageKey);
		});

		$('.ncoi---revoke--button').on('click', function (e) {
			e.preventDefault();
			ajaxDeleteCookies(storageKey);
			$('.ncoi---behind').removeClass('ncoi---hidden--page-load')
				.removeClass('ncoi---hidden');
			$('#FBTracking').remove();
			$('#matomoTracking').remove();
		});

		$('#ncoi---infos--show').on('click', function (e) {
			e.preventDefault();
			$('.ncoi---cookie-groups').toggleClass('ncoi---hidden');
			$('.ncoi---hint').toggleClass('ncoi---hidden');
			$('.ncoi---table').toggleClass('ncoi---hidden');
			$('.ncoi---infos--show-active').toggleClass('ncoi---hidden');
			$('.ncoi---infos--show-deactivate').toggleClass('ncoi---hidden');

		});

		$('.ncoi---sliding-input').on('change', function () {
			let group = $(this);
			$('.ncoi---cookie').each(function () {
				let cookie = $(this).data('group');
				if (group.val().localeCompare(cookie) === 0)
					$(this).prop('checked', group.prop('checked'));
			});
		});

		let cookiesSelect = $('.ncoi---cookie');

		cookiesSelect.on('change', function () {
			let cookie = $(this).data('group');
			let allChecked = true;
			cookiesSelect.each(function () {
				let group = $(this).data('group');
				if (cookie === group && !$(this).prop('checked'))
					allChecked = false;
			});
			$('.ncoi---cookie-group input').each(function () {
				let group = $(this).val();
				if (group.localeCompare(cookie) === 0)
					$(this).prop('checked', allChecked);
			});

		});
		$('.ncoi---release').on('click', function (e) {
			e.preventDefault();
			//Um richtige Chechbox zu finden
			//und um Blockcontainer vielleicht auszublenden und iFrame anzuhängen
			let parent = $(this).parents('.ncoi---blocked');
			let input = parent.find('.ncoi---sliding');
			let blockClass = $('[data-block-class="' + input.data('block-class') + '"]');
			if (input.prop('checked')) {
				//In der Info Tabelle entsprechen checken damit über track() gespeichert werden kann.
				blockClass.prop('checked', true).trigger('change');
				let inputClass = input.data('block-class')+"";
				let blockClassIds = $('[data-block-class="ncoi---' + inputClass + '"]');
				blockClassIds.prop('checked', true).trigger('change');
				track(1, storageKey);
				checkExternalMediaOnClick();

				let parents = $('.' + input.data('block-class'));
				parents.each(function () {
					addIframe($(this));
				})
			} else {
				addIframe(parent);
			}
			addCustomGmapOnClickLoad();
		});
});

function getLocalStorage(storageKey) {
	let storageData = localStorage.getItem(storageKey);
	if (storageData !== null) {
		storageData = JSON.parse(storageData);
	}
	return (storageData ? storageData : '');
}

function setLocalStorage(storageKey, storageValue) {
	localStorage.setItem(storageKey, storageValue)
}

function track(newConsent, storageKey) {
	let id = null;
	let data = {
		id: id,
		cookieIds: [],
		modId: $('[data-ncoi-mod-id]').data('ncoi-mod-id'),
		newConsent: newConsent,
		storageKey: storageKey,
		cookieVersion: 0
	};
	if (newConsent === 1) {
		let cookieSelected = $('.ncoi---cookie');
		Object.keys(cookieSelected).forEach(function (key) {
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
			data: data
		},
		success: function (response) {
			let tools = response.tools;
			let body = $('body');
			let googleAnalytics = false;
			let matomoCookiesNames = {};
			let templateScriptsGoogle = $('.analytics-decoded-googleAnalytics');
			let templateScriptsMatomo = $('.analytics-decoded-matomo');
			let cookieVersion = 1;
			if (response.cookieVersion !== null)
				cookieVersion = response.cookieVersion;
			setLocalStorage(storageKey, JSON.stringify({
				id: response.id,
				cookieVersion: cookieVersion,
				cookieIds: data.cookieIds,
				expireTime: response.expireTime
			}));
			matomoCookiesNames = false;
			if (tools !== null) {
				// delete all cookie that are not allowed
				let allCookies = Cookies.get();
				tools.forEach(function (tool) {
					let toolName = tool.cookieToolsSelect;
					allCookies = checkCookieArray(allCookies,tool.cookieToolsTechnicalName);
					if (toolName.localeCompare('googleAnalytics') === 0) {
						googleAnalytics = true;
						let templateScriptsEncodeElement = $('#analytics-encoded-googleAnalytics');
						if (templateScriptsEncodeElement.length > 0 && templateScriptsGoogle.length === 0) {
							decodeAfter(templateScriptsEncodeElement);
						} else if (templateScriptsEncodeElement.length === 0 && templateScriptsGoogle.length === 0) {
							$.getScript('https://www.googletagmanager.com/gtag/js?id=' + tool.cookieToolsTrackingID);
							window.dataLayer = window.dataLayer || [];

							function gtag() {
								dataLayer.push(arguments);
							}

							gtag('js', new Date());

							gtag('config', tool.cookieToolsTrackingID, {
								'cookie_update': false,
								'cookie_flags': 'SameSite=None;Secure'
							});
						}
					}
					if (toolName.localeCompare('googleTagManager') === 0) {
						(function (w, d, s, l, i) {
							w[l] = w[l] || [];
							w[l].push({
								'gtm.start':
									new Date().getTime(), event: 'gtm.js'
							});
							var f = d.getElementsByTagName(s)[0],
								j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
							j.async = true;
							j.src =
								'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
							f.parentNode.insertBefore(j, f);
						})(window, document, 'script', 'dataLayer', tool.cookieToolsTrackingID);
					}
					if (toolName.localeCompare('facebookPixel') === 0) {
						<!-- Facebook Pixel Code -->
						!function (f, b, e, v, n, t, s) {
							if (f.fbq) return;
							n = f.fbq = function () {
								n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments)
							};
							if (!f._fbq) f._fbq = n;
							n.push = n;
							n.loaded = !0;
							n.version = '2.0';
							n.queue = [];
							t = b.createElement(e);
							t.async = !0;
							t.src = v;
							s = b.getElementsByTagName(e)[0];
							s.parentNode.insertBefore(t, s)
						}(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
						fbq('init', tool.cookieToolsTrackingID);
						fbq('track', 'PageView');
					}
					if (toolName.localeCompare('matomo') === 0) {
						matomoCookiesNames = tools.cookieToolsTechnicalName;
						let templateScriptsEncodeElement = $('#analytics-encoded-matomo');

						if (
							templateScriptsEncodeElement.length !== 0
						) {
							decodeAfter(templateScriptsEncodeElement);

						} else  {

							let url = tool.cookieToolsTrackingServerUrl;
							if (url.slice(-1) !== '/')
								url += '/';
							body.append("<script type=\"text/javascript\">" +
								"var _paq = window._paq || [];" +
								"_paq.push(['trackPageView']);" +
								"_paq.push(['enableLinkTracking']);" +
								"(function() {" +
								"var u = '" + url + "';" +
								"_paq.push(['setTrackerUrl', u+'matomo.php']);" +
								"_paq.push(['setSiteId', '" + tool.cookieToolsTrackingID + "']);" +
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
						allCookies = checkCookieArray(allCookies,otherScript.cookieToolsTechnicalName);
						body.append(otherScript.cookieToolsCode);
					});
				}
				for (let cookie in allCookies) {
					if (
						allCookies.hasOwnProperty(cookie)
						&& cookie !== 'XDEBUG_SESSION'
						&& cookie !== 'BE_USER_AUTH'
						&& cookie !== 'FE_USER_AUTH'
						&& cookie !== 'BE_PAGE_OFFSET'
						&& cookie !== 'trusted_device'
						&& cookie !== 'csrf_contao_csrf_token'
						&& cookie !== 'csrf_https-contao_csrf_token'
						&& cookie !== 'PHPSESSID'
						&& cookie !== 'contao_settings'
					) {
						//tries all paths from root to current subpage
						let cookiePath = '',
							cookiePaths = window.location.pathname.split('/')
						while (cookiePaths.length > 0) {
							cookiePath = cookiePath + cookiePaths.shift() + '/';
							Cookies.remove(cookie, {path: cookiePath});
						}
					}
				}
			}
			if (!googleAnalytics && templateScriptsGoogle.length > 0) {
				templateScriptsGoogle.remove();
			}
			if (!matomoCookiesNames && templateScriptsMatomo.length > 0) {
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
					if (!$(this).hasClass('ncoi---googleMaps')) {
						$(this).removeClass('ncoi---hidden');
						$(this).next('iframe').addClass('ncoi---hidden');
					}
				});
			}
		});
	}

	function checkExternalMediaOnLoad(cookieIds) {
		$('.ncoi---blocked').each(function (key,value) {
			let iframe = $(this);
			cookieIds.forEach(function (cookieId) {
				if ($(value).hasClass('ncoi---cookie-id-'+cookieId)) {
					iframe.trigger('change');
					if (iframe.length > 0) {
						addIframe(iframe);
					}
				}
			});
		});
	}

	function checkGroupsOnLoad(cookieIds) {
		cookieIds.forEach(function (cookieId) {
			$('.ncoi---cookie-id-' + cookieId).prop('checked');
		});
	}

	function addCustomGmapOnClickLoad() {
		$('.ce_google_map').removeClass('ncoi---hidden');
		let gmapBlockContainer = $('.ncoi---custom_gmap');
		gmapBlockContainer.find('.ce_google_map_inside').css('height',0);
		gmapBlockContainer.addClass('ncoi---hidden');
	}



	function addIframe(parent) {

		if (!parent.hasClass('ncoi---hidden')) {
			let html = '';
			try {
				if (parent.length > 1) {
					for (let i = 0; i < parent.length; i++) {
						$('.'+parent[i].classList[3]).each(function (){
							html = atob($(this).find('script').text().trim());
						});
					}
				} else {
					html = atob(parent.find('script').text().trim());
				}
			} catch (e) {
				console.error('Das IFrame html enthält invalide Zeichen.')
			}
			parent.addClass('ncoi---hidden');
			parent.after(html);
		}
	}

	function decodeAfter(templateScriptsEncodeElement) {
		let templateScriptsEncode = templateScriptsEncodeElement.html();
		templateScriptsEncode = templateScriptsEncode.replace('<!--', '');
		templateScriptsEncode = templateScriptsEncode.replace('-->', '');
		try {
			templateScriptsEncode = atob(templateScriptsEncode);
		} catch (e) {
			console.error('Das Analyse Template enthält invalide Zeichen.')
		}
		templateScriptsEncodeElement.after(templateScriptsEncode);
	}

	function dateString() {
		let datum = new Date();
		let monat = datum.getMonth() + 1;
		let tag = datum.getDate();
		if (monat < 10)
			monat = '0' + monat;
		if (tag < 10)
			tag = '0' + tag;
		return datum.getFullYear() + '-' + monat + '-' + tag;
	}

	function checkCookieArray(cookiesToDelete,cookieToolsTechnicalName) {
		if (cookieToolsTechnicalName.indexOf(',') > -1) {
			let technicalNames = cookieToolsTechnicalName.split(',');
			let cookiesToDeleteIndex = 0;
			for (let cookie in cookiesToDelete) {
				technicalNames.forEach(function (element, index) {
					if (
						technicalNames[index] === cookie
						&& cookiesToDelete.hasOwnProperty(cookie)
					) {
						unsetCookie(technicalNames[index],cookie,cookiesToDelete);
					}
				});
				cookiesToDeleteIndex++;
			}
		} else {
			for (let cookie in cookiesToDelete) {
				if (
					cookiesToDelete.hasOwnProperty(cookie)
					&& cookieToolsTechnicalName === cookie
				)
					unsetCookie(cookieToolsTechnicalName,cookie,cookiesToDelete);
			}
		}
		return cookiesToDelete;
	}

	function unsetCookie(cookieToolsTechnicalName,cookie,cookiesToDelete) {
		if (cookieToolsTechnicalName === cookie) {
				delete cookiesToDelete[cookie];
		}
	}

	function ajaxDeleteCookies(storageKey) {
		$.ajax({
			dataType: "json",
			type: 'POST',
			url: '/cookie/delete',
			success: function () {
				setLocalStorage(storageKey, null);
			}
		});
	}

})(jQuery);