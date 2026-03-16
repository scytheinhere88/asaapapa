class DragDrop {
    constructor(options = {}) {
        this.element = options.element || null;
        this.accept = options.accept || '*';
        this.multiple = options.multiple !== false;
        this.maxSize = options.maxSize || 10 * 1024 * 1024;
        this.onDrop = options.onDrop || (() => {});
        this.onError = options.onError || (() => {});
        this.onChange = options.onChange || (() => {});

        if (this.element) {
            this.init();
        }
    }

    init() {
        if (!this.element) return;

        const existingInput = this.element.querySelector('input[type="file"]');
        if (existingInput) {
            this.fileInput = existingInput;
        } else {
            this.fileInput = document.createElement('input');
            this.fileInput.type = 'file';
            this.fileInput.accept = this.accept;
            this.fileInput.multiple = this.multiple;
            this.fileInput.style.display = 'none';
            this.element.appendChild(this.fileInput);
        }

        this.element.classList.add('dragdrop-zone');

        this.setupStyles();
        this.attachEvents();
    }

    setupStyles() {
        if (document.getElementById('dragdrop-styles')) return;

        const style = document.createElement('style');
        style.id = 'dragdrop-styles';
        style.textContent = `
            .dragdrop-zone {
                position: relative;
                border: 2px dashed var(--border);
                border-radius: 16px;
                padding: 48px 32px;
                text-align: center;
                background: rgba(255,255,255,0.02);
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
                cursor: pointer;
                min-height: 200px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 16px;
            }

            .dragdrop-zone:hover {
                border-color: var(--a1);
                background: rgba(240,165,0,0.05);
                transform: translateY(-2px);
                box-shadow: 0 8px 24px rgba(240,165,0,0.15);
            }

            .dragdrop-zone.dragover {
                border-color: var(--a2);
                background: rgba(0,212,170,0.1);
                border-style: solid;
                transform: scale(1.02);
                box-shadow: 0 0 0 4px rgba(0,212,170,0.1), 0 12px 32px rgba(0,212,170,0.2);
            }

            .dragdrop-zone.has-files {
                border-color: var(--ok);
                background: rgba(0,230,118,0.05);
            }

            .dragdrop-icon {
                width: 64px;
                height: 64px;
                background: rgba(240,165,0,0.1);
                border-radius: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 8px;
            }

            .dragdrop-icon svg {
                width: 32px;
                height: 32px;
                stroke: var(--a1);
                stroke-width: 2;
            }

            .dragdrop-zone.dragover .dragdrop-icon {
                background: rgba(0,212,170,0.15);
                transform: scale(1.1);
                animation: iconPulse 0.6s ease-in-out infinite;
            }

            .dragdrop-zone.dragover .dragdrop-icon svg {
                stroke: var(--a2);
            }

            @keyframes iconPulse {
                0%, 100% { transform: scale(1.1); }
                50% { transform: scale(1.2); }
            }

            .dragdrop-title {
                font-family: 'Syne', sans-serif;
                font-size: 18px;
                font-weight: 700;
                color: #fff;
                margin-bottom: 4px;
            }

            .dragdrop-subtitle {
                font-size: 14px;
                color: var(--muted);
                line-height: 1.6;
            }

            .dragdrop-subtitle strong {
                color: var(--a1);
                font-weight: 600;
            }

            .dragdrop-info {
                display: flex;
                gap: 16px;
                margin-top: 8px;
                font-size: 12px;
                color: var(--muted);
            }

            .dragdrop-info-item {
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .dragdrop-info-item svg {
                width: 14px;
                height: 14px;
                stroke: var(--a2);
            }

            .dragdrop-files {
                width: 100%;
                margin-top: 24px;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .dragdrop-file {
                display: flex;
                align-items: center;
                gap: 14px;
                padding: 14px 18px;
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 12px;
                transition: all 0.2s;
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

            .dragdrop-file:hover {
                background: rgba(255,255,255,0.05);
                transform: translateX(4px);
            }

            .dragdrop-file-icon {
                width: 40px;
                height: 40px;
                background: rgba(240,165,0,0.1);
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .dragdrop-file-icon svg {
                width: 20px;
                height: 20px;
                stroke: var(--a1);
            }

            .dragdrop-file-info {
                flex: 1;
                min-width: 0;
            }

            .dragdrop-file-name {
                font-family: 'Syne', sans-serif;
                font-size: 14px;
                font-weight: 600;
                color: #fff;
                margin-bottom: 4px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .dragdrop-file-meta {
                font-size: 12px;
                color: var(--muted);
                font-family: 'JetBrains Mono', monospace;
            }

            .dragdrop-file-remove {
                width: 32px;
                height: 32px;
                background: rgba(255,69,96,0.1);
                border: 1px solid rgba(255,69,96,0.2);
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s;
                flex-shrink: 0;
            }

            .dragdrop-file-remove:hover {
                background: rgba(255,69,96,0.2);
                transform: rotate(90deg);
            }

            .dragdrop-file-remove svg {
                width: 16px;
                height: 16px;
                stroke: var(--err);
            }

            .dragdrop-error {
                padding: 12px 16px;
                background: rgba(255,69,96,0.1);
                border: 1px solid rgba(255,69,96,0.3);
                border-radius: 10px;
                color: var(--err);
                font-size: 13px;
                margin-top: 16px;
                animation: shake 0.4s ease;
            }

            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-8px); }
                75% { transform: translateX(8px); }
            }

            @media (max-width: 768px) {
                .dragdrop-zone {
                    padding: 32px 20px;
                    min-height: 160px;
                }

                .dragdrop-icon {
                    width: 48px;
                    height: 48px;
                }

                .dragdrop-icon svg {
                    width: 24px;
                    height: 24px;
                }

                .dragdrop-title {
                    font-size: 16px;
                }

                .dragdrop-subtitle {
                    font-size: 13px;
                }

                .dragdrop-info {
                    flex-direction: column;
                    gap: 8px;
                }
            }
        `;
        document.head.appendChild(style);
    }

    attachEvents() {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            this.element.addEventListener(eventName, this.preventDefaults, false);
        });

        this.element.addEventListener('dragenter', () => this.highlight());
        this.element.addEventListener('dragover', () => this.highlight());
        this.element.addEventListener('dragleave', () => this.unhighlight());
        this.element.addEventListener('drop', (e) => this.handleDrop(e));

        this.element.addEventListener('click', (e) => {
            if (!e.target.closest('.dragdrop-file-remove')) {
                this.fileInput.click();
            }
        });

        this.fileInput.addEventListener('change', (e) => {
            this.handleFiles(e.target.files);
        });
    }

    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    highlight() {
        this.element.classList.add('dragover');
    }

    unhighlight() {
        this.element.classList.remove('dragover');
    }

    handleDrop(e) {
        this.unhighlight();
        const dt = e.dataTransfer;
        const files = dt.files;
        this.handleFiles(files);
    }

    handleFiles(files) {
        const fileArray = Array.from(files);
        const validFiles = [];
        const errors = [];

        fileArray.forEach(file => {
            if (this.accept !== '*' && !this.matchesAccept(file)) {
                errors.push(`${file.name}: Invalid file type`);
                return;
            }

            if (file.size > this.maxSize) {
                errors.push(`${file.name}: File too large (max ${this.formatBytes(this.maxSize)})`);
                return;
            }

            validFiles.push(file);
        });

        if (errors.length > 0) {
            this.onError(errors);
            this.showError(errors.join(', '));
        }

        if (validFiles.length > 0) {
            this.element.classList.add('has-files');
            this.onChange(validFiles);
            this.onDrop(validFiles);
        }
    }

    matchesAccept(file) {
        const acceptTypes = this.accept.split(',').map(t => t.trim());
        return acceptTypes.some(type => {
            if (type.startsWith('.')) {
                return file.name.toLowerCase().endsWith(type.toLowerCase());
            }
            if (type.includes('*')) {
                const baseType = type.split('/')[0];
                return file.type.startsWith(baseType);
            }
            return file.type === type;
        });
    }

    formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    showError(message) {
        const existing = this.element.querySelector('.dragdrop-error');
        if (existing) existing.remove();

        const error = document.createElement('div');
        error.className = 'dragdrop-error';
        error.textContent = message;
        this.element.appendChild(error);

        setTimeout(() => error.remove(), 5000);
    }

    renderFiles(files) {
        let container = this.element.querySelector('.dragdrop-files');
        if (!container) {
            container = document.createElement('div');
            container.className = 'dragdrop-files';
            this.element.appendChild(container);
        }
        container.innerHTML = '';

        files.forEach((file, index) => {
            const fileEl = document.createElement('div');
            fileEl.className = 'dragdrop-file';
            fileEl.innerHTML = `
                <div class="dragdrop-file-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                        <polyline points="13 2 13 9 20 9"></polyline>
                    </svg>
                </div>
                <div class="dragdrop-file-info">
                    <div class="dragdrop-file-name">${file.name}</div>
                    <div class="dragdrop-file-meta">${this.formatBytes(file.size)}</div>
                </div>
                <button class="dragdrop-file-remove" data-index="${index}" type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            `;

            const removeBtn = fileEl.querySelector('.dragdrop-file-remove');
            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.removeFile(index);
            });

            container.appendChild(fileEl);
        });
    }

    removeFile(index) {
        const dt = new DataTransfer();
        const files = Array.from(this.fileInput.files);

        files.splice(index, 1);

        files.forEach(file => dt.items.add(file));
        this.fileInput.files = dt.files;

        if (files.length === 0) {
            this.element.classList.remove('has-files');
            const container = this.element.querySelector('.dragdrop-files');
            if (container) container.remove();
        } else {
            this.renderFiles(files);
        }

        this.onChange(files);
    }

    getFiles() {
        return Array.from(this.fileInput.files);
    }

    clear() {
        this.fileInput.value = '';
        this.element.classList.remove('has-files');
        const container = this.element.querySelector('.dragdrop-files');
        if (container) container.remove();
    }

    static createZone(options = {}) {
        const zone = document.createElement('div');
        zone.innerHTML = `
            <div class="dragdrop-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
            </div>
            <div class="dragdrop-title">${options.title || 'Drop files here'}</div>
            <div class="dragdrop-subtitle">
                ${options.subtitle || 'or <strong>click to browse</strong>'}
            </div>
            ${options.showInfo !== false ? `
                <div class="dragdrop-info">
                    ${options.accept ? `
                        <div class="dragdrop-info-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                <polyline points="13 2 13 9 20 9"></polyline>
                            </svg>
                            ${options.accept}
                        </div>
                    ` : ''}
                    ${options.maxSize ? `
                        <div class="dragdrop-info-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            Max ${new DragDrop().formatBytes(options.maxSize)}
                        </div>
                    ` : ''}
                </div>
            ` : ''}
        `;

        return new DragDrop({
            element: zone,
            ...options
        });
    }
}

window.DragDrop = DragDrop;
