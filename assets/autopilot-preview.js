class AutopilotPreview {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.data = [];
        this.corrections = new Map();
        this.onApprove = null;
    }

    async loadPreview(domains, keywordHint) {
        this.showLoading();

        try {
            const response = await fetch('/api/autopilot_preview.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ domains, keyword_hint: keywordHint })
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || 'Failed to load preview');
            }

            this.data = result.results;
            this.render();

        } catch (error) {
            this.showError(error.message);
        }
    }

    showLoading() {
        this.container.innerHTML = `
            <div class="loading-preview">
                <div class="spinner"></div>
                <p>Generating preview...</p>
            </div>
        `;
    }

    showError(message) {
        this.container.innerHTML = `
            <div class="error-preview">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h3>Preview Error</h3>
                <p>${this.escapeHtml(message)}</p>
            </div>
        `;
    }

    render() {
        const html = `
            <div class="preview-container">
                <div class="preview-header">
                    <h3>Data Preview & Correction</h3>
                    <div class="preview-stats">
                        <span class="stat">
                            <strong>${this.data.length}</strong> domains
                        </span>
                        <span class="stat">
                            <strong>${this.corrections.size}</strong> corrections
                        </span>
                    </div>
                </div>

                <div class="preview-info">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>Review parsed data below. Click any cell to edit. Changes are highlighted in yellow.</span>
                </div>

                <div class="preview-table-wrapper">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th>Institution</th>
                                <th>Location</th>
                                <th>Province</th>
                                <th>Source</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${this.data.map((item, index) => this.renderRow(item, index)).join('')}
                        </tbody>
                    </table>
                </div>

                <div class="preview-actions">
                    <button class="btn btn-secondary" onclick="autopilotPreview.cancel()">
                        Cancel
                    </button>
                    <button class="btn btn-primary" onclick="autopilotPreview.approve()">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Approve & Process ${this.data.length} Domains
                    </button>
                </div>
            </div>
        `;

        this.container.innerHTML = html;
    }

    renderRow(item, index) {
        const corrected = this.corrections.has(item.domain);
        const data = corrected ? this.corrections.get(item.domain) : item;

        const sourceIcon = data.parse_source === 'ai'
            ? '<span class="source-badge ai">AI</span>'
            : '<span class="source-badge regex">Regex</span>';

        return `
            <tr class="${corrected ? 'corrected' : ''}" data-index="${index}">
                <td class="domain-cell">
                    <code>${this.escapeHtml(item.domain)}</code>
                </td>
                <td class="editable"
                    contenteditable="true"
                    data-field="institution"
                    data-domain="${this.escapeHtml(item.domain)}"
                    onblur="autopilotPreview.onCellEdit(this)">
                    ${this.escapeHtml(data.institution)}
                </td>
                <td class="editable"
                    contenteditable="true"
                    data-field="location_display"
                    data-domain="${this.escapeHtml(item.domain)}"
                    onblur="autopilotPreview.onCellEdit(this)">
                    ${this.escapeHtml(data.location_display)}
                </td>
                <td class="editable"
                    contenteditable="true"
                    data-field="province"
                    data-domain="${this.escapeHtml(item.domain)}"
                    onblur="autopilotPreview.onCellEdit(this)">
                    ${this.escapeHtml(data.province)}
                </td>
                <td>${sourceIcon}</td>
                <td>
                    ${corrected ? `
                        <button class="btn-icon"
                                onclick="autopilotPreview.resetRow('${this.escapeHtml(item.domain)}')"
                                title="Reset to original">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                    ` : ''}
                </td>
            </tr>
        `;
    }

    onCellEdit(cell) {
        const domain = cell.dataset.domain;
        const field = cell.dataset.field;
        const newValue = cell.textContent.trim();

        const originalItem = this.data.find(item => item.domain === domain);
        if (!originalItem) return;

        let correctedData = this.corrections.get(domain) || { ...originalItem };
        correctedData[field] = newValue;
        correctedData.parse_source = 'manual';

        this.corrections.set(domain, correctedData);

        const row = cell.closest('tr');
        row.classList.add('corrected');

        this.updateStats();
    }

    resetRow(domain) {
        this.corrections.delete(domain);
        this.render();
    }

    updateStats() {
        const statsEl = this.container.querySelector('.preview-stats');
        if (statsEl) {
            statsEl.innerHTML = `
                <span class="stat">
                    <strong>${this.data.length}</strong> domains
                </span>
                <span class="stat">
                    <strong>${this.corrections.size}</strong> corrections
                </span>
            `;
        }
    }

    async approve() {
        if (this.onApprove) {
            const finalData = this.data.map(item => {
                if (this.corrections.has(item.domain)) {
                    return this.corrections.get(item.domain);
                }
                return item;
            });

            await this.onApprove(finalData, Array.from(this.corrections.values()));
        }
    }

    cancel() {
        this.container.innerHTML = '';
        this.data = [];
        this.corrections.clear();
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

window.AutopilotPreview = AutopilotPreview;
