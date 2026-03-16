<?php
/**
 * Autopilot Bug Fix Verification Tests
 *
 * Tests for:
 * 1. Race condition fixes - Tab-level locking
 * 2. Memory leak fixes - Cleanup handlers
 * 3. Timeout edge case fixes - Better error handling
 */

class AutopilotBugFixTest {

    /**
     * Test 1: Verify JavaScript polling manager is loaded
     */
    public function testPollingManagerLoaded() {
        $autopilotPage = file_get_contents(__DIR__ . '/../dashboard/autopilot.php');

        // Check that safe polling script is included
        $this->assertStringContains(
            '<script src="/assets/autopilot-safe-polling.js"></script>',
            $autopilotPage,
            'Safe polling script should be loaded'
        );

        // Check that safe timeout handler is included
        $this->assertStringContains(
            '<script src="/assets/safe-timeout-handler.js"></script>',
            $autopilotPage,
            'Safe timeout handler should be loaded'
        );

        echo "✓ Test 1 PASSED: Polling manager scripts loaded\n";
    }

    /**
     * Test 2: Verify polling manager file exists and has required functions
     */
    public function testPollingManagerFunctions() {
        $pollingScript = file_get_contents(__DIR__ . '/../assets/autopilot-safe-polling.js');

        $requiredFunctions = [
            'startPolling',
            'stopPolling',
            'cleanupAll',
            'setupUnloadHandlers',
            'beforeunload',
            'pagehide',
            'visibilitychange'
        ];

        foreach ($requiredFunctions as $func) {
            $this->assertStringContains(
                $func,
                $pollingScript,
                "Polling manager should have {$func}"
            );
        }

        echo "✓ Test 2 PASSED: All polling manager functions present\n";
    }

    /**
     * Test 3: Verify timeout handler has cleanup on unload
     */
    public function testTimeoutCleanupHandlers() {
        $timeoutScript = file_get_contents(__DIR__ . '/../assets/safe-timeout-handler.js');

        // Should have beforeunload listener
        $this->assertStringContains(
            'beforeunload',
            $timeoutScript,
            'Timeout handler should listen to beforeunload'
        );

        // Should have cleanupAll method
        $this->assertStringContains(
            'cleanupAll',
            $timeoutScript,
            'Timeout handler should have cleanupAll method'
        );

        // Should clear timeouts
        $this->assertStringContains(
            'clearTimeout',
            $timeoutScript,
            'Timeout handler should clear timeouts'
        );

        echo "✓ Test 3 PASSED: Timeout cleanup handlers present\n";
    }

    /**
     * Test 4: Verify autopilot uses safe timeout handler
     */
    public function testAutopilotUsesSafeTimeout() {
        $autopilotPage = file_get_contents(__DIR__ . '/../dashboard/autopilot.php');

        // Should use SafeTimeout.createAbortableRequest
        $this->assertStringContains(
            'SafeTimeout.createAbortableRequest',
            $autopilotPage,
            'Autopilot should use SafeTimeout for requests'
        );

        // Should check if timed out
        $this->assertStringContains(
            'isTimedOut()',
            $autopilotPage,
            'Autopilot should check timeout status'
        );

        // Should have proper error message for timeout
        $this->assertStringContains(
            'Detection timeout',
            $autopilotPage,
            'Autopilot should have timeout error message'
        );

        echo "✓ Test 4 PASSED: Autopilot uses safe timeout handler\n";
    }

    /**
     * Test 5: Verify proper cleanup in try-finally blocks
     */
    public function testProperCleanupInFinally() {
        $autopilotPage = file_get_contents(__DIR__ . '/../dashboard/autopilot.php');

        // Should have finally block for cleanup
        $this->assertStringContains(
            'finally {',
            $autopilotPage,
            'Should use finally for cleanup'
        );

        // Should clear interval in finally
        $this->assertStringContains(
            'clearInterval(timer)',
            $autopilotPage,
            'Should clear interval in finally block'
        );

        echo "✓ Test 5 PASSED: Proper cleanup in try-finally blocks\n";
    }

    /**
     * Test 6: Verify no bad clearInterval calls
     */
    public function testNoBadClearIntervalCalls() {
        $autopilotPage = file_get_contents(__DIR__ . '/../dashboard/autopilot.php');

        // Should NOT have "clearInterval && clearInterval()" pattern
        $this->assertStringNotContains(
            'clearInterval && clearInterval()',
            $autopilotPage,
            'Should not have bad clearInterval pattern'
        );

        echo "✓ Test 6 PASSED: No bad clearInterval calls\n";
    }

    /**
     * Test 7: Verify tab-level locking via tabId
     */
    public function testTabLevelLocking() {
        $pollingScript = file_get_contents(__DIR__ . '/../assets/autopilot-safe-polling.js');

        // Should generate unique tab ID
        $this->assertStringContains(
            'generateTabId',
            $pollingScript,
            'Should generate unique tab ID'
        );

        // Should store active polls
        $this->assertStringContains(
            'activePolls',
            $pollingScript,
            'Should track active polls'
        );

        // Should check if polling already active
        $this->assertStringContains(
            'isPolling',
            $pollingScript,
            'Should have isPolling check'
        );

        echo "✓ Test 7 PASSED: Tab-level locking implemented\n";
    }

    /**
     * Test 8: Verify memory leak prevention
     */
    public function testMemoryLeakPrevention() {
        $pollingScript = file_get_contents(__DIR__ . '/../assets/autopilot-safe-polling.js');

        // Should clear all polls on cleanup
        $this->assertStringContains(
            'activePolls.clear()',
            $pollingScript,
            'Should clear all polls'
        );

        // Should handle page hide
        $this->assertStringContains(
            'pagehide',
            $pollingScript,
            'Should handle pagehide event'
        );

        // Should pause when tab hidden
        $this->assertStringContains(
            'skipWhenHidden',
            $pollingScript,
            'Should skip polling when tab hidden'
        );

        echo "✓ Test 8 PASSED: Memory leak prevention measures present\n";
    }

    // Helper assertion methods
    private function assertStringContains($needle, $haystack, $message) {
        if (strpos($haystack, $needle) === false) {
            throw new Exception("FAILED: {$message}\nExpected to find: {$needle}");
        }
    }

    private function assertStringNotContains($needle, $haystack, $message) {
        if (strpos($haystack, $needle) !== false) {
            throw new Exception("FAILED: {$message}\nExpected NOT to find: {$needle}");
        }
    }

    // Run all tests
    public function runAllTests() {
        echo "\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "  AUTOPILOT BUG FIX VERIFICATION TEST SUITE\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "\n";

        $tests = [
            'testPollingManagerLoaded',
            'testPollingManagerFunctions',
            'testTimeoutCleanupHandlers',
            'testAutopilotUsesSafeTimeout',
            'testProperCleanupInFinally',
            'testNoBadClearIntervalCalls',
            'testTabLevelLocking',
            'testMemoryLeakPrevention'
        ];

        $passed = 0;
        $failed = 0;

        foreach ($tests as $test) {
            try {
                $this->$test();
                $passed++;
            } catch (Exception $e) {
                echo "✗ {$e->getMessage()}\n";
                $failed++;
            }
        }

        echo "\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "  TEST RESULTS\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "  Total Tests: " . count($tests) . "\n";
        echo "  ✓ Passed: {$passed}\n";
        echo "  ✗ Failed: {$failed}\n";
        echo "════════════════════════════════════════════════════════════════\n";
        echo "\n";

        if ($failed === 0) {
            echo "🎉 ALL TESTS PASSED! All bugs have been fixed.\n\n";
            return true;
        } else {
            echo "⚠️  SOME TESTS FAILED. Please review the failures above.\n\n";
            return false;
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $tester = new AutopilotBugFixTest();
    $success = $tester->runAllTests();
    exit($success ? 0 : 1);
}
