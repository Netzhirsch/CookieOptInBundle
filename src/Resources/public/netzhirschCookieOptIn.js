(function($){
	$(document).ready(function () {

		let errorMessage = '';
		$('.ncoi---mod-missing').each(function () {
			errorMessage = $(this).data('ncoi-mod-missing');
			if(errorMessage.localeCompare('') !== 0)
				console.error(errorMessage);
		});

		if (
			$('[data-ncoi-allowed]').data('ncoi-allowed') === 1
			&& $('[data-ncoi-is-version-new]').data('ncoi-is-version-new') === 0
		) {
			track();
		}

		$('#ncoi---allowed').on('click', function (e) {
			e.preventDefault();
			$('.ncoi---behind').addClass('ncoi---hidden');
			track();
		});

		$('#ncoi---allowed--all').on('click', function (e) {
			e.preventDefault();
			$('.ncoi---behind').addClass('ncoi---hidden');
			$('.ncoi---groups input').prop('checked',true);
			$('.ncoi---cookie').prop('checked',true);
			track();
		});

		$('#ncoi---revoke').on('click',function (e) {
			e.preventDefault();
			$('.ncoi---behind').removeClass('ncoi---hidden');
			$('#FBTracking').remove();
			$('#matomoTracking').remove();
		});

		$('.ncoi---infos--show').on('click',function (e) {
			e.preventDefault();
			$('.ncoi---behind .ncoi---scroll')
				.toggleClass('ncoi---content--question')
				.toggleClass('ncoi---content--info')
				.toggleClass('ncoi---scroll--overflow');
			$('.ncoi---scroll--overflow').animate({
				scrollTop:$('.ncoi--headline').offset().bottom
			},2000);
			$('#ncoi---infos').toggleClass('ncoi---hidden');
			$('.ncoi---hint').toggleClass('ncoi---hidden');
			$('.ncoi---infos--show__active').toggleClass('ncoi---hidden');
			$('.ncoi---infos--sho__deactivate').toggleClass('ncoi---hidden');

		});

		$('.cookieGroup').on('change',function () {
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
				if( group.localeCompare(cookie) === 0 && !$(this).prop('checked'))
					allChecked = false;
			});
			$('.cookieGroup').each(function () {
				let group = $(this).val();
				if(group.localeCompare(cookie) === 0)
					$(this).prop('checked',allChecked);
			});

		});
	});

//  only for testing
//	function getCookie(cname) {
//		let name = cname + "=";
//		let decodedCookie = decodeURIComponent(document.cookie);
//		let ca = decodedCookie.split(';');
//		for(let i = 0; i <ca.length; i++) {
//			let c = ca[i];
//			while (c.charAt(0).localeCompare(' ') === 0 ) {
//				c = c.substring(1);
//			}
//			if (c.indexOf(name) === 0) {
//				return c.substring(name.length, c.length);
//			}
//		}
//		return false;
//	}

	function track(){
		let selected = {
			cookieIds : [{}],
			modId : {}
		};
		let cookieSelected = $('.ncoi---cookie');
		Object.keys(cookieSelected).forEach(function(key) {
			if (key.localeCompare('length') !== 0 && key.localeCompare('prevObject') !== 0) {
				if ($(cookieSelected[key]).prop('checked')) {
					selected.cookieIds.push($(cookieSelected[key]).data('cookie-id'))
				}
			}
		});
		selected.modId = $('[data-ncoi-mod-id]').data('ncoi-mod-id');
		$.ajax({
			dataType: "json",
			type: 'POST',
			url: '/cookie/allowed',
			data: {
				selected : selected
			},
			success: function (data) {
				let tools = data.tools;
				let otherScripts = data.otherScripts;
				if (otherScripts !== null) {
					otherScripts.forEach(function (otherScript) {
						eval(otherScript.cookieToolsCode);
					});
				}
				let content = $('body');
				tools.forEach(function (tool) {
					let toolName = tool.cookieToolsSelect;
					if (toolName.localeCompare('googleAnalytics') === 0) {
						$.getScript('https://www.googletagmanager.com/gtag/js?id=' + tool.cookieToolsTrackingId);
						window.dataLayer = window.dataLayer || [];
						function gtag(){dataLayer.push(arguments);}

						gtag('js', new Date());

						gtag('config', tool.cookieToolsTrackingId);
					}
					if (toolName.localeCompare('facebookPixel') === 0) {
						!function(f,b,e,v,n,t,s)
						{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
							n.callMethod.apply(n,arguments):n.queue.push(arguments)};
							if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
							n.queue=[];t=b.createElement(e);t.async=!0;
							t.src=v;s=b.getElementsByTagName(e)[0];
							s.parentNode.insertBefore(t,s)}(window, document,'script',
							'https://connect.facebook.net/en_US/fbevents.js');

						fbq('init', tool.cookieToolsTrackingId);

						fbq('track', 'PageView');
					}
					if (toolName.localeCompare('matomo') === 0) {
						var _paq = window._paq || [];
						/* tracker methods like "setCustomDimension" should be called before "trackPageView" */
						if (!$.isPlainObject(_paq)) {
							_paq.push(['trackPageView']);
							_paq.push(['enableLinkTracking']);
						}
						(function () {
							var u = "//"+tool.cookieToolsTrackingServerUrl+"/";
							if (!$.isPlainObject(_paq)) {
								_paq.push(['setTrackerUrl', u + 'matomo.php']);
								_paq.push(['setSiteId', '2']);
							}
							var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
							g.type = 'text/javascript';
							g.async = true;
							g.defer = true;
							g.src = u + 'matomo.js';
							s.parentNode.insertBefore(g, s);
							content.append('<img id="matomoTracking"  src="http://matomo/matomo.php?idsite='+tool.cookieToolsTrackingId+'&amp;rec=1" alt=""/>').appendHTML;

						})();
					}
				});
			}
		});
	}
})(jQuery);