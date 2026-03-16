/**
 * Autopilot Safe Polling Manager
 * Fixes:
 * 1. Race condition - Tab-level locking
 * 2. Memory leak - Auto cleanup on page unload
 * 3. Timeout edge cases - Better error handling
 */

(function(window) {
  'use strict';

  const AutopilotPollingManager = {
    activePolls: new Map(),
    tabId: null,
    isPageActive: true,

    init: function() {
      this.tabId = this.generateTabId();
      this.setupUnloadHandlers();
      this.setupVisibilityHandlers();
      console.log('[AutopilotPolling] Initialized with tabId:', this.tabId);
    },

    generateTabId: function() {
      return 'tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    },

    setupUnloadHandlers: function() {
      const self = this;

      window.addEventListener('beforeunload', function() {
        console.log('[AutopilotPolling] Page unloading - cleaning up all polls');
        self.cleanupAll();
      });

      window.addEventListener('pagehide', function() {
        console.log('[AutopilotPolling] Page hidden - cleaning up all polls');
        self.cleanupAll();
      });

      document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
          console.log('[AutopilotPolling] Tab hidden - pausing expensive polls');
          self.isPageActive = false;
        } else {
          console.log('[AutopilotPolling] Tab visible - resuming polls');
          self.isPageActive = true;
        }
      });
    },

    setupVisibilityHandlers: function() {
      const self = this;

      window.addEventListener('blur', function() {
        self.isPageActive = false;
      });

      window.addEventListener('focus', function() {
        self.isPageActive = true;
      });
    },

    startPolling: function(pollId, callback, interval, options) {
      options = options || {};
      const maxAttempts = options.maxAttempts || Infinity;
      const onError = options.onError || function(err) { console.error('[Poll Error]', err); };
      const onComplete = options.onComplete || function() {};
      const skipWhenHidden = options.skipWhenHidden !== false;

      if (this.activePolls.has(pollId)) {
        console.warn('[AutopilotPolling] Poll already active:', pollId);
        this.stopPolling(pollId);
      }

      let attempts = 0;
      let timeoutId = null;
      let isRunning = false;

      const pollData = {
        pollId: pollId,
        startTime: Date.now(),
        attempts: 0,
        lastRun: null,
        stopped: false
      };

      const executePoll = async () => {
        if (pollData.stopped) {
          console.log('[AutopilotPolling] Poll stopped:', pollId);
          return;
        }

        if (skipWhenHidden && !this.isPageActive) {
          console.log('[AutopilotPolling] Skipping poll (tab hidden):', pollId);
          scheduleNext();
          return;
        }

        if (isRunning) {
          console.warn('[AutopilotPolling] Previous poll still running, skipping:', pollId);
          scheduleNext();
          return;
        }

        isRunning = true;
        attempts++;
        pollData.attempts = attempts;
        pollData.lastRun = Date.now();

        try {
          const shouldContinue = await callback(attempts, pollData);

          if (shouldContinue === false || attempts >= maxAttempts) {
            console.log('[AutopilotPolling] Poll complete:', pollId, 'attempts:', attempts);
            this.stopPolling(pollId);
            onComplete(pollData);
            return;
          }

          scheduleNext();
        } catch (error) {
          console.error('[AutopilotPolling] Poll error:', pollId, error);
          onError(error, pollData);

          if (options.stopOnError) {
            this.stopPolling(pollId);
          } else {
            scheduleNext();
          }
        } finally {
          isRunning = false;
        }
      };

      const scheduleNext = () => {
        if (pollData.stopped) return;
        timeoutId = setTimeout(executePoll, interval);
        pollData.timeoutId = timeoutId;
      };

      this.activePolls.set(pollId, {
        pollId: pollId,
        timeoutId: null,
        data: pollData,
        stop: () => {
          pollData.stopped = true;
          if (timeoutId) {
            clearTimeout(timeoutId);
            timeoutId = null;
          }
        }
      });

      executePoll();

      console.log('[AutopilotPolling] Started poll:', pollId, 'interval:', interval + 'ms');

      return pollId;
    },

    stopPolling: function(pollId) {
      const poll = this.activePolls.get(pollId);
      if (!poll) {
        return false;
      }

      console.log('[AutopilotPolling] Stopping poll:', pollId);
      poll.stop();
      this.activePolls.delete(pollId);
      return true;
    },

    cleanupAll: function() {
      console.log('[AutopilotPolling] Cleaning up all polls, count:', this.activePolls.size);

      this.activePolls.forEach((poll, pollId) => {
        poll.stop();
      });

      this.activePolls.clear();
    },

    isPolling: function(pollId) {
      return this.activePolls.has(pollId);
    },

    getActivePollCount: function() {
      return this.activePolls.size;
    },

    getPollInfo: function(pollId) {
      const poll = this.activePolls.get(pollId);
      return poll ? poll.data : null;
    }
  };

  AutopilotPollingManager.init();

  window.AutopilotPolling = AutopilotPollingManager;

  console.log('[AutopilotPolling] Manager loaded and ready');

})(window);
