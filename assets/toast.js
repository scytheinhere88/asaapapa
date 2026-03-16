class Toast {
    constructor() {
        this.container = null;
        this.toastQueue = [];
        this.maxToasts = 5;
        this.init();
    }

    init() {
        if (!document.getElementById('toast-container')) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);

            const style = document.createElement('style');
            style.textContent = `
                .toast-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                    max-width: 420px;
                    pointer-events: none;
                }

                .toast {
                    background: var(--card);
                    border: 1px solid var(--border);
                    border-radius: 14px;
                    padding: 16px 20px;
                    display: flex;
                    align-items: flex-start;
                    gap: 14px;
                    box-shadow: 0 12px 40px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05);
                    animation: toastSlideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
                    pointer-events: auto;
                    position: relative;
                    overflow: hidden;
                    backdrop-filter: blur(12px);
                    transform-origin: top right;
                }

                .toast.toast-success {
                    border-left: 4px solid #00e676;
                    background: linear-gradient(135deg, var(--card), rgba(0,230,118,0.05));
                }

                .toast.toast-error {
                    border-left: 4px solid #ff5252;
                    background: linear-gradient(135deg, var(--card), rgba(255,82,82,0.05));
                }

                .toast.toast-warning {
                    border-left: 4px solid #ffc107;
                    background: linear-gradient(135deg, var(--card), rgba(255,193,7,0.05));
                }

                .toast.toast-info {
                    border-left: 4px solid var(--a1);
                    background: linear-gradient(135deg, var(--card), rgba(240,165,0,0.05));
                }

                .toast-icon-wrapper {
                    width: 32px;
                    height: 32px;
                    border-radius: 10px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                    font-size: 16px;
                    font-weight: 700;
                    position: relative;
                }

                .toast-success .toast-icon-wrapper {
                    background: rgba(0,230,118,0.15);
                    color: #00e676;
                }

                .toast-error .toast-icon-wrapper {
                    background: rgba(255,82,82,0.15);
                    color: #ff5252;
                }

                .toast-warning .toast-icon-wrapper {
                    background: rgba(255,193,7,0.15);
                    color: #ffc107;
                }

                .toast-info .toast-icon-wrapper {
                    background: rgba(240,165,0,0.15);
                    color: var(--a1);
                }

                .toast-icon-svg {
                    width: 18px;
                    height: 18px;
                }

                .toast-content {
                    flex: 1;
                    min-width: 0;
                }

                .toast-title {
                    font-family: 'Syne', sans-serif;
                    font-weight: 700;
                    font-size: 14px;
                    color: #fff;
                    margin-bottom: 4px;
                    line-height: 1.3;
                }

                .toast-message {
                    font-size: 13px;
                    color: var(--muted);
                    line-height: 1.5;
                }

                .toast-actions {
                    display: flex;
                    gap: 8px;
                    margin-top: 12px;
                }

                .toast-action-btn {
                    padding: 6px 14px;
                    background: rgba(255,255,255,0.08);
                    border: 1px solid rgba(255,255,255,0.12);
                    border-radius: 8px;
                    color: #fff;
                    font-family: 'Syne', sans-serif;
                    font-size: 12px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .toast-action-btn:hover {
                    background: rgba(255,255,255,0.15);
                    transform: translateY(-1px);
                }

                .toast-action-btn.primary {
                    background: var(--a1);
                    border-color: var(--a1);
                    color: #000;
                }

                .toast-action-btn.primary:hover {
                    background: #ffb733;
                }

                .toast-close {
                    background: rgba(255,255,255,0.05);
                    border: none;
                    color: var(--muted);
                    cursor: pointer;
                    padding: 6px;
                    width: 28px;
                    height: 28px;
                    border-radius: 8px;
                    font-size: 16px;
                    line-height: 1;
                    opacity: 0.8;
                    transition: all 0.2s;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                }

                .toast-close:hover {
                    opacity: 1;
                    background: rgba(255,255,255,0.1);
                    transform: rotate(90deg);
                }

                .toast-progress {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    height: 3px;
                    background: linear-gradient(90deg, var(--a1), var(--a2));
                    animation: toastProgress linear;
                    transform-origin: left;
                    box-shadow: 0 0 8px rgba(240,165,0,0.5);
                }

                .toast.toast-removing {
                    animation: toastSlideOut 0.3s cubic-bezier(0.4, 0, 1, 1) forwards;
                }

                @keyframes toastSlideIn {
                    from {
                        transform: translateX(calc(100% + 24px)) scale(0.9);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0) scale(1);
                        opacity: 1;
                    }
                }

                @keyframes toastSlideOut {
                    from {
                        transform: translateX(0) scale(1);
                        opacity: 1;
                        max-height: 200px;
                        margin-bottom: 12px;
                    }
                    to {
                        transform: translateX(calc(100% + 24px)) scale(0.9);
                        opacity: 0;
                        max-height: 0;
                        margin-bottom: 0;
                        padding-top: 0;
                        padding-bottom: 0;
                    }
                }

                @keyframes toastProgress {
                    from {
                        transform: scaleX(1);
                    }
                    to {
                        transform: scaleX(0);
                    }
                }

                @media (prefers-reduced-motion: reduce) {
                    .toast {
                        animation: none;
                    }
                    .toast.toast-removing {
                        animation: none;
                    }
                }

                @media (max-width: 768px) {
                    .toast-container {
                        left: 12px;
                        right: 12px;
                        top: 12px;
                        max-width: none;
                    }

                    .toast {
                        padding: 14px 16px;
                        gap: 12px;
                    }

                    .toast-icon-wrapper {
                        width: 28px;
                        height: 28px;
                    }

                    .toast-title {
                        font-size: 13px;
                    }

                    .toast-message {
                        font-size: 12px;
                    }
                }
            `;
            document.head.appendChild(style);
        } else {
            this.container = document.getElementById('toast-container');
        }
    }

    getIconSVG(type) {
        const icons = {
            success: `<svg class="toast-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`,
            error: `<svg class="toast-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`,
            warning: `<svg class="toast-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`,
            info: `<svg class="toast-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`
        };
        return icons[type] || icons.info;
    }

    show(options) {
        const {
            type = 'info',
            title = '',
            message = '',
            duration = 5000,
            closeable = true,
            actions = []
        } = options;

        if (this.container.children.length >= this.maxToasts) {
            const oldestToast = this.container.firstChild;
            this.remove(oldestToast);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        let actionsHTML = '';
        if (actions && actions.length > 0) {
            actionsHTML = '<div class="toast-actions">';
            actions.forEach((action, index) => {
                actionsHTML += `<button class="toast-action-btn ${action.primary ? 'primary' : ''}" data-action-index="${index}">${action.label}</button>`;
            });
            actionsHTML += '</div>';
        }

        toast.innerHTML = `
            <div class="toast-icon-wrapper">${this.getIconSVG(type)}</div>
            <div class="toast-content">
                ${title ? `<div class="toast-title">${title}</div>` : ''}
                <div class="toast-message">${message}</div>
                ${actionsHTML}
            </div>
            ${closeable ? '<button class="toast-close" aria-label="Close">×</button>' : ''}
            ${duration > 0 ? `<div class="toast-progress" style="animation-duration: ${duration}ms;"></div>` : ''}
        `;

        this.container.appendChild(toast);

        if (closeable) {
            const closeBtn = toast.querySelector('.toast-close');
            closeBtn.addEventListener('click', () => this.remove(toast));
        }

        if (actions && actions.length > 0) {
            const actionBtns = toast.querySelectorAll('.toast-action-btn');
            actionBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const actionIndex = parseInt(btn.dataset.actionIndex);
                    const action = actions[actionIndex];
                    if (action.onClick) {
                        action.onClick();
                    }
                    if (action.closeOnClick !== false) {
                        this.remove(toast);
                    }
                });
            });
        }

        let timeoutId;
        if (duration > 0) {
            timeoutId = setTimeout(() => this.remove(toast), duration);
        }

        toast.addEventListener('mouseenter', () => {
            if (timeoutId) clearTimeout(timeoutId);
            const progressBar = toast.querySelector('.toast-progress');
            if (progressBar) {
                progressBar.style.animationPlayState = 'paused';
            }
        });

        toast.addEventListener('mouseleave', () => {
            if (duration > 0) {
                const progressBar = toast.querySelector('.toast-progress');
                if (progressBar) {
                    progressBar.style.animationPlayState = 'running';
                }
                const remainingTime = duration * 0.3;
                timeoutId = setTimeout(() => this.remove(toast), remainingTime);
            }
        });

        this.toastQueue.push(toast);
        return toast;
    }

    remove(toast) {
        if (!toast || !toast.parentNode) return;

        toast.classList.add('toast-removing');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
                const index = this.toastQueue.indexOf(toast);
                if (index > -1) {
                    this.toastQueue.splice(index, 1);
                }
            }
        }, 300);
    }

    success(message, title = 'Success', options = {}) {
        return this.show({ type: 'success', title, message, ...options });
    }

    error(message, title = 'Error', options = {}) {
        return this.show({ type: 'error', title, message, duration: 7000, ...options });
    }

    warning(message, title = 'Warning', options = {}) {
        return this.show({ type: 'warning', title, message, duration: 6000, ...options });
    }

    info(message, title = '', options = {}) {
        return this.show({ type: 'info', title, message, ...options });
    }

    clearAll() {
        this.toastQueue.forEach(toast => this.remove(toast));
        this.toastQueue = [];
    }
}

window.toast = new Toast();
