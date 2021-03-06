<?php
	header("Content-type: text/javascript");
?>
/*!
 * jQuery Smart Banner
 * Copyright (c) 2012 Arnold Daniels <arnold@jasny.net>
 * Based on 'jQuery Smart Web App Banner' by Kurt Zenisek @ kzeni.com
 */
!function ($) {
    var SmartBanner = function (options) {
        this.origHtmlMargin = parseFloat($('html').css('margin-top')) // Get the original margin-top of the HTML element so we can take that into account
        this.options = $.extend({}, $.smartbanner.defaults, options)

        var standalone = navigator.standalone // Check if it's already a standalone web app or running within a webui view of an app (not mobile safari)

        // Detect banner type (iOS, Android or Windows)
        if (this.options.force) {
            this.type = this.options.force
        } else if (navigator.userAgent.match(/iPad|iPhone|iPod/i) != null) {
            if (navigator.userAgent.match(/Safari/i) != null && window.Number(navigator.userAgent.substr(navigator.userAgent.indexOf('OS ') + 3, 3).replace('_', '.')) < 6) this.type = 'ios' // Check webview and native smart banner support (iOS 6+)
        } else if (navigator.userAgent.match(/Android/i) != null) {
            this.type = 'android'
        } else if (navigator.userAgent.match(/Windows NT 6.2/i) != null) {
            this.type = 'windows'
        } else if (navigator.userAgent.match(/Windows Phone/i) != null) {
            this.type = 'windows-phone'
        }

        // Don't show banner if device isn't iOS or Android, website is loaded in app or user dismissed banner
        if (!this.type || standalone || this.getCookie('sb-closed') || this.getCookie('sb-installed')) {
            return
        }

        // Calculate scale
        this.scale = this.options.scale == 'auto' ? $(window).width() / window.screen.width : this.options.scale
        if (this.scale < 1) this.scale = 1

        // Get info from meta data
        // TODO : refactoring all SWITCH :
        //var meta = $(this.type == 'android' ? 'meta[name="google-play-app"]' : this.type == 'ios' ? 'meta[name="apple-itunes-app"]' : 'meta[name="msApplication-ID"]');
        switch(this.type) {
          case 'windows':
            var metaString = 'meta[name="msApplication-ID"]';
            break;
          case 'windows-phone':
            var metaString = 'meta[name="msApplication-WinPhonePackageUrl"]';
            break;
          case 'android':
            var metaString = 'meta[name="google-play-app"]';
            break;
          case 'ios':
            var metaString = 'meta[name="apple-itunes-app"]';
            break;
        }
        var meta = $(metaString);

        if (meta.length == 0) return

        // For Windows Store apps, get the PackageFamilyName for protocol launch
        if (this.type == 'windows') {
            this.pfn = $('meta[name="msApplication-PackageFamilyName"]').attr('content');
            this.appId = meta.attr('content')[1]
        } else if (this.type == 'windows-phone') {
            this.appId = meta.attr('content')
        } else {
            this.appId = /app-id=([^\s,]+)/.exec(meta.attr('content'))[1]
        }

        this.title = this.options.title ? this.options.title : $('title').text().replace(/\s*[|\-·].*$/, '')
        this.author = this.options.author ? this.options.author : ($('meta[name="author"]').length ? $('meta[name="author"]').attr('content') : window.location.hostname)

        // Create banner
        this.create()
        this.show()
        this.listen()
    }

    SmartBanner.prototype = {

        constructor: SmartBanner

      , create: function () {
          var iconURL
            , link = (this.type == 'windows' || this.type == 'windows-phone') ? 'ms-windows-store:PDP?PFN=' + this.pfn : (this.type == 'android' ? 'market://details?id=' : 'https://itunes.apple.com/' + this.options.appStoreLanguage + '/app/id') + this.appId
            , inStore = this.options.price ? '<span class="sb-price">'+ this.options.price + '</span> ' + (this.type == 'android' ? this.options.inGooglePlay : this.type == 'ios' ? this.options.inAppStore : this.options.inWindowsStore) : ''
            , gloss = this.options.iconGloss;

        switch(this.type){
          case('windows'):
            link = 'ms-windows-store:PDP?PFN=' + this.pfn;
            break;
          case('windows-phone'):
            link = 'http://windowsphone.com/s?appId='+this.appId;
            break;
          case('android'):
            link = 'market://details?id=' + this.appId;
            break;
          case('ios'):
            link = 'https://itunes.apple.com/' + this.options.appStoreLanguage + '/app/id' + this.appId;
            break;
        }

          var container = this.options.container;
          if($(container).length<1) return;
          //$('body').append('<div id="smartbanner" class="' + this.type + '"><div class="sb-container"><a href="#" class="sb-close">&times;</a><span class="sb-icon"></span><div class="sb-info"><strong>' + this.title + '</strong><span>' + this.author + '</span><span>' + inStore + '</span></div><a href="' + link + '" class="sb-button"><span>' + this.options.button + '</span></a></div></div>')
          $(container).append('<div id="smartbanner" class="' + this.type + '"><div class="sb-container"><a href="#" class="sb-close">&times;</a><span class="sb-icon"></span><div class="sb-info"><strong>' + this.title + '</strong><span>' + this.author + '</span><span>' + inStore + '</span></div><a href="' + link + '" target="_blank" class="sb-button"><span>' + this.options.button + '</span></a></div></div>')

          if (this.options.icon) {
              iconURL = this.options.icon
          } else if ($('link[rel="apple-touch-icon-precomposed"]').length > 0) {
              iconURL = $('link[rel="apple-touch-icon-precomposed"]').attr('href')
              if (this.options.iconGloss === null) gloss = false
          } else if ($('link[rel="apple-touch-icon"]').length > 0) {
              iconURL = $('link[rel="apple-touch-icon"]').attr('href')
          } else if ($('meta[name="msApplication-TileImage"]').length > 0) {
              iconURL = $('meta[name="msApplication-TileImage"]').attr('content')
          } else if ($('meta[name="msapplication-TileImage"]').length > 0) { /* redundant because ms docs show two case usages */
              iconURL = $('meta[name="msapplication-TileImage"]').attr('content')
          }
          if (iconURL) {
              $('#smartbanner .sb-icon').css('background-image', 'url(' + iconURL + ')')
              if (gloss) $('#smartbanner .sb-icon').addClass('gloss')
          } else {
              $('#smartbanner').addClass('no-icon')
          }

          this.bannerHeight = $('#smartbanner').outerHeight() + 2

          if (this.scale > 1) {
              $('#smartbanner')
                  .css('top', parseFloat($('#smartbanner').css('top')) * this.scale)
                  .css('height', parseFloat($('#smartbanner').css('height')) * this.scale)
              $('#smartbanner .sb-container')
                  .css('-webkit-transform', 'scale(' + this.scale + ')')
                  .css('-msie-transform', 'scale(' + this.scale + ')')
                  .css('-moz-transform', 'scale(' + this.scale + ')')
                  .css('width', $(window).width() / this.scale)
          }
      }

      , listen: function () {
          $('#smartbanner .sb-close').on('click', $.proxy(this.close, this))
          $('#smartbanner .sb-button').on('click', $.proxy(this.install, this))
      }

      , show: function (callback) {
          //$('#smartbanner').stop().animate({ top: 0 }, this.options.speedIn).addClass('shown')
          //$('html').animate({ marginTop: this.origHtmlMargin + (this.bannerHeight * this.scale) }, this.options.speedIn, 'swing', callback)

          //$('#smartbanner').addClass('shown')
          //$('html').addClass('smartBannerHey');
          $('html').get(0).className = $('html').get(0).className+' smartBanner ';
        if(callback)  callback();
      }

      , hide: function (callback) {
          //$('#smartbanner').stop().animate({ top: 0 }, this.options.speedIn).addClass('shown')
          //$('html').animate({ marginTop: this.origHtmlMargin }, this.options.speedOut, 'swing', callback)

          //$('#smartbanner').addClass('shown')
          $('html').removeClass('smartBanner');
          if(callback)  callback();
      }

      , close: function (e) {
          e.preventDefault()
          this.hide()
          this.setCookie('sb-closed', 'true', this.options.daysHidden)
      }

      , install: function (e) {
          this.hide()
          this.setCookie('sb-installed', 'true', this.options.daysReminder)
      }

      , setCookie: function (name, value, exdays) {
          var exdate = new Date()
          exdate.setDate(exdate.getDate() + exdays)
          value = escape(value) + ((exdays == null) ? '' : '; expires=' + exdate.toUTCString())
          document.cookie = name + '=' + value + '; path=/;'
      }

      , getCookie: function (name) {
          var i, x, y, ARRcookies = document.cookie.split(";")
          for (i = 0; i < ARRcookies.length; i++) {
              x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="))
              y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1)
              x = x.replace(/^\s+|\s+$/g, "")
              if (x == name) {
                  return unescape(y)
              }
          }
          return null
      }

        // Demo only
      , switchType: function () {
          var that = this
          var a_format = ['ios', 'android', 'windows', 'windows-phone'];

          // Array.indexOf polyfill from mozilla : https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/indexOf
          if (!Array.prototype.indexOf) {
            Array.prototype.indexOf = function (searchElement, fromIndex) {
              if ( this === undefined || this === null ) throw new TypeError( '"this" is null or not defined' );
              var length = this.length >>> 0; // Hack to convert object.length to a UInt32
              fromIndex = +fromIndex || 0;
              if (Math.abs(fromIndex) === Infinity) fromIndex = 0;
              if (fromIndex < 0) {  fromIndex += length;  if (fromIndex < 0)  fromIndex = 0;  }
              for (;fromIndex < length; fromIndex++)  if (this[fromIndex] === searchElement)  return fromIndex;
              return -1;
            };
          }

          this.hide(function () {
              //that.type = that.type == 'android' ? 'ios' : 'android';
              var newIndex = a_format.indexOf(that.type)+1
              that.type = (!a_format[newIndex]) ? a_format[0] : a_format[newIndex];
              var meta = $(that.type == 'android' ? 'meta[name="google-play-app"]' : 'meta[name="apple-itunes-app"]').attr('content')
              that.appId = /app-id=([^\s,]+)/.exec(meta)[1]

              $('#smartbanner').detach()
              that.create()
              that.show()
              if(window.console && console.log) console.log(that.type);
          })
      }
    }

    $.smartbanner = function (option) {
      var $window = $(window)
        , data = $window.data('typeahead')
        , options = typeof option == 'object' && option
        if (!data) $window.data('typeahead', (data = new SmartBanner(options)))
        if (typeof option == 'string') data[option]()
    }

    // override these globally if you like (they are all optional)
    $.smartbanner.defaults = {
        title: 'yanniks.de App', // What the title of the app should be in the banner (defaults to <title>)
        author: 'Yannik Ehlert', // What the author of the app should be in the banner (defaults to <meta name="author"> or hostname)
	    icon: null, // The URL of the icon (defaults to <meta name="apple-touch-icon">)
	    iconGloss: null, // Force gloss effect for iOS even for precomposed
        scale: 'auto', // Scale based on viewport size (set to 1 to disable)
        //speedIn: 300, // Show animation speed of the banner
        //speedOut: 400, // Close animation speed of the banner
        daysHidden: 15, // Duration to hide the banner after being closed (0 = always show banner)
        daysReminder: 30, // Duration to hide the banner after "VIEW" is clicked *separate from when the close button is clicked* (0 = always show banner)
        container: 'body', // Container where the banner will be injected
        force: null, // Choose 'ios', 'android' or 'windows'. Don't do a browser check, just always show this banner
		price: '<?php if ($_GET["lang"] == "deutsch") { echo "KOSTENLOS";} else {echo "FREE";} ?>',
		appStoreLanguage: '<?php if ($_GET["lang"] == "deutsch") { echo "de";} else {echo "us";} ?>',
		inAppStore: '<?php if ($_GET["lang"] == "deutsch") { echo "Im App Store";} else {echo "On the App Store";} ?>',
		inGooglePlay: '<?php if ($_GET["lang"] == "deutsch") { echo "In Google Play";} else {echo "In Google Play";} ?>',
		inWindowsStore: '<?php if ($_GET["lang"] == "deutsch") { echo "Im Windows Store";} else {echo "In the Windows Store";} ?>',
		button: '<?php if ($_GET["lang"] == "deutsch") { echo "Im Store angucken";} else {echo "View in Store";} ?>'
	}

    $.smartbanner.Constructor = SmartBanner;

}(window.jQuery);
