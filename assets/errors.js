class ErrorHandler {
    constructor() {
        this.errorCodes = {
            NETWORK_ERROR: {
                code: 'E001',
                title: 'Network Error',
                defaultMessage: 'Unable to connect to the server',
                retryable: true
            },
            VALIDATION_ERROR: {
                code: 'E002',
                title: 'Validation Error',
                defaultMessage: 'Please check your input and try again',
                retryable: false
            },
            AUTH_ERROR: {
                code: 'E003',
                title: 'Authentication Error',
                defaultMessage: 'Please log in again',
                retryable: false
            },
            PERMISSION_ERROR: {
                code: 'E004',
                title: 'Permission Denied',
                defaultMessage: 'You do not have permission to perform this action',
                retryable: false
            },
            NOT_FOUND: {
                code: 'E005',
                title: 'Not Found',
                defaultMessage: 'The requested resource was not found',
                retryable: false
            },
            SERVER_ERROR: {
                code: 'E006',
                title: 'Server Error',
                defaultMessage: 'Something went wrong on our end',
                retryable: true
            },
            TIMEOUT_ERROR: {
                code: 'E007',
                title: 'Timeout Error',
                defaultMessage: 'The request took too long to complete',
                retryable: true
            },
            RATE_LIMIT: {
                code: 'E008',
                title: 'Rate Limit Exceeded',
                defaultMessage: 'Too many requests. Please try again later',
                retryable: true
            },
            FILE_ERROR: {
                code: 'E009',
                title: 'File Error',
                defaultMessage: 'Failed to process the file',
                retryable: true
            },
            QUOTA_EXCEEDED: {
                code: 'E010',
                title: 'Quota Exceeded',
                defaultMessage: 'You have exceeded your usage quota',
                retryable: false
            }
        };

        this.init();
    }

    init() {
        const style = document.createElement('style');
        style.textContent = `
            .error-modal {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.85);
                backdrop-filter: blur(8px);
                z-index: 10001;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                animation: fadeIn 0.3s ease;
            }

            .error-content {
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 20px;
                padding: 32px;
                max-width: 500px;
                width: 100%;
                box-shadow: 0 20px 60px rgba(255,69,96,0.3);
                position: relative;
                animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            }

            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px) scale(0.95);
                }
                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }

            .error-icon {
                width: 64px;
                height: 64px;
                background: rgba(255,69,96,0.15);
                border-radius: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                animation: errorPulse 2s ease-in-out infinite;
            }

            @keyframes errorPulse {
                0%, 100% {
                    transform: scale(1);
                    box-shadow: 0 0 0 0 rgba(255,69,96,0.4);
                }
                50% {
                    transform: scale(1.05);
                    box-shadow: 0 0 0 10px rgba(255,69,96,0);
                }
            }

            .error-icon svg {
                width: 32px;
                height: 32px;
                stroke: var(--err);
                stroke-width: 2.5;
            }

            .error-code {
                font-family: 'JetBrains Mono', monospace;
                font-size: 11px;
                color: var(--muted);
                text-align: center;
                margin-bottom: 8px;
                opacity: 0.6;
            }

            .error-title {
                font-family: 'Syne', sans-serif;
                font-size: 22px;
                font-weight: 800;
                color: #fff;
                text-align: center;
                margin-bottom: 12px;
            }

            .error-message {
                font-size: 14px;
                color: var(--muted);
                text-align: center;
                line-height: 1.6;
                margin-bottom: 24px;
            }

            .error-details {
                background: rgba(0,0,0,0.3);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 24px;
                max-height: 200px;
                overflow-y: auto;
            }

            .error-details-toggle {
                background: none;
                border: none;
                color: var(--a1);
                font-family: 'Syne', sans-serif;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 6px;
                margin: 0 auto 24px;
                padding: 8px 12px;
                border-radius: 8px;
                transition: all 0.2s;
            }

            .error-details-toggle:hover {
                background: rgba(240,165,0,0.1);
            }

            .error-details-toggle svg {
                width: 14px;
                height: 14px;
                transition: transform 0.3s;
            }

            .error-details-toggle.open svg {
                transform: rotate(180deg);
            }

            .error-details pre {
                font-family: 'JetBrains Mono', monospace;
                font-size: 11px;
                color: var(--muted);
                margin: 0;
                white-space: pre-wrap;
                word-wrap: break-word;
            }

            .error-suggestions {
                background: rgba(240,165,0,0.05);
                border: 1px solid rgba(240,165,0,0.2);
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 24px;
            }

            .error-suggestions-title {
                font-family: 'Syne', sans-serif;
                font-size: 13px;
                font-weight: 700;
                color: var(--a1);
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .error-suggestions-title svg {
                width: 16px;
                height: 16px;
                stroke: var(--a1);
            }

            .error-suggestions-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .error-suggestions-list li {
                font-size: 13px;
                color: var(--muted);
                line-height: 1.6;
                padding-left: 20px;
                position: relative;
                margin-bottom: 8px;
            }

            .error-suggestions-list li:before {
                content: '→';
                position: absolute;
                left: 0;
                color: var(--a1);
                font-weight: 700;
            }

            .error-suggestions-list li:last-child {
                margin-bottom: 0;
            }

            .error-actions {
                display: flex;
                gap: 12px;
                justify-content: center;
            }

            .error-btn {
                padding: 12px 24px;
                border-radius: 12px;
                font-family: 'Syne', sans-serif;
                font-size: 14px;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.2s;
                border: none;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .error-btn-retry {
                background: var(--a1);
                color: #000;
            }

            .error-btn-retry:hover {
                background: #ffb733;
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(240,165,0,0.3);
            }

            .error-btn-close {
                background: rgba(255,255,255,0.08);
                border: 1px solid rgba(255,255,255,0.12);
                color: #fff;
            }

            .error-btn-close:hover {
                background: rgba(255,255,255,0.15);
                transform: translateY(-2px);
            }

            .error-btn svg {
                width: 16px;
                height: 16px;
            }

            .error-inline {
                background: rgba(255,69,96,0.1);
                border: 1px solid rgba(255,69,96,0.3);
                border-left: 4px solid var(--err);
                border-radius: 12px;
                padding: 16px 20px;
                margin: 16px 0;
                display: flex;
                gap: 14px;
                align-items: flex-start;
                animation: slideDown 0.3s ease;
            }

            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .error-inline-icon {
                width: 24px;
                height: 24px;
                flex-shrink: 0;
            }

            .error-inline-icon svg {
                width: 24px;
                height: 24px;
                stroke: var(--err);
            }

            .error-inline-content {
                flex: 1;
            }

            .error-inline-title {
                font-family: 'Syne', sans-serif;
                font-size: 14px;
                font-weight: 700;
                color: var(--err);
                margin-bottom: 4px;
            }

            .error-inline-message {
                font-size: 13px;
                color: rgba(255,69,96,0.9);
                line-height: 1.5;
            }

            .error-inline-close {
                background: none;
                border: none;
                color: var(--err);
                cursor: pointer;
                padding: 4px;
                opacity: 0.6;
                transition: opacity 0.2s;
                flex-shrink: 0;
            }

            .error-inline-close:hover {
                opacity: 1;
            }

            @media (max-width: 768px) {
                .error-content {
                    padding: 24px;
                }

                .error-icon {
                    width: 56px;
                    height: 56px;
                }

                .error-icon svg {
                    width: 28px;
                    height: 28px;
                }

                .error-title {
                    font-size: 20px;
                }

                .error-actions {
                    flex-direction: column;
                }

                .error-btn {
                    width: 100%;
                    justify-content: center;
                }
            }
        `;
        document.head.appendChild(style);
    }

    showModal(options = {}) {
        const {
            type = 'SERVER_ERROR',
            message = null,
            details = null,
            suggestions = null,
            onRetry = null,
            onClose = null
        } = options;

        const errorInfo = this.errorCodes[type] || this.errorCodes.SERVER_ERROR;
        const finalMessage = message || errorInfo.defaultMessage;

        const modal = document.createElement('div');
        modal.className = 'error-modal';
        modal.innerHTML = `
            <div class="error-content">
                <div class="error-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="error-code">${errorInfo.code}</div>
                <div class="error-title">${errorInfo.title}</div>
                <div class="error-message">${finalMessage}</div>
                ${details ? `
                    <button class="error-details-toggle">
                        Show Details
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="error-details" style="display: none;">
                        <pre>${details}</pre>
                    </div>
                ` : ''}
                ${suggestions && suggestions.length > 0 ? `
                    <div class="error-suggestions">
                        <div class="error-suggestions-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            Suggestions
                        </div>
                        <ul class="error-suggestions-list">
                            ${suggestions.map(s => `<li>${s}</li>`).join('')}
                        </ul>
                    </div>
                ` : ''}
                <div class="error-actions">
                    ${errorInfo.retryable && onRetry ? `
                        <button class="error-btn error-btn-retry">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                            </svg>
                            Try Again
                        </button>
                    ` : ''}
                    <button class="error-btn error-btn-close">Close</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const closeModal = () => {
            modal.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                modal.remove();
                if (onClose) onClose();
            }, 300);
        };

        if (details) {
            const toggleBtn = modal.querySelector('.error-details-toggle');
            const detailsDiv = modal.querySelector('.error-details');
            toggleBtn.addEventListener('click', () => {
                const isOpen = detailsDiv.style.display === 'block';
                detailsDiv.style.display = isOpen ? 'none' : 'block';
                toggleBtn.classList.toggle('open');
                toggleBtn.textContent = isOpen ? 'Show Details' : 'Hide Details';
                toggleBtn.appendChild(toggleBtn.querySelector('svg'));
            });
        }

        const closeBtn = modal.querySelector('.error-btn-close');
        closeBtn.addEventListener('click', closeModal);

        if (errorInfo.retryable && onRetry) {
            const retryBtn = modal.querySelector('.error-btn-retry');
            retryBtn.addEventListener('click', () => {
                closeModal();
                onRetry();
            });
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        return modal;
    }

    showInline(container, options = {}) {
        const {
            type = 'VALIDATION_ERROR',
            message = null,
            closeable = true
        } = options;

        const errorInfo = this.errorCodes[type] || this.errorCodes.SERVER_ERROR;
        const finalMessage = message || errorInfo.defaultMessage;

        const existing = container.querySelector('.error-inline');
        if (existing) existing.remove();

        const error = document.createElement('div');
        error.className = 'error-inline';
        error.innerHTML = `
            <div class="error-inline-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div class="error-inline-content">
                <div class="error-inline-title">${errorInfo.title}</div>
                <div class="error-inline-message">${finalMessage}</div>
            </div>
            ${closeable ? `
                <button class="error-inline-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            ` : ''}
        `;

        if (closeable) {
            const closeBtn = error.querySelector('.error-inline-close');
            closeBtn.addEventListener('click', () => error.remove());
        }

        container.insertBefore(error, container.firstChild);
        return error;
    }

    handleFetchError(error, retryFn = null) {
        let type = 'SERVER_ERROR';
        let suggestions = [];

        if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
            type = 'NETWORK_ERROR';
            suggestions = [
                'Check your internet connection',
                'Try refreshing the page',
                'Contact support if the problem persists'
            ];
        } else if (error.status === 401) {
            type = 'AUTH_ERROR';
            suggestions = [
                'Log out and log in again',
                'Clear your browser cache',
                'Check if your session has expired'
            ];
        } else if (error.status === 403) {
            type = 'PERMISSION_ERROR';
            suggestions = [
                'Contact your administrator',
                'Upgrade your plan for access',
                'Check your account permissions'
            ];
        } else if (error.status === 404) {
            type = 'NOT_FOUND';
            suggestions = [
                'Check the URL and try again',
                'Go back to the previous page',
                'Return to the dashboard'
            ];
        } else if (error.status === 429) {
            type = 'RATE_LIMIT';
            suggestions = [
                'Wait a few minutes before trying again',
                'Reduce the frequency of your requests',
                'Upgrade your plan for higher limits'
            ];
        } else if (error.status >= 500) {
            type = 'SERVER_ERROR';
            suggestions = [
                'Try again in a few moments',
                'Contact support if this continues',
                'Check our status page for updates'
            ];
        }

        return this.showModal({
            type,
            message: error.message,
            details: error.stack || JSON.stringify(error, null, 2),
            suggestions,
            onRetry: retryFn
        });
    }
}

window.errorHandler = new ErrorHandler();
