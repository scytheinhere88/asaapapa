/**
 * Safe Timeout Handler
 * Fixes edge cases with timeout handling
 * - Ensures cleanup happens even on timeout
 * - Prevents ambiguous status when timeout is exactly at limit
 * - Provides better error messages
 */

(function(window) {
  'use strict';

  const SafeTimeoutHandler = {
    activeTimeouts: new Map(),

    createTimeout: function(fn, delay, options) {
      options = options || {};
      const timeoutId = this.generateId();
      const onTimeout = options.onTimeout || function() {};
      const label = options.label || 'unnamed';

      let completed = false;
      let timedOut = false;

      const timeoutHandle = setTimeout(function() {
        if (completed) return;
        timedOut = true;
        console.warn('[SafeTimeout] Timeout reached:', label, delay + 'ms');

        try {
          onTimeout();
        } catch (error) {
          console.error('[SafeTimeout] Timeout handler error:', error);
        }

        SafeTimeoutHandler.cleanup(timeoutId);
      }, delay);

      const wrapper = {
        id: timeoutId,
        label: label,
        delay: delay,
        startTime: Date.now(),
        handle: timeoutHandle,
        completed: false,
        timedOut: false,

        clear: function() {
          if (this.completed || this.timedOut) return false;
          completed = true;
          this.completed = true;
          clearTimeout(timeoutHandle);
          SafeTimeoutHandler.cleanup(timeoutId);
          return true;
        },

        isTimedOut: function() {
          return timedOut || this.timedOut;
        },

        isCompleted: function() {
          return completed || this.completed;
        },

        getElapsed: function() {
          return Date.now() - this.startTime;
        },

        getRemainingTime: function() {
          const elapsed = this.getElapsed();
          return Math.max(0, this.delay - elapsed);
        }
      };

      this.activeTimeouts.set(timeoutId, wrapper);
      return wrapper;
    },

    createAbortableRequest: function(url, options, timeout) {
      options = options || {};
      const controller = new AbortController();
      const signal = controller.signal;

      const requestOptions = Object.assign({}, options, { signal: signal });

      const timeoutWrapper = this.createTimeout(
        function() {
          console.log('[SafeTimeout] Aborting request:', url);
          controller.abort();
        },
        timeout,
        {
          label: 'fetch:' + url,
          onTimeout: options.onTimeout
        }
      );

      const fetchPromise = fetch(url, requestOptions)
        .finally(function() {
          timeoutWrapper.clear();
        });

      return {
        promise: fetchPromise,
        timeout: timeoutWrapper,
        controller: controller,
        abort: function() {
          controller.abort();
          timeoutWrapper.clear();
        }
      };
    },

    generateId: function() {
      return 'timeout_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    },

    cleanup: function(timeoutId) {
      this.activeTimeouts.delete(timeoutId);
    },

    cleanupAll: function() {
      console.log('[SafeTimeout] Cleaning up all timeouts, count:', this.activeTimeouts.size);

      this.activeTimeouts.forEach(function(wrapper) {
        wrapper.clear();
      });

      this.activeTimeouts.clear();
    },

    getActiveCount: function() {
      return this.activeTimeouts.size;
    }
  };

  window.addEventListener('beforeunload', function() {
    SafeTimeoutHandler.cleanupAll();
  });

  window.SafeTimeout = SafeTimeoutHandler;

  console.log('[SafeTimeout] Handler loaded and ready');

})(window);
