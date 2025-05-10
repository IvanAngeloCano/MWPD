/**
 * MWPD Guided Tour
 * A step-by-step interactive guide to help users navigate the MWPD system
 */
class GuidedTour {
    constructor(options = {}) {
        this.steps = options.steps || [];
        this.currentStep = 0;
        this.overlay = null;
        this.tooltipElement = null;
        this.highlightedElement = null;
        this.onCompleteCallback = options.onComplete || null;
        this.tooltipClass = options.tooltipClass || 'mwpd-tooltip';
        this.highlightClass = options.highlightClass || 'mwpd-highlight';
        this.overlayClass = options.overlayClass || 'mwpd-overlay';
        this.initialized = false;
        
        // Navigation buttons
        this.prevButton = null;
        this.nextButton = null;
        this.skipButton = null;
        
        // Bind methods to this context
        this.start = this.start.bind(this);
        this.end = this.end.bind(this);
        this.next = this.next.bind(this);
        this.prev = this.prev.bind(this);
        this.goToStep = this.goToStep.bind(this);
        this.createOverlay = this.createOverlay.bind(this);
        this.createTooltip = this.createTooltip.bind(this);
        this.positionTooltip = this.positionTooltip.bind(this);
        this.highlightElement = this.highlightElement.bind(this);
        this.removeHighlight = this.removeHighlight.bind(this);
        this.handleKeyboard = this.handleKeyboard.bind(this);
    }
    
    init() {
        if (this.initialized) return;
        
        // Create base elements
        this.createOverlay();
        this.createTooltip();
        
        // Add keyboard listeners
        document.addEventListener('keydown', this.handleKeyboard);
        
        this.initialized = true;
        console.log('🧙‍♂️ Guided Tour initialized with ' + this.steps.length + ' steps');
    }
    
    createOverlay() {
        const overlay = document.createElement('div');
        overlay.className = this.overlayClass;
        overlay.style.position = 'fixed';
        overlay.style.top = 0;
        overlay.style.left = 0;
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.6)';
        overlay.style.zIndex = 9990;
        overlay.style.display = 'none';
        
        document.body.appendChild(overlay);
        this.overlay = overlay;
    }
    
    createTooltip() {
        const tooltip = document.createElement('div');
        tooltip.className = this.tooltipClass;
        tooltip.style.position = 'absolute';
        tooltip.style.zIndex = 9999;
        tooltip.style.backgroundColor = '#fff';
        tooltip.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.3)';
        tooltip.style.borderRadius = '5px';
        tooltip.style.padding = '15px';
        tooltip.style.maxWidth = '350px';
        tooltip.style.display = 'none';
        
        // Progress indicator
        const progressContainer = document.createElement('div');
        progressContainer.className = 'progress-container';
        progressContainer.style.display = 'flex';
        progressContainer.style.justifyContent = 'center';
        progressContainer.style.margin = '10px 0';
        
        tooltip.appendChild(progressContainer);
        
        // Navigation buttons
        const buttonsContainer = document.createElement('div');
        buttonsContainer.className = 'tour-buttons';
        buttonsContainer.style.display = 'flex';
        buttonsContainer.style.justifyContent = 'space-between';
        buttonsContainer.style.marginTop = '15px';
        
        // Previous button
        this.prevButton = document.createElement('button');
        this.prevButton.textContent = 'Previous';
        this.prevButton.className = 'btn btn-sm btn-outline-secondary';
        this.prevButton.addEventListener('click', this.prev);
        
        // Next button
        this.nextButton = document.createElement('button');
        this.nextButton.textContent = 'Next';
        this.nextButton.className = 'btn btn-sm btn-primary';
        this.nextButton.addEventListener('click', this.next);
        
        // Skip button
        this.skipButton = document.createElement('button');
        this.skipButton.textContent = 'Skip Tour';
        this.skipButton.className = 'btn btn-sm btn-link';
        this.skipButton.addEventListener('click', this.end);
        
        buttonsContainer.appendChild(this.skipButton);
        buttonsContainer.appendChild(this.prevButton);
        buttonsContainer.appendChild(this.nextButton);
        
        tooltip.appendChild(buttonsContainer);
        
        document.body.appendChild(tooltip);
        this.tooltipElement = tooltip;
    }
    
    updateProgress() {
        const progressContainer = this.tooltipElement.querySelector('.progress-container');
        progressContainer.innerHTML = '';
        
        for (let i = 0; i < this.steps.length; i++) {
            const dot = document.createElement('div');
            dot.style.width = '8px';
            dot.style.height = '8px';
            dot.style.borderRadius = '50%';
            dot.style.margin = '0 3px';
            
            if (i === this.currentStep) {
                dot.style.backgroundColor = '#28a745';
                dot.style.width = '10px';
                dot.style.height = '10px';
            } else if (i < this.currentStep) {
                dot.style.backgroundColor = '#6c757d';
            } else {
                dot.style.backgroundColor = '#dee2e6';
            }
            
            progressContainer.appendChild(dot);
        }
    }
    
    positionTooltip() {
        const step = this.steps[this.currentStep];
        const elementSelector = step.element;
        const targetElement = document.querySelector(elementSelector);
        
        if (!targetElement) {
            console.warn(`Element not found: ${elementSelector}`);
            return;
        }
        
        // Highlight the element with stronger visual cue
        this.highlightElement(targetElement);
        
        // Flash the element briefly to draw attention to it
        targetElement.classList.add('tour-highlight-flash');
        setTimeout(() => {
            targetElement.classList.remove('tour-highlight-flash');
        }, 1000);
        
        // Get element position
        const elementRect = targetElement.getBoundingClientRect();
        const tooltipRect = this.tooltipElement.getBoundingClientRect();
        
        const position = step.position || 'bottom';
        
        // Calculate position
        let top, left;
        
        switch (position) {
            case 'top':
                top = elementRect.top - tooltipRect.height - 10;
                left = elementRect.left + (elementRect.width / 2) - (tooltipRect.width / 2);
                break;
            case 'bottom':
                top = elementRect.bottom + 10;
                left = elementRect.left + (elementRect.width / 2) - (tooltipRect.width / 2);
                break;
            case 'left':
                top = elementRect.top + (elementRect.height / 2) - (tooltipRect.height / 2);
                // Special case for floating menu to move tooltip to the left
                if (targetElement.className.includes('floating-action-menu')) {
                    left = elementRect.left - tooltipRect.width - 60;
                } else {
                    left = elementRect.left - tooltipRect.width - 10;
                }
                break;
            case 'right':
                top = elementRect.top + (elementRect.height / 2) - (tooltipRect.height / 2);
                left = elementRect.right + 10;
                break;
        }
        
        // Ensure tooltip is in viewport
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        if (left < 10) left = 10;
        if (left + tooltipRect.width > viewportWidth - 10) {
            left = viewportWidth - tooltipRect.width - 10;
        }
        
        if (top < 10) top = 10;
        if (top + tooltipRect.height > viewportHeight - 10) {
            top = viewportHeight - tooltipRect.height - 10;
        }
        
        // Set position
        this.tooltipElement.style.top = `${top}px`;
        this.tooltipElement.style.left = `${left}px`;
        
        // Add arrow
        const arrow = document.createElement('div');
        arrow.style.position = 'absolute';
        arrow.style.width = '0';
        arrow.style.height = '0';
        arrow.style.borderStyle = 'solid';
        
        // Remove existing arrows
        const existingArrow = this.tooltipElement.querySelector('.tooltip-arrow');
        if (existingArrow) {
            existingArrow.remove();
        }
        
        // Position arrow based on tooltip position
        switch (position) {
            case 'top':
                arrow.style.borderWidth = '8px 8px 0 8px';
                arrow.style.borderColor = '#fff transparent transparent transparent';
                arrow.style.bottom = '-8px';
                arrow.style.left = '50%';
                arrow.style.transform = 'translateX(-50%)';
                break;
            case 'bottom':
                arrow.style.borderWidth = '0 8px 8px 8px';
                arrow.style.borderColor = 'transparent transparent #fff transparent';
                arrow.style.top = '-8px';
                arrow.style.left = '50%';
                arrow.style.transform = 'translateX(-50%)';
                break;
            case 'left':
                arrow.style.borderWidth = '8px 0 8px 8px';
                arrow.style.borderColor = 'transparent transparent transparent #fff';
                arrow.style.right = '-8px';
                arrow.style.top = '50%';
                arrow.style.transform = 'translateY(-50%)';
                break;
            case 'right':
                arrow.style.borderWidth = '8px 8px 8px 0';
                arrow.style.borderColor = 'transparent #fff transparent transparent';
                arrow.style.left = '-8px';
                arrow.style.top = '50%';
                arrow.style.transform = 'translateY(-50%)';
                break;
        }
        
        arrow.className = 'tooltip-arrow';
        this.tooltipElement.appendChild(arrow);
        
        // Scroll element into view if needed
        const isInViewport = (
            elementRect.top >= 0 &&
            elementRect.left >= 0 &&
            elementRect.bottom <= viewportHeight &&
            elementRect.right <= viewportWidth
        );
        
        if (!isInViewport) {
            targetElement.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            
            // Reposition tooltip after scroll
            setTimeout(() => {
                this.positionTooltip();
            }, 500);
        }
    }
    
    highlightElement(element) {
        // Remove any existing highlight
        this.removeHighlight();
        
        // Store current highlighted element
        this.highlightedElement = element;
        
        // Dim previous elements
        this.dimPreviousElements();
        
        // Special handling for floating-action-menu
        const isFloatingMenu = element.classList.contains('floating-action-menu');
        
        // For floating menu, don't move it from its original position
        if (isFloatingMenu) {
            // Just add a class to highlight it without changing position
            element.classList.add('tour-highlight');
            // Force its position to remain fixed
            element.style.position = 'fixed';
            element.style.right = '30px';
            element.style.bottom = '30px';
            element.style.transform = 'none';
            return; // Exit early, don't create the highlight div
        }
        
        // For all other elements, proceed with normal highlighting
        const rect = element.getBoundingClientRect();
        const highlight = document.createElement('div');
        highlight.className = this.highlightClass;
        highlight.style.position = 'fixed';
        highlight.style.top = (rect.top - 4) + 'px';
        highlight.style.left = (rect.left - 4) + 'px';
        highlight.style.width = (rect.width + 8) + 'px';
        highlight.style.height = (rect.height + 8) + 'px';
        highlight.style.zIndex = 9991;
        highlight.style.pointerEvents = 'none';
        highlight.style.borderRadius = '4px';
        
        // Special handling for quick access menu
        if (element.classList.contains('main-button') || element.closest('.floating-action-menu')) {
            // Ensure the menu dots are visible
            const menuDots = element.closest('.floating-action-menu').querySelectorAll('.menu-dots span');
            if (menuDots.length) {
                menuDots.forEach(dot => {
                    dot.style.backgroundColor = '#fff';
                    dot.style.opacity = '1';
                });
            }
        }
        
        // Make sure the element itself can be seen/clicked
        element.style.zIndex = '9992';
        element.style.position = 'relative';
        
        document.body.appendChild(highlight);
    }
    
    removeHighlight() {
        const existingHighlight = document.querySelector('.' + this.highlightClass);
        if (existingHighlight) {
            existingHighlight.remove();
        }
        
        // Reset z-index of previously highlighted element
        if (this.highlightedElement) {
            this.highlightedElement.style.zIndex = '';
            this.highlightedElement.style.position = '';
        }
        
        this.highlightedElement = null;
        
        // Remove dimming from all elements
        document.querySelectorAll('.mwpd-dimmed').forEach(el => {
            el.classList.remove('mwpd-dimmed');
        });
    }
    
    dimPreviousElements() {
        // Get the current step
        const currentStepIndex = this.currentStep;
        
        // If we're on step 1 or later, dim the previous step's element
        if (currentStepIndex > 0) {
            for (let i = 0; i < currentStepIndex; i++) {
                const prevStepSelector = this.steps[i].element;
                const prevElement = document.querySelector(prevStepSelector);
                
                if (prevElement && !prevElement.classList.contains('mwpd-dimmed')) {
                    prevElement.classList.add('mwpd-dimmed');
                }
            }
        }
    }
    
    renderStep() {
        const step = this.steps[this.currentStep];
        if (!step) return;
        
        // Update content
        const contentContainer = this.tooltipElement.querySelector('.tour-content') || document.createElement('div');
        if (!this.tooltipElement.contains(contentContainer)) {
            contentContainer.className = 'tour-content';
            this.tooltipElement.insertBefore(contentContainer, this.tooltipElement.querySelector('.progress-container'));
        }
        
        let content = '';
        if (step.title) {
            content += `<h5 style="margin-top: 0;">${step.title}</h5>`;
        }
        content += `<p style="margin-bottom: 0;">${step.content}</p>`;
        
        // Add navigation instructions for first step
        if (this.currentStep === 0) {
            content += `
                <div class="nav-instructions">
                    <p class="text-muted mt-2" style="font-size: 12px; border-top: 1px solid #eee; padding-top: 10px; margin-top: 10px;">
                        <i class="fa fa-info-circle"></i> <strong>Navigation Tips:</strong><br>
                        • Click <strong>Next</strong> or press <strong>→</strong> key to continue<br>
                        • Click <strong>Previous</strong> or press <strong>←</strong> key to go back<br>
                        • Press <strong>ESC</strong> to exit the tour at any time
                    </p>
                </div>
            `;
        }
        
        contentContainer.innerHTML = content;
        
        // Update buttons
        this.prevButton.disabled = this.currentStep === 0;
        
        if (this.currentStep === this.steps.length - 1) {
            this.nextButton.textContent = 'Finish';
        } else {
            this.nextButton.textContent = 'Next';
        }
        
        // Update progress
        this.updateProgress();
        
        // Position tooltip
        this.positionTooltip();
    }
    
    goToStep(index) {
        if (index < 0 || index >= this.steps.length) return;
        
        this.currentStep = index;
        this.renderStep();
    }
    
    start() {
        if (!this.initialized) {
            this.init();
        }
        
        if (this.steps.length === 0) {
            console.warn('No steps defined for the tour');
            return;
        }
        
        this.currentStep = 0;
        this.overlay.style.display = 'block';
        this.tooltipElement.style.display = 'block';
        
        this.renderStep();
        
        // Trigger onStart callback if defined
        if (typeof this.onStartCallback === 'function') {
            this.onStartCallback();
        }
    }
    
    next() {
        if (this.currentStep < this.steps.length - 1) {
            this.goToStep(this.currentStep + 1);
        } else {
            this.end();
        }
    }
    
    prev() {
        if (this.currentStep > 0) {
            this.goToStep(this.currentStep - 1);
        }
    }
    
    end() {
        this.overlay.style.display = 'none';
        this.tooltipElement.style.display = 'none';
        this.removeHighlight();
        
        // Trigger onComplete callback if defined
        if (typeof this.onCompleteCallback === 'function') {
            this.onCompleteCallback();
        }
        
        // Set a flag that we've completed the tour, then refresh the page immediately
        localStorage.setItem('mwpdTourCompleted', 'true');
        window.location.reload();
    }
    
    handleKeyboard(e) {
        // Only handle keys when tour is active
        if (this.overlay.style.display !== 'block') return;
        
        switch (e.key) {
            case 'Escape':
                this.end();
                break;
            case 'ArrowRight':
            case 'Enter':
                this.next();
                break;
            case 'ArrowLeft':
                this.prev();
                break;
        }
    }
}

// MWPD Dashboard Tour Steps
const dashboardTourSteps = [
    {
        element: '.navbar-brand',
        title: 'Welcome to MWPD',
        content: 'This is the Migrant Workers Protection Division Filing System. Let\'s take a quick tour!',
        position: 'bottom'
    },
    {
        element: '.sidebar',
        title: 'Navigation Menu',
        content: 'Access all system modules through this sidebar menu. Click on any item to navigate to that section.',
        position: 'right'
    },
    {
        element: '.quick-add',
        title: 'Quick Add',
        content: 'Quickly add new records without navigating to specific modules.',
        position: 'bottom'
    },
    {
        element: '#notificationIcon',
        title: 'Notifications Center',
        content: 'View all system notifications here. The badge shows how many unread notifications you have.',
        position: 'bottom'
    },
    {
        element: '.user-profile',
        title: 'User Profile',
        content: 'Access your profile settings, change password, or log out from the system.',
        position: 'bottom'
    },
    {
        element: '#directHireCard',
        title: 'Direct Hire Records',
        content: 'Manage direct hire applications for both professional and household service workers here.',
        position: 'bottom'
    },
    {
        element: '#balikManggawaCard',
        title: 'Balik Manggagawa Records',
        content: 'Process returning Filipino workers\' documentation and requirements.',
        position: 'bottom'
    },
    {
        element: '#govToGovCard',
        title: 'Gov-to-Gov Records',
        content: 'Handle worker deployments under bilateral government agreements.',
        position: 'bottom'
    },
    {
        element: '#jobFairsCard',
        title: 'Job Fairs Records',
        content: 'Track all job fair events and registrations.',
        position: 'bottom'
    },
    {
        element: '#pendingApprovalsBox',
        title: 'Pending Approvals',
        content: 'Monitor applications awaiting approval from Regional Directors.',
        position: 'right'
    },
    {
        element: '#calendarBox',
        title: 'Calendar View',
        content: 'View scheduled job fairs and important events for the month.',
        position: 'left'
    },
    {
        element: '.recent-activity',
        title: 'Recent System Activity',
        content: 'Track the latest activities and updates in the system.',
        position: 'top'
    },
    {
        element: '.job-fairs-this-month',
        title: 'This Month\'s Job Fairs',
        content: 'Quick overview of all job fairs scheduled for the current month.',
        position: 'top'
    },
    {
        element: '.floating-action-menu',
        title: 'Quick Access Menu',
        content: 'Use this floating menu for quick access to common actions like the interactive guide, blacklist checking, and generating reports.',
        position: 'left'
    }
];

// Direct Hire Module Tour Steps
const directHireTourSteps = [
    {
        element: '.direct-hire-header',
        title: 'Direct Hire Module',
        content: 'This module handles all direct hire applications for overseas Filipino workers.',
        position: 'bottom'
    },
    {
        element: '.direct-hire-tabs',
        title: 'Application Types',
        content: 'Toggle between Professional and Household Service Worker applications.',
        position: 'bottom'
    },
    {
        element: '.add-record-btn',
        title: 'Add New Record',
        content: 'Click here to create a new direct hire application record.',
        position: 'bottom'
    },
    {
        element: '.search-records',
        title: 'Search Records',
        content: 'Quickly find specific records using the search function.',
        position: 'bottom'
    },
    {
        element: '.record-table',
        title: 'Records Table',
        content: 'View all your records here. Double-click any row to view details.',
        position: 'top'
    },
    {
        element: '.approval-col',
        title: 'Approval Status',
        content: 'Track the approval status of each application.',
        position: 'left'
    }
];

// Gov-to-Gov Module Tour Steps
const govToGovTourSteps = [
    {
        element: '.gov-to-gov-header',
        title: 'Government-to-Government Module',
        content: 'Manage deployments under bilateral agreements between countries.',
        position: 'bottom'
    },
    {
        element: '.gov-agreement-section',
        title: 'Bilateral Agreements',
        content: 'View and manage different government agreements and deployment programs.',
        position: 'bottom'
    },
    {
        element: '.application-form-section',
        title: 'Application Processing',
        content: 'Process applications under specific bilateral agreements and track their status.',
        position: 'right'
    }
];

// Balik Manggagawa Module Tour Steps
const balikManggagawaTourSteps = [
    {
        element: '.bm-header',
        title: 'Balik Manggagawa Module',
        content: 'Process returning Filipino workers documentation quickly and efficiently.',
        position: 'bottom'
    },
    {
        element: '.verification-section',
        title: 'Verification Tools',
        content: 'Verify OFW identity and employment history with these tools.',
        position: 'right'
    },
    {
        element: '.document-processing',
        title: 'Document Processing',
        content: 'Handle returning worker documentation and renewal requirements.',
        position: 'bottom'
    }
];

// Tour button handlers (using event delegation)
document.addEventListener('click', function(e) {
    // Handle Skip Tour button click
    if (e.target && (e.target.id === 'skipTourBtn' || e.target.classList.contains('skip-tour') || e.target.innerText === 'Skip Tour')) {
        localStorage.setItem('mwpdTourCompleted', 'true');
        localStorage.setItem('mwpdTourShown', 'true');
        // Immediate refresh
        window.location.reload();
    }
    
    // Handle Finish button click
    if (e.target && (e.target.innerText === 'Finish' || e.target.classList.contains('finish-tour'))) {
        localStorage.setItem('mwpdTourCompleted', 'true');
        localStorage.setItem('mwpdTourShown', 'true');
        // Immediate refresh
        window.location.reload();
    }
});

// Export the tour instances
const mwpdTours = {
    dashboard: dashboardTourSteps,
    directHire: directHireTourSteps,
    govToGov: govToGovTourSteps,
    balikManggagawa: balikManggagawaTourSteps,
    
    // Initialize appropriate tour based on current page
    initTour: function(tourName) {
        let steps = [];
        
        switch(tourName) {
            case 'dashboard':
                steps = this.dashboard;
                break;
            case 'directHire':
                steps = this.directHire;
                break;
            case 'govToGov':
                steps = this.govToGov;
                break;
            case 'balikManggagawa':
                steps = this.balikManggagawa;
                break;
            default:
                steps = this.dashboard;
        }
        
        return new GuidedTour({
            steps: steps
        });
    }
};
