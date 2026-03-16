class ProgressIndicator {
    constructor() {
        this.activeIndicators = new Map();
        this.init();
    }

    init() {
        const style = document.createElement('style');
        style.textContent = `
            .progress-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.85);
                backdrop-filter: blur(8px);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                animation: fadeIn 0.3s ease;
            }

            .progress-content {
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 16px;
                padding: 32px;
                max-width: 600px;
                width: 100%;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
            }

            .progress-header {
                display: flex;
                align-items: center;
                gap: 16px;
                margin-bottom: 24px;
            }

            .progress-spinner {
                width: 40px;
                height: 40px;
                border: 3px solid rgba(240, 165, 0, 0.1);
                border-top-color: var(--a1);
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
            }

            .progress-title {
                font-family: 'Syne', sans-serif;
                font-size: 20px;
                font-weight: 800;
                color: #fff;
            }

            .progress-bar-container {
                background: rgba(255, 255, 255, 0.05);
                border-radius: 100px;
                height: 8px;
                overflow: hidden;
                margin-bottom: 16px;
                position: relative;
            }

            .progress-bar {
                height: 100%;
                background: linear-gradient(90deg, var(--a1), var(--a2));
                border-radius: 100px;
                transition: width 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .progress-bar::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(
                    90deg,
                    transparent,
                    rgba(255, 255, 255, 0.3),
                    transparent
                );
                animation: shimmer 2s infinite;
            }

            .progress-stats {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .progress-percentage {
                font-family: 'Syne', sans-serif;
                font-size: 24px;
                font-weight: 900;
                color: var(--a1);
            }

            .progress-meta {
                font-family: 'JetBrains Mono', monospace;
                font-size: 11px;
                color: var(--muted);
            }

            .progress-logs {
                background: rgba(0, 0, 0, 0.3);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 16px;
                max-height: 200px;
                overflow-y: auto;
                font-family: 'JetBrains Mono', monospace;
                font-size: 11px;
                line-height: 1.8;
            }

            .progress-log-entry {
                color: var(--muted);
                margin-bottom: 6px;
                display: flex;
                gap: 8px;
                align-items: flex-start;
            }

            .progress-log-entry:last-child {
                margin-bottom: 0;
            }

            .progress-log-entry.success {
                color: var(--ok);
            }

            .progress-log-entry.error {
                color: var(--err);
            }

            .progress-log-entry.warning {
                color: var(--warn);
            }

            .progress-log-time {
                color: var(--muted);
                opacity: 0.6;
            }

            .progress-actions {
                margin-top: 20px;
                display: flex;
                gap: 12px;
                justify-content: flex-end;
            }

            .progress-pause-btn,
            .progress-cancel-btn {
                padding: 10px 20px;
                border-radius: 10px;
                font-family: 'Syne', sans-serif;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .progress-pause-btn {
                background: rgba(240, 165, 0, 0.1);
                border: 1px solid rgba(240, 165, 0, 0.3);
                color: var(--a1);
            }

            .progress-pause-btn:hover {
                background: rgba(240, 165, 0, 0.2);
                transform: translateY(-1px);
            }

            .progress-cancel-btn {
                background: rgba(255, 69, 96, 0.1);
                border: 1px solid rgba(255, 69, 96, 0.3);
                color: var(--err);
            }

            .progress-cancel-btn:hover {
                background: rgba(255, 69, 96, 0.2);
                transform: translateY(-1px);
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                }
                to {
                    opacity: 1;
                }
            }

            @keyframes spin {
                to {
                    transform: rotate(360deg);
                }
            }

            @keyframes shimmer {
                0% {
                    transform: translateX(-100%);
                }
                100% {
                    transform: translateX(100%);
                }
            }

            @media (max-width: 768px) {
                .progress-content {
                    padding: 24px;
                }

                .progress-title {
                    font-size: 18px;
                }

                .progress-percentage {
                    font-size: 20px;
                }
            }
        `;
        document.head.appendChild(style);
    }

    show(options) {
        const {
            id = 'default',
            title = 'Processing...',
            cancellable = false,
            pauseable = false,
            onCancel = null,
            onPause = null,
            onResume = null
        } = options;

        if (this.activeIndicators.has(id)) {
            return this.activeIndicators.get(id);
        }

        const modal = document.createElement('div');
        modal.className = 'progress-modal';
        modal.innerHTML = `
            <div class="progress-content">
                <div class="progress-header">
                    <div class="progress-spinner"></div>
                    <div class="progress-title">${title}</div>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: 0%"></div>
                </div>
                <div class="progress-stats">
                    <div class="progress-percentage">0%</div>
                    <div class="progress-meta">
                        <span class="progress-current">0</span> / <span class="progress-total">0</span>
                        <span class="progress-speed" style="margin-left: 12px; opacity: 0.6;"></span>
                    </div>
                </div>
                <div class="progress-logs"></div>
                ${cancellable || pauseable ? `
                    <div class="progress-actions">
                        ${pauseable ? `
                            <button class="progress-pause-btn">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="6" y="4" width="4" height="16"></rect>
                                    <rect x="14" y="4" width="4" height="16"></rect>
                                </svg>
                                Pause
                            </button>
                        ` : ''}
                        ${cancellable ? `
                            <button class="progress-cancel-btn">Cancel</button>
                        ` : ''}
                    </div>
                ` : ''}
            </div>
        `;

        document.body.appendChild(modal);

        let isPaused = false;
        let startTime = Date.now();
        let lastUpdate = Date.now();
        let lastCurrent = 0;

        const indicator = {
            modal,
            id,
            logs: [],
            isPaused: false,
            update: (current, total) => {
                const percentage = total > 0 ? Math.round((current / total) * 100) : 0;
                modal.querySelector('.progress-bar').style.width = `${percentage}%`;
                modal.querySelector('.progress-percentage').textContent = `${percentage}%`;
                modal.querySelector('.progress-current').textContent = current;
                modal.querySelector('.progress-total').textContent = total;

                const now = Date.now();
                const timeDiff = (now - lastUpdate) / 1000;
                const itemsDiff = current - lastCurrent;

                if (timeDiff > 0 && itemsDiff > 0) {
                    const speed = itemsDiff / timeDiff;
                    const speedText = speed.toFixed(1) + ' items/s';
                    const remaining = total - current;
                    const eta = remaining / speed;
                    const etaText = eta > 60 ? `${Math.floor(eta / 60)}m ${Math.floor(eta % 60)}s` : `${Math.floor(eta)}s`;

                    modal.querySelector('.progress-speed').textContent = `${speedText} • ETA: ${etaText}`;
                    lastUpdate = now;
                    lastCurrent = current;
                }
            },
            log: (message, type = 'info') => {
                const logsContainer = modal.querySelector('.progress-logs');
                const time = new Date().toLocaleTimeString('en-US', { hour12: false });
                const entry = document.createElement('div');
                entry.className = `progress-log-entry ${type}`;
                entry.innerHTML = `
                    <span class="progress-log-time">${time}</span>
                    <span>${message}</span>
                `;
                logsContainer.appendChild(entry);
                logsContainer.scrollTop = logsContainer.scrollHeight;
                indicator.logs.push({ time, message, type });
            },
            pause: () => {
                if (isPaused) return;
                isPaused = true;
                indicator.isPaused = true;
                modal.querySelector('.progress-spinner').style.animationPlayState = 'paused';
                modal.querySelector('.progress-bar').style.animationPlayState = 'paused';
                const pauseBtn = modal.querySelector('.progress-pause-btn');
                if (pauseBtn) {
                    pauseBtn.innerHTML = `
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                        </svg>
                        Resume
                    `;
                }
                indicator.log('Operation paused', 'warning');
                if (onPause) onPause();
            },
            resume: () => {
                if (!isPaused) return;
                isPaused = false;
                indicator.isPaused = false;
                modal.querySelector('.progress-spinner').style.animationPlayState = 'running';
                modal.querySelector('.progress-bar').style.animationPlayState = 'running';
                const pauseBtn = modal.querySelector('.progress-pause-btn');
                if (pauseBtn) {
                    pauseBtn.innerHTML = `
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="6" y="4" width="4" height="16"></rect>
                            <rect x="14" y="4" width="4" height="16"></rect>
                        </svg>
                        Pause
                    `;
                }
                indicator.log('Operation resumed', 'success');
                if (onResume) onResume();
            },
            close: () => {
                modal.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => {
                    modal.remove();
                    this.activeIndicators.delete(id);
                }, 300);
            }
        };

        if (pauseable) {
            const pauseBtn = modal.querySelector('.progress-pause-btn');
            pauseBtn.addEventListener('click', () => {
                if (isPaused) {
                    indicator.resume();
                } else {
                    indicator.pause();
                }
            });
        }

        if (cancellable && onCancel) {
            const cancelBtn = modal.querySelector('.progress-cancel-btn');
            cancelBtn.addEventListener('click', () => {
                if (confirm('Are you sure you want to cancel this operation?')) {
                    onCancel();
                    indicator.close();
                }
            });
        }

        this.activeIndicators.set(id, indicator);
        return indicator;
    }

    close(id = 'default') {
        const indicator = this.activeIndicators.get(id);
        if (indicator) {
            indicator.close();
        }
    }

    closeAll() {
        this.activeIndicators.forEach(indicator => indicator.close());
    }
}

window.progress = new ProgressIndicator();
