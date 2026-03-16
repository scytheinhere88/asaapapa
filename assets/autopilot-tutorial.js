const autopilotTutorials = {
    main: [
        {
            element: '#domainInput',
            title: 'Enter Your Domains',
            description: 'Paste your list of domains here. You can paste multiple domains (one per line) or a comma-separated list. The system will automatically detect and parse them.',
            tip: 'Try "Kota Pasuruan" format domains - AI will parse them correctly now!'
        },
        {
            element: '#keywordHintInput',
            title: 'Add Keyword Hint (Optional)',
            description: 'If all your domains share a common keyword (like "APTISI", "KSBSI"), enter it here. This helps the AI parse locations more accurately.',
            tip: 'Example: If parsing "aptisikotapasuruan.org", enter "APTISI" as hint'
        },
        {
            element: '.btn-start-autopilot',
            title: 'Preview Before Processing',
            description: 'Click here to generate a preview of how your domains will be parsed. You can review and manually correct any mistakes before finalizing!',
            tip: 'NEW: You can now edit location names directly in the preview table'
        },
        {
            element: '#previewContainer',
            title: 'Review & Edit Data',
            description: 'This preview shows how each domain was parsed. Click any cell to edit it manually. Yellow highlighted rows indicate your corrections.',
            tip: 'The parsing now uses the same AI logic as CSV Generator - 95% accuracy!'
        },
        {
            element: '.preview-actions',
            title: 'Approve or Cancel',
            description: 'Once you are satisfied with the preview, click "Approve & Process" to start the automation. You can also cancel and start over.',
            tip: 'All your manual corrections will be saved and applied to the final output'
        }
    ],

    csvGenerator: [
        {
            element: '#csvDomainInput',
            title: 'CSV Generator Workflow',
            description: 'This is the proven CSV Generator that has 95% accuracy. Autopilot now uses the same AI parsing logic!',
            tip: 'Use this as reference for expected output quality'
        }
    ]
};

function initAutopilotTutorial() {
    if (typeof TutorialSystem === 'undefined') {
        console.warn('Tutorial System not loaded');
        return;
    }

    const tutorial = new TutorialSystem();
    tutorial.init(autopilotTutorials.main);

    const tutorialId = 'autopilot_v2';
    if (!tutorial.isCompleted(tutorialId)) {
        const showTutorial = confirm('Welcome to Autopilot! Would you like a quick tour of the new features?');
        if (showTutorial) {
            tutorial.onComplete = () => {
                tutorial.markCompleted(tutorialId);
                showToast('Tutorial completed! You are ready to use Autopilot.', 'success');
            };
            setTimeout(() => tutorial.start(tutorialId), 500);
        } else {
            tutorial.markCompleted(tutorialId);
        }
    }

    window.tutorial = tutorial;

    const helpBtn = document.createElement('button');
    helpBtn.className = 'tutorial-help-btn';
    helpBtn.innerHTML = `
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Tutorial
    `;
    helpBtn.style.cssText = `
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        color: white;
        border: none;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        font-size: 10px;
        font-weight: 600;
        transition: all 0.3s;
        z-index: 9999;
    `;
    helpBtn.onmouseover = () => {
        helpBtn.style.transform = 'scale(1.1)';
        helpBtn.style.boxShadow = '0 8px 20px rgba(59, 130, 246, 0.5)';
    };
    helpBtn.onmouseout = () => {
        helpBtn.style.transform = 'scale(1)';
        helpBtn.style.boxShadow = '0 4px 12px rgba(59, 130, 246, 0.4)';
    };
    helpBtn.onclick = () => {
        tutorial.start(tutorialId);
    };

    document.body.appendChild(helpBtn);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAutopilotTutorial);
} else {
    initAutopilotTutorial();
}
