  // Replace the current history state
  window.history.replaceState({ page: 'dashboard' }, '', '');
        
  // Add a new history entry
  window.history.pushState({ page: 'dashboard' }, '', '');
  
  // Handle back button
  window.addEventListener('popstate', function(event) {
      // If trying to go back, push forward again
      window.history.pushState({ page: 'dashboard' }, '', '');
      
      // Refresh the page to ensure latest state
      window.location.reload();
  });

  // Ensure page is fresh on load
  if (performance.navigation.type === 2) {
      location.reload(true);
  }

  // Periodic session check
  async function checkSession() {
      try {
          const response = await fetch('../endpoint/check_session.php');
          const data = await response.json();
          if (!data.logged_in || data.role !== 'employee') {
              window.location.href = 'http://localhost/IMS/';
          }
      } catch (error) {
          console.error('Session check failed:', error);
      }
  }

  // Check session every 30 seconds
  setInterval(checkSession, 30000);

  // Check session on page visibility change
  document.addEventListener('visibilitychange', function() {
      if (document.visibilityState === 'visible') {
          checkSession();
      }
  });

   // Disable back button
   history.pushState(null, null, location.href);
   window.onpopstate = function () {
       history.go(1);
   };