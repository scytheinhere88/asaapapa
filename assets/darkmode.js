class DarkModeToggle {
    constructor() {
        this.themes = {
            dark: {
                '--bg': '#080810',
                '--bg2': '#0d0d1a',
                '--card': '#0f0f20',
                '--card2': '#141428',
                '--border': '#1e1e3a',
                '--border2': '#2a2a48',
                '--text': '#c8c8e8',
                '--muted': '#454568',
                '--dim': '#181830'
            },
            light: {
                '--bg': '#f8f9fa',
                '--bg2': '#ffffff',
                '--card': '#ffffff',
                '--card2': '#f1f3f5',
                '--border': '#dee2e6',
                '--border2': '#adb5bd',
                '--text': '#212529',
                '--muted': '#6c757d',
                '--dim': '#e9ecef'
            }
        };

        this.currentTheme = this.getStoredTheme();
        this.prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.init();
        this.listenToSystemPreference();
    }

    init() {
        this.applyTheme(this.currentTheme, false);
        this.createToggleButton();
        this.setupKeyboardShortcut();
    }

    getStoredTheme() {
        const stored = localStorage.getItem('theme');
        if (stored) return stored;

        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        return prefersDark ? 'dark' : 'light';
    }

    listenToSystemPreference() {
        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        darkModeQuery.addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                const newTheme = e.matches ? 'dark' : 'light';
                this.applyTheme(newTheme, true);
            }
        });
    }

    applyTheme(theme, animate = true) {
        const colors = this.themes[theme];
        const root = document.documentElement;

        if (animate && !this.prefersReducedMotion) {
            root.style.setProperty('--theme-transition-duration', '0.4s');

            const transitionOverlay = document.createElement('div');
            transitionOverlay.style.cssText = `
                position: fixed;
                inset: 0;
                background: ${theme === 'dark' ? '#000' : '#fff'};
                opacity: 0;
                pointer-events: none;
                z-index: 99999;
                transition: opacity 0.2s ease;
            `;
            document.body.appendChild(transitionOverlay);

            requestAnimationFrame(() => {
                transitionOverlay.style.opacity = '0.3';
            });

            setTimeout(() => {
                transitionOverlay.style.opacity = '0';
                setTimeout(() => transitionOverlay.remove(), 200);
            }, 200);

            setTimeout(() => {
                root.style.setProperty('--theme-transition-duration', '0s');
            }, 400);
        }

        Object.entries(colors).forEach(([property, value]) => {
            root.style.setProperty(property, value);
        });

        this.currentTheme = theme;
        localStorage.setItem('theme', theme);

        document.body.classList.remove('theme-dark', 'theme-light');
        document.body.classList.add(`theme-${theme}`);

        document.documentElement.setAttribute('data-theme', theme);

        this.updateToggleButton();

        document.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
    }

    toggle() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.applyTheme(newTheme, true);

        if (window.toast) {
            window.toast.info(
                `${newTheme === 'dark' ? 'Dark' : 'Light'} mode activated`,
                '',
                { duration: 2000 }
            );
        }
    }

    setupKeyboardShortcut() {
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'L') {
                e.preventDefault();
                this.toggle();
            }
        });
    }

    createToggleButton() {
        const style = document.createElement('style');
        style.textContent = `
            :root {
                --theme-transition-duration: 0s;
            }

            * {
                transition: background-color var(--theme-transition-duration) cubic-bezier(0.16, 1, 0.3, 1),
                           border-color var(--theme-transition-duration) cubic-bezier(0.16, 1, 0.3, 1),
                           color var(--theme-transition-duration) cubic-bezier(0.16, 1, 0.3, 1);
            }

            @media (prefers-reduced-motion: reduce) {
                * {
                    transition: none !important;
                }
            }

            .theme-toggle {
                position: fixed;
                bottom: 24px;
                right: 24px;
                width: 56px;
                height: 56px;
                background: var(--card);
                border: 2px solid var(--border);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                z-index: 9998;
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3), 0 0 0 0 rgba(240, 165, 0, 0);
                overflow: hidden;
                position: relative;
            }

            .theme-toggle::before {
                content: '';
                position: absolute;
                inset: 0;
                background: radial-gradient(circle, rgba(240,165,0,0.15), transparent);
                opacity: 0;
                transition: opacity 0.3s;
            }

            .theme-toggle:hover::before {
                opacity: 1;
            }

            .theme-toggle:hover {
                transform: scale(1.15);
                box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4), 0 0 0 4px rgba(240, 165, 0, 0.2);
                border-color: var(--a1);
            }

            .theme-toggle:active {
                transform: scale(0.95);
            }

            .theme-toggle-icon {
                width: 24px;
                height: 24px;
                position: relative;
                transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            }

            .theme-toggle:hover .theme-toggle-icon {
                transform: rotate(360deg);
            }

            .theme-toggle-icon svg {
                width: 24px;
                height: 24px;
                stroke: currentColor;
                fill: none;
                stroke-width: 2;
                stroke-linecap: round;
                stroke-linejoin: round;
                color: var(--a1);
            }

            .theme-toggle-tooltip {
                position: absolute;
                bottom: 70px;
                right: 0;
                background: var(--card);
                border: 1px solid var(--border);
                padding: 8px 12px;
                border-radius: 8px;
                font-size: 12px;
                font-family: 'Syne', sans-serif;
                font-weight: 600;
                color: var(--text);
                white-space: nowrap;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.2s, transform 0.2s;
                transform: translateY(4px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            }

            .theme-toggle:hover .theme-toggle-tooltip {
                opacity: 1;
                transform: translateY(0);
            }

            .theme-toggle-tooltip kbd {
                background: rgba(255,255,255,0.1);
                padding: 2px 6px;
                border-radius: 4px;
                font-family: 'JetBrains Mono', monospace;
                font-size: 10px;
                margin-left: 6px;
            }

            @media (prefers-reduced-motion: reduce) {
                .theme-toggle {
                    transition: none;
                }

                .theme-toggle:hover .theme-toggle-icon {
                    transform: none;
                }

                .theme-toggle-icon {
                    transition: none;
                }
            }

            @media (max-width: 768px) {
                .theme-toggle {
                    bottom: 20px;
                    right: 20px;
                    width: 48px;
                    height: 48px;
                }

                .theme-toggle-icon svg {
                    width: 20px;
                    height: 20px;
                }

                .theme-toggle-tooltip {
                    display: none;
                }
            }
        `;
        document.head.appendChild(style);

        const button = document.createElement('button');
        button.className = 'theme-toggle';
        button.setAttribute('aria-label', 'Toggle dark mode');
        button.setAttribute('title', 'Toggle theme (Ctrl+Shift+L)');
        button.innerHTML = `
            <div class="theme-toggle-icon"></div>
            <div class="theme-toggle-tooltip">
                Toggle theme
                <kbd>⌃⇧L</kbd>
            </div>
        `;

        button.addEventListener('click', () => this.toggle());

        document.body.appendChild(button);
        this.toggleButton = button;

        this.updateToggleButton();
    }

    updateToggleButton() {
        if (!this.toggleButton) return;

        const icon = this.toggleButton.querySelector('.theme-toggle-icon');

        if (this.currentTheme === 'dark') {
            icon.innerHTML = `
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
            `;
        } else {
            icon.innerHTML = `
                <svg viewBox="0 0 24 24">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            `;
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.darkMode = new DarkModeToggle();
    });
} else {
    window.darkMode = new DarkModeToggle();
}
