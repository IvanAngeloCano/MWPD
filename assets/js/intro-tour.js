/**
 * MWPD Intro.js Tour
 * An implementation of intro.js for the MWPD dashboard
 */

document.addEventListener('DOMContentLoaded', function() {
  // Define tour steps for dashboard
  const dashboardSteps = [
    {
      // Welcome step - centered on screen
      intro: '<h4>Welcome to MWPD Filing System</h4><p>This interactive guide will walk you through the main features of the Migrant Workers Protection Division Filing System. We will highlight key components you need to work efficiently in the system.</p>',
      position: 'center'
    },
    {
      element: '.sidebar',
      intro: '<h4>Navigation Menu</h4><p>Access all system modules through this sidebar menu. Click on any item to navigate to that section.</p>',
      position: 'right'
    },
    {
      element: '.quick-add',
      intro: '<h4>Quick Add</h4><p>Quickly add new records without navigating to specific modules.</p>',
      position: 'bottom'
    },
    {
      element: '#notificationIcon',
      intro: '<h4>Notifications Center</h4><p>View all system notifications here. The badge shows how many unread notifications you have.</p>',
      position: 'bottom'
    },
    {
      element: '.user-profile',
      intro: '<h4>User Profile</h4><p>Access your profile settings, change password, or log out from the system.</p>',
      position: 'bottom'
    },
    {
      element: '.bento-box-grid .bento-box:nth-child(1)',
      intro: '<h4>Direct Hire Records</h4><p>Manage direct hire applications for both professional and household service workers here.</p>',
      position: 'bottom'
    },
    {
      element: '.bento-box-grid .bento-box:nth-child(2)',
      intro: '<h4>Balik Manggagawa Records</h4><p>Process returning Filipino workers\' documentation and requirements.</p>',
      position: 'bottom'
    },
    {
      element: '.bento-box-grid .bento-box:nth-child(3)',
      intro: '<h4>Gov-to-Gov Records</h4><p>Handle worker deployments under bilateral government agreements.</p>',
      position: 'bottom'
    },
    {
      element: '.bento-box-grid .bento-box:nth-child(4)',
      intro: '<h4>Job Fairs Records</h4><p>Track all job fair events and registrations.</p>',
      position: 'bottom'
    },
    {
      element: '#pendingApprovalsBox',
      intro: '<h4>Pending Approvals</h4><p>Monitor applications awaiting approval from Regional Directors.</p>',
      position: 'right'
    },
    {
      element: '#calendarBox',
      intro: '<h4>Calendar View</h4><p>View scheduled job fairs and important events for the month.</p>',
      position: 'left'
    },
    {
      element: '.recent-activity',
      intro: '<h4>Recent System Activity</h4><p>Track the latest activities and updates in the system.</p>',
      position: 'top'
    },
    {
      element: '.job-fairs-this-month',
      intro: '<h4>This Month\'s Job Fairs</h4><p>Quick overview of all job fairs scheduled for the current month.</p>',
      position: 'top'
    },
    {
      element: '.floating-action-menu',
      intro: '<h4>Quick Access Menu</h4><p>Use this floating menu for quick access to common actions like this interactive guide, blacklist checking, and generating reports.</p>',
      position: 'left'
    }
  ];

  // Function to check if element exists
  function elementExists(selector) {
    return document.querySelector(selector) !== null;
  }

  // Filter steps to include only elements that exist on the page
  function getValidSteps(steps) {
    return steps.filter(step => {
      // Welcome step (no element) is always valid
      if (!step.element) return true;
      // Otherwise check if the element exists
      return elementExists(step.element);
    });
  }

  // Initialize intro.js with proper options
  function startTour(pageName) {
    let steps;
    
    // Select the appropriate steps based on the page
    switch(pageName) {
      case 'dashboard':
      default:
        steps = getValidSteps(dashboardSteps);
        break;
    }
    
    // If no valid steps, don't start the tour
    if (steps.length === 0) {
      console.log('No valid tour steps found for this page');
      return;
    }
    
    // Initialize intro.js
    const intro = introJs();
    
    // Configure options
    intro.setOptions({
      steps: steps,
      showBullets: true,
      showProgress: true,
      exitOnOverlayClick: false,
      showStepNumbers: false,
      keyboardNavigation: true,
      disableInteraction: false,
      doneLabel: 'Finish',
      nextLabel: 'Next →',
      prevLabel: '← Back',
      hidePrev: false,
      hideNext: false
    });
    
    // Start the tour
    intro.start();
    
    // Store reference to current tour
    window.currentTour = intro;
  }

  // Check if we should auto-start the tour (for first-time visitors)
  const shouldAutoStart = !localStorage.getItem('mwpdTourShown');
  
  // Auto-start tour for first-time visitors with delay
  if (shouldAutoStart) {
    setTimeout(() => {
      // Determine current page
      const currentPath = window.location.pathname;
      if (currentPath.includes('dashboard')) {
        startTour('dashboard');
        localStorage.setItem('mwpdTourShown', 'true');
      }
    }, 1000);
  }
  
  // Add event listener for the wizard guide button
  const wizardGuideButton = document.getElementById('wizardGuideButton');
  if (wizardGuideButton) {
    wizardGuideButton.addEventListener('click', function(e) {
      e.preventDefault();
      
      // Force the tour to start regardless of previous settings
      const currentPath = window.location.pathname;
      if (currentPath.includes('dashboard')) {
        startTour('dashboard');
      } else {
        // Default to dashboard tour if can't determine page
        startTour('dashboard');
      }
    });
  }
  
  // Make the startTour function globally available
  window.startMWPDTour = startTour;
});
