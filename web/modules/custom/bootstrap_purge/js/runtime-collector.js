/**
 * @file
 * Runtime CSS/JS usage collector for Bootstrap Purge.
 */

(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * Bootstrap Purge runtime collector.
   */
  Drupal.behaviors.bootstrapPurgeRuntimeCollector = {
    attach: function (context, settings) {
      if (!settings.bootstrapPurge || !settings.bootstrapPurge.endpoint) {
        return;
      }

      var collector = new BootstrapPurgeCollector(settings.bootstrapPurge);
      collector.start();
    }
  };

  /**
   * Runtime usage collector class.
   */
  function BootstrapPurgeCollector(config) {
    this.config = config;
    this.usedSelectors = new Set();
    this.usedEvents = new Set();
    this.startTime = Date.now();
    this.observer = null;
    this.collectInterval = null;
  }

  BootstrapPurgeCollector.prototype = {
    start: function () {
      this.collectInitialSelectors();
      this.setupMutationObserver();
      this.setupEventListeners();
      this.setupPeriodicCollection();
      this.setupBeaconSending();
    },

    collectInitialSelectors: function () {
      var elements = document.querySelectorAll('*[class]');
      for (var i = 0; i < elements.length; i++) {
        this.collectElementClasses(elements[i]);
      }
    },

    setupMutationObserver: function () {
      if (!window.MutationObserver) {
        return;
      }

      var self = this;
      this.observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
          if (mutation.type === 'childList') {
            mutation.addedNodes.forEach(function (node) {
              if (node.nodeType === Node.ELEMENT_NODE) {
                self.collectElementClasses(node);
                var children = node.querySelectorAll('*[class]');
                for (var i = 0; i < children.length; i++) {
                  self.collectElementClasses(children[i]);
                }
              }
            });
          } else if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
            self.collectElementClasses(mutation.target);
          }
        });
      });

      this.observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['class']
      });
    },

    collectElementClasses: function (element) {
      if (!element.className) {
        return;
      }

      var classes = element.className.split(/\s+/);
      for (var i = 0; i < classes.length; i++) {
        var className = classes[i].trim();
        if (className) {
          this.usedSelectors.add('.' + className);
        }
      }

      if (element.id) {
        this.usedSelectors.add('#' + element.id);
      }
    },

    setupEventListeners: function () {
      var self = this;
      var events = ['click', 'mouseover', 'focus', 'keydown', 'scroll', 'resize'];

      events.forEach(function (eventType) {
        document.addEventListener(eventType, function (event) {
          self.usedEvents.add(eventType);
          if (event.target) {
            self.collectElementClasses(event.target);
          }
        }, true);
      });
    },

    setupPeriodicCollection: function () {
      var self = this;
      this.collectInterval = setInterval(function () {
        self.collectComputedStyles();
      }, 5000);
    },

    collectComputedStyles: function () {
      var elements = document.querySelectorAll('*[class]');
      for (var i = 0; i < Math.min(elements.length, 100); i++) {
        var element = elements[i];
        var rect = element.getBoundingClientRect();
        if (rect.width > 0 && rect.height > 0) {
          this.collectElementClasses(element);
        }
      }
    },

    setupBeaconSending: function () {
      var self = this;
      window.addEventListener('beforeunload', function () {
        self.sendData();
      });
      setTimeout(function () {
        self.sendData();
      }, 30000);
    },

    sendData: function () {
      if (this.usedSelectors.size === 0) {
        return;
      }

      var data = {
        route: this.config.route,
        selectors: Array.from(this.usedSelectors),
        events: Array.from(this.usedEvents),
        duration: Date.now() - this.startTime,
        timestamp: Date.now(),
        url: window.location.href,
        viewport: {
          width: window.innerWidth,
          height: window.innerHeight
        }
      };

      if (navigator.sendBeacon) {
        navigator.sendBeacon(this.config.endpoint, JSON.stringify(data));
      } else if (window.fetch) {
        fetch(this.config.endpoint, {
          method: 'POST',
          body: JSON.stringify(data),
          headers: {'Content-Type': 'application/json'},
          keepalive: true
        }).catch(function () {});
      }

      if (this.observer) {
        this.observer.disconnect();
      }
      if (this.collectInterval) {
        clearInterval(this.collectInterval);
      }
    }
  };

})(Drupal, drupalSettings);