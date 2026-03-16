class TutorialSystem {
    constructor() {
        this.currentStep = 0;
        this.steps = [];
        this.overlay = null;
        this.spotlight = null;
        this.tooltip = null;
        this.onComplete = null;
        this.storageKey = 'tutorial_completed';
    }

    init(tutorials) {
        this.steps = tutorials;

        const tutorialDiv = document.createElement('div');
        tutorialDiv.id = 'tutorial-overlay';
        tutorialDiv.style.cssText = 'display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:10000;';
        document.body.appendChild(tutorialDiv);
        this.overlay = tutorialDiv;

        const spotlightDiv = document.createElement('div');
        spotlightDiv.id = 'tutorial-spotlight';
        spotlightDiv.style.cssText = 'position:absolute;background:white;border-radius:8px;box-shadow:0 0 0 9999px rgba(0,0,0,0.7);z-index:10001;pointer-events:none;transition:all 0.3s ease;';
        tutorialDiv.appendChild(spotlightDiv);
        this.spotlight = spotlightDiv;

        const tooltipDiv = document.createElement('div');
        tooltipDiv.id = 'tutorial-tooltip';
        tooltipDiv.style.cssText = 'position:absolute;background:white;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.2);padding:24px;max-width:400px;z-index:10002;';
        tutorialDiv.appendChild(tooltipDiv);
        this.tooltip = tooltipDiv;
    }

    isCompleted(tutorialId) {
        const completed = localStorage.getItem(this.storageKey);
        if (!completed) return false;
        try {
            const completedList = JSON.parse(completed);
            return completedList.includes(tutorialId);
        } catch {
            return false;
        }
    }

    markCompleted(tutorialId) {
        let completed = [];
        try {
            const stored = localStorage.getItem(this.storageKey);
            if (stored) {
                completed = JSON.parse(stored);
            }
        } catch {}

        if (!completed.includes(tutorialId)) {
            completed.push(tutorialId);
            localStorage.setItem(this.storageKey, JSON.stringify(completed));
        }
    }

    start(tutorialId) {
        if (this.isCompleted(tutorialId)) {
            return;
        }

        this.currentStep = 0;
        this.overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
        this.showStep(0);
    }

    showStep(index) {
        if (index < 0 || index >= this.steps.length) {
            this.complete();
            return;
        }

        this.currentStep = index;
        const step = this.steps[index];

        const element = typeof step.element === 'string'
            ? document.querySelector(step.element)
            : step.element;

        if (element) {
            const rect = element.getBoundingClientRect();
            const padding = 8;

            this.spotlight.style.top = `${rect.top - padding}px`;
            this.spotlight.style.left = `${rect.left - padding}px`;
            this.spotlight.style.width = `${rect.width + padding * 2}px`;
            this.spotlight.style.height = `${rect.height + padding * 2}px`;

            element.style.position = 'relative';
            element.style.zIndex = '10003';

            const tooltipPosition = this.calculateTooltipPosition(rect);
            this.tooltip.style.top = tooltipPosition.top;
            this.tooltip.style.left = tooltipPosition.left;
        } else {
            this.spotlight.style.display = 'none';
            this.tooltip.style.top = '50%';
            this.tooltip.style.left = '50%';
            this.tooltip.style.transform = 'translate(-50%, -50%)';
        }

        this.renderTooltip(step, index);
    }

    calculateTooltipPosition(rect) {
        const tooltipWidth = 400;
        const tooltipHeight = 200;
        const padding = 16;

        let top = rect.bottom + padding;
        let left = rect.left;

        if (top + tooltipHeight > window.innerHeight) {
            top = rect.top - tooltipHeight - padding;
        }

        if (left + tooltipWidth > window.innerWidth) {
            left = window.innerWidth - tooltipWidth - padding;
        }

        if (left < padding) {
            left = padding;
        }

        return {
            top: `${top}px`,
            left: `${left}px`
        };
    }

    renderTooltip(step, index) {
        const isLast = index === this.steps.length - 1;
        const progress = ((index + 1) / this.steps.length) * 100;

        this.tooltip.innerHTML = `
            <div class="tutorial-tooltip-content">
                <div class="tutorial-progress">
                    <div class="tutorial-progress-bar" style="width: ${progress}%"></div>
                </div>
                <div class="tutorial-step-number">Step ${index + 1} of ${this.steps.length}</div>
                <h3 class="tutorial-title">${this.escapeHtml(step.title)}</h3>
                <p class="tutorial-description">${this.escapeHtml(step.description)}</p>
                ${step.tip ? `
                    <div class="tutorial-tip">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span>${this.escapeHtml(step.tip)}</span>
                    </div>
                ` : ''}
                <div class="tutorial-actions">
                    <button class="tutorial-btn tutorial-btn-skip" onclick="tutorial.skip()">
                        Skip Tutorial
                    </button>
                    <div class="tutorial-nav">
                        ${index > 0 ? `
                            <button class="tutorial-btn tutorial-btn-secondary" onclick="tutorial.prev()">
                                Previous
                            </button>
                        ` : ''}
                        <button class="tutorial-btn tutorial-btn-primary" onclick="tutorial.next()">
                            ${isLast ? 'Finish' : 'Next'}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    next() {
        const currentElement = this.steps[this.currentStep]?.element;
        if (typeof currentElement === 'string') {
            const el = document.querySelector(currentElement);
            if (el) {
                el.style.position = '';
                el.style.zIndex = '';
            }
        }

        if (this.currentStep < this.steps.length - 1) {
            this.showStep(this.currentStep + 1);
        } else {
            this.complete();
        }
    }

    prev() {
        if (this.currentStep > 0) {
            const currentElement = this.steps[this.currentStep]?.element;
            if (typeof currentElement === 'string') {
                const el = document.querySelector(currentElement);
                if (el) {
                    el.style.position = '';
                    el.style.zIndex = '';
                }
            }
            this.showStep(this.currentStep - 1);
        }
    }

    skip() {
        this.complete(false);
    }

    complete(markAsCompleted = true) {
        this.overlay.style.display = 'none';
        document.body.style.overflow = '';

        this.steps.forEach(step => {
            if (typeof step.element === 'string') {
                const el = document.querySelector(step.element);
                if (el) {
                    el.style.position = '';
                    el.style.zIndex = '';
                }
            }
        });

        if (markAsCompleted && this.onComplete) {
            this.onComplete();
        }
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

const tutorialStyles = document.createElement('style');
tutorialStyles.textContent = `
.tutorial-tooltip-content {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.tutorial-progress {
    height: 4px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 16px;
}

.tutorial-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #8b5cf6);
    transition: width 0.3s ease;
}

.tutorial-step-number {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.tutorial-title {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 12px;
}

.tutorial-description {
    font-size: 14px;
    line-height: 1.6;
    color: #4b5563;
    margin: 0 0 16px;
}

.tutorial-tip {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px;
    background: #fef3c7;
    border-left: 3px solid #f59e0b;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #92400e;
}

.tutorial-tip svg {
    flex-shrink: 0;
    margin-top: 2px;
    color: #f59e0b;
}

.tutorial-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.tutorial-nav {
    display: flex;
    gap: 8px;
}

.tutorial-btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    outline: none;
}

.tutorial-btn-primary {
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    color: white;
}

.tutorial-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.tutorial-btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
}

.tutorial-btn-secondary:hover {
    background: #e5e7eb;
}

.tutorial-btn-skip {
    background: transparent;
    color: #6b7280;
    padding: 10px 16px;
}

.tutorial-btn-skip:hover {
    color: #374151;
    background: #f9fafb;
}
`;
document.head.appendChild(tutorialStyles);

window.TutorialSystem = TutorialSystem;
